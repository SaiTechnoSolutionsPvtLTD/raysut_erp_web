<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Counter;
use App\Models\NumberingSeries;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed Company
        $company = Company::create([
            'company_name' => 'Raysut Marketing & Supply Co. LLC (RMSC)',
            'trade_license_no' => 'CR-1098234',
            'liquor_license_no' => 'LL-2026-OM',
            'base_currency_code' => 'OMR',
            'address' => 'Way 3508, Muscat Central Industrial Area, Sultanate of Oman',
            'logo_path' => 'images/rmsc-logo-mark-square.png',
            'is_active' => true,
        ]);

        // 2. Seed Branches
        $branchMct = Branch::create([
            'company_id' => $company->company_id,
            'branch_code' => 'BR-MCT',
            'branch_name' => 'Muscat Headquarters & Distribution',
            'branch_type' => 'MIXED',
            'city' => 'Muscat',
            'is_active' => true,
        ]);

        $branchSll = Branch::create([
            'company_id' => $company->company_id,
            'branch_code' => 'BR-SLL',
            'branch_name' => 'Salalah Regional Depot',
            'branch_type' => 'WHOLESALE',
            'city' => 'Salalah',
            'is_active' => true,
        ]);

        $branchSoh = Branch::create([
            'company_id' => $company->company_id,
            'branch_code' => 'BR-SOH',
            'branch_name' => 'Sohar Terminal Office',
            'branch_type' => 'WHOLESALE',
            'city' => 'Sohar',
            'is_active' => true,
        ]);

        $branchNzw = Branch::create([
            'company_id' => $company->company_id,
            'branch_code' => 'BR-NZW',
            'branch_name' => 'Nizwa Regional Distribution',
            'branch_type' => 'RETAIL',
            'city' => 'Nizwa',
            'is_active' => true,
        ]);

        // 3. Seed 3 Duty-Free Bonded Facilities & Main Warehouses
        $whMuscatBonded = Warehouse::create([
            'company_id' => $company->company_id,
            'branch_id' => $branchMct->branch_id,
            'warehouse_code' => 'WH-MCT-BOND',
            'warehouse_name' => 'Muscat Central Bonded Warehouse',
            'warehouse_type' => 'DUTY_FREE_BONDED',
            'customs_bond_ref' => 'BOND-MCT-001',
            'is_active' => true,
        ]);

        $whSalalahDepot = Warehouse::create([
            'company_id' => $company->company_id,
            'branch_id' => $branchSll->branch_id,
            'warehouse_code' => 'WH-SLL-DEPOT',
            'warehouse_name' => 'Salalah Freezone Depot',
            'warehouse_type' => 'DUTY_FREE_BONDED',
            'customs_bond_ref' => 'SFZ-BOND-002',
            'is_active' => true,
        ]);

        $whSoharBonded = Warehouse::create([
            'company_id' => $company->company_id,
            'branch_id' => $branchSoh->branch_id,
            'warehouse_code' => 'WH-SOH-BOND',
            'warehouse_name' => 'Sohar Bonded Terminal',
            'warehouse_type' => 'DUTY_FREE_BONDED',
            'customs_bond_ref' => 'SOH-BOND-003',
            'is_active' => true,
        ]);

        $whMuscatMain = Warehouse::create([
            'company_id' => $company->company_id,
            'branch_id' => $branchMct->branch_id,
            'warehouse_code' => 'WH-MCT-MAIN',
            'warehouse_name' => 'Muscat Main Distribution Center',
            'warehouse_type' => 'MAIN',
            'customs_bond_ref' => null,
            'is_active' => true,
        ]);

        $whNizwaMain = Warehouse::create([
            'company_id' => $company->company_id,
            'branch_id' => $branchNzw->branch_id,
            'warehouse_code' => 'WH-NZW-MAIN',
            'warehouse_name' => 'Nizwa Main Depot',
            'warehouse_type' => 'MAIN',
            'customs_bond_ref' => null,
            'is_active' => true,
        ]);

        // 4. Seed Counters per Branch
        Counter::create([
            'branch_id' => $branchMct->branch_id,
            'counter_code' => 'CNT-MCT-01',
            'counter_name' => 'Main POS Counter 01',
            'terminal_id' => 'POS-T101',
            'is_active' => true,
        ]);

        Counter::create([
            'branch_id' => $branchMct->branch_id,
            'counter_code' => 'CNT-MCT-02',
            'counter_name' => 'Express Counter 02',
            'terminal_id' => 'POS-T102',
            'is_active' => true,
        ]);

        Counter::create([
            'branch_id' => $branchSll->branch_id,
            'counter_code' => 'CNT-SLL-01',
            'counter_name' => 'Salalah Regional Checkout 01',
            'terminal_id' => 'POS-T201',
            'is_active' => true,
        ]);

        Counter::create([
            'branch_id' => $branchSoh->branch_id,
            'counter_code' => 'CNT-SOH-01',
            'counter_name' => 'Sohar Terminal Counter 01',
            'terminal_id' => 'POS-T301',
            'is_active' => true,
        ]);

        Counter::create([
            'branch_id' => $branchNzw->branch_id,
            'counter_code' => 'CNT-NZW-01',
            'counter_name' => 'Nizwa Branch Counter 01',
            'terminal_id' => 'POS-T401',
            'is_active' => true,
        ]);

        // 5. Seed Permissions Catalog
        $modules = [
            'System, Security & Setup',
            'Master Data',
            'Purchase & Import',
            'Warehouse & Inventory',
            'Branch & Shift Operations',
            'Retail & Wholesale Sales',
            'Promotions & Expiry Management',
            'Harbor, Grocery & E-Commerce',
            'Finance — Core Accounting',
            'Finance — Statutory Statements',
            'HRMS & Payroll',
            'Reports & Dashboards',
            'Integrations & System Settings',
        ];

        $actions = ['VIEW', 'CREATE', 'EDIT', 'DELETE', 'APPROVE'];
        $createdPermissions = [];

        foreach ($modules as $mod) {
            foreach ($actions as $act) {
                $createdPermissions[] = Permission::create([
                    'module_name' => $mod,
                    'action_code' => $act,
                    'description' => "Allows {$act} action in {$mod}",
                ]);
            }
        }

        // 6. Seed Roles & Permissions
        $superAdminRole = Role::create([
            'role_name' => 'Super Admin',
            'description' => 'Full unrestricted system-wide administrative access',
        ]);

        $hqAdminRole = Role::create([
            'role_name' => 'HQ Admin',
            'description' => 'HQ administrative access across all branches',
        ]);

        $branchAdminRole = Role::create([
            'role_name' => 'Branch Admin',
            'description' => 'Branch administration, staff, and counters management',
        ]);

        $supervisorRole = Role::create([
            'role_name' => 'Supervisor',
            'description' => 'Branch supervisor with shift variance approvals and credit override permissions',
        ]);

        $branchManagerRole = Role::create([
            'role_name' => 'Branch Manager',
            'description' => 'Manages branch operations, stock transfers, and sales approvals',
        ]);

        $cashierRole = Role::create([
            'role_name' => 'Cashier',
            'description' => 'Executes retail POS counter transactions and shift openings',
        ]);

        $whOperatorRole = Role::create([
            'role_name' => 'Warehouse Operator',
            'description' => 'Handles goods receipts, stock counts, and bonded transfers',
        ]);

        // Attach permissions to roles
        $superAdminRole->permissions()->sync(collect($createdPermissions)->pluck('permission_id')->toArray());
        $hqAdminRole->permissions()->sync(collect($createdPermissions)->pluck('permission_id')->toArray());

        $branchAdminPerms = Permission::whereIn('module_name', ['Branch & Shift Operations', 'Retail & Wholesale Sales', 'Master Data', 'Warehouse & Inventory'])->pluck('permission_id');
        $branchAdminRole->permissions()->sync($branchAdminPerms);

        $supervisorPerms = Permission::where(function ($q) {
            $q->whereIn('module_name', ['Branch & Shift Operations', 'Retail & Wholesale Sales'])
              ->whereIn('action_code', ['VIEW', 'CREATE', 'EDIT', 'APPROVE']);
        })->orWhere(function ($q) {
            $q->whereIn('module_name', ['Master Data', 'Warehouse & Inventory'])
              ->where('action_code', 'VIEW');
        })->pluck('permission_id');
        $supervisorRole->permissions()->sync($supervisorPerms);

        $bmPerms = Permission::whereIn('module_name', ['Master Data', 'Purchase & Import', 'Warehouse & Inventory', 'Branch & Shift Operations', 'Retail & Wholesale Sales'])->pluck('permission_id');
        $branchManagerRole->permissions()->sync($bmPerms);

        $cashierPerms = Permission::whereIn('module_name', ['Branch & Shift Operations', 'Retail & Wholesale Sales'])
            ->whereIn('action_code', ['VIEW', 'CREATE'])
            ->pluck('permission_id');
        $cashierRole->permissions()->sync($cashierPerms);

        $whPerms = Permission::whereIn('module_name', ['Warehouse & Inventory', 'Purchase & Import'])
            ->whereIn('action_code', ['VIEW', 'CREATE', 'EDIT'])
            ->pluck('permission_id');
        $whOperatorRole->permissions()->sync($whPerms);

        // 7. Seed Users
        $adminUser = User::create([
            'username' => 'admin',
            'password_hash' => Hash::make('password'),
            'full_name' => 'RMSC Super Admin',
            'email' => 'admin@rmsc.om',
            'default_branch_id' => $branchMct->branch_id,
            'preferred_language' => 'EN',
            'is_active' => true,
        ]);
        $adminUser->roles()->attach($superAdminRole->role_id);
        $adminUser->branches()->attach([$branchMct->branch_id, $branchSll->branch_id, $branchSoh->branch_id]);

        $managerUser = User::create([
            'username' => 'manager_mct',
            'password_hash' => Hash::make('password'),
            'full_name' => 'Muscat Branch Manager',
            'email' => 'manager.mct@rmsc.om',
            'default_branch_id' => $branchMct->branch_id,
            'preferred_language' => 'EN',
            'is_active' => true,
        ]);
        $managerUser->roles()->attach($branchManagerRole->role_id);
        $managerUser->branches()->attach([$branchMct->branch_id]);

        $cashierUser = User::create([
            'username' => 'cashier01',
            'password_hash' => Hash::make('password'),
            'full_name' => 'Muscat Cashier 01',
            'email' => 'cashier01@rmsc.om',
            'default_branch_id' => $branchMct->branch_id,
            'preferred_language' => 'EN',
            'is_active' => true,
        ]);
        $cashierUser->roles()->attach($cashierRole->role_id);
        $cashierUser->branches()->attach([$branchMct->branch_id]);

        // 8. Seed Starter Numbering Series
        NumberingSeries::create([
            'document_type' => 'PO',
            'prefix' => 'PO-',
            'next_number' => 1,
            'fiscal_year' => '2026',
        ]);

        NumberingSeries::create([
            'document_type' => 'INVOICE',
            'prefix' => 'INV-',
            'next_number' => 1,
            'fiscal_year' => '2026',
        ]);

        NumberingSeries::create([
            'document_type' => 'GRN',
            'prefix' => 'GRN-',
            'next_number' => 1,
            'fiscal_year' => '2026',
        ]);

        NumberingSeries::create([
            'document_type' => 'SO',
            'prefix' => 'SO-',
            'next_number' => 1,
            'fiscal_year' => '2026',
        ]);

        // 9. Seed System Settings
        SystemSetting::create([
            'setting_key' => 'company_name',
            'setting_value' => 'Raysut Marketing & Supply Co. LLC (RMSC)',
            'description' => 'Legal Entity Name',
        ]);

        SystemSetting::create([
            'setting_key' => 'default_currency',
            'setting_value' => 'OMR',
            'description' => 'Base Reporting Currency (Omani Rial)',
        ]);

        SystemSetting::create([
            'setting_key' => 'currency_decimals',
            'setting_value' => '3',
            'description' => 'Decimal precision for OMR monetary values',
        ]);

        SystemSetting::create([
            'setting_key' => 'bayan_customs_integration',
            'setting_value' => 'ENABLED',
            'description' => 'Royal Oman Police Bayan Customs Portal Status',
        ]);

        // 10. Seed Phase 2 Master Data & Transactions
        $this->call([
            MasterDataSeeder::class,
            PurchaseAndInventorySeeder::class,
            DutyPaidConversionSeeder::class,
        ]);
    }
}
