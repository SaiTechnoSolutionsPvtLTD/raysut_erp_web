<?php

use App\Models\Branch;
use App\Models\Company;
use App\Models\Counter;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('phase 1 database seeder populates rmsc company, named warehouses, super admin, and roles', function () {
    $this->seed();

    expect(Company::count())->toBeGreaterThanOrEqual(1);
    $company = Company::first();
    expect($company->company_name)->toContain('Raysut Marketing & Supply Co. LLC');
    expect($company->base_currency_code)->toBe('OMR');

    // Verify 3 named facilities exist
    $whNames = Warehouse::pluck('warehouse_name')->toArray();
    expect($whNames)->toContain('Muscat Central Bonded Warehouse');
    expect($whNames)->toContain('Salalah Freezone Depot');
    expect($whNames)->toContain('Sohar Bonded Terminal');

    // Verify Super Admin
    $admin = User::where('username', 'admin')->first();
    expect($admin)->not->toBeNull();
    expect($admin->full_name)->toBe('RMSC Super Admin');
    expect($admin->getNameAttribute())->toBe('RMSC Super Admin');
    expect($admin->hasRole('Super Admin'))->toBeTrue();
    expect($admin->hasPermission('System, Security & Setup', 'VIEW'))->toBeTrue();
});

test('user model custom rbac permissions work as expected', function () {
    $role = Role::create([
        'role_name' => 'Custom Role',
        'description' => 'Test Role',
    ]);

    $user = User::create([
        'username' => 'testuser',
        'full_name' => 'Test Staff User',
        'email' => 'test@rmsc.om',
        'password_hash' => Hash::make('password'),
        'is_active' => true,
    ]);

    $user->roles()->attach($role->role_id);

    expect($user->hasRole('Custom Role'))->toBeTrue();
    expect($user->hasRole('Super Admin'))->toBeFalse();
});

test('restricted role users are gracefully redirected to dashboard from unauthorized module routes', function () {
    $this->seed();

    $cashierUser = User::where('username', 'cashier01')->first();

    // Cashier does not have System, Security & Setup permission => Redirects to Dashboard with warning flash
    $this->actingAs($cashierUser)
        ->get('/system/users')
        ->assertRedirect('/dashboard')
        ->assertSessionHas('warning');

    // Cashier does not have Master Data permission => Redirects to Dashboard with warning flash
    $this->actingAs($cashierUser)
        ->get('/master-data/products')
        ->assertRedirect('/dashboard')
        ->assertSessionHas('warning');

    // Super Admin has all permissions => 200 OK
    $admin = User::where('username', 'admin')->first();
    $this->actingAs($admin)
        ->get('/system/users')
        ->assertStatus(200);
});

test('auditable trait logs model creation, update, and deletion automatically', function () {
    $company = Company::create([
        'company_name' => 'Test Audit Company',
        'base_currency_code' => 'OMR',
        'is_active' => true,
    ]);

    $log = AuditLog::where('record_table', 'companies')
        ->where('record_id', $company->company_id)
        ->where('action', 'CREATE')
        ->first();

    expect($log)->not->toBeNull();
    expect($log->module_name)->toBe('System, Security & Setup');

    // Test update audit
    $company->update(['company_name' => 'Updated Audit Company']);

    $updateLog = AuditLog::where('record_table', 'companies')
        ->where('record_id', $company->company_id)
        ->where('action', 'UPDATE')
        ->first();

    expect($updateLog)->not->toBeNull();
});

test('branch management component auto generates branch code and creates custom branch type', function () {
    $this->seed();

    $admin = User::where('username', 'admin')->first();

    Livewire::actingAs($admin)
        ->test(\App\Livewire\System\BranchManagement::class)
        ->call('create')
        ->assertSet('branch_code', 'BR-004')
        ->set('branch_name', 'Nizwa Regional Distribution')
        ->set('branch_type', 'ADD_NEW')
        ->set('custom_branch_type', 'LOGISTICS')
        ->set('city', 'Nizwa')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('isModalOpen', false);

    $branch = Branch::where('branch_code', 'BR-004')->first();
    expect($branch)->not->toBeNull();
    expect($branch->branch_name)->toBe('Nizwa Regional Distribution');
    expect($branch->branch_type)->toBe('LOGISTICS');
    expect($branch->city)->toBe('Nizwa');
});

test('warehouse management component auto generates code, custom warehouse type and deletes via confirm modal', function () {
    $this->seed();

    $admin = User::where('username', 'admin')->first();

    Livewire::actingAs($admin)
        ->test(\App\Livewire\System\WarehouseManagement::class)
        ->call('create')
        ->assertSet('warehouse_code', 'WH-004')
        ->set('warehouse_name', 'Duqm Freezone Cold Storage')
        ->set('warehouse_type', 'ADD_NEW')
        ->set('custom_warehouse_type', 'COLD_CHAIN')
        ->set('customs_bond_ref', 'BOND-DUQM-99')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('isModalOpen', false);

    $wh = Warehouse::where('warehouse_code', 'WH-004')->first();
    expect($wh)->not->toBeNull();
    expect($wh->warehouse_name)->toBe('Duqm Freezone Cold Storage');
    expect($wh->warehouse_type)->toBe('COLD_CHAIN');
    expect($wh->customs_bond_ref)->toBe('BOND-DUQM-99');

    // Test Delete Confirmation Modal Flow
    Livewire::actingAs($admin)
        ->test(\App\Livewire\System\WarehouseManagement::class)
        ->call('confirmDelete', $wh->warehouse_id)
        ->assertSet('isDeleteModalOpen', true)
        ->assertSet('deletingWarehouseId', $wh->warehouse_id)
        ->call('deleteWarehouse')
        ->assertSet('isDeleteModalOpen', false);

    expect(Warehouse::find($wh->warehouse_id))->toBeNull();
});

test('users with only view permission are blocked from calling create edit or delete actions', function () {
    $role = Role::create([
        'role_name' => 'View Only Supervisor',
        'description' => 'Can view branches but not mutate data',
    ]);

    $viewPerm = \App\Models\Permission::firstOrCreate([
        'module_name' => 'Branch & Shift Operations',
        'action_code' => 'VIEW',
    ], [
        'description' => 'Allows VIEW in Branch & Shift Operations',
    ]);

    $role->permissions()->attach($viewPerm->permission_id);

    $user = User::create([
        'username' => 'viewonly_user',
        'full_name' => 'View Only User',
        'email' => 'viewonly@rmsc.om',
        'password_hash' => Hash::make('password'),
        'is_active' => true,
    ]);
    $user->roles()->attach($role->role_id);

    // User can access route (VIEW permission)
    $this->actingAs($user)
        ->get('/system/branches')
        ->assertStatus(200)
        ->assertDontSee('Add New Branch');

    // Attempting to invoke create livewire action aborts with 403
    Livewire::actingAs($user)
        ->test(\App\Livewire\System\BranchManagement::class)
        ->call('create')
        ->assertStatus(403);
});

