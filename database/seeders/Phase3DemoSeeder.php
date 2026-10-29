<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\CashierShift;
use App\Models\Company;
use App\Models\Counter;
use App\Models\Customer;
use App\Models\CustomerCreditTerm;
use App\Models\OmanPermit;
use App\Models\OmanPermitTransaction;
use App\Models\PermitVerification;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductPricingTier;
use App\Models\RetailSale;
use App\Models\Role;
use App\Models\StockBatch;
use App\Models\TaxVatRate;
use App\Models\Uom;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class Phase3DemoSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first() ?? Company::create([
            'company_name' => 'Raysut Marketing & Supply Co. LLC (RMSC)',
            'trade_license_no' => 'CR-1098234',
            'liquor_license_no' => 'LL-2026-OM',
            'base_currency_code' => 'OMR',
            'address' => 'Way 3508, Muscat Central Industrial Area, Sultanate of Oman',
            'is_active' => true,
        ]);

        $cashierRole = Role::firstOrCreate(
            ['role_name' => 'Cashier'],
            ['description' => 'Executes retail POS counter transactions and shift openings']
        );

        $supervisorRole = Role::firstOrCreate(
            ['role_name' => 'Supervisor'],
            ['description' => 'Branch supervisor with shift variance approvals and credit override permissions']
        );

        $vat5 = TaxVatRate::firstOrCreate(
            ['tax_name' => 'Standard VAT 5%'],
            [
                'tax_type' => 'VAT',
                'rate_percent' => 5.000,
                'effective_from' => '2021-04-16',
                'is_active' => true,
            ]
        );

        $btlUom = Uom::firstOrCreate(
            ['uom_code' => 'BTL'],
            ['uom_name' => 'Bottle']
        );

        $canUom = Uom::firstOrCreate(
            ['uom_code' => 'CAN'],
            ['uom_name' => 'Can']
        );

        // 1. Ensure 4 Branches Exist
        $branchesData = [
            'BR-MCT' => ['name' => 'Muscat Headquarters & Distribution', 'type' => 'MIXED', 'city' => 'Muscat'],
            'BR-SLL' => ['name' => 'Salalah Regional Depot', 'type' => 'WHOLESALE', 'city' => 'Salalah'],
            'BR-SOH' => ['name' => 'Sohar Terminal Office', 'type' => 'WHOLESALE', 'city' => 'Sohar'],
            'BR-NZW' => ['name' => 'Nizwa Regional Distribution', 'type' => 'RETAIL', 'city' => 'Nizwa'],
        ];

        $branches = [];
        foreach ($branchesData as $code => $info) {
            $branches[$code] = Branch::updateOrCreate(
                ['branch_code' => $code],
                [
                    'company_id' => $company->company_id,
                    'branch_name' => $info['name'],
                    'branch_type' => $info['type'],
                    'city' => $info['city'],
                    'is_active' => true,
                ]
            );
        }

        // 2. Ensure Branch Warehouses Exist (Duty-Paid / Main per branch)
        $warehouses = [];
        $warehouses['BR-MCT'] = Warehouse::updateOrCreate(
            ['warehouse_code' => 'WH-MCT-MAIN'],
            [
                'company_id' => $company->company_id,
                'branch_id' => $branches['BR-MCT']->branch_id,
                'warehouse_name' => 'Muscat Main Distribution Center',
                'warehouse_type' => 'MAIN',
                'is_active' => true,
            ]
        );

        $whMctBonded = Warehouse::updateOrCreate(
            ['warehouse_code' => 'WH-MCT-BOND'],
            [
                'company_id' => $company->company_id,
                'branch_id' => $branches['BR-MCT']->branch_id,
                'warehouse_name' => 'Muscat Central Bonded Warehouse',
                'warehouse_type' => 'DUTY_FREE_BONDED',
                'customs_bond_ref' => 'BOND-MCT-001',
                'is_active' => true,
            ]
        );

        $warehouses['BR-SLL'] = Warehouse::updateOrCreate(
            ['warehouse_code' => 'WH-SLL-MAIN'],
            [
                'company_id' => $company->company_id,
                'branch_id' => $branches['BR-SLL']->branch_id,
                'warehouse_name' => 'Salalah Main Warehouse',
                'warehouse_type' => 'MAIN',
                'is_active' => true,
            ]
        );

        $warehouses['BR-SOH'] = Warehouse::updateOrCreate(
            ['warehouse_code' => 'WH-SOH-MAIN'],
            [
                'company_id' => $company->company_id,
                'branch_id' => $branches['BR-SOH']->branch_id,
                'warehouse_name' => 'Sohar Main Warehouse',
                'warehouse_type' => 'MAIN',
                'is_active' => true,
            ]
        );

        $warehouses['BR-NZW'] = Warehouse::updateOrCreate(
            ['warehouse_code' => 'WH-NZW-MAIN'],
            [
                'company_id' => $company->company_id,
                'branch_id' => $branches['BR-NZW']->branch_id,
                'warehouse_name' => 'Nizwa Main Depot',
                'warehouse_type' => 'MAIN',
                'is_active' => true,
            ]
        );

        // 3. Counters and Assigned Cashiers for all 4 branches
        $cashiersConfig = [
            'BR-MCT' => ['username' => 'cashier01', 'name' => 'Muscat Cashier 01', 'counter' => 'CNT-MCT-01', 't_id' => 'POS-T101'],
            'BR-SLL' => ['username' => 'cashier_sll', 'name' => 'Salalah Cashier 01', 'counter' => 'CNT-SLL-01', 't_id' => 'POS-T201'],
            'BR-SOH' => ['username' => 'cashier_soh', 'name' => 'Sohar Cashier 01', 'counter' => 'CNT-SOH-01', 't_id' => 'POS-T301'],
            'BR-NZW' => ['username' => 'cashier_nzw', 'name' => 'Nizwa Cashier 01', 'counter' => 'CNT-NZW-01', 't_id' => 'POS-T401'],
        ];

        foreach ($cashiersConfig as $bCode => $cfg) {
            $branch = $branches[$bCode];

            $cashierUser = User::updateOrCreate(
                ['username' => $cfg['username']],
                [
                    'full_name' => $cfg['name'],
                    'email' => "{$cfg['username']}@rmsc.om",
                    'password_hash' => Hash::make('password'),
                    'default_branch_id' => $branch->branch_id,
                    'is_active' => true,
                ]
            );

            if (! $cashierUser->roles()->where('roles.role_id', $cashierRole->role_id)->exists()) {
                $cashierUser->roles()->attach($cashierRole->role_id);
            }

            if (! $cashierUser->branches()->where('branches.branch_id', $branch->branch_id)->exists()) {
                $cashierUser->branches()->attach($branch->branch_id);
            }

            $counter = Counter::updateOrCreate(
                ['counter_code' => $cfg['counter']],
                [
                    'branch_id' => $branch->branch_id,
                    'counter_name' => "{$branch->city} Main Counter",
                    'terminal_id' => $cfg['t_id'],
                    'assigned_user_id' => $cashierUser->user_id,
                    'is_active' => true,
                ]
            );
        }

        // Also ensure a Muscat Supervisor exists for approvals
        $supervisorUser = User::updateOrCreate(
            ['username' => 'supervisor_mct'],
            [
                'full_name' => 'Muscat Branch Supervisor',
                'email' => 'supervisor.mct@rmsc.om',
                'password_hash' => Hash::make('password'),
                'default_branch_id' => $branches['BR-MCT']->branch_id,
                'is_active' => true,
            ]
        );
        if (! $supervisorUser->roles()->where('roles.role_id', $supervisorRole->role_id)->exists()) {
            $supervisorUser->roles()->attach($supervisorRole->role_id);
        }
        if (! $supervisorUser->branches()->where('branches.branch_id', $branches['BR-MCT']->branch_id)->exists()) {
            $supervisorUser->branches()->attach($branches['BR-MCT']->branch_id);
        }

        // 4. Products: Liquor (Controlled) and Non-Liquor (Uncontrolled)
        $spiritsCat = ProductCategory::firstOrCreate(['category_name' => 'Spirits']);
        $beveragesCat = ProductCategory::firstOrCreate(['category_name' => 'Beverages']);

        // Liquor product (controlled: is_permit_required = true)
        $liquorProduct = Product::updateOrCreate(
            ['sku_code' => 'SKU-JW-BLK-750'],
            [
                'barcode' => '5000267014005',
                'product_name' => 'Johnnie Walker Black Label 12YO 750ml',
                'category_id' => $spiritsCat->category_id,
                'base_uom_id' => $btlUom->uom_id,
                'tax_rate_id' => $vat5->tax_rate_id,
                'is_permit_required' => true,
                'is_duty_free_eligible' => true,
                'is_active' => true,
            ]
        );

        // Pricing tiers for liquor product
        ProductPricingTier::updateOrCreate(
            ['product_id' => $liquorProduct->product_id, 'price_tier' => 'RETAIL'],
            ['min_quantity' => 1, 'unit_price' => 18.500, 'effective_from' => '2026-01-01']
        );
        ProductPricingTier::updateOrCreate(
            ['product_id' => $liquorProduct->product_id, 'price_tier' => 'WHOLESALE'],
            ['min_quantity' => 1, 'unit_price' => 15.200, 'effective_from' => '2026-01-01']
        );

        // Non-liquor product (uncontrolled: is_permit_required = false)
        $nonLiquorProduct = Product::updateOrCreate(
            ['sku_code' => 'SKU-TONIC-CAN-250'],
            [
                'barcode' => '5449000012345',
                'product_name' => 'Schweppes Indian Tonic 250ml Can',
                'category_id' => $beveragesCat->category_id,
                'base_uom_id' => $canUom->uom_id,
                'tax_rate_id' => $vat5->tax_rate_id,
                'is_permit_required' => false,
                'is_duty_free_eligible' => false,
                'is_active' => true,
            ]
        );

        // Pricing tiers for non-liquor product
        ProductPricingTier::updateOrCreate(
            ['product_id' => $nonLiquorProduct->product_id, 'price_tier' => 'RETAIL'],
            ['min_quantity' => 1, 'unit_price' => 1.000, 'effective_from' => '2026-01-01']
        );
        ProductPricingTier::updateOrCreate(
            ['product_id' => $nonLiquorProduct->product_id, 'price_tier' => 'WHOLESALE'],
            ['min_quantity' => 1, 'unit_price' => 0.800, 'effective_from' => '2026-01-01']
        );

        // 5. Duty-Paid Stock in each branch warehouse + One Duty-Suspended Batch
        foreach ($branchesData as $bCode => $info) {
            $wh = $warehouses[$bCode];

            // Duty-Paid Liquor Batch
            $batchLiquor = StockBatch::updateOrCreate(
                ['batch_number' => "BATCH-{$bCode}-JW-DP"],
                [
                    'product_id' => $liquorProduct->product_id,
                    'manufacture_date' => '2025-10-01',
                    'expiry_date' => '2030-10-01',
                    'duty_status' => 'DUTY_PAID',
                ]
            );

            WarehouseStock::updateOrCreate(
                ['warehouse_id' => $wh->warehouse_id, 'batch_id' => $batchLiquor->batch_id],
                [
                    'product_id' => $liquorProduct->product_id,
                    'quantity_on_hand' => 100.00,
                    'quantity_reserved' => 0.00,
                    'quantity_available' => 100.00,
                ]
            );

            // Duty-Paid Non-Liquor Batch
            $batchTonic = StockBatch::updateOrCreate(
                ['batch_number' => "BATCH-{$bCode}-TONIC-DP"],
                [
                    'product_id' => $nonLiquorProduct->product_id,
                    'manufacture_date' => '2026-01-01',
                    'expiry_date' => '2027-01-01',
                    'duty_status' => 'DUTY_PAID',
                ]
            );

            WarehouseStock::updateOrCreate(
                ['warehouse_id' => $wh->warehouse_id, 'batch_id' => $batchTonic->batch_id],
                [
                    'product_id' => $nonLiquorProduct->product_id,
                    'quantity_on_hand' => 500.00,
                    'quantity_reserved' => 0.00,
                    'quantity_available' => 500.00,
                ]
            );
        }

        // One Duty-Suspended Batch in Bonded Warehouse
        $batchSuspended = StockBatch::updateOrCreate(
            ['batch_number' => 'BATCH-MCT-JW-SUSPENDED'],
            [
                'product_id' => $liquorProduct->product_id,
                'manufacture_date' => '2025-09-01',
                'expiry_date' => '2030-09-01',
                'duty_status' => 'DUTY_FREE_BONDED',
            ]
        );

        WarehouseStock::updateOrCreate(
            ['warehouse_id' => $whMctBonded->warehouse_id, 'batch_id' => $batchSuspended->batch_id],
            [
                'product_id' => $liquorProduct->product_id,
                'quantity_on_hand' => 50.00,
                'quantity_reserved' => 0.00,
                'quantity_available' => 50.00,
            ]
        );

        // 6. Oman Permit Verifications (Captured at POS billing)
        $mctBranch = $branches['BR-MCT'];
        $mctCounter = Counter::where('counter_code', 'CNT-MCT-01')->first();
        $mctCashier = User::where('username', 'cashier01')->first();

        $histShift = CashierShift::firstOrCreate(
            [
                'branch_id' => $mctBranch->branch_id,
                'counter_id' => $mctCounter->counter_id,
                'user_id' => $mctCashier->user_id,
                'status' => 'RECONCILED',
            ],
            [
                'opening_float' => 100.000,
                'opened_at' => now()->startOfYear()->addDays(5),
                'closed_at' => now()->startOfYear()->addDays(5)->addHours(8),
                'expected_cash' => 100.000,
                'counted_cash' => 100.000,
                'variance' => 0.000,
                'variance_status' => 'APPROVED',
            ]
        );

        $histSale = RetailSale::updateOrCreate(
            ['receipt_no' => 'RS-BR-MCT-HIST-0001'],
            [
                'branch_id' => $mctBranch->branch_id,
                'counter_id' => $mctCounter->counter_id,
                'cashier_shift_id' => $histShift->shift_id,
                'user_id' => $mctCashier->user_id,
                'customer_civil_id' => '12345678',
                'subtotal' => 457.143,
                'vat_total' => 22.857,
                'grand_total' => 480.000,
                'payment_method' => 'CARD',
                'card_amount' => 480.000,
                'card_reference' => 'HIST-CARD-001',
                'status' => 'COMPLETED',
                'created_at' => now()->startOfYear()->addDays(5),
            ]
        );

        // Verification 1: Manual entry linked to historical sale
        PermitVerification::updateOrCreate(
            ['retail_sale_id' => $histSale->id],
            [
                'branch_id' => $mctBranch->branch_id,
                'counter_id' => $mctCounter->counter_id,
                'cashier_shift_id' => $histShift->shift_id,
                'entered_by' => $mctCashier->user_id,
                'civil_id' => '12345678',
                'civil_id_hash' => hash_hmac('sha256', '12345678', (string) config('app.key')),
                'civil_id_last4' => '5678',
                'holder_name' => 'Alexander Wright',
                'permit_no' => 'PERM-2026-0082',
                'annual_limit' => 500.000,
                'used_amount' => 0.000,
                'remaining_amount' => 500.000,
                'valid_from' => '2026-01-01',
                'valid_to' => '2026-12-31',
                'verified_at' => now()->startOfYear()->addDays(5),
                'entry_method' => 'MANUAL',
                'controlled_total' => 480.000,
                'status' => 'USED',
                'attested_by' => $mctCashier->user_id,
                'attested_at' => now()->startOfYear()->addDays(5),
                'created_at' => now()->startOfYear()->addDays(5),
            ]
        );

        // Verification 2: Screenshot extraction demonstration (Fatima Al-Harthy)
        $histSale2 = RetailSale::updateOrCreate(
            ['receipt_no' => 'RS-BR-MCT-HIST-0002'],
            [
                'branch_id' => $mctBranch->branch_id,
                'counter_id' => $mctCounter->counter_id,
                'cashier_shift_id' => $histShift->shift_id,
                'user_id' => $mctCashier->user_id,
                'customer_civil_id' => '87654321',
                'subtotal' => 38.095,
                'vat_total' => 1.905,
                'grand_total' => 40.000,
                'payment_method' => 'CARD',
                'card_amount' => 40.000,
                'card_reference' => 'HIST-CARD-002',
                'status' => 'COMPLETED',
                'created_at' => now()->startOfYear()->addDays(6),
            ]
        );

        PermitVerification::updateOrCreate(
            ['retail_sale_id' => $histSale2->id],
            [
                'branch_id' => $mctBranch->branch_id,
                'counter_id' => $mctCounter->counter_id,
                'cashier_shift_id' => $histShift->shift_id,
                'entered_by' => $mctCashier->user_id,
                'civil_id' => '87654321',
                'civil_id_hash' => hash_hmac('sha256', '87654321', (string) config('app.key')),
                'civil_id_last4' => '4321',
                'holder_name' => 'Fatima Al-Harthy',
                'permit_no' => 'PERM-2026-0145',
                'annual_limit' => 600.000,
                'used_amount' => 150.000,
                'remaining_amount' => 450.000,
                'valid_from' => '2026-01-01',
                'valid_to' => '2026-12-31',
                'verified_at' => now()->startOfYear()->addDays(6),
                'entry_method' => 'SCREENSHOT',
                'screenshot_path' => 'permit-screenshots/BR-MCT/2026/01/demo-screenshot.png',
                'extraction_confidence' => 0.95,
                'extraction_raw' => ['source' => 'demo_extractor'],
                'controlled_total' => 40.000,
                'status' => 'USED',
                'attested_by' => $mctCashier->user_id,
                'attested_at' => now()->startOfYear()->addDays(6),
                'created_at' => now()->startOfYear()->addDays(6),
            ]
        );

        // 7. Wholesale Customers: One ACTIVE with credit_limit & 30-day terms; One ON_HOLD
        $wholesaleCustomerActive = Customer::updateOrCreate(
            ['customer_code' => 'CUST-HYATT-MCT'],
            [
                'customer_name' => 'Grand Hyatt Muscat',
                'customer_segment' => 'HORECA',
                'phone' => '+968 2464 1234',
                'email' => 'purchase@grandhyattmuscat.com',
                'oman_card_no' => 'CR-1092834',
                'vat_number' => 'OM1100223344',
                'credit_limit' => 25000.000,
                'payment_terms_days' => 30,
                'credit_status' => 'ACTIVE',
                'customer_type' => 'WHOLESALE',
                'is_active' => true,
            ]
        );

        CustomerCreditTerm::updateOrCreate(
            ['customer_id' => $wholesaleCustomerActive->customer_id],
            [
                'credit_limit' => 25000.000,
                'payment_terms_days' => 30,
                'outstanding_balance' => 0.000,
            ]
        );

        $wholesaleCustomerOnHold = Customer::updateOrCreate(
            ['customer_code' => 'CUST-DEMO-ONHOLD'],
            [
                'customer_name' => 'Al Bustan Palace Hotel (Hold)',
                'customer_segment' => 'HORECA',
                'phone' => '+968 2479 9666',
                'email' => 'finance@albustanpalace.om',
                'oman_card_no' => 'CR-9928172',
                'vat_number' => 'OM9988776655',
                'credit_limit' => 10000.000,
                'payment_terms_days' => 30,
                'credit_status' => 'ON_HOLD',
                'customer_type' => 'WHOLESALE',
                'is_active' => true,
            ]
        );

        CustomerCreditTerm::updateOrCreate(
            ['customer_id' => $wholesaleCustomerOnHold->customer_id],
            [
                'credit_limit' => 10000.000,
                'payment_terms_days' => 30,
                'outstanding_balance' => 0.000,
            ]
        );
    }
}
