<?php

namespace Tests\Feature;

use App\Livewire\BranchOperations\BranchManagement;
use App\Livewire\BranchOperations\CounterManagement;
use App\Livewire\BranchOperations\ShiftManagement;
use App\Models\Branch;
use App\Models\CashierShift;
use App\Models\Counter;
use App\Models\Product;
use App\Models\Role;
use App\Models\StockBatch;
use App\Models\TaxVatRate;
use App\Models\Uom;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Services\ShiftService;
use App\Services\StockLedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class Sprint31BranchAndShiftTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $cashier;
    protected User $guestUser;
    protected Branch $branch1;
    protected Branch $branch2;
    protected Counter $counter1;
    protected Counter $counter2;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles & basic structure
        $this->seed();

        $this->superAdmin = User::where('username', 'admin')->first() ?? User::factory()->create([
            'full_name' => 'Super Admin',
            'username' => 'superadmin',
            'email' => 'admin@rmsc.om',
        ]);
        $superAdminRole = Role::firstOrCreate(['role_name' => 'Super Admin']);
        if (! $this->superAdmin->hasRole('Super Admin')) {
            $this->superAdmin->roles()->attach($superAdminRole->role_id);
        }
        $this->superAdmin->load('roles.permissions');

        $this->branch1 = Branch::where('branch_code', 'BR-MCT')->first();
        $this->branch2 = Branch::where('branch_code', 'BR-SLL')->first();

        $this->counter1 = Counter::where('counter_code', 'CNT-MCT-01')->first() ?? Counter::create([
            'branch_id' => $this->branch1->branch_id,
            'counter_code' => 'CNT-MCT-01',
            'counter_name' => 'Muscat Checkout 1',
            'is_active' => true,
        ]);

        $this->counter2 = Counter::where('counter_code', 'CNT-SLL-01')->first() ?? Counter::create([
            'branch_id' => $this->branch2->branch_id,
            'counter_code' => 'CNT-SLL-01',
            'counter_name' => 'Salalah Checkout 1',
            'is_active' => true,
        ]);

        $this->cashier = User::where('username', 'cashier01')->first() ?? User::factory()->create([
            'full_name' => 'Muscat Cashier',
            'username' => 'cashier_mct',
            'email' => 'cashier@rmsc.om',
            'default_branch_id' => $this->branch1->branch_id,
        ]);
        $cashierRole = Role::where('role_name', 'Cashier')->first();
        if ($cashierRole && ! $this->cashier->hasRole('Cashier')) {
            $this->cashier->roles()->attach($cashierRole->role_id);
        }
        $this->cashier->load('roles.permissions');

        $this->guestUser = User::factory()->create([
            'full_name' => 'Guest Staff',
            'username' => 'guest_staff',
            'email' => 'guest@rmsc.om',
        ]);
    }

    #[Test]
    public function day1_and_day2_admin_can_manage_branches_and_counters()
    {
        // 1. Create branch with address
        Livewire::actingAs($this->superAdmin)
            ->test(BranchManagement::class)
            ->call('openCreateModal')
            ->set('branch_code', 'BR-TEST')
            ->set('branch_name', 'Test Branch')
            ->set('branch_type', 'RETAIL')
            ->set('city', 'Sur')
            ->set('address', 'Sur Port Road, Building 14')
            ->set('is_active', true)
            ->call('saveBranch')
            ->assertHasNoErrors()
            ->assertSee('Branch created successfully.');

        $this->assertDatabaseHas('branches', [
            'branch_code' => 'BR-TEST',
            'address' => 'Sur Port Road, Building 14',
        ]);

        $testBranch = Branch::where('branch_code', 'BR-TEST')->first();

        // 2. Update branch
        Livewire::actingAs($this->superAdmin)
            ->test(BranchManagement::class)
            ->call('editBranch', $testBranch->branch_id)
            ->set('branch_name', 'Test Branch Updated')
            ->set('city', 'Sur Coastal')
            ->set('address', 'Updated Commercial District, Sur')
            ->set('is_active', true)
            ->call('saveBranch')
            ->assertHasNoErrors()
            ->assertSee('Branch updated successfully.');

        $this->assertDatabaseHas('branches', [
            'branch_id' => $testBranch->branch_id,
            'branch_name' => 'Test Branch Updated',
            'address' => 'Updated Commercial District, Sur',
        ]);

        // 3. Toggle branch status
        Livewire::actingAs($this->superAdmin)
            ->test(BranchManagement::class)
            ->call('toggleStatus', $testBranch->branch_id)
            ->assertSee('Branch status updated to Inactive.');

        $testBranch->refresh();
        $this->assertFalse($testBranch->is_active);

        // 4. Create counter for test branch
        Livewire::actingAs($this->superAdmin)
            ->test(CounterManagement::class)
            ->call('openCreateModal')
            ->set('branch_id', $testBranch->branch_id)
            ->set('counter_code', 'CNT-TEST-01')
            ->set('counter_name', 'Sur Terminal 1')
            ->set('terminal_id', 'TERM-SUR-01')
            ->set('is_active', true)
            ->call('saveCounter')
            ->assertHasNoErrors()
            ->assertSee('created successfully');

        $this->assertDatabaseHas('counters', [
            'counter_code' => 'CNT-TEST-01',
            'terminal_id' => 'TERM-SUR-01',
        ]);

        $counter = Counter::where('counter_code', 'CNT-TEST-01')->first();

        // 5. Update counter
        Livewire::actingAs($this->superAdmin)
            ->test(CounterManagement::class)
            ->call('editCounter', $counter->counter_id)
            ->set('counter_name', 'Sur Express Checkout')
            ->set('terminal_id', 'TERM-SUR-99')
            ->call('saveCounter')
            ->assertHasNoErrors()
            ->assertSee('updated successfully');

        $this->assertDatabaseHas('counters', [
            'counter_id' => $counter->counter_id,
            'counter_name' => 'Sur Express Checkout',
            'terminal_id' => 'TERM-SUR-99',
        ]);

        // 6. Toggle counter status
        Livewire::actingAs($this->superAdmin)
            ->test(CounterManagement::class)
            ->call('toggleStatus', $counter->counter_id)
            ->assertSee('status updated to Inactive');

        $counter->refresh();
        $this->assertFalse($counter->is_active);

        // 7. Delete counter
        Livewire::actingAs($this->superAdmin)
            ->test(CounterManagement::class)
            ->call('confirmDeleteCounter', $counter->counter_id, $counter->counter_name)
            ->call('executeDeleteItem')
            ->assertSee('deleted successfully');

        $this->assertDatabaseMissing('counters', ['counter_id' => $counter->counter_id]);

        // 8. Delete branch
        Livewire::actingAs($this->superAdmin)
            ->test(BranchManagement::class)
            ->call('confirmDeleteBranch', $testBranch->branch_id)
            ->call('executeDeleteItem')
            ->assertSee('deleted successfully');

        $this->assertDatabaseMissing('branches', ['branch_id' => $testBranch->branch_id]);
    }

    #[Test]
    public function day1_and_day2_branch_and_counter_validation_and_policies_enforced()
    {
        // Duplicate branch code should fail validation
        Livewire::actingAs($this->superAdmin)
            ->test(BranchManagement::class)
            ->call('openCreateModal')
            ->set('branch_code', $this->branch1->branch_code)
            ->set('branch_name', 'Duplicate Code Branch')
            ->set('branch_type', 'RETAIL')
            ->call('saveBranch')
            ->assertHasErrors(['branch_code']);

        // Missing required branch_name should fail validation
        Livewire::actingAs($this->superAdmin)
            ->test(BranchManagement::class)
            ->call('openCreateModal')
            ->set('branch_code', 'BR-NEW-VALID')
            ->set('branch_name', '')
            ->set('branch_type', 'RETAIL')
            ->call('saveBranch')
            ->assertHasErrors(['branch_name']);

        // Unauthorized user without permissions cannot store branch
        Livewire::actingAs($this->guestUser)
            ->test(BranchManagement::class)
            ->call('openCreateModal')
            ->assertForbidden();
    }

    #[Test]
    public function day2_sidebar_renders_branch_and_sales_operations_group_with_active_routes()
    {
        $this->actingAs($this->superAdmin);

        $response = $this->get(route('dashboard'));
        $response->assertStatus(200);

        // Active sidebar group name
        $response->assertSee('Branch & Sales Operations');

        // Verify active items present in sidebar
        $response->assertSee('Cashier Shifts & Floats');
        $response->assertSee('Retail POS Billing');
        $response->assertSee('Branches & Facilities');
        $response->assertSee('POS Counters');

        // Master Data entries present
        $response->assertSee('Units of Measure (UOM)');
        $response->assertSee('Tax & VAT Setup');

        // Inventory entries present
        $response->assertSee('Duty-Paid Conversions');
        $response->assertSee('End-to-End Stock Flow Demonstration');
    }

    #[Test]
    public function day3_branch_scoping_restricts_cashier_visibility()
    {
        $this->actingAs($this->cashier);

        $response = $this->get(route('system.branches.index'));
        $response->assertStatus(200);

        // Cashier sees their default branch
        $response->assertSee('Muscat');
    }

    #[Test]
    public function day3_stock_ledger_service_calculates_branch_counter_stock()
    {
        $service = app(StockLedgerService::class);
        $stockList = $service->getBranchCounterStock($this->branch1->branch_id);

        $this->assertNotNull($stockList);
    }

    #[Test]
    public function day3_stock_ledger_deductions_respect_duty_paid_vs_duty_suspended()
    {
        $service = app(StockLedgerService::class);

        $bondedWh = Warehouse::where('warehouse_type', 'DUTY_FREE_BONDED')->first();
        $mainWh = Warehouse::where('warehouse_type', 'MAIN')->first();
        $product = Product::first();

        // 1. Bonded (duty-free suspended) batch
        $bondedBatch = StockBatch::create([
            'product_id' => $product->product_id,
            'batch_number' => 'TEST-BONDED-BATCH',
            'duty_status' => 'DUTY_FREE_BONDED',
        ]);
        $service->recordIntake($bondedWh->warehouse_id, $product->product_id, $bondedBatch->batch_id, 100.0);

        // 2. Duty-paid batch
        $dutyPaidBatch = StockBatch::create([
            'product_id' => $product->product_id,
            'batch_number' => 'TEST-DUTY-PAID-BATCH',
            'duty_status' => 'DUTY_PAID',
        ]);
        $service->recordIntake($mainWh->warehouse_id, $product->product_id, $dutyPaidBatch->batch_id, 100.0);

        // Attempting to deduct retail or wholesale sales from bonded batch or warehouse must throw exception
        $threwException = false;
        try {
            $service->deductSaleStock(
                warehouseId: $bondedWh->warehouse_id,
                productId: $product->product_id,
                batchId: $bondedBatch->batch_id,
                quantity: 10.0,
                saleType: 'RETAIL'
            );
        } catch (InvalidArgumentException $e) {
            $threwException = true;
            $this->assertStringContainsString('may only deduct duty-paid stock', $e->getMessage());
        }
        $this->assertTrue($threwException, 'Expected InvalidArgumentException when deducting retail sale from duty-suspended stock');

        // Deducting retail sales from duty-paid stock must succeed
        $stockAfter = $service->deductSaleStock(
            warehouseId: $mainWh->warehouse_id,
            productId: $product->product_id,
            batchId: $dutyPaidBatch->batch_id,
            quantity: 10.0,
            saleType: 'RETAIL'
        );
        $this->assertEquals(90.0, (float) $stockAfter->quantity_on_hand);
    }

    #[Test]
    public function day4_cashier_can_open_shift_with_float()
    {
        Livewire::actingAs($this->cashier)
            ->test(ShiftManagement::class)
            ->call('openShiftModal')
            ->set('openCounterId', $this->counter1->counter_id)
            ->set('openFloat', 50.000)
            ->set('openNotes', 'Starting shift with 50 OMR float.')
            ->call('executeOpenShift')
            ->assertHasNoErrors()
            ->assertSee('opened successfully');

        $this->assertDatabaseHas('cashier_shifts', [
            'user_id' => $this->cashier->user_id,
            'counter_id' => $this->counter1->counter_id,
            'opening_float' => 50.000,
            'status' => 'OPEN',
        ]);
    }

    #[Test]
    public function day4_cashier_cannot_open_duplicate_shifts()
    {
        $shiftService = app(ShiftService::class);
        $shiftService->open($this->cashier, $this->counter1->counter_id, 50.000);

        // Try opening another shift for same cashier
        Livewire::actingAs($this->cashier)
            ->test(ShiftManagement::class)
            ->set('openCounterId', $this->counter1->counter_id)
            ->set('openFloat', 30.000)
            ->call('executeOpenShift')
            ->assertSee('already has an active open shift');
    }

    #[Test]
    public function day4_pos_routes_are_blocked_without_an_open_shift()
    {
        $this->actingAs($this->cashier);

        // Access POS terminal without open shift
        $response = $this->get(route('pos.terminal'));
        $response->assertRedirect(route('shifts.open'));
    }

    #[Test]
    public function day4_pos_terminal_uses_master_data_uom_and_tax_rates_when_shift_is_open()
    {
        $this->actingAs($this->cashier);

        // Open shift for cashier
        $shiftService = app(ShiftService::class);
        $shift = $shiftService->open($this->cashier, $this->counter1->counter_id, 50.000);

        // POS terminal is accessible
        $response = $this->get(route('pos.terminal'));
        $response->assertStatus(200);

        // Verify dynamic shift and counter information
        $response->assertSee("SHIFT #{$shift->shift_id} OPEN");
        $response->assertSee($this->counter1->counter_code);

        // Verify Master Data UOM and dynamic tax rates are rendered without hardcoding
        $response->assertSee('Retail POS Billing Terminal');
        $response->assertSee('Calculated Oman VAT');
    }

    #[Test]
    public function day5_shift_closing_calculates_variance_and_flag_threshold()
    {
        $shiftService = app(ShiftService::class);
        $shift = $shiftService->open($this->cashier, $this->counter1->counter_id, 50.000);

        // Close with excess cash variance (Over 5.000 OMR threshold)
        Livewire::actingAs($this->cashier)
            ->test(ShiftManagement::class)
            ->call('openCloseModal', $shift->shift_id)
            ->set('countedCash', 100.000) // Expected is 50, counted 100 => +50 variance
            ->set('cardTotal', 20.000)
            ->set('closeNotes', 'Counted drawer cash after evening sales.')
            ->call('executeCloseShift')
            ->assertHasNoErrors()
            ->assertSee('closed successfully');

        $shift->refresh();
        $this->assertEquals('FLAGGED', $shift->variance_status);
        $this->assertEquals(50.000, $shift->variance);

        // Supervisor approves flagged shift
        Livewire::actingAs($this->superAdmin)
            ->test(ShiftManagement::class)
            ->call('openApproveModal', $shift->shift_id)
            ->set('supervisorNotes', 'Approved drawer surplus.')
            ->call('executeApproveShift')
            ->assertHasNoErrors()
            ->assertSee('variance approved and reconciled successfully');

        $shift->refresh();
        $this->assertEquals('APPROVED', $shift->variance_status);
        $this->assertEquals('RECONCILED', $shift->status);
    }

    #[Test]
    public function safe_deletion_blocks_branch_with_linked_records_and_allows_when_empty()
    {
        // 1. Branch with linked counters & users (e.g. branch1) must be refused
        $test = Livewire::actingAs($this->superAdmin)
            ->test(BranchManagement::class)
            ->call('confirmDeleteBranch', $this->branch1->branch_id)
            ->call('executeDeleteItem');
        $test->assertSee('Please deactivate the branch instead');

        $this->assertDatabaseHas('branches', ['branch_id' => $this->branch1->branch_id]);

        // 2. Empty branch with no counters, users, shifts, warehouses or sales can be deleted
        $cleanBranch = Branch::create([
            'company_id' => $this->branch1->company_id,
            'branch_code' => 'BR-CLEAN',
            'branch_name' => 'Clean Test Branch',
            'branch_type' => 'RETAIL',
            'city' => 'Nizwa',
            'is_active' => true,
        ]);

        Livewire::actingAs($this->superAdmin)
            ->test(BranchManagement::class)
            ->call('confirmDeleteBranch', $cleanBranch->branch_id)
            ->call('executeDeleteItem')
            ->assertSee("Branch 'Clean Test Branch' deleted successfully.");

        $this->assertDatabaseMissing('branches', ['branch_id' => $cleanBranch->branch_id]);
    }

    #[Test]
    public function safe_deletion_blocks_counter_with_shifts_and_allows_when_empty()
    {
        // 1. Counter with a cashier shift must be refused
        $shiftService = app(ShiftService::class);
        $shift = $shiftService->open($this->cashier, $this->counter1->counter_id, 25.000);

        Livewire::actingAs($this->superAdmin)
            ->test(CounterManagement::class)
            ->call('confirmDeleteCounter', $this->counter1->counter_id, $this->counter1->counter_name)
            ->call('executeDeleteItem')
            ->assertSee('Please deactivate the counter instead');

        $this->assertDatabaseHas('counters', ['counter_id' => $this->counter1->counter_id]);

        // Close the shift so counter is not active-locked
        $shiftService->close($shift, 25.000, 0.000);

        // 2. Clean counter with no shifts or sales can be deleted
        $cleanCounter = Counter::create([
            'branch_id' => $this->branch2->branch_id,
            'counter_code' => 'CNT-CLEAN-01',
            'counter_name' => 'Clean Unused Counter',
            'is_active' => true,
        ]);

        Livewire::actingAs($this->superAdmin)
            ->test(CounterManagement::class)
            ->call('confirmDeleteCounter', $cleanCounter->counter_id, $cleanCounter->counter_name)
            ->call('executeDeleteItem')
            ->assertSee('deleted successfully');

        $this->assertDatabaseMissing('counters', ['counter_id' => $cleanCounter->counter_id]);
    }

    #[Test]
    public function hq_branch_switcher_allows_hq_and_locks_branch_users()
    {
        // 1. Super Admin (HQ role) can view all branches and switch context
        $this->actingAs($this->superAdmin);

        // Switch to Salalah branch
        $switchResponse = $this->post(route('system.branches.switch'), [
            'branch_id' => $this->branch2->branch_id,
        ]);
        $switchResponse->assertSessionHas('success');
        $this->assertEquals($this->branch2->branch_id, session('active_branch_id'));

        // Salalah is listed, Muscat is scoped out
        $indexResponse = $this->get(route('system.branches.index'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee($this->branch2->branch_name);
        $indexResponse->assertDontSee($this->branch1->branch_name);

        // Switch back to "all" branches
        $resetResponse = $this->post(route('system.branches.switch'), [
            'branch_id' => 'all',
        ]);
        $resetResponse->assertSessionHas('success');
        $this->assertNull(session('active_branch_id'));

        // All branches visible again
        $allResponse = $this->get(route('system.branches.index'));
        $allResponse->assertSee($this->branch1->branch_name);
        $allResponse->assertSee($this->branch2->branch_name);

        // 2. Branch user (cashier) is locked to their own branch and cannot switch
        $this->actingAs($this->cashier);

        $unauthSwitch = $this->post(route('system.branches.switch'), [
            'branch_id' => $this->branch2->branch_id,
        ]);
        $unauthSwitch->assertStatus(403);

        // Cashier sees only their assigned branch (Muscat), locked out of Salalah
        $cashierIndex = $this->get(route('system.branches.index'));
        $cashierIndex->assertStatus(200);
        $cashierIndex->assertSee($this->branch1->branch_name);
        $cashierIndex->assertDontSee($this->branch2->branch_name);
    }

    #[Test]
    public function cashier_to_counter_assignment_enforces_assigned_user()
    {
        // Assign counter 1 specifically to cashier
        Livewire::actingAs($this->superAdmin)
            ->test(CounterManagement::class)
            ->call('editCounter', $this->counter1->counter_id)
            ->set('assigned_user_id', $this->cashier->user_id)
            ->call('saveCounter')
            ->assertHasNoErrors()
            ->assertSee('updated successfully');

        $this->counter1->refresh();
        $this->assertEquals($this->cashier->user_id, $this->counter1->assigned_user_id);

        // Create a different cashier
        $otherCashier = User::factory()->create([
            'full_name' => 'Other Cashier',
            'username' => 'cashier_other',
            'email' => 'other_cashier@rmsc.om',
            'default_branch_id' => $this->branch1->branch_id,
        ]);
        $cashierRole = Role::where('role_name', 'Cashier')->first();
        if ($cashierRole) {
            $otherCashier->roles()->attach($cashierRole->role_id);
        }

        // Other cashier tries to open shift on counter 1 -> blocked
        Livewire::actingAs($otherCashier)
            ->test(ShiftManagement::class)
            ->set('openCounterId', $this->counter1->counter_id)
            ->set('openFloat', 40.000)
            ->call('executeOpenShift')
            ->assertSee('is assigned to another cashier');

        $this->assertDatabaseMissing('cashier_shifts', [
            'user_id' => $otherCashier->user_id,
            'counter_id' => $this->counter1->counter_id,
        ]);

        // Assigned cashier can open shift on counter 1 -> succeeds
        Livewire::actingAs($this->cashier)
            ->test(ShiftManagement::class)
            ->set('openCounterId', $this->counter1->counter_id)
            ->set('openFloat', 40.000)
            ->call('executeOpenShift')
            ->assertHasNoErrors()
            ->assertSee('opened successfully');

        $this->assertDatabaseHas('cashier_shifts', [
            'user_id' => $this->cashier->user_id,
            'counter_id' => $this->counter1->counter_id,
            'status' => 'OPEN',
        ]);
    }

    #[Test]
    public function rule_one_open_shift_per_counter_is_strictly_enforced()
    {
        // Open shift for cashier on counter 2
        $shiftService = app(ShiftService::class);
        $shiftService->open($this->cashier, $this->counter2->counter_id, 30.000);

        // Another cashier tries to open shift on the same counter 2
        $anotherCashier = User::factory()->create([
            'full_name' => 'Second Cashier',
            'username' => 'cashier_second',
            'email' => 'second_cashier@rmsc.om',
            'default_branch_id' => $this->branch2->branch_id,
        ]);
        $cashierRole = Role::where('role_name', 'Cashier')->first();
        if ($cashierRole) {
            $anotherCashier->roles()->attach($cashierRole->role_id);
        }

        Livewire::actingAs($anotherCashier)
            ->test(ShiftManagement::class)
            ->set('openCounterId', $this->counter2->counter_id)
            ->set('openFloat', 20.000)
            ->call('executeOpenShift')
            ->assertSee('already has an active open shift');

        // Total OPEN shifts for counter 2 must be exactly 1
        $openShiftsCount = CashierShift::where('counter_id', $this->counter2->counter_id)
            ->where('status', 'OPEN')
            ->count();
        $this->assertEquals(1, $openShiftsCount);
    }

    #[Test]
    public function supervisor_can_approve_and_reject_shifts_flagged_for_variance()
    {
        $shiftService = app(ShiftService::class);

        // Shift 1: Approval flow
        $shift1 = $shiftService->open($this->cashier, $this->counter1->counter_id, 50.000);
        $shiftService->close($shift1, 100.000, 10.000, 'Drawer variance test');
        $shift1->refresh();
        $this->assertEquals('FLAGGED', $shift1->variance_status);

        // Non-supervisor cashier cannot approve or reject
        Livewire::actingAs($this->cashier)
            ->test(ShiftManagement::class)
            ->call('openApproveModal', $shift1->shift_id)
            ->assertForbidden();

        Livewire::actingAs($this->cashier)
            ->test(ShiftManagement::class)
            ->call('openRejectModal', $shift1->shift_id)
            ->assertForbidden();

        // Super Admin approves shift 1
        Livewire::actingAs($this->superAdmin)
            ->test(ShiftManagement::class)
            ->call('openApproveModal', $shift1->shift_id)
            ->set('supervisorNotes', 'Audited and approved drawer surplus.')
            ->call('executeApproveShift')
            ->assertHasNoErrors()
            ->assertSee('variance approved and reconciled successfully');

        $shift1->refresh();
        $this->assertEquals('APPROVED', $shift1->variance_status);
        $this->assertEquals('RECONCILED', $shift1->status);
        $this->assertEquals($this->superAdmin->user_id, $shift1->approved_by);
        $this->assertNotNull($shift1->approved_at);
        $this->assertStringContainsString('Audited and approved drawer surplus.', $shift1->notes);

        // Shift 2: Rejection flow
        $cashier2 = User::factory()->create([
            'full_name' => 'Third Cashier',
            'username' => 'cashier_third',
            'email' => 'third_cashier@rmsc.om',
            'default_branch_id' => $this->branch2->branch_id,
        ]);
        $cashierRole = Role::where('role_name', 'Cashier')->first();
        if ($cashierRole) {
            $cashier2->roles()->attach($cashierRole->role_id);
        }

        $shift2 = $shiftService->open($cashier2, $this->counter2->counter_id, 50.000);
        $shiftService->close($shift2, 20.000, 0.000, 'Cash shortage detected');
        $shift2->refresh();
        $this->assertEquals('FLAGGED', $shift2->variance_status);

        // Super Admin rejects shift 2
        Livewire::actingAs($this->superAdmin)
            ->test(ShiftManagement::class)
            ->call('openRejectModal', $shift2->shift_id)
            ->set('supervisorNotes', 'Unexplained shortage rejected - salary deduction initiated.')
            ->call('executeRejectShift')
            ->assertHasNoErrors()
            ->assertSee('variance rejected by supervisor.');

        $shift2->refresh();
        $this->assertEquals('REJECTED', $shift2->variance_status);
        $this->assertEquals($this->superAdmin->user_id, $shift2->approved_by);
        $this->assertNotNull($shift2->approved_at);
        $this->assertStringContainsString('salary deduction initiated', $shift2->notes);
    }

    #[Test]
    public function printable_shift_summary_displays_required_metrics_and_status()
    {
        $shiftService = app(ShiftService::class);
        $shift = $shiftService->open($this->cashier, $this->counter1->counter_id, 50.000);
        $shiftService->close($shift, 95.000, 30.000, 'End of evening shift');

        $this->actingAs($this->cashier);
        $response = $this->get(route('shifts.print', $shift->shift_id));

        $response->assertStatus(200);
        $response->assertSee('RMSC ERP - SHIFT SUMMARY');
        $response->assertSee('Total Sales');
        $response->assertSee('Cash / Card Split');
        $response->assertSee('Opening Float');
        $response->assertSee('Expected Cash');
        $response->assertSee('Counted Cash');
        $response->assertSee('Variance');
        $response->assertSee('Shift Status');
        $response->assertSee('Variance Status');
        $response->assertSee(number_format(50.000, 3));
        $response->assertSee(number_format(95.000, 3));
        $response->assertSee(number_format(30.000, 3));
    }
}
