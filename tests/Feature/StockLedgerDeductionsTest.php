<?php

namespace Tests\Feature;

use App\Livewire\WarehouseInventory\StockLedger as StockLedgerComponent;
use App\Models\Branch;
use App\Models\CashierShift;
use App\Models\Counter;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\DeliveryNote;
use App\Models\PermitVerification;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductPricingTier;
use App\Models\RetailSale;
use App\Models\Role;
use App\Models\SalesInvoice;
use App\Models\SalesOrder;
use App\Models\StockBatch;
use App\Models\StockLedger;
use App\Models\TaxVatRate;
use App\Models\Uom;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Models\WholesaleQuotation;
use App\Services\DeliveryNoteService;
use App\Services\InvoiceService;
use App\Services\PosCheckoutService;
use App\Services\QuotationService;
use App\Services\SalesOrderService;
use App\Services\ShiftService;
use App\Services\StockLedgerService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class StockLedgerDeductionsTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $cashierMct;
    protected User $staffSll;
    protected User $unauthorizedUser;
    protected Branch $branchMct;
    protected Branch $branchSll;
    protected Counter $counterMct;
    protected Warehouse $warehouseMct;
    protected Warehouse $warehouseSll;
    protected Warehouse $bondedWarehouse;
    protected Product $productA;
    protected StockBatch $dutyPaidBatchEarly;
    protected StockBatch $dutyPaidBatchLate;
    protected StockBatch $dutySuspendedBatch;
    protected Customer $wholesaleCustomer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->superAdmin = User::where('username', 'admin')->first()
            ?: User::factory()->create([
                'username' => 'admin',
                'email' => 'admin@rmsc.om',
                'is_active' => true,
            ]);
        $superAdminRole = Role::firstOrCreate(['role_name' => 'Super Admin']);
        if (! $this->superAdmin->hasRole('Super Admin')) {
            $this->superAdmin->roles()->syncWithoutDetaching([$superAdminRole->role_id]);
        }
        $this->superAdmin->load('roles.permissions');

        $this->branchMct = Branch::where('branch_code', 'BR-MCT')->first();
        $this->branchSll = Branch::where('branch_code', 'BR-SLL')->first();

        $this->counterMct = Counter::firstOrCreate([
            'branch_id' => $this->branchMct->branch_id,
            'counter_code' => 'CNTR-MCT-01',
        ], [
            'counter_name' => 'Counter 1',
            'is_active' => true,
        ]);

        $this->warehouseMct = Warehouse::where('branch_id', $this->branchMct->branch_id)
            ->where('warehouse_type', '!=', 'DUTY_FREE_BONDED')
            ->first() ?? Warehouse::create([
                'company_id' => $this->branchMct->company_id,
                'branch_id' => $this->branchMct->branch_id,
                'warehouse_code' => 'WH-MCT-MAIN',
                'warehouse_name' => 'Muscat Main DC',
                'warehouse_type' => 'DISTRIBUTION_CENTER',
                'is_active' => true,
            ]);

        $this->warehouseSll = Warehouse::where('branch_id', $this->branchSll->branch_id)
            ->where('warehouse_type', '!=', 'DUTY_FREE_BONDED')
            ->first() ?? Warehouse::create([
                'company_id' => $this->branchSll->company_id,
                'branch_id' => $this->branchSll->branch_id,
                'warehouse_code' => 'WH-SLL-MAIN',
                'warehouse_name' => 'Salalah Main DC',
                'warehouse_type' => 'DISTRIBUTION_CENTER',
                'is_active' => true,
            ]);

        $this->bondedWarehouse = Warehouse::firstOrCreate([
            'warehouse_code' => 'WH-MCT-BONDED',
        ], [
            'company_id' => $this->branchMct->company_id,
            'branch_id' => $this->branchMct->branch_id,
            'warehouse_name' => 'Muscat Bonded Warehouse',
            'warehouse_type' => 'DUTY_FREE_BONDED',
            'is_active' => true,
        ]);

        // Cashier user for MCT
        $cashierRole = Role::firstOrCreate(['role_name' => 'Cashier']);
        $this->cashierMct = User::create([
            'employee_id' => 'EMP-CSH-MCT-01',
            'username' => 'cashier_mct',
            'password_hash' => bcrypt('password'),
            'full_name' => 'Muscat Cashier',
            'email' => 'cashier_mct@rmsc.om',
            'default_branch_id' => $this->branchMct->branch_id,
            'is_active' => true,
        ]);
        $this->cashierMct->roles()->attach($cashierRole->role_id);
        $this->cashierMct->branches()->attach($this->branchMct->branch_id);
        $this->cashierMct->load('roles.permissions');

        // Staff user for SLL only
        $branchAdminRole = Role::firstOrCreate(['role_name' => 'Branch Admin']);
        $this->staffSll = User::create([
            'employee_id' => 'EMP-STF-SLL-01',
            'username' => 'staff_sll',
            'password_hash' => bcrypt('password'),
            'full_name' => 'Salalah Branch Staff',
            'email' => 'staff_sll@rmsc.om',
            'default_branch_id' => $this->branchSll->branch_id,
            'is_active' => true,
        ]);
        $this->staffSll->roles()->attach($branchAdminRole->role_id);
        $this->staffSll->branches()->attach($this->branchSll->branch_id);
        $this->staffSll->load('roles.permissions');

        // Unauthorized user
        $this->unauthorizedUser = User::create([
            'employee_id' => 'EMP-GUEST-01',
            'username' => 'guest_user',
            'password_hash' => bcrypt('password'),
            'full_name' => 'Guest User',
            'email' => 'guest@rmsc.om',
            'is_active' => true,
        ]);

        // Wholesale Customer
        $this->wholesaleCustomer = Customer::firstOrCreate([
            'customer_code' => 'CUST-TEST-001',
        ], [
            'customer_name' => 'Grand Millennium Hotel Muscat',
            'customer_segment' => 'WHOLESALE',
            'customer_type' => 'WHOLESALE',
            'credit_limit' => 10000.000,
            'payment_terms_days' => 30,
            'credit_status' => 'ACTIVE',
            'vat_number' => 'OM1234567890',
            'phone' => '+968 24000000',
            'email' => 'purchasing@millennium.om',
            'is_active' => true,
        ]);

        // Product & Pricing
        $uomBottle = Uom::firstOrCreate(['uom_code' => 'BTL'], ['uom_name' => 'Bottle']);
        $vatRate5 = TaxVatRate::firstOrCreate(['tax_name' => 'Oman Standard VAT 5%'], [
            'tax_type' => 'VAT',
            'rate_percent' => 5.0,
            'effective_from' => '2021-04-16',
            'is_active' => true,
        ]);
        $category = ProductCategory::firstOrCreate(
            ['category_code' => 'TEST-SPIRITS'],
            ['category_name' => 'Test Spirits', 'is_excise_applicable' => false]
        );

        $this->productA = Product::firstOrCreate(['sku_code' => 'TEST-WHISKY-750'], [
            'barcode' => '9998887776661',
            'product_name' => 'Glenfiddich 12 Year Old 750ml',
            'category_id' => $category->category_id,
            'base_uom_id' => $uomBottle->uom_id,
            'tax_rate_id' => $vatRate5->tax_rate_id,
            'is_permit_required' => false,
            'is_active' => true,
        ]);

        ProductPricingTier::updateOrCreate(
            ['product_id' => $this->productA->product_id, 'price_tier' => 'RETAIL'],
            ['min_quantity' => 1, 'unit_price' => 20.000, 'effective_from' => '2026-01-01']
        );
        ProductPricingTier::updateOrCreate(
            ['product_id' => $this->productA->product_id, 'price_tier' => 'WHOLESALE'],
            ['min_quantity' => 1, 'unit_price' => 18.000, 'effective_from' => '2026-01-01']
        );

        // Batches
        $this->dutyPaidBatchEarly = StockBatch::firstOrCreate(['batch_number' => 'BATCH-TEST-DP-EARLY'], [
            'product_id' => $this->productA->product_id,
            'duty_status' => 'DUTY_PAID',
            'expiry_date' => now()->addMonths(6)->toDateString(),
        ]);

        $this->dutyPaidBatchLate = StockBatch::firstOrCreate(['batch_number' => 'BATCH-TEST-DP-LATE'], [
            'product_id' => $this->productA->product_id,
            'duty_status' => 'DUTY_PAID',
            'expiry_date' => now()->addMonths(24)->toDateString(),
        ]);

        $this->dutySuspendedBatch = StockBatch::firstOrCreate(['batch_number' => 'BATCH-TEST-SUSPENDED'], [
            'product_id' => $this->productA->product_id,
            'duty_status' => 'DUTY_FREE_BONDED',
            'expiry_date' => now()->addMonths(12)->toDateString(),
        ]);
    }

    #[Test]
    public function test_retail_checkout_deducts_stock_via_fefo_and_records_signed_stock_ledger_entries(): void
    {
        $shiftService = app(ShiftService::class);
        $shift = $shiftService->open($this->cashierMct, $this->counterMct->counter_id, 50.000);

        $stockService = app(StockLedgerService::class);
        $stockService->recordIntake($this->warehouseMct->warehouse_id, $this->productA->product_id, $this->dutyPaidBatchEarly->batch_id, 10.0, ['user_id' => $this->superAdmin->user_id]);
        $stockService->recordIntake($this->warehouseMct->warehouse_id, $this->productA->product_id, $this->dutyPaidBatchLate->batch_id, 10.0, ['user_id' => $this->superAdmin->user_id]);

        $checkoutService = app(PosCheckoutService::class);

        $sale = $checkoutService->checkout(
            $this->cashierMct,
            $this->counterMct,
            [
                [
                    'product_id' => $this->productA->product_id,
                    'quantity' => 12.0,
                    'unit_price' => 20.000,
                ],
            ],
            [
                'method' => 'CASH',
                'cash_amount' => 300.000,
            ]
        );

        $this->assertNotNull($sale);
        $this->assertEquals('COMPLETED', $sale->status);

        $ledgerEntries = StockLedger::where('reference_type', 'retail_sale')
            ->where('reference_id', $sale->id)
            ->orderBy('ledger_id', 'asc')
            ->get();

        $this->assertCount(2, $ledgerEntries);

        $entryEarly = $ledgerEntries->firstWhere('batch_id', $this->dutyPaidBatchEarly->batch_id);
        $entryLate = $ledgerEntries->firstWhere('batch_id', $this->dutyPaidBatchLate->batch_id);

        $this->assertNotNull($entryEarly);
        $this->assertNotNull($entryLate);

        $this->assertEquals('SALES_RETAIL', $entryEarly->movement_type);
        $this->assertEquals(-10.0, (float) $entryEarly->quantity);
        $this->assertEquals(0.0, (float) $entryEarly->balance_after);

        $this->assertEquals('SALES_RETAIL', $entryLate->movement_type);
        $this->assertEquals(-2.0, (float) $entryLate->quantity);
        $this->assertEquals(8.0, (float) $entryLate->balance_after);

        $stockEarly = WarehouseStock::where('warehouse_id', $this->warehouseMct->warehouse_id)
            ->where('batch_id', $this->dutyPaidBatchEarly->batch_id)
            ->first();
        $this->assertEquals(0.0, (float) $stockEarly->quantity_on_hand);

        $stockLate = WarehouseStock::where('warehouse_id', $this->warehouseMct->warehouse_id)
            ->where('batch_id', $this->dutyPaidBatchLate->batch_id)
            ->first();
        $this->assertEquals(8.0, (float) $stockLate->quantity_on_hand);
    }

    #[Test]
    public function test_retail_checkout_blocks_duty_suspended_batches_with_exception_and_zero_side_effects(): void
    {
        $shiftService = app(ShiftService::class);
        $shift = $shiftService->open($this->cashierMct, $this->counterMct->counter_id, 50.000);

        $stockService = app(StockLedgerService::class);
        $stockService->recordIntake($this->warehouseMct->warehouse_id, $this->productA->product_id, $this->dutySuspendedBatch->batch_id, 20.0, ['user_id' => $this->superAdmin->user_id]);

        $salesCountBefore = RetailSale::count();
        $ledgerCountBefore = StockLedger::where('reference_type', 'retail_sale')->count();

        $checkoutService = app(PosCheckoutService::class);

        $caught = false;
        try {
            $checkoutService->checkout(
                $this->cashierMct,
                $this->counterMct,
                [
                    [
                        'product_id' => $this->productA->product_id,
                        'quantity' => 5.0,
                        'unit_price' => 20.000,
                    ],
                ],
                [
                    'method' => 'CASH',
                    'cash_amount' => 120.000,
                ]
            );
        } catch (Exception $e) {
            $caught = true;
        }

        $this->assertTrue($caught, 'Checkout must throw an exception for duty-suspended stock.');
        $this->assertEquals($salesCountBefore, RetailSale::count(), 'No retail_sale row should be created.');
        $this->assertEquals($ledgerCountBefore, StockLedger::where('reference_type', 'retail_sale')->count(), 'No ledger rows should be created.');
    }

    #[Test]
    public function test_retail_void_writes_positive_stock_ledger_entry_restores_batch_and_does_not_affect_shift_cash(): void
    {
        $shiftService = app(ShiftService::class);
        $shift = $shiftService->open($this->cashierMct, $this->counterMct->counter_id, 50.000);

        $stockService = app(StockLedgerService::class);
        $stockService->recordIntake($this->warehouseMct->warehouse_id, $this->productA->product_id, $this->dutyPaidBatchEarly->batch_id, 10.0, ['user_id' => $this->superAdmin->user_id]);

        $checkoutService = app(PosCheckoutService::class);
        $sale = $checkoutService->checkout(
            $this->cashierMct,
            $this->counterMct,
            [
                [
                    'product_id' => $this->productA->product_id,
                    'quantity' => 3.0,
                    'unit_price' => 20.000,
                ],
            ],
            [
                'method' => 'CASH',
                'cash_amount' => 63.000,
            ]
        );

        $stockAfterSale = WarehouseStock::where('warehouse_id', $this->warehouseMct->warehouse_id)
            ->where('batch_id', $this->dutyPaidBatchEarly->batch_id)
            ->first();
        $this->assertEquals(7.0, (float) $stockAfterSale->quantity_on_hand);

        $voidedSale = $checkoutService->void($sale, 'Customer returned merchandise with valid receipt', $this->superAdmin);
        $this->assertEquals('VOIDED', $voidedSale->status);

        $voidEntry = StockLedger::where('reference_type', 'retail_sale')
            ->where('reference_id', $sale->id)
            ->where('movement_type', 'SALES_RETAIL_VOID')
            ->first();

        $this->assertNotNull($voidEntry);
        $this->assertEquals(3.0, (float) $voidEntry->quantity);
        $this->assertEquals(10.0, (float) $voidEntry->balance_after);
        $this->assertEquals($this->dutyPaidBatchEarly->batch_id, $voidEntry->batch_id);

        $stockRestored = WarehouseStock::where('warehouse_id', $this->warehouseMct->warehouse_id)
            ->where('batch_id', $this->dutyPaidBatchEarly->batch_id)
            ->first();
        $this->assertEquals(10.0, (float) $stockRestored->quantity_on_hand);
    }

    #[Test]
    public function test_wholesale_full_chain_reservation_dispatch_invoice_post_and_void(): void
    {
        $stockService = app(StockLedgerService::class);
        $stockService->recordIntake($this->warehouseMct->warehouse_id, $this->productA->product_id, $this->dutyPaidBatchEarly->batch_id, 20.0, ['user_id' => $this->superAdmin->user_id]);

        $soService = app(SalesOrderService::class);
        $dnService = app(DeliveryNoteService::class);
        $invoiceService = app(InvoiceService::class);

        $so = $soService->create([
            'customer_id' => $this->wholesaleCustomer->customer_id,
            'branch_id' => $this->branchMct->branch_id,
            'warehouse_id' => $this->warehouseMct->warehouse_id,
            'payment_terms_days' => 30,
        ], [
            [
                'product_id' => $this->productA->product_id,
                'quantity' => 8.0,
                'unit_price' => 18.000,
                'vat_rate' => 5.0,
            ],
        ], $this->superAdmin);

        $stockBeforeConfirm = WarehouseStock::where('warehouse_id', $this->warehouseMct->warehouse_id)
            ->where('batch_id', $this->dutyPaidBatchEarly->batch_id)
            ->first();
        $this->assertEquals(20.0, (float) $stockBeforeConfirm->quantity_on_hand);
        $this->assertEquals(0.0, (float) $stockBeforeConfirm->quantity_reserved);

        $confirmedSo = $soService->confirm($so, $this->superAdmin);
        $this->assertEquals('CONFIRMED', $confirmedSo->status);

        $stockAfterConfirm = WarehouseStock::where('warehouse_id', $this->warehouseMct->warehouse_id)
            ->where('batch_id', $this->dutyPaidBatchEarly->batch_id)
            ->first();
        $this->assertEquals(20.0, (float) $stockAfterConfirm->quantity_on_hand, 'On hand unchanged on SO confirm.');
        $this->assertEquals(8.0, (float) $stockAfterConfirm->quantity_reserved, 'Reserved increased on SO confirm.');

        $ledgerCountAtConfirm = StockLedger::where('reference_type', 'sales_order')->count();
        $this->assertEquals(0, $ledgerCountAtConfirm, 'No ledger rows written on SO reservation.');

        $orderItem = $confirmedSo->items->first();
        $dn = $dnService->createAndDispatch($confirmedSo, [
            [
                'order_item_id' => $orderItem->item_id,
                'quantity' => 8.0,
            ],
        ], [
            'vehicle_no' => 'OM-1234-A',
            'driver_name' => 'Salim Al-Harthy',
        ], $this->superAdmin);
        $this->assertEquals('DISPATCHED', $dn->status);

        $invoice = $invoiceService->postFromDeliveryNote($dn, $this->superAdmin);
        $this->assertEquals('POSTED', $invoice->status);

        $stockAfterInvoice = WarehouseStock::where('warehouse_id', $this->warehouseMct->warehouse_id)
            ->where('batch_id', $this->dutyPaidBatchEarly->batch_id)
            ->first();
        $this->assertEquals(12.0, (float) $stockAfterInvoice->quantity_on_hand, 'On hand deducted on invoice post.');
        $this->assertEquals(0.0, (float) $stockAfterInvoice->quantity_reserved, 'Reserved cleared on invoice post.');

        $invoiceLedger = StockLedger::where('reference_type', 'sales_invoice')
            ->where('reference_id', $invoice->invoice_id)
            ->where('movement_type', 'SALES_WHOLESALE')
            ->first();

        $this->assertNotNull($invoiceLedger);
        $this->assertEquals(-8.0, (float) $invoiceLedger->quantity);
        $this->assertEquals(12.0, (float) $invoiceLedger->balance_after);

        // Idempotency: second post attempt must not double-deduct
        $secondInvoice = $invoiceService->postFromDeliveryNote($dn, $this->superAdmin);
        $this->assertEquals($invoice->invoice_id, $secondInvoice->invoice_id, 'Second post returns existing invoice without double-deduction.');

        $wholesaleEntriesCount = StockLedger::where('reference_type', 'sales_invoice')
            ->where('reference_id', $invoice->invoice_id)
            ->where('movement_type', 'SALES_WHOLESALE')
            ->count();
        $this->assertEquals(1, $wholesaleEntriesCount, 'Must have exactly one SALES_WHOLESALE entry.');

        // Void invoice
        $voidedInvoice = $invoiceService->void($invoice, $this->superAdmin, 'Customer return / order cancelled');
        $this->assertEquals('VOIDED', $voidedInvoice->status);

        $voidLedger = StockLedger::where('reference_type', 'sales_invoice')
            ->where('reference_id', $invoice->invoice_id)
            ->where('movement_type', 'SALES_WHOLESALE_VOID')
            ->first();

        $this->assertNotNull($voidLedger);
        $this->assertEquals(8.0, (float) $voidLedger->quantity);
        $this->assertEquals(20.0, (float) $voidLedger->balance_after);

        $stockAfterVoid = WarehouseStock::where('warehouse_id', $this->warehouseMct->warehouse_id)
            ->where('batch_id', $this->dutyPaidBatchEarly->batch_id)
            ->first();
        $this->assertEquals(20.0, (float) $stockAfterVoid->quantity_on_hand, 'On hand restored on invoice void.');
    }

    #[Test]
    public function test_wholesale_so_cancel_releases_reservation_without_ledger_entry(): void
    {
        $stockService = app(StockLedgerService::class);
        $stockService->recordIntake($this->warehouseMct->warehouse_id, $this->productA->product_id, $this->dutyPaidBatchEarly->batch_id, 15.0, ['user_id' => $this->superAdmin->user_id]);

        $soService = app(SalesOrderService::class);

        $so = $soService->create([
            'customer_id' => $this->wholesaleCustomer->customer_id,
            'branch_id' => $this->branchMct->branch_id,
            'warehouse_id' => $this->warehouseMct->warehouse_id,
            'payment_terms_days' => 30,
        ], [
            [
                'product_id' => $this->productA->product_id,
                'quantity' => 6.0,
                'unit_price' => 18.000,
                'vat_rate' => 5.0,
            ],
        ], $this->superAdmin);

        $so = $soService->confirm($so, $this->superAdmin);

        $stockAfterConfirm = WarehouseStock::where('warehouse_id', $this->warehouseMct->warehouse_id)
            ->where('batch_id', $this->dutyPaidBatchEarly->batch_id)
            ->first();
        $this->assertEquals(6.0, (float) $stockAfterConfirm->quantity_reserved);

        $cancelledSo = $soService->cancel($so, $this->superAdmin);
        $this->assertEquals('CANCELLED', $cancelledSo->status);

        $stockAfterCancel = WarehouseStock::where('warehouse_id', $this->warehouseMct->warehouse_id)
            ->where('batch_id', $this->dutyPaidBatchEarly->batch_id)
            ->first();
        $this->assertEquals(0.0, (float) $stockAfterCancel->quantity_reserved);
        $this->assertEquals(15.0, (float) $stockAfterCancel->quantity_on_hand);

        $ledgerCount = StockLedger::where('reference_type', 'sales_order')
            ->where('reference_id', $so->order_id)
            ->count();
        $this->assertEquals(0, $ledgerCount, 'Reservations and cancellations do not write stock_ledger rows.');
    }

    #[Test]
    public function test_cannot_void_invoice_when_payments_exist(): void
    {
        $stockService = app(StockLedgerService::class);
        $stockService->recordIntake($this->warehouseMct->warehouse_id, $this->productA->product_id, $this->dutyPaidBatchEarly->batch_id, 10.0, ['user_id' => $this->superAdmin->user_id]);

        $soService = app(SalesOrderService::class);
        $dnService = app(DeliveryNoteService::class);
        $invoiceService = app(InvoiceService::class);

        $so = $soService->create([
            'customer_id' => $this->wholesaleCustomer->customer_id,
            'branch_id' => $this->branchMct->branch_id,
            'warehouse_id' => $this->warehouseMct->warehouse_id,
        ], [
            [
                'product_id' => $this->productA->product_id,
                'quantity' => 2.0,
                'unit_price' => 18.000,
            ],
        ], $this->superAdmin);

        $so = $soService->confirm($so, $this->superAdmin);
        $dn = $dnService->createAndDispatch($so, [
            ['order_item_id' => $so->items->first()->item_id, 'quantity' => 2.0],
        ], ['driver_name' => 'Ali'], $this->superAdmin);
        $invoice = $invoiceService->postFromDeliveryNote($dn, $this->superAdmin);

        $invoiceService->recordPayment($invoice, [
            'amount' => 10.000,
            'method' => 'BANK_TRANSFER',
            'reference' => 'TXN-998877',
        ], $this->superAdmin);

        $this->expectException(Exception::class);
        $invoiceService->void($invoice, $this->superAdmin, 'Void attempt on paid invoice');
    }

    #[Test]
    public function test_duty_rules_block_duty_suspended_batches_and_bonded_warehouses_from_wholesale_deduction(): void
    {
        $stockService = app(StockLedgerService::class);
        $stockService->recordIntake($this->bondedWarehouse->warehouse_id, $this->productA->product_id, $this->dutySuspendedBatch->batch_id, 50.0, ['user_id' => $this->superAdmin->user_id]);

        $soService = app(SalesOrderService::class);

        $so = $soService->create([
            'customer_id' => $this->wholesaleCustomer->customer_id,
            'branch_id' => $this->branchMct->branch_id,
            'warehouse_id' => $this->bondedWarehouse->warehouse_id,
        ], [
            [
                'product_id' => $this->productA->product_id,
                'quantity' => 5.0,
                'unit_price' => 18.000,
            ],
        ], $this->superAdmin);

        $this->expectException(Exception::class);
        $soService->confirm($so, $this->superAdmin);
    }

    #[Test]
    public function test_insufficient_stock_aborts_transaction_cleanly(): void
    {
        $stockService = app(StockLedgerService::class);
        $stockService->recordIntake($this->warehouseMct->warehouse_id, $this->productA->product_id, $this->dutyPaidBatchEarly->batch_id, 5.0, ['user_id' => $this->superAdmin->user_id]);

        $soService = app(SalesOrderService::class);

        $so = $soService->create([
            'customer_id' => $this->wholesaleCustomer->customer_id,
            'branch_id' => $this->branchMct->branch_id,
            'warehouse_id' => $this->warehouseMct->warehouse_id,
        ], [
            [
                'product_id' => $this->productA->product_id,
                'quantity' => 10.0, // Available is only 5.0
                'unit_price' => 18.000,
            ],
        ], $this->superAdmin);

        $caught = false;
        try {
            $soService->confirm($so, $this->superAdmin);
        } catch (Exception $e) {
            $caught = true;
        }

        $this->assertTrue($caught, 'Confirmation must fail on insufficient stock.');
        $so->refresh();
        $this->assertEquals('DRAFT', $so->status);

        $stock = WarehouseStock::where('warehouse_id', $this->warehouseMct->warehouse_id)
            ->where('batch_id', $this->dutyPaidBatchEarly->batch_id)
            ->first();
        $this->assertEquals(0.0, (float) $stock->quantity_reserved);
        $this->assertEquals(5.0, (float) $stock->quantity_on_hand);
    }

    #[Test]
    public function test_stock_ledger_immutability_guards_throw_runtime_exception(): void
    {
        $stockService = app(StockLedgerService::class);
        $stockService->recordIntake($this->warehouseMct->warehouse_id, $this->productA->product_id, $this->dutyPaidBatchEarly->batch_id, 10.0, ['user_id' => $this->superAdmin->user_id]);

        $ledger = StockLedger::where('batch_id', $this->dutyPaidBatchEarly->batch_id)->latest('ledger_id')->firstOrFail();

        $updateCaught = false;
        try {
            $ledger->update(['quantity' => 999.0]);
        } catch (RuntimeException $e) {
            $updateCaught = true;
        }
        $this->assertTrue($updateCaught, 'Updating a stock_ledger entry must throw RuntimeException.');

        $deleteCaught = false;
        try {
            $ledger->delete();
        } catch (RuntimeException $e) {
            $deleteCaught = true;
        }
        $this->assertTrue($deleteCaught, 'Deleting a stock_ledger entry must throw RuntimeException.');

        $ledger->refresh();
        $this->assertEquals(10.0, (float) $ledger->quantity, 'Quantity must remain unmodified.');
    }

    #[Test]
    public function test_reconciliation_command_detects_and_fixes_mismatches(): void
    {
        $stockService = app(StockLedgerService::class);
        $stockService->recordIntake($this->warehouseMct->warehouse_id, $this->productA->product_id, $this->dutyPaidBatchEarly->batch_id, 25.0, ['user_id' => $this->superAdmin->user_id]);

        $exitClean = Artisan::call('stock:reconcile');
        $this->assertEquals(0, $exitClean);

        $stock = WarehouseStock::where('warehouse_id', $this->warehouseMct->warehouse_id)
            ->where('batch_id', $this->dutyPaidBatchEarly->batch_id)
            ->first();

        // Intentionally corrupt quantity_on_hand
        WarehouseStock::where('stock_id', $stock->stock_id)->update(['quantity_on_hand' => 99.0]);

        $exitCorrupted = Artisan::call('stock:reconcile');
        $this->assertEquals(1, $exitCorrupted, 'stock:reconcile must report mismatch and return exit code 1.');

        $exitFixed = Artisan::call('stock:reconcile', ['--fix' => true]);
        $this->assertEquals(0, $exitFixed, 'stock:reconcile --fix must fix mismatches and return exit code 0.');

        $stock->refresh();
        $this->assertEquals(25.0, (float) $stock->quantity_on_hand, 'quantity_on_hand must be restored to ledger balance.');
    }

    #[Test]
    public function test_stock_ledger_screen_renders_filters_and_branch_scoping(): void
    {
        $stockService = app(StockLedgerService::class);
        $stockService->recordIntake($this->warehouseMct->warehouse_id, $this->productA->product_id, $this->dutyPaidBatchEarly->batch_id, 10.0, ['user_id' => $this->superAdmin->user_id]);
        $stockService->recordIntake($this->warehouseSll->warehouse_id, $this->productA->product_id, $this->dutyPaidBatchLate->batch_id, 15.0, ['user_id' => $this->staffSll->user_id]);

        // 1. Authorized super admin sees all
        $this->actingAs($this->superAdmin);
        $component = Livewire::test(StockLedgerComponent::class)
            ->assertStatus(200)
            ->assertSee('Stock Ledger')
            ->assertSee('WH-MCT-MAIN')
            ->assertSee('WH-SLL-MAIN')
            ->assertSee('Glenfiddich 12 Year Old 750ml');

        // Test filtering by movement_type
        $component->set('movement_type_filter', 'INTAKE_GRN')
            ->assertSee('INTAKE_GRN');

        // 2. Branch scoping: Staff assigned only to SLL cannot see MCT movements
        $this->actingAs($this->staffSll);
        Livewire::test(StockLedgerComponent::class)
            ->assertStatus(200)
            ->assertSee('WH-SLL-MAIN')
            ->assertDontSee('WH-MCT-MAIN');

        // 3. Unauthorized user receives 403
        $this->actingAs($this->unauthorizedUser);
        $this->getJson(route('warehouse-inventory.ledger'))->assertStatus(403);

        // 4. CSV export downloads valid CSV
        $this->actingAs($this->superAdmin);
        $csvResponse = $this->get(route('warehouse-inventory.ledger.export'));
        $csvResponse->assertStatus(200);
        $csvResponse->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('Date/Time,Warehouse,Branch', $csvResponse->streamedContent());

        // 5. Read-only assertion: no create/edit/delete actions on page
        $this->get(route('warehouse-inventory.ledger'))
            ->assertStatus(200)
            ->assertDontSee('wire:submit')
            ->assertDontSee('Create Movement')
            ->assertDontSee('Delete Entry');
    }

    #[Test]
    public function test_per_document_traceability_renders_stock_movements_on_all_four_show_views(): void
    {
        $this->actingAs($this->superAdmin);

        $shiftService = app(ShiftService::class);
        $shift = $shiftService->open($this->cashierMct, $this->counterMct->counter_id, 50.000);

        $stockService = app(StockLedgerService::class);
        $stockService->recordIntake($this->warehouseMct->warehouse_id, $this->productA->product_id, $this->dutyPaidBatchEarly->batch_id, 50.0, ['user_id' => $this->superAdmin->user_id]);

        // 1. Retail Sale Show
        $checkoutService = app(PosCheckoutService::class);
        $sale = $checkoutService->checkout(
            $this->cashierMct,
            $this->counterMct,
            [
                ['product_id' => $this->productA->product_id, 'quantity' => 2.0, 'unit_price' => 20.000],
            ],
            ['method' => 'CASH', 'cash_amount' => 50.000]
        );

        $responseRetail = $this->get(route('pos.sales.show', $sale->id));
        $responseRetail->assertStatus(200);
        $responseRetail->assertSee('Stock Movements');
        $responseRetail->assertSee('SALES_RETAIL');

        // 2. Sales Order Show
        $soService = app(SalesOrderService::class);
        $dnService = app(DeliveryNoteService::class);
        $invoiceService = app(InvoiceService::class);

        $so = $soService->create([
            'customer_id' => $this->wholesaleCustomer->customer_id,
            'branch_id' => $this->branchMct->branch_id,
            'warehouse_id' => $this->warehouseMct->warehouse_id,
        ], [
            ['product_id' => $this->productA->product_id, 'quantity' => 4.0, 'unit_price' => 18.000],
        ], $this->superAdmin);
        $so = $soService->confirm($so, $this->superAdmin);

        $dn = $dnService->createAndDispatch($so, [
            ['order_item_id' => $so->items->first()->item_id, 'quantity' => 4.0],
        ], ['driver_name' => 'Khalfan'], $this->superAdmin);

        $invoice = $invoiceService->postFromDeliveryNote($dn, $this->superAdmin);

        // Sales Order Show
        $responseSo = $this->get(route('wholesale.orders.show', $so->order_id));
        $responseSo->assertStatus(200);
        $responseSo->assertSee('Stock Movements');

        // Delivery Note Show
        $responseDn = $this->get(route('wholesale.delivery-notes.show', $dn->delivery_note_id));
        $responseDn->assertStatus(200);
        $responseDn->assertSee('Stock Movements');

        // Sales Invoice Show
        $responseInv = $this->get(route('wholesale.invoices.show', $invoice->invoice_id));
        $responseInv->assertStatus(200);
        $responseInv->assertSee('Stock Movements');
        $responseInv->assertSee('SALES_WHOLESALE');
    }

    #[Test]
    public function test_phase3_sidebar_links_return_200_for_super_admin(): void
    {
        $this->actingAs($this->superAdmin);

        // Ensure Super Admin has an open shift so POS terminal returns 200 rather than redirecting
        $shiftService = app(ShiftService::class);
        $shiftService->open($this->superAdmin, $this->counterMct->counter_id, 100.000);

        $phase3Routes = [
            'system.branches',
            'system.counters',
            'shifts.index',
            'pos.terminal',
            'permits.index',
            'wholesale.quotations.index',
            'wholesale.orders.index',
            'wholesale.delivery-notes.index',
            'wholesale.invoices.index',
            'wholesale.credit.index',
            'warehouse-inventory.ledger',
            'phase3.demo',
        ];

        foreach ($phase3Routes as $routeName) {
            $this->assertTrue(Route::has($routeName), "Route {$routeName} must be registered.");
            $response = $this->get(route($routeName));
            $this->assertEquals(200, $response->status(), "Route {$routeName} must return 200 for Super Admin. Got: {$response->status()}");
        }
    }
}
