<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\CashierShift;
use App\Models\Company;
use App\Models\Counter;
use App\Models\Customer;
use App\Models\CustomerCreditTerm;
use App\Models\CustomerPayment;
use App\Models\DeliveryNote;
use App\Models\DeliveryNoteItem;
use App\Models\PermitVerification;
use App\Models\Product;
use App\Models\ProductPricingTier;
use App\Models\RetailSale;
use App\Models\RetailSaleItem;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\SalesOrderReservation;
use App\Models\StockBatch;
use App\Models\StockLedger;
use App\Models\TaxVatRate;
use App\Models\Uom;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Models\WholesaleDocumentSequence;
use App\Models\WholesaleQuotation;
use App\Models\WholesaleQuotationItem;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class RetailAndWholesaleSalesSeeder extends Seeder
{
    public function run(): void
    {
        // 0. Ensure Phase 3 Core Master Data and Users exist
        $this->call(Phase3DemoSeeder::class);

        $company = Company::first();
        $branches = Branch::all()->keyBy('branch_code');
        $warehouses = Warehouse::all()->keyBy('warehouse_code');
        $counters = Counter::all()->keyBy('counter_code');
        $users = User::all()->keyBy('username');
        $products = Product::all()->keyBy('sku_code');
        $vat5 = TaxVatRate::where('tax_name', 'like', '%VAT 5%')->first()
            ?? TaxVatRate::firstOrCreate(['tax_name' => 'Standard VAT 5%'], ['tax_type' => 'VAT', 'rate_percent' => 5.000, 'effective_from' => '2021-04-16', 'is_active' => true]);

        $mctBranch = $branches['BR-MCT'] ?? Branch::where('branch_code', 'BR-MCT')->first();
        $sllBranch = $branches['BR-SLL'] ?? Branch::where('branch_code', 'BR-SLL')->first();
        $sohBranch = $branches['BR-SOH'] ?? Branch::where('branch_code', 'BR-SOH')->first();
        $nzwBranch = $branches['BR-NZW'] ?? Branch::where('branch_code', 'BR-NZW')->first();

        $mctMainWh = $warehouses['WH-MCT-MAIN'] ?? Warehouse::where('warehouse_code', 'WH-MCT-MAIN')->first();
        $sllMainWh = $warehouses['WH-SLL-MAIN'] ?? Warehouse::where('warehouse_code', 'WH-SLL-MAIN')->first();
        $sohMainWh = $warehouses['WH-SOH-MAIN'] ?? Warehouse::where('warehouse_code', 'WH-SOH-MAIN')->first();
        $nzwMainWh = $warehouses['WH-NZW-MAIN'] ?? Warehouse::where('warehouse_code', 'WH-NZW-MAIN')->first();

        $managerMct = $users['manager_mct'] ?? User::where('username', 'manager_mct')->first();
        $supervisorMct = $users['supervisor_mct'] ?? User::where('username', 'supervisor_mct')->first();
        $cashierMct = $users['cashier01'] ?? User::where('username', 'cashier01')->first();
        $cashierSll = $users['cashier_sll'] ?? User::where('username', 'cashier_sll')->first();
        $cashierSoh = $users['cashier_soh'] ?? User::where('username', 'cashier_soh')->first();
        $cashierNzw = $users['cashier_nzw'] ?? User::where('username', 'cashier_nzw')->first();

        // 1. Ensure Product Pricing Tiers for all 5 products
        $pricingMatrix = [
            'SKU-JW-BLK-750' => ['RETAIL' => 18.500, 'WHOLESALE' => 15.200, 'HARBOR' => 9.800],
            'SKU-HEIN-CAN-330' => ['RETAIL' => 16.000, 'WHOLESALE' => 14.500, 'HARBOR' => 11.000],
            'SKU-CHIV-12-1L' => ['RETAIL' => 22.000, 'WHOLESALE' => 18.500, 'HARBOR' => 13.500],
            'SKU-HEN-VSOP-700' => ['RETAIL' => 28.000, 'WHOLESALE' => 24.000, 'HARBOR' => 17.500],
            'SKU-TONIC-CAN-250' => ['RETAIL' => 1.000, 'WHOLESALE' => 0.800, 'HARBOR' => 0.600],
        ];

        foreach ($pricingMatrix as $sku => $tiers) {
            if (isset($products[$sku])) {
                foreach ($tiers as $tier => $price) {
                    ProductPricingTier::updateOrCreate(
                        ['product_id' => $products[$sku]->product_id, 'price_tier' => $tier],
                        ['min_quantity' => 1, 'unit_price' => $price, 'effective_from' => '2026-01-01']
                    );
                }
            }
        }

        // 2. Ensure Stock Batches & Warehouse Stocks in Duty-Paid warehouses
        $warehouseList = [
            'MCT' => $mctMainWh,
            'SLL' => $sllMainWh,
            'SOH' => $sohMainWh,
            'NZW' => $nzwMainWh,
        ];

        $stockBatches = [];
        foreach ($warehouseList as $code => $wh) {
            if (! $wh) continue;

            foreach ($products as $sku => $product) {
                $batchCode = "BATCH-{$code}-{$sku}-DP";
                $batch = StockBatch::updateOrCreate(
                    ['batch_number' => $batchCode],
                    [
                        'product_id' => $product->product_id,
                        'manufacture_date' => '2025-11-01',
                        'expiry_date' => '2029-12-31',
                        'duty_status' => 'DUTY_PAID',
                    ]
                );
                $stockBatches["{$code}_{$sku}"] = $batch;

                $ws = WarehouseStock::firstOrCreate(
                    ['warehouse_id' => $wh->warehouse_id, 'batch_id' => $batch->batch_id],
                    [
                        'product_id' => $product->product_id,
                        'quantity_on_hand' => 250.00,
                        'quantity_reserved' => 0.00,
                        'quantity_available' => 250.00,
                        'last_updated_at' => now(),
                    ]
                );

                if (! StockLedger::where('warehouse_id', $wh->warehouse_id)->where('batch_id', $batch->batch_id)->exists()) {
                    StockLedger::create([
                        'warehouse_id' => $wh->warehouse_id,
                        'branch_id' => $wh->branch_id,
                        'product_id' => $product->product_id,
                        'batch_id' => $batch->batch_id,
                        'movement_type' => 'OPENING_BALANCE',
                        'quantity' => (float) $ws->quantity_on_hand,
                        'balance_after' => (float) $ws->quantity_on_hand,
                        'reference_type' => 'opening_balance',
                        'reference_id' => $ws->stock_id,
                        'reference_no' => 'INIT-STOCK-' . $ws->stock_id,
                        'notes' => 'Seeded opening stock balance',
                        'created_at' => now(),
                    ]);
                }
            }
        }

        // 3. Wholesale Customers & Credit Terms
        $customersData = [
            [
                'code' => 'CUST-HYATT-MCT',
                'name' => 'Grand Hyatt Muscat',
                'segment' => 'HORECA',
                'phone' => '+968 2464 1234',
                'email' => 'purchase@grandhyattmuscat.com',
                'card_no' => 'CR-1092834',
                'limit' => 25000.000,
                'terms' => 30,
                'status' => 'ACTIVE',
                'balance' => 0.000,
                'vat' => 'OM1100223344',
            ],
            [
                'code' => 'CUST-SHANG-MCT',
                'name' => 'Shangri-La Barr Al Jissah Resort',
                'segment' => 'HORECA',
                'phone' => '+968 2477 6666',
                'email' => 'fnb.procurement@shangri-la.com',
                'card_no' => 'CR-1083742',
                'limit' => 35000.000,
                'terms' => 30,
                'status' => 'ACTIVE',
                'balance' => 596.000,
                'vat' => 'OM1100334455',
            ],
            [
                'code' => 'CUST-BALEED-SLL',
                'name' => 'Al Baleed Resort Salalah by Anantara',
                'segment' => 'HORECA',
                'phone' => '+968 2322 8222',
                'email' => 'resort.supplies@anantara.com',
                'card_no' => 'CR-2019481',
                'limit' => 20000.000,
                'terms' => 45,
                'status' => 'ACTIVE',
                'balance' => 425.040,
                'vat' => 'OM1100445566',
            ],
            [
                'code' => 'CUST-RADIS-SOH',
                'name' => 'Radisson Blu Hotel Sohar',
                'segment' => 'HORECA',
                'phone' => '+968 2685 0000',
                'email' => 'procurement.sohar@radissonblu.com',
                'card_no' => 'CR-3019284',
                'limit' => 15000.000,
                'terms' => 30,
                'status' => 'ACTIVE',
                'balance' => 0.000,
                'vat' => 'OM1100556677',
            ],
            [
                'code' => 'CUST-OMANAIR-CAT',
                'name' => 'Oman Air Catering Services SAOC',
                'segment' => 'HARBOR',
                'phone' => '+968 2451 9000',
                'email' => 'airline.catering@omanair.com',
                'card_no' => 'CR-1002938',
                'limit' => 40000.000,
                'terms' => 60,
                'status' => 'ACTIVE',
                'balance' => 18500.000,
                'vat' => 'OM1100667788',
            ],
            [
                'code' => 'CUST-EMBASSY-UK',
                'name' => 'British Embassy Muscat (Diplomatic)',
                'segment' => 'WHOLESALE',
                'phone' => '+968 2460 9000',
                'email' => 'commissary.muscat@fcdo.gov.uk',
                'card_no' => 'DIP-OM-UK-01',
                'limit' => 10000.000,
                'terms' => 30,
                'status' => 'ACTIVE',
                'balance' => 0.000,
                'vat' => null,
            ],
            [
                'code' => 'CUST-DEMO-ONHOLD',
                'name' => 'Al Bustan Palace Hotel (Hold)',
                'segment' => 'HORECA',
                'phone' => '+968 2479 9666',
                'email' => 'finance@albustanpalace.om',
                'card_no' => 'CR-9928172',
                'limit' => 10000.000,
                'terms' => 30,
                'status' => 'ON_HOLD',
                'balance' => 10500.000,
                'vat' => 'OM9988776655',
            ],
            [
                'code' => 'CUST-GOLDEN-NZW',
                'name' => 'Golden Tulip Nizwa Hotel',
                'segment' => 'HORECA',
                'phone' => '+968 2543 1515',
                'email' => 'purchasing@goldentulipnizwa.com',
                'card_no' => 'CR-4019283',
                'limit' => 12000.000,
                'terms' => 30,
                'status' => 'ACTIVE',
                'balance' => 0.000,
                'vat' => 'OM1100778899',
            ],
        ];

        $customers = [];
        foreach ($customersData as $cData) {
            $customer = Customer::updateOrCreate(
                ['customer_code' => $cData['code']],
                [
                    'customer_name' => $cData['name'],
                    'customer_segment' => $cData['segment'],
                    'phone' => $cData['phone'],
                    'email' => $cData['email'],
                    'oman_card_no' => $cData['card_no'],
                    'credit_limit' => $cData['limit'],
                    'payment_terms_days' => $cData['terms'],
                    'credit_status' => $cData['status'],
                    'customer_type' => 'WHOLESALE',
                    'vat_number' => $cData['vat'],
                    'is_active' => true,
                ]
            );

            CustomerCreditTerm::updateOrCreate(
                ['customer_id' => $customer->customer_id],
                [
                    'credit_limit' => $cData['limit'],
                    'payment_terms_days' => $cData['terms'],
                    'outstanding_balance' => $cData['balance'],
                ]
            );

            $customers[$cData['code']] = $customer;
        }

        // =========================================================================
        // WHOLESALE MODULE SEEDING
        // =========================================================================

        // 4. Wholesale Quotations
        // WQ 1: Grand Hyatt Muscat (CONVERTED to SO-MCT-2026-00001)
        $wq1 = WholesaleQuotation::updateOrCreate(
            ['quote_no' => 'WQ-MCT-2026-00001'],
            [
                'customer_id' => $customers['CUST-HYATT-MCT']->customer_id,
                'branch_id' => $mctBranch->branch_id,
                'valid_until' => Carbon::now()->subDays(10)->toDateString(),
                'status' => 'CONVERTED',
                'subtotal' => 821.000,
                'vat_total' => 41.050,
                'grand_total' => 862.050,
                'payment_terms_days' => 30,
                'notes' => 'Q4 Hospitality banquet package contract quote.',
                'created_by' => $managerMct->user_id,
            ]
        );
        WholesaleQuotationItem::firstOrCreate(
            ['quotation_id' => $wq1->quotation_id, 'product_id' => $products['SKU-JW-BLK-750']->product_id],
            [
                'uom_id' => $products['SKU-JW-BLK-750']->base_uom_id,
                'quantity' => 40.00,
                'unit_price' => 15.200,
                'vat_rate' => 5.00,
                'vat_amount' => 30.400,
                'line_total' => 638.400,
            ]
        );
        WholesaleQuotationItem::firstOrCreate(
            ['quotation_id' => $wq1->quotation_id, 'product_id' => $products['SKU-CHIV-12-1L']->product_id],
            [
                'uom_id' => $products['SKU-CHIV-12-1L']->base_uom_id,
                'quantity' => 10.00,
                'unit_price' => 18.500,
                'vat_rate' => 5.00,
                'vat_amount' => 9.250,
                'line_total' => 194.250,
            ]
        );
        WholesaleQuotationItem::firstOrCreate(
            ['quotation_id' => $wq1->quotation_id, 'product_id' => $products['SKU-TONIC-CAN-250']->product_id],
            [
                'uom_id' => $products['SKU-TONIC-CAN-250']->base_uom_id,
                'quantity' => 35.00,
                'unit_price' => 0.800,
                'vat_rate' => 5.00,
                'vat_amount' => 1.400,
                'line_total' => 29.400,
            ]
        );

        // WQ 2: Shangri-La Resort (SENT, Valid for 25 days)
        $wq2 = WholesaleQuotation::updateOrCreate(
            ['quote_no' => 'WQ-MCT-2026-00002'],
            [
                'customer_id' => $customers['CUST-SHANG-MCT']->customer_id,
                'branch_id' => $mctBranch->branch_id,
                'valid_until' => Carbon::now()->addDays(25)->toDateString(),
                'status' => 'SENT',
                'subtotal' => 1240.000,
                'vat_total' => 62.000,
                'grand_total' => 1302.000,
                'payment_terms_days' => 30,
                'notes' => 'Special event weekend stock quote sent to F&B Director.',
                'created_by' => $managerMct->user_id,
            ]
        );
        WholesaleQuotationItem::firstOrCreate(
            ['quotation_id' => $wq2->quotation_id, 'product_id' => $products['SKU-JW-BLK-750']->product_id],
            [
                'uom_id' => $products['SKU-JW-BLK-750']->base_uom_id,
                'quantity' => 50.00,
                'unit_price' => 15.200,
                'vat_rate' => 5.00,
                'vat_amount' => 38.000,
                'line_total' => 798.000,
            ]
        );
        WholesaleQuotationItem::firstOrCreate(
            ['quotation_id' => $wq2->quotation_id, 'product_id' => $products['SKU-HEN-VSOP-700']->product_id],
            [
                'uom_id' => $products['SKU-HEN-VSOP-700']->base_uom_id,
                'quantity' => 20.00,
                'unit_price' => 24.000,
                'vat_rate' => 5.00,
                'vat_amount' => 24.000,
                'line_total' => 504.000,
            ]
        );

        // WQ 3: Al Baleed Resort Salalah (CONVERTED to SO-SLL-2026-00001)
        $wq3 = WholesaleQuotation::updateOrCreate(
            ['quote_no' => 'WQ-SLL-2026-00001'],
            [
                'customer_id' => $customers['CUST-BALEED-SLL']->customer_id,
                'branch_id' => $sllBranch->branch_id,
                'valid_until' => Carbon::now()->addDays(14)->toDateString(),
                'status' => 'CONVERTED',
                'subtotal' => 444.800,
                'vat_total' => 22.240,
                'grand_total' => 467.040,
                'payment_terms_days' => 45,
                'notes' => 'Salalah seasonal replenishment quote accepted by resort manager.',
                'created_by' => $cashierSll->user_id,
            ]
        );
        WholesaleQuotationItem::firstOrCreate(
            ['quotation_id' => $wq3->quotation_id, 'product_id' => $products['SKU-JW-BLK-750']->product_id],
            [
                'uom_id' => $products['SKU-JW-BLK-750']->base_uom_id,
                'quantity' => 24.00,
                'unit_price' => 15.200,
                'vat_rate' => 5.00,
                'vat_amount' => 18.240,
                'line_total' => 383.040,
            ]
        );
        WholesaleQuotationItem::firstOrCreate(
            ['quotation_id' => $wq3->quotation_id, 'product_id' => $products['SKU-TONIC-CAN-250']->product_id],
            [
                'uom_id' => $products['SKU-TONIC-CAN-250']->base_uom_id,
                'quantity' => 100.00,
                'unit_price' => 0.800,
                'vat_rate' => 5.00,
                'vat_amount' => 4.000,
                'line_total' => 84.000,
            ]
        );

        // WQ 4: Radisson Blu Sohar (DRAFT)
        $wq4 = WholesaleQuotation::updateOrCreate(
            ['quote_no' => 'WQ-SOH-2026-00001'],
            [
                'customer_id' => $customers['CUST-RADIS-SOH']->customer_id,
                'branch_id' => $sohBranch->branch_id,
                'valid_until' => Carbon::now()->addDays(30)->toDateString(),
                'status' => 'DRAFT',
                'subtotal' => 410.000,
                'vat_total' => 20.500,
                'grand_total' => 430.500,
                'payment_terms_days' => 30,
                'notes' => 'Draft quote pending final confirmation of scotch quantities.',
                'created_by' => $cashierSoh->user_id,
            ]
        );
        WholesaleQuotationItem::firstOrCreate(
            ['quotation_id' => $wq4->quotation_id, 'product_id' => $products['SKU-CHIV-12-1L']->product_id],
            [
                'uom_id' => $products['SKU-CHIV-12-1L']->base_uom_id,
                'quantity' => 20.00,
                'unit_price' => 18.500,
                'vat_rate' => 5.00,
                'vat_amount' => 18.500,
                'line_total' => 388.500,
            ]
        );
        WholesaleQuotationItem::firstOrCreate(
            ['quotation_id' => $wq4->quotation_id, 'product_id' => $products['SKU-TONIC-CAN-250']->product_id],
            [
                'uom_id' => $products['SKU-TONIC-CAN-250']->base_uom_id,
                'quantity' => 50.00,
                'unit_price' => 0.800,
                'vat_rate' => 5.00,
                'vat_amount' => 2.000,
                'line_total' => 42.000,
            ]
        );

        // WQ 5: Oman Air Catering (EXPIRED)
        $wq5 = WholesaleQuotation::updateOrCreate(
            ['quote_no' => 'WQ-MCT-2026-00003'],
            [
                'customer_id' => $customers['CUST-OMANAIR-CAT']->customer_id,
                'branch_id' => $mctBranch->branch_id,
                'valid_until' => Carbon::now()->subDays(15)->toDateString(),
                'status' => 'EXPIRED',
                'subtotal' => 1520.000,
                'vat_total' => 76.000,
                'grand_total' => 1596.000,
                'payment_terms_days' => 60,
                'notes' => 'Tender quotation expired before award cycle.',
                'created_by' => $managerMct->user_id,
            ]
        );
        WholesaleQuotationItem::firstOrCreate(
            ['quotation_id' => $wq5->quotation_id, 'product_id' => $products['SKU-JW-BLK-750']->product_id],
            [
                'uom_id' => $products['SKU-JW-BLK-750']->base_uom_id,
                'quantity' => 100.00,
                'unit_price' => 15.200,
                'vat_rate' => 5.00,
                'vat_amount' => 76.000,
                'line_total' => 1596.000,
            ]
        );

        // WQ 6: Golden Tulip Nizwa (REJECTED)
        $wq6 = WholesaleQuotation::updateOrCreate(
            ['quote_no' => 'WQ-NZW-2026-00001'],
            [
                'customer_id' => $customers['CUST-GOLDEN-NZW']->customer_id,
                'branch_id' => $nzwBranch->branch_id,
                'valid_until' => Carbon::now()->subDays(5)->toDateString(),
                'status' => 'REJECTED',
                'subtotal' => 370.000,
                'vat_total' => 18.500,
                'grand_total' => 388.500,
                'payment_terms_days' => 30,
                'notes' => 'Customer rejected: opted for alternative beverage arrangement for corporate retreat.',
                'created_by' => $cashierNzw->user_id,
            ]
        );
        WholesaleQuotationItem::firstOrCreate(
            ['quotation_id' => $wq6->quotation_id, 'product_id' => $products['SKU-CHIV-12-1L']->product_id],
            [
                'uom_id' => $products['SKU-CHIV-12-1L']->base_uom_id,
                'quantity' => 20.00,
                'unit_price' => 18.500,
                'vat_rate' => 5.00,
                'vat_amount' => 18.500,
                'line_total' => 388.500,
            ]
        );

        // 5. Sales Orders & Reservations
        // SO 1: Grand Hyatt (INVOICED - Fully fulfilled from WQ 1)
        $so1 = SalesOrder::updateOrCreate(
            ['so_no' => 'SO-MCT-2026-00001'],
            [
                'quotation_id' => $wq1->quotation_id,
                'customer_id' => $customers['CUST-HYATT-MCT']->customer_id,
                'branch_id' => $mctBranch->branch_id,
                'warehouse_id' => $mctMainWh->warehouse_id,
                'status' => 'INVOICED',
                'subtotal' => 821.000,
                'vat_total' => 41.050,
                'grand_total' => 862.050,
                'payment_terms_days' => 30,
                'credit_check_status' => 'PASSED',
                'notes' => 'Confirmed order converted from WQ-MCT-2026-00001.',
                'created_by' => $managerMct->user_id,
            ]
        );

        $so1Item1 = SalesOrderItem::firstOrCreate(
            ['order_id' => $so1->order_id, 'product_id' => $products['SKU-JW-BLK-750']->product_id],
            [
                'uom_id' => $products['SKU-JW-BLK-750']->base_uom_id,
                'quantity' => 40.00,
                'unit_price' => 15.200,
                'vat_rate' => 5.00,
                'vat_amount' => 30.400,
                'line_total' => 638.400,
                'quantity_reserved' => 40.00,
                'quantity_dispatched' => 40.00,
            ]
        );
        SalesOrderReservation::firstOrCreate(
            [
                'order_id' => $so1->order_id,
                'order_item_id' => $so1Item1->item_id,
                'product_id' => $products['SKU-JW-BLK-750']->product_id,
            ],
            [
                'warehouse_id' => $mctMainWh->warehouse_id,
                'batch_id' => $stockBatches['MCT_SKU-JW-BLK-750']->batch_id,
                'quantity_reserved' => 40.00,
                'quantity_dispatched' => 40.00,
            ]
        );

        $so1Item2 = SalesOrderItem::firstOrCreate(
            ['order_id' => $so1->order_id, 'product_id' => $products['SKU-CHIV-12-1L']->product_id],
            [
                'uom_id' => $products['SKU-CHIV-12-1L']->base_uom_id,
                'quantity' => 10.00,
                'unit_price' => 18.500,
                'vat_rate' => 5.00,
                'vat_amount' => 9.250,
                'line_total' => 194.250,
                'quantity_reserved' => 10.00,
                'quantity_dispatched' => 10.00,
            ]
        );
        SalesOrderReservation::firstOrCreate(
            [
                'order_id' => $so1->order_id,
                'order_item_id' => $so1Item2->item_id,
                'product_id' => $products['SKU-CHIV-12-1L']->product_id,
            ],
            [
                'warehouse_id' => $mctMainWh->warehouse_id,
                'batch_id' => $stockBatches['MCT_SKU-CHIV-12-1L']->batch_id,
                'quantity_reserved' => 10.00,
                'quantity_dispatched' => 10.00,
            ]
        );

        $so1Item3 = SalesOrderItem::firstOrCreate(
            ['order_id' => $so1->order_id, 'product_id' => $products['SKU-TONIC-CAN-250']->product_id],
            [
                'uom_id' => $products['SKU-TONIC-CAN-250']->base_uom_id,
                'quantity' => 35.00,
                'unit_price' => 0.800,
                'vat_rate' => 5.00,
                'vat_amount' => 1.400,
                'line_total' => 29.400,
                'quantity_reserved' => 35.00,
                'quantity_dispatched' => 35.00,
            ]
        );
        SalesOrderReservation::firstOrCreate(
            [
                'order_id' => $so1->order_id,
                'order_item_id' => $so1Item3->item_id,
                'product_id' => $products['SKU-TONIC-CAN-250']->product_id,
            ],
            [
                'warehouse_id' => $mctMainWh->warehouse_id,
                'batch_id' => $stockBatches['MCT_SKU-TONIC-CAN-250']->batch_id,
                'quantity_reserved' => 35.00,
                'quantity_dispatched' => 35.00,
            ]
        );

        // SO 2: Shangri-La Resort (CONFIRMED - Ready for dispatch)
        $so2 = SalesOrder::updateOrCreate(
            ['so_no' => 'SO-MCT-2026-00002'],
            [
                'quotation_id' => null,
                'customer_id' => $customers['CUST-SHANG-MCT']->customer_id,
                'branch_id' => $mctBranch->branch_id,
                'warehouse_id' => $mctMainWh->warehouse_id,
                'status' => 'CONFIRMED',
                'subtotal' => 504.000,
                'vat_total' => 25.200,
                'grand_total' => 529.200,
                'payment_terms_days' => 30,
                'credit_check_status' => 'PASSED',
                'notes' => 'Direct order for weekly pool bar lounge service. Stock reserved.',
                'created_by' => $managerMct->user_id,
            ]
        );

        $so2Item1 = SalesOrderItem::firstOrCreate(
            ['order_id' => $so2->order_id, 'product_id' => $products['SKU-JW-BLK-750']->product_id],
            [
                'uom_id' => $products['SKU-JW-BLK-750']->base_uom_id,
                'quantity' => 30.00,
                'unit_price' => 15.200,
                'vat_rate' => 5.00,
                'vat_amount' => 22.800,
                'line_total' => 478.800,
                'quantity_reserved' => 30.00,
                'quantity_dispatched' => 0.00,
            ]
        );
        SalesOrderReservation::firstOrCreate(
            [
                'order_id' => $so2->order_id,
                'order_item_id' => $so2Item1->item_id,
                'product_id' => $products['SKU-JW-BLK-750']->product_id,
            ],
            [
                'warehouse_id' => $mctMainWh->warehouse_id,
                'batch_id' => $stockBatches['MCT_SKU-JW-BLK-750']->batch_id,
                'quantity_reserved' => 30.00,
                'quantity_dispatched' => 0.00,
            ]
        );

        $so2Item2 = SalesOrderItem::firstOrCreate(
            ['order_id' => $so2->order_id, 'product_id' => $products['SKU-TONIC-CAN-250']->product_id],
            [
                'uom_id' => $products['SKU-TONIC-CAN-250']->base_uom_id,
                'quantity' => 60.00,
                'unit_price' => 0.800,
                'vat_rate' => 5.00,
                'vat_amount' => 2.400,
                'line_total' => 50.400,
                'quantity_reserved' => 60.00,
                'quantity_dispatched' => 0.00,
            ]
        );
        SalesOrderReservation::firstOrCreate(
            [
                'order_id' => $so2->order_id,
                'order_item_id' => $so2Item2->item_id,
                'product_id' => $products['SKU-TONIC-CAN-250']->product_id,
            ],
            [
                'warehouse_id' => $mctMainWh->warehouse_id,
                'batch_id' => $stockBatches['MCT_SKU-TONIC-CAN-250']->batch_id,
                'quantity_reserved' => 60.00,
                'quantity_dispatched' => 0.00,
            ]
        );

        // SO 3: Al Baleed Resort Salalah (PARTIALLY_DISPATCHED from WQ 3)
        $so3 = SalesOrder::updateOrCreate(
            ['so_no' => 'SO-SLL-2026-00001'],
            [
                'quotation_id' => $wq3->quotation_id,
                'customer_id' => $customers['CUST-BALEED-SLL']->customer_id,
                'branch_id' => $sllBranch->branch_id,
                'warehouse_id' => $sllMainWh->warehouse_id,
                'status' => 'PARTIALLY_DISPATCHED',
                'subtotal' => 444.800,
                'vat_total' => 22.240,
                'grand_total' => 467.040,
                'payment_terms_days' => 45,
                'credit_check_status' => 'PASSED',
                'notes' => 'Salalah resort order. Split delivery: 50 tonic cans held for next run.',
                'created_by' => $cashierSll->user_id,
            ]
        );

        $so3Item1 = SalesOrderItem::firstOrCreate(
            ['order_id' => $so3->order_id, 'product_id' => $products['SKU-JW-BLK-750']->product_id],
            [
                'uom_id' => $products['SKU-JW-BLK-750']->base_uom_id,
                'quantity' => 24.00,
                'unit_price' => 15.200,
                'vat_rate' => 5.00,
                'vat_amount' => 18.240,
                'line_total' => 383.040,
                'quantity_reserved' => 24.00,
                'quantity_dispatched' => 24.00,
            ]
        );
        SalesOrderReservation::firstOrCreate(
            [
                'order_id' => $so3->order_id,
                'order_item_id' => $so3Item1->item_id,
                'product_id' => $products['SKU-JW-BLK-750']->product_id,
            ],
            [
                'warehouse_id' => $sllMainWh->warehouse_id,
                'batch_id' => $stockBatches['SLL_SKU-JW-BLK-750']->batch_id,
                'quantity_reserved' => 24.00,
                'quantity_dispatched' => 24.00,
            ]
        );

        $so3Item2 = SalesOrderItem::firstOrCreate(
            ['order_id' => $so3->order_id, 'product_id' => $products['SKU-TONIC-CAN-250']->product_id],
            [
                'uom_id' => $products['SKU-TONIC-CAN-250']->base_uom_id,
                'quantity' => 100.00,
                'unit_price' => 0.800,
                'vat_rate' => 5.00,
                'vat_amount' => 4.000,
                'line_total' => 84.000,
                'quantity_reserved' => 100.00,
                'quantity_dispatched' => 50.00,
            ]
        );
        SalesOrderReservation::firstOrCreate(
            [
                'order_id' => $so3->order_id,
                'order_item_id' => $so3Item2->item_id,
                'product_id' => $products['SKU-TONIC-CAN-250']->product_id,
            ],
            [
                'warehouse_id' => $sllMainWh->warehouse_id,
                'batch_id' => $stockBatches['SLL_SKU-TONIC-CAN-250']->batch_id,
                'quantity_reserved' => 100.00,
                'quantity_dispatched' => 50.00,
            ]
        );

        // SO 4: Oman Air Catering (CONFIRMED with CREDIT OVERRIDE)
        $so4 = SalesOrder::updateOrCreate(
            ['so_no' => 'SO-MCT-2026-00003'],
            [
                'quotation_id' => null,
                'customer_id' => $customers['CUST-OMANAIR-CAT']->customer_id,
                'branch_id' => $mctBranch->branch_id,
                'warehouse_id' => $mctMainWh->warehouse_id,
                'status' => 'CONFIRMED',
                'subtotal' => 1456.000,
                'vat_total' => 72.800,
                'grand_total' => 1528.800,
                'payment_terms_days' => 60,
                'credit_check_status' => 'OVERRIDDEN',
                'credit_override_by' => $supervisorMct->user_id,
                'credit_override_at' => Carbon::now()->subDays(2),
                'credit_override_reason' => 'Approved by supervisor per General Manager authorization for airline contract fulfillment.',
                'notes' => 'In-flight service replenishment order.',
                'created_by' => $managerMct->user_id,
            ]
        );

        $so4Item1 = SalesOrderItem::firstOrCreate(
            ['order_id' => $so4->order_id, 'product_id' => $products['SKU-JW-BLK-750']->product_id],
            [
                'uom_id' => $products['SKU-JW-BLK-750']->base_uom_id,
                'quantity' => 80.00,
                'unit_price' => 15.200,
                'vat_rate' => 5.00,
                'vat_amount' => 60.800,
                'line_total' => 1276.800,
                'quantity_reserved' => 80.00,
                'quantity_dispatched' => 0.00,
            ]
        );
        SalesOrderReservation::firstOrCreate(
            [
                'order_id' => $so4->order_id,
                'order_item_id' => $so4Item1->item_id,
                'product_id' => $products['SKU-JW-BLK-750']->product_id,
            ],
            [
                'warehouse_id' => $mctMainWh->warehouse_id,
                'batch_id' => $stockBatches['MCT_SKU-JW-BLK-750']->batch_id,
                'quantity_reserved' => 80.00,
                'quantity_dispatched' => 0.00,
            ]
        );

        $so4Item2 = SalesOrderItem::firstOrCreate(
            ['order_id' => $so4->order_id, 'product_id' => $products['SKU-HEN-VSOP-700']->product_id],
            [
                'uom_id' => $products['SKU-HEN-VSOP-700']->base_uom_id,
                'quantity' => 10.00,
                'unit_price' => 24.000,
                'vat_rate' => 5.00,
                'vat_amount' => 12.000,
                'line_total' => 252.000,
                'quantity_reserved' => 10.00,
                'quantity_dispatched' => 0.00,
            ]
        );
        SalesOrderReservation::firstOrCreate(
            [
                'order_id' => $so4->order_id,
                'order_item_id' => $so4Item2->item_id,
                'product_id' => $products['SKU-HEN-VSOP-700']->product_id,
            ],
            [
                'warehouse_id' => $mctMainWh->warehouse_id,
                'batch_id' => $stockBatches['MCT_SKU-HEN-VSOP-700']->batch_id,
                'quantity_reserved' => 10.00,
                'quantity_dispatched' => 0.00,
            ]
        );

        // SO 5: Radisson Blu Sohar (DRAFT)
        $so5 = SalesOrder::updateOrCreate(
            ['so_no' => 'SO-SOH-2026-00001'],
            [
                'quotation_id' => null,
                'customer_id' => $customers['CUST-RADIS-SOH']->customer_id,
                'branch_id' => $sohBranch->branch_id,
                'warehouse_id' => $sohMainWh->warehouse_id,
                'status' => 'DRAFT',
                'subtotal' => 309.500,
                'vat_total' => 15.475,
                'grand_total' => 324.975,
                'payment_terms_days' => 30,
                'credit_check_status' => 'PENDING',
                'notes' => 'Draft sales order created for Sohar port executive lounge.',
                'created_by' => $cashierSoh->user_id,
            ]
        );
        SalesOrderItem::firstOrCreate(
            ['order_id' => $so5->order_id, 'product_id' => $products['SKU-CHIV-12-1L']->product_id],
            [
                'uom_id' => $products['SKU-CHIV-12-1L']->base_uom_id,
                'quantity' => 15.00,
                'unit_price' => 18.500,
                'vat_rate' => 5.00,
                'vat_amount' => 13.875,
                'line_total' => 291.375,
                'quantity_reserved' => 0.00,
                'quantity_dispatched' => 0.00,
            ]
        );
        SalesOrderItem::firstOrCreate(
            ['order_id' => $so5->order_id, 'product_id' => $products['SKU-TONIC-CAN-250']->product_id],
            [
                'uom_id' => $products['SKU-TONIC-CAN-250']->base_uom_id,
                'quantity' => 40.00,
                'unit_price' => 0.800,
                'vat_rate' => 5.00,
                'vat_amount' => 1.600,
                'line_total' => 33.600,
                'quantity_reserved' => 0.00,
                'quantity_dispatched' => 0.00,
            ]
        );

        // 6. Delivery Notes
        // DN 1: Linked to SO 1 (Grand Hyatt - DISPATCHED)
        $dn1 = DeliveryNote::updateOrCreate(
            ['dn_no' => 'DN-MCT-2026-00001'],
            [
                'sales_order_id' => $so1->order_id,
                'branch_id' => $mctBranch->branch_id,
                'warehouse_id' => $mctMainWh->warehouse_id,
                'dispatched_by' => $managerMct->user_id,
                'dispatched_at' => Carbon::now()->subDays(4),
                'vehicle_no' => 'OM-8921-AD',
                'driver_name' => 'Said Al-Hajri',
                'status' => 'DISPATCHED',
                'notes' => 'Delivered to Grand Hyatt Muscat central receiving dock with temperature report.',
            ]
        );
        $dn1Item1 = DeliveryNoteItem::firstOrCreate(
            ['delivery_note_id' => $dn1->delivery_note_id, 'product_id' => $products['SKU-JW-BLK-750']->product_id],
            [
                'order_item_id' => $so1Item1->item_id,
                'batch_id' => $stockBatches['MCT_SKU-JW-BLK-750']->batch_id,
                'quantity_dispatched' => 40.00,
            ]
        );
        $dn1Item2 = DeliveryNoteItem::firstOrCreate(
            ['delivery_note_id' => $dn1->delivery_note_id, 'product_id' => $products['SKU-CHIV-12-1L']->product_id],
            [
                'order_item_id' => $so1Item2->item_id,
                'batch_id' => $stockBatches['MCT_SKU-CHIV-12-1L']->batch_id,
                'quantity_dispatched' => 10.00,
            ]
        );
        $dn1Item3 = DeliveryNoteItem::firstOrCreate(
            ['delivery_note_id' => $dn1->delivery_note_id, 'product_id' => $products['SKU-TONIC-CAN-250']->product_id],
            [
                'order_item_id' => $so1Item3->item_id,
                'batch_id' => $stockBatches['MCT_SKU-TONIC-CAN-250']->batch_id,
                'quantity_dispatched' => 35.00,
            ]
        );

        // DN 2: Linked to SO 3 (Al Baleed Salalah - DISPATCHED partial)
        $dn2 = DeliveryNote::updateOrCreate(
            ['dn_no' => 'DN-SLL-2026-00001'],
            [
                'sales_order_id' => $so3->order_id,
                'branch_id' => $sllBranch->branch_id,
                'warehouse_id' => $sllMainWh->warehouse_id,
                'dispatched_by' => $cashierSll->user_id,
                'dispatched_at' => Carbon::now()->subDays(2),
                'vehicle_no' => 'OM-4412-BD',
                'driver_name' => 'Khalfan Al-Kathiri',
                'status' => 'DISPATCHED',
                'notes' => 'Part 1 delivery: All bottles and 50 cans tonic received and signed.',
            ]
        );
        $dn2Item1 = DeliveryNoteItem::firstOrCreate(
            ['delivery_note_id' => $dn2->delivery_note_id, 'product_id' => $products['SKU-JW-BLK-750']->product_id],
            [
                'order_item_id' => $so3Item1->item_id,
                'batch_id' => $stockBatches['SLL_SKU-JW-BLK-750']->batch_id,
                'quantity_dispatched' => 24.00,
            ]
        );
        $dn2Item2 = DeliveryNoteItem::firstOrCreate(
            ['delivery_note_id' => $dn2->delivery_note_id, 'product_id' => $products['SKU-TONIC-CAN-250']->product_id],
            [
                'order_item_id' => $so3Item2->item_id,
                'batch_id' => $stockBatches['SLL_SKU-TONIC-CAN-250']->batch_id,
                'quantity_dispatched' => 50.00,
            ]
        );

        // DN 3: Linked to SO 4 (Oman Air - DRAFT)
        $dn3 = DeliveryNote::updateOrCreate(
            ['dn_no' => 'DN-MCT-2026-00002'],
            [
                'sales_order_id' => $so4->order_id,
                'branch_id' => $mctBranch->branch_id,
                'warehouse_id' => $mctMainWh->warehouse_id,
                'dispatched_by' => null,
                'dispatched_at' => null,
                'vehicle_no' => 'OM-1109-AD',
                'driver_name' => 'Mubarak Al-Farsi',
                'status' => 'DRAFT',
                'notes' => 'Prepared for security clearance and airport airside transport tomorrow morning.',
            ]
        );
        DeliveryNoteItem::firstOrCreate(
            ['delivery_note_id' => $dn3->delivery_note_id, 'product_id' => $products['SKU-JW-BLK-750']->product_id],
            [
                'order_item_id' => $so4Item1->item_id,
                'batch_id' => $stockBatches['MCT_SKU-JW-BLK-750']->batch_id,
                'quantity_dispatched' => 80.00,
            ]
        );
        DeliveryNoteItem::firstOrCreate(
            ['delivery_note_id' => $dn3->delivery_note_id, 'product_id' => $products['SKU-HEN-VSOP-700']->product_id],
            [
                'order_item_id' => $so4Item2->item_id,
                'batch_id' => $stockBatches['MCT_SKU-HEN-VSOP-700']->batch_id,
                'quantity_dispatched' => 10.00,
            ]
        );

        // 7. Sales Invoices & Customer Payments
        // INV 1: Grand Hyatt (PAID in full)
        $inv1 = SalesInvoice::updateOrCreate(
            ['invoice_no' => 'INV-MCT-2026-00001'],
            [
                'sales_order_id' => $so1->order_id,
                'delivery_note_id' => $dn1->delivery_note_id,
                'customer_id' => $customers['CUST-HYATT-MCT']->customer_id,
                'branch_id' => $mctBranch->branch_id,
                'warehouse_id' => $mctMainWh->warehouse_id,
                'invoice_date' => Carbon::now()->subDays(4)->toDateString(),
                'due_date' => Carbon::now()->addDays(26)->toDateString(),
                'payment_terms_days' => 30,
                'subtotal' => 821.000,
                'vat_total' => 41.050,
                'grand_total' => 862.050,
                'amount_paid' => 862.050,
                'status' => 'PAID',
                'posted_at' => Carbon::now()->subDays(4),
                'notes' => 'Invoiced against DN-MCT-2026-00001. Paid via Bank Muscat bank transfer.',
                'created_by' => $managerMct->user_id,
            ]
        );
        SalesInvoiceItem::firstOrCreate(
            ['invoice_id' => $inv1->invoice_id, 'product_id' => $products['SKU-JW-BLK-750']->product_id],
            [
                'order_item_id' => $so1Item1->item_id,
                'delivery_note_item_id' => $dn1Item1->item_id,
                'batch_id' => $stockBatches['MCT_SKU-JW-BLK-750']->batch_id,
                'uom_id' => $products['SKU-JW-BLK-750']->base_uom_id,
                'quantity' => 40.00,
                'unit_price' => 15.200,
                'vat_rate' => 5.00,
                'vat_amount' => 30.400,
                'line_total' => 638.400,
            ]
        );
        SalesInvoiceItem::firstOrCreate(
            ['invoice_id' => $inv1->invoice_id, 'product_id' => $products['SKU-CHIV-12-1L']->product_id],
            [
                'order_item_id' => $so1Item2->item_id,
                'delivery_note_item_id' => $dn1Item2->item_id,
                'batch_id' => $stockBatches['MCT_SKU-CHIV-12-1L']->batch_id,
                'uom_id' => $products['SKU-CHIV-12-1L']->base_uom_id,
                'quantity' => 10.00,
                'unit_price' => 18.500,
                'vat_rate' => 5.00,
                'vat_amount' => 9.250,
                'line_total' => 194.250,
            ]
        );
        SalesInvoiceItem::firstOrCreate(
            ['invoice_id' => $inv1->invoice_id, 'product_id' => $products['SKU-TONIC-CAN-250']->product_id],
            [
                'order_item_id' => $so1Item3->item_id,
                'delivery_note_item_id' => $dn1Item3->item_id,
                'batch_id' => $stockBatches['MCT_SKU-TONIC-CAN-250']->batch_id,
                'uom_id' => $products['SKU-TONIC-CAN-250']->base_uom_id,
                'quantity' => 35.00,
                'unit_price' => 0.800,
                'vat_rate' => 5.00,
                'vat_amount' => 1.400,
                'line_total' => 29.400,
            ]
        );

        CustomerPayment::updateOrCreate(
            ['reference' => 'FT-BM-2026-98124'],
            [
                'invoice_id' => $inv1->invoice_id,
                'customer_id' => $customers['CUST-HYATT-MCT']->customer_id,
                'amount' => 862.050,
                'method' => 'BANK_TRANSFER',
                'received_by' => $managerMct->user_id,
                'received_at' => Carbon::now()->subDays(2),
                'notes' => 'Settled in full via Corporate Online Banking (Bank Muscat Ref: 98124).',
            ]
        );

        // INV 2: Shangri-La Resort (PARTIALLY_PAID)
        $inv2 = SalesInvoice::updateOrCreate(
            ['invoice_no' => 'INV-MCT-2026-00002'],
            [
                'sales_order_id' => $so2->order_id,
                'delivery_note_id' => null,
                'customer_id' => $customers['CUST-SHANG-MCT']->customer_id,
                'branch_id' => $mctBranch->branch_id,
                'warehouse_id' => $mctMainWh->warehouse_id,
                'invoice_date' => Carbon::now()->subDays(12)->toDateString(),
                'due_date' => Carbon::now()->addDays(18)->toDateString(),
                'payment_terms_days' => 30,
                'subtotal' => 1520.000,
                'vat_total' => 76.000,
                'grand_total' => 1596.000,
                'amount_paid' => 1000.000,
                'status' => 'PARTIALLY_PAID',
                'posted_at' => Carbon::now()->subDays(12),
                'notes' => 'Partial advance payment received via cheque. Balance due OMR 596.000.',
                'created_by' => $managerMct->user_id,
            ]
        );
        SalesInvoiceItem::firstOrCreate(
            ['invoice_id' => $inv2->invoice_id, 'product_id' => $products['SKU-JW-BLK-750']->product_id],
            [
                'order_item_id' => null,
                'delivery_note_item_id' => null,
                'batch_id' => $stockBatches['MCT_SKU-JW-BLK-750']->batch_id,
                'uom_id' => $products['SKU-JW-BLK-750']->base_uom_id,
                'quantity' => 100.00,
                'unit_price' => 15.200,
                'vat_rate' => 5.00,
                'vat_amount' => 76.000,
                'line_total' => 1596.000,
            ]
        );

        CustomerPayment::updateOrCreate(
            ['reference' => 'CHQ-NBO-0049128'],
            [
                'invoice_id' => $inv2->invoice_id,
                'customer_id' => $customers['CUST-SHANG-MCT']->customer_id,
                'amount' => 1000.000,
                'method' => 'CHEQUE',
                'received_by' => $managerMct->user_id,
                'received_at' => Carbon::now()->subDays(5),
                'notes' => 'Part-payment cheque deposited into NBO RMSC Account #01928374.',
            ]
        );

        // INV 3: Al Baleed Resort Salalah (POSTED - Unpaid)
        $inv3 = SalesInvoice::updateOrCreate(
            ['invoice_no' => 'INV-SLL-2026-00001'],
            [
                'sales_order_id' => $so3->order_id,
                'delivery_note_id' => $dn2->delivery_note_id,
                'customer_id' => $customers['CUST-BALEED-SLL']->customer_id,
                'branch_id' => $sllBranch->branch_id,
                'warehouse_id' => $sllMainWh->warehouse_id,
                'invoice_date' => Carbon::now()->subDays(2)->toDateString(),
                'due_date' => Carbon::now()->addDays(43)->toDateString(),
                'payment_terms_days' => 45,
                'subtotal' => 404.800,
                'vat_total' => 20.240,
                'grand_total' => 425.040,
                'amount_paid' => 0.000,
                'status' => 'POSTED',
                'posted_at' => Carbon::now()->subDays(2),
                'notes' => 'Billed against DN-SLL-2026-00001. Awaiting 45-day payment credit cycle.',
                'created_by' => $cashierSll->user_id,
            ]
        );
        SalesInvoiceItem::firstOrCreate(
            ['invoice_id' => $inv3->invoice_id, 'product_id' => $products['SKU-JW-BLK-750']->product_id],
            [
                'order_item_id' => $so3Item1->item_id,
                'delivery_note_item_id' => $dn2Item1->item_id,
                'batch_id' => $stockBatches['SLL_SKU-JW-BLK-750']->batch_id,
                'uom_id' => $products['SKU-JW-BLK-750']->base_uom_id,
                'quantity' => 24.00,
                'unit_price' => 15.200,
                'vat_rate' => 5.00,
                'vat_amount' => 18.240,
                'line_total' => 383.040,
            ]
        );
        SalesInvoiceItem::firstOrCreate(
            ['invoice_id' => $inv3->invoice_id, 'product_id' => $products['SKU-TONIC-CAN-250']->product_id],
            [
                'order_item_id' => $so3Item2->item_id,
                'delivery_note_item_id' => $dn2Item2->item_id,
                'batch_id' => $stockBatches['SLL_SKU-TONIC-CAN-250']->batch_id,
                'uom_id' => $products['SKU-TONIC-CAN-250']->base_uom_id,
                'quantity' => 50.00,
                'unit_price' => 0.800,
                'vat_rate' => 5.00,
                'vat_amount' => 2.000,
                'line_total' => 42.000,
            ]
        );

        // INV 4: Radisson Blu Sohar (DRAFT)
        $inv4 = SalesInvoice::updateOrCreate(
            ['invoice_no' => 'INV-SOH-2026-00001'],
            [
                'sales_order_id' => $so5->order_id,
                'delivery_note_id' => null,
                'customer_id' => $customers['CUST-RADIS-SOH']->customer_id,
                'branch_id' => $sohBranch->branch_id,
                'warehouse_id' => $sohMainWh->warehouse_id,
                'invoice_date' => Carbon::now()->toDateString(),
                'due_date' => Carbon::now()->addDays(30)->toDateString(),
                'payment_terms_days' => 30,
                'subtotal' => 309.500,
                'vat_total' => 15.475,
                'grand_total' => 324.975,
                'amount_paid' => 0.000,
                'status' => 'DRAFT',
                'notes' => 'Draft invoice for Radisson Blu Sohar. Ready for supervisor review.',
                'created_by' => $cashierSoh->user_id,
            ]
        );
        SalesInvoiceItem::firstOrCreate(
            ['invoice_id' => $inv4->invoice_id, 'product_id' => $products['SKU-CHIV-12-1L']->product_id],
            [
                'order_item_id' => null,
                'delivery_note_item_id' => null,
                'batch_id' => $stockBatches['SOH_SKU-CHIV-12-1L']->batch_id,
                'uom_id' => $products['SKU-CHIV-12-1L']->base_uom_id,
                'quantity' => 15.00,
                'unit_price' => 18.500,
                'vat_rate' => 5.00,
                'vat_amount' => 13.875,
                'line_total' => 291.375,
            ]
        );
        SalesInvoiceItem::firstOrCreate(
            ['invoice_id' => $inv4->invoice_id, 'product_id' => $products['SKU-TONIC-CAN-250']->product_id],
            [
                'order_item_id' => null,
                'delivery_note_item_id' => null,
                'batch_id' => $stockBatches['SOH_SKU-TONIC-CAN-250']->batch_id,
                'uom_id' => $products['SKU-TONIC-CAN-250']->base_uom_id,
                'quantity' => 40.00,
                'unit_price' => 0.800,
                'vat_rate' => 5.00,
                'vat_amount' => 1.600,
                'line_total' => 33.600,
            ]
        );

        // 8. Sync Document Sequences to prevent number collision in UI
        $year = Carbon::now()->format('Y');
        $sequences = [
            ['branch' => $mctBranch->branch_id, 'type' => 'WQ', 'seq' => 3],
            ['branch' => $sllBranch->branch_id, 'type' => 'WQ', 'seq' => 1],
            ['branch' => $sohBranch->branch_id, 'type' => 'WQ', 'seq' => 1],
            ['branch' => $nzwBranch->branch_id, 'type' => 'WQ', 'seq' => 1],
            ['branch' => $mctBranch->branch_id, 'type' => 'SO', 'seq' => 3],
            ['branch' => $sllBranch->branch_id, 'type' => 'SO', 'seq' => 1],
            ['branch' => $sohBranch->branch_id, 'type' => 'SO', 'seq' => 1],
            ['branch' => $mctBranch->branch_id, 'type' => 'DN', 'seq' => 2],
            ['branch' => $sllBranch->branch_id, 'type' => 'DN', 'seq' => 1],
            ['branch' => $mctBranch->branch_id, 'type' => 'INV', 'seq' => 2],
            ['branch' => $sllBranch->branch_id, 'type' => 'INV', 'seq' => 1],
            ['branch' => $sohBranch->branch_id, 'type' => 'INV', 'seq' => 1],
        ];

        foreach ($sequences as $sq) {
            WholesaleDocumentSequence::updateOrCreate(
                [
                    'branch_id' => $sq['branch'],
                    'doc_type' => $sq['type'],
                    'sequence_year' => $year,
                ],
                [
                    'last_sequence' => $sq['seq'],
                ]
            );
        }

        // =========================================================================
        // RETAIL POS MODULE SEEDING
        // =========================================================================

        $mctCounter1 = $counters['CNT-MCT-01'] ?? Counter::where('counter_code', 'CNT-MCT-01')->first();
        $mctCounter2 = $counters['CNT-MCT-02'] ?? Counter::where('counter_code', 'CNT-MCT-02')->first();
        $sllCounter1 = $counters['CNT-SLL-01'] ?? Counter::where('counter_code', 'CNT-SLL-01')->first();
        $sohCounter1 = $counters['CNT-SOH-01'] ?? Counter::where('counter_code', 'CNT-SOH-01')->first();

        // 9. Cashier Shifts: OPEN Shifts for Immediate Testing + Reconciled Shifts
        // Ensure cashier01 has an active OPEN shift on CNT-MCT-01
        $currentOpenShiftMct = CashierShift::where('counter_id', $mctCounter1->counter_id)
            ->where('status', 'OPEN')
            ->first();

        if (! $currentOpenShiftMct) {
            $currentOpenShiftMct = CashierShift::create([
                'branch_id' => $mctBranch->branch_id,
                'counter_id' => $mctCounter1->counter_id,
                'user_id' => $cashierMct->user_id,
                'status' => 'OPEN',
                'opening_float' => 50.000,
                'expected_cash' => 50.000,
                'counted_cash' => 0.000,
                'card_total' => 0.000,
                'variance' => 0.000,
                'opened_at' => Carbon::now()->startOfDay()->addHours(8),
                'notes' => 'Active morning shift opened for POS counter.',
            ]);
        }

        // Ensure cashier_sll has an active OPEN shift on CNT-SLL-01
        $currentOpenShiftSll = CashierShift::where('counter_id', $sllCounter1->counter_id)
            ->where('status', 'OPEN')
            ->first();

        if (! $currentOpenShiftSll) {
            $currentOpenShiftSll = CashierShift::create([
                'branch_id' => $sllBranch->branch_id,
                'counter_id' => $sllCounter1->counter_id,
                'user_id' => $cashierSll->user_id,
                'status' => 'OPEN',
                'opening_float' => 50.000,
                'expected_cash' => 50.000,
                'counted_cash' => 0.000,
                'card_total' => 0.000,
                'variance' => 0.000,
                'opened_at' => Carbon::now()->startOfDay()->addHours(9),
                'notes' => 'Active Salalah morning shift.',
            ]);
        }

        // Reconciled Shift 1 (Muscat Counter 01, 7 days ago - Balanced)
        $reconciledShiftMct = CashierShift::updateOrCreate(
            [
                'counter_id' => $mctCounter1->counter_id,
                'opened_at' => Carbon::now()->subDays(7)->setTime(8, 0, 0),
            ],
            [
                'branch_id' => $mctBranch->branch_id,
                'user_id' => $cashierMct->user_id,
                'closed_at' => Carbon::now()->subDays(7)->setTime(16, 0, 0),
                'opening_float' => 100.000,
                'expected_cash' => 137.000,
                'counted_cash' => 137.000,
                'card_total' => 71.250,
                'variance' => 0.000,
                'variance_status' => 'APPROVED',
                'approved_by' => $supervisorMct->user_id,
                'approved_at' => Carbon::now()->subDays(7)->setTime(16, 30, 0),
                'status' => 'RECONCILED',
                'notes' => 'Shift closed with zero cash variance. Reconciled successfully.',
            ]
        );

        // Reconciled Shift 2 (Muscat Counter 02, 6 days ago - Small Approved Variance)
        $reconciledShiftMct2 = CashierShift::updateOrCreate(
            [
                'counter_id' => $mctCounter2->counter_id,
                'opened_at' => Carbon::now()->subDays(6)->setTime(9, 0, 0),
            ],
            [
                'branch_id' => $mctBranch->branch_id,
                'user_id' => $cashierMct->user_id,
                'closed_at' => Carbon::now()->subDays(6)->setTime(17, 0, 0),
                'opening_float' => 75.000,
                'expected_cash' => 152.000,
                'counted_cash' => 150.500,
                'card_total' => 120.000,
                'variance' => -1.500,
                'variance_status' => 'APPROVED',
                'approved_by' => $supervisorMct->user_id,
                'approved_at' => Carbon::now()->subDays(6)->setTime(17, 45, 0),
                'status' => 'RECONCILED',
                'notes' => 'Minor cash shortage of OMR 1.500 approved by supervisor.',
            ]
        );

        // Reconciled Shift 3 (Sohar Counter 01, 5 days ago)
        $reconciledShiftSoh = CashierShift::updateOrCreate(
            [
                'counter_id' => $sohCounter1->counter_id,
                'opened_at' => Carbon::now()->subDays(5)->setTime(10, 0, 0),
            ],
            [
                'branch_id' => $sohBranch->branch_id,
                'user_id' => $cashierSoh->user_id,
                'closed_at' => Carbon::now()->subDays(5)->setTime(18, 0, 0),
                'opening_float' => 50.000,
                'expected_cash' => 92.000,
                'counted_cash' => 92.000,
                'card_total' => 45.000,
                'variance' => 0.000,
                'variance_status' => 'APPROVED',
                'approved_by' => $supervisorMct->user_id,
                'approved_at' => Carbon::now()->subDays(5)->setTime(18, 15, 0),
                'status' => 'RECONCILED',
                'notes' => 'Sohar shift completed with exact cash match.',
            ]
        );

        // 10. Sample Permit Screenshot File in Private Storage
        $screenshotDir = storage_path('app/private/permit-screenshots/BR-MCT/2026/01');
        if (! File::exists($screenshotDir)) {
            File::makeDirectory($screenshotDir, 0755, true, true);
        }

        $samplePngPath = $screenshotDir . '/demo-screenshot.png';
        if (! File::exists($samplePngPath)) {
            // Write a valid 1x1 transparent PNG binary
            $dummyPng = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
            File::put($samplePngPath, $dummyPng);
        }

        // 11. Retail Sales & Permit Verifications
        // Sale 1: CASH sale with Tariq Al-Balushi (Controlled liquor item + Manual Verification)
        $sale1 = RetailSale::updateOrCreate(
            ['receipt_no' => 'RS-BR-MCT-2026-0001'],
            [
                'branch_id' => $mctBranch->branch_id,
                'counter_id' => $mctCounter1->counter_id,
                'cashier_shift_id' => $reconciledShiftMct->shift_id,
                'user_id' => $cashierMct->user_id,
                'customer_civil_id' => '10293847',
                'subtotal' => 35.238,
                'vat_total' => 1.762,
                'grand_total' => 37.000,
                'payment_method' => 'CASH',
                'cash_amount' => 40.000,
                'card_amount' => 0.000,
                'card_reference' => null,
                'change_given' => 3.000,
                'status' => 'COMPLETED',
                'created_at' => Carbon::now()->subDays(7)->setTime(10, 15, 0),
            ]
        );
        RetailSaleItem::firstOrCreate(
            ['retail_sale_id' => $sale1->id, 'product_id' => $products['SKU-JW-BLK-750']->product_id],
            [
                'batch_id' => $stockBatches['MCT_SKU-JW-BLK-750']->batch_id,
                'uom_id' => $products['SKU-JW-BLK-750']->base_uom_id,
                'quantity' => 2.000,
                'unit_price' => 18.500,
                'vat_rate' => 5.000,
                'vat_amount' => 1.762,
                'line_total' => 37.000,
            ]
        );

        PermitVerification::updateOrCreate(
            ['retail_sale_id' => $sale1->id],
            [
                'branch_id' => $mctBranch->branch_id,
                'counter_id' => $mctCounter1->counter_id,
                'cashier_shift_id' => $reconciledShiftMct->shift_id,
                'entered_by' => $cashierMct->user_id,
                'civil_id' => '10293847',
                'holder_name' => 'Tariq Al-Balushi',
                'permit_no' => 'PERM-2026-0091',
                'annual_limit' => 600.000,
                'used_amount' => 120.000,
                'remaining_amount' => 480.000,
                'valid_from' => '2026-01-01',
                'valid_to' => '2026-12-31',
                'verified_at' => Carbon::now()->subDays(7)->setTime(10, 12, 0),
                'entry_method' => 'MANUAL',
                'controlled_total' => 37.000,
                'status' => 'USED',
                'attested_by' => $cashierMct->user_id,
                'attested_at' => Carbon::now()->subDays(7)->setTime(10, 14, 0),
                'created_at' => Carbon::now()->subDays(7)->setTime(10, 12, 0),
            ]
        );

        // Sale 2: CARD sale (Non-liquor Schweppes tonic cans - no permit required)
        $sale2 = RetailSale::updateOrCreate(
            ['receipt_no' => 'RS-BR-MCT-2026-0002'],
            [
                'branch_id' => $mctBranch->branch_id,
                'counter_id' => $mctCounter1->counter_id,
                'cashier_shift_id' => $reconciledShiftMct->shift_id,
                'user_id' => $cashierMct->user_id,
                'customer_civil_id' => '78901234',
                'subtotal' => 15.000,
                'vat_total' => 0.750,
                'grand_total' => 15.750,
                'payment_method' => 'CARD',
                'cash_amount' => 0.000,
                'card_amount' => 15.750,
                'card_reference' => 'POS-AUTH-99120',
                'change_given' => 0.000,
                'status' => 'COMPLETED',
                'created_at' => Carbon::now()->subDays(7)->setTime(11, 40, 0),
            ]
        );
        RetailSaleItem::firstOrCreate(
            ['retail_sale_id' => $sale2->id, 'product_id' => $products['SKU-TONIC-CAN-250']->product_id],
            [
                'batch_id' => $stockBatches['MCT_SKU-TONIC-CAN-250']->batch_id,
                'uom_id' => $products['SKU-TONIC-CAN-250']->base_uom_id,
                'quantity' => 15.000,
                'unit_price' => 1.050,
                'vat_rate' => 5.000,
                'vat_amount' => 0.750,
                'line_total' => 15.750,
            ]
        );

        // Sale 3: SPLIT payment sale with Alexander Wright (Cash + Card)
        $sale3 = RetailSale::updateOrCreate(
            ['receipt_no' => 'RS-BR-MCT-2026-0003'],
            [
                'branch_id' => $mctBranch->branch_id,
                'counter_id' => $mctCounter1->counter_id,
                'cashier_shift_id' => $reconciledShiftMct->shift_id,
                'user_id' => $cashierMct->user_id,
                'customer_civil_id' => '12345678',
                'subtotal' => 52.857,
                'vat_total' => 2.643,
                'grand_total' => 55.500,
                'payment_method' => 'SPLIT',
                'cash_amount' => 20.000,
                'card_amount' => 35.500,
                'card_reference' => 'SPLIT-POS-1042',
                'change_given' => 0.000,
                'status' => 'COMPLETED',
                'created_at' => Carbon::now()->subDays(7)->setTime(14, 20, 0),
            ]
        );
        RetailSaleItem::firstOrCreate(
            ['retail_sale_id' => $sale3->id, 'product_id' => $products['SKU-JW-BLK-750']->product_id],
            [
                'batch_id' => $stockBatches['MCT_SKU-JW-BLK-750']->batch_id,
                'uom_id' => $products['SKU-JW-BLK-750']->base_uom_id,
                'quantity' => 3.000,
                'unit_price' => 18.500,
                'vat_rate' => 5.000,
                'vat_amount' => 2.643,
                'line_total' => 55.500,
            ]
        );

        PermitVerification::updateOrCreate(
            ['retail_sale_id' => $sale3->id],
            [
                'branch_id' => $mctBranch->branch_id,
                'counter_id' => $mctCounter1->counter_id,
                'cashier_shift_id' => $reconciledShiftMct->shift_id,
                'entered_by' => $cashierMct->user_id,
                'civil_id' => '12345678',
                'holder_name' => 'Alexander Wright',
                'permit_no' => 'PERM-2026-0082',
                'annual_limit' => 500.000,
                'used_amount' => 57.000,
                'remaining_amount' => 443.000,
                'valid_from' => '2026-01-01',
                'valid_to' => '2026-12-31',
                'verified_at' => Carbon::now()->subDays(7)->setTime(14, 18, 0),
                'entry_method' => 'MANUAL',
                'controlled_total' => 55.500,
                'status' => 'USED',
                'attested_by' => $cashierMct->user_id,
                'attested_at' => Carbon::now()->subDays(7)->setTime(14, 19, 0),
                'created_at' => Carbon::now()->subDays(7)->setTime(14, 18, 0),
            ]
        );

        // Sale 4: VOIDED sale
        $sale4 = RetailSale::updateOrCreate(
            ['receipt_no' => 'RS-BR-MCT-2026-0004'],
            [
                'branch_id' => $mctBranch->branch_id,
                'counter_id' => $mctCounter1->counter_id,
                'cashier_shift_id' => $reconciledShiftMct->shift_id,
                'user_id' => $cashierMct->user_id,
                'customer_civil_id' => null,
                'subtotal' => 17.619,
                'vat_total' => 0.881,
                'grand_total' => 18.500,
                'payment_method' => 'CASH',
                'cash_amount' => 18.500,
                'card_amount' => 0.000,
                'card_reference' => null,
                'change_given' => 0.000,
                'status' => 'VOIDED',
                'created_at' => Carbon::now()->subDays(7)->setTime(15, 5, 0),
            ]
        );
        RetailSaleItem::firstOrCreate(
            ['retail_sale_id' => $sale4->id, 'product_id' => $products['SKU-JW-BLK-750']->product_id],
            [
                'batch_id' => $stockBatches['MCT_SKU-JW-BLK-750']->batch_id,
                'uom_id' => $products['SKU-JW-BLK-750']->base_uom_id,
                'quantity' => 1.000,
                'unit_price' => 18.500,
                'vat_rate' => 5.000,
                'vat_amount' => 0.881,
                'line_total' => 18.500,
            ]
        );

        // Sale 5: Salalah Branch retail sale with Screenshot Extraction Verification (Fatima Al-Harthy)
        $sale5 = RetailSale::updateOrCreate(
            ['receipt_no' => 'RS-BR-SLL-2026-0001'],
            [
                'branch_id' => $sllBranch->branch_id,
                'counter_id' => $sllCounter1->counter_id,
                'cashier_shift_id' => $currentOpenShiftSll->shift_id,
                'user_id' => $cashierSll->user_id,
                'customer_civil_id' => '87654321',
                'subtotal' => 37.143,
                'vat_total' => 1.857,
                'grand_total' => 39.000,
                'payment_method' => 'CARD',
                'cash_amount' => 0.000,
                'card_amount' => 39.000,
                'card_reference' => 'SLL-POS-3310',
                'change_given' => 0.000,
                'status' => 'COMPLETED',
                'created_at' => Carbon::now()->subDays(1)->setTime(12, 10, 0),
            ]
        );
        RetailSaleItem::firstOrCreate(
            ['retail_sale_id' => $sale5->id, 'product_id' => $products['SKU-JW-BLK-750']->product_id],
            [
                'batch_id' => $stockBatches['SLL_SKU-JW-BLK-750']->batch_id,
                'uom_id' => $products['SKU-JW-BLK-750']->base_uom_id,
                'quantity' => 2.000,
                'unit_price' => 19.500,
                'vat_rate' => 5.000,
                'vat_amount' => 1.857,
                'line_total' => 39.000,
            ]
        );

        PermitVerification::updateOrCreate(
            ['retail_sale_id' => $sale5->id],
            [
                'branch_id' => $sllBranch->branch_id,
                'counter_id' => $sllCounter1->counter_id,
                'cashier_shift_id' => $currentOpenShiftSll->shift_id,
                'entered_by' => $cashierSll->user_id,
                'civil_id' => '87654321',
                'holder_name' => 'Fatima Al-Harthy',
                'permit_no' => 'PERM-2026-0145',
                'annual_limit' => 600.000,
                'used_amount' => 150.000,
                'remaining_amount' => 450.000,
                'valid_from' => '2026-01-01',
                'valid_to' => '2026-12-31',
                'verified_at' => Carbon::now()->subDays(1)->setTime(12, 5, 0),
                'entry_method' => 'SCREENSHOT',
                'screenshot_path' => 'permit-screenshots/BR-MCT/2026/01/demo-screenshot.png',
                'extraction_confidence' => 0.9500,
                'extraction_raw' => ['source' => 'tesseract', 'langs' => 'eng+ara'],
                'controlled_total' => 39.000,
                'status' => 'USED',
                'attested_by' => $cashierSll->user_id,
                'attested_at' => Carbon::now()->subDays(1)->setTime(12, 8, 0),
                'created_at' => Carbon::now()->subDays(1)->setTime(12, 5, 0),
            ]
        );

        // 12. PENDING Permit Verifications (Available for immediate POS checkout test)
        // Pending 1: Sheema Al-Riyami (Screenshot OCR extraction)
        PermitVerification::updateOrCreate(
            ['permit_no' => 'PERM-2026-0210'],
            [
                'retail_sale_id' => null,
                'branch_id' => $mctBranch->branch_id,
                'counter_id' => $mctCounter1->counter_id,
                'cashier_shift_id' => $currentOpenShiftMct->shift_id,
                'entered_by' => $cashierMct->user_id,
                'civil_id' => '23456789',
                'holder_name' => 'Sheema Al-Riyami',
                'annual_limit' => 450.000,
                'used_amount' => 0.000,
                'remaining_amount' => 450.000,
                'valid_from' => '2026-01-01',
                'valid_to' => '2026-12-31',
                'verified_at' => Carbon::now()->subHours(2),
                'entry_method' => 'SCREENSHOT',
                'screenshot_path' => 'permit-screenshots/BR-MCT/2026/01/demo-screenshot.png',
                'extraction_confidence' => 0.9400,
                'extraction_raw' => ['source' => 'tesseract_ocr', 'permit_status' => 'ACTIVE'],
                'fields_edited_after_extraction' => null,
                'controlled_total' => 0.000,
                'status' => 'PENDING',
                'attested_by' => $cashierMct->user_id,
                'attested_at' => Carbon::now()->subHours(2),
                'created_at' => Carbon::now()->subHours(2),
            ]
        );

        // Pending 2: David Miller (Manual entry with generous remaining balance)
        PermitVerification::updateOrCreate(
            ['permit_no' => 'PERM-2026-0330'],
            [
                'retail_sale_id' => null,
                'branch_id' => $mctBranch->branch_id,
                'counter_id' => $mctCounter1->counter_id,
                'cashier_shift_id' => $currentOpenShiftMct->shift_id,
                'entered_by' => $cashierMct->user_id,
                'civil_id' => '98765432',
                'holder_name' => 'David Miller',
                'annual_limit' => 700.000,
                'used_amount' => 100.000,
                'remaining_amount' => 600.000,
                'valid_from' => '2026-01-01',
                'valid_to' => '2026-12-31',
                'verified_at' => Carbon::now()->subDay(),
                'entry_method' => 'MANUAL',
                'controlled_total' => 0.000,
                'status' => 'PENDING',
                'attested_by' => $cashierMct->user_id,
                'attested_at' => Carbon::now()->subDay(),
                'created_at' => Carbon::now()->subDay(),
            ]
        );

        // Pending 3: Salim Al-Amri (Low remaining balance - 15 OMR left)
        PermitVerification::updateOrCreate(
            ['permit_no' => 'PERM-2026-0012'],
            [
                'retail_sale_id' => null,
                'branch_id' => $mctBranch->branch_id,
                'counter_id' => $mctCounter1->counter_id,
                'cashier_shift_id' => $currentOpenShiftMct->shift_id,
                'entered_by' => $cashierMct->user_id,
                'civil_id' => '55443322',
                'holder_name' => 'Salim Al-Amri',
                'annual_limit' => 300.000,
                'used_amount' => 285.000,
                'remaining_amount' => 15.000,
                'valid_from' => '2026-01-01',
                'valid_to' => '2026-06-30',
                'verified_at' => Carbon::now()->subDays(3),
                'entry_method' => 'MANUAL',
                'controlled_total' => 0.000,
                'status' => 'PENDING',
                'attested_by' => $cashierMct->user_id,
                'attested_at' => Carbon::now()->subDays(3),
                'created_at' => Carbon::now()->subDays(3),
            ]
        );
    }
}
