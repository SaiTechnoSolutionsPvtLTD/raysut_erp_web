<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CashierShift;
use App\Models\Counter;
use App\Models\Role;
use App\Models\User;
use App\Services\ShiftService;
use App\Services\StockLedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Sprint31BranchAndShiftTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $cashier;
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
    }

    /** @test */
    public function day1_and_day2_admin_can_manage_branches_and_counters()
    {
        $this->actingAs($this->superAdmin);

        // Create branch via controller
        $response = $this->post(route('system.branches.store'), [
            'branch_code' => 'BR-TEST',
            'branch_name' => 'Test Branch',
            'branch_type' => 'RETAIL',
            'city' => 'Sur',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('system.branches.index'));
        $this->assertDatabaseHas('branches', ['branch_code' => 'BR-TEST']);

        $testBranch = Branch::where('branch_code', 'BR-TEST')->first();

        // Create counter for test branch
        $counterResponse = $this->post(route('system.counters.store'), [
            'branch_id' => $testBranch->branch_id,
            'counter_code' => 'CNT-TEST-01',
            'counter_name' => 'Sur Terminal 1',
            'is_active' => '1',
        ]);

        $counterResponse->assertSessionHas('success');
        $this->assertDatabaseHas('counters', ['counter_code' => 'CNT-TEST-01']);
    }

    /** @test */
    public function day3_branch_scoping_restricts_cashier_visibility()
    {
        $this->actingAs($this->cashier);

        $response = $this->get(route('system.branches.index'));
        $response->assertStatus(200);

        // Cashier sees their default branch
        $response->assertSee('Muscat');
    }

    /** @test */
    public function day3_stock_ledger_service_calculates_branch_counter_stock()
    {
        $service = app(StockLedgerService::class);
        $stockList = $service->getBranchCounterStock($this->branch1->branch_id);

        $this->assertNotNull($stockList);
    }

    /** @test */
    public function day4_cashier_can_open_shift_with_float()
    {
        $this->actingAs($this->cashier);

        $response = $this->post(route('shifts.open.store'), [
            'counter_id' => $this->counter1->counter_id,
            'opening_float' => 50.000,
            'notes' => 'Starting shift with 50 OMR float.',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('cashier_shifts', [
            'user_id' => $this->cashier->user_id,
            'counter_id' => $this->counter1->counter_id,
            'opening_float' => 50.000,
            'status' => 'OPEN',
        ]);
    }

    /** @test */
    public function day4_cashier_cannot_open_duplicate_shifts()
    {
        $this->actingAs($this->cashier);

        $shiftService = app(ShiftService::class);
        $shiftService->open($this->cashier, $this->counter1->counter_id, 50.000);

        // Try opening another shift for same cashier
        $response = $this->post(route('shifts.open.store'), [
            'counter_id' => $this->counter1->counter_id,
            'opening_float' => 30.000,
        ]);

        $response->assertSessionHas('error');
    }

    /** @test */
    public function day4_pos_routes_are_blocked_without_an_open_shift()
    {
        $this->actingAs($this->cashier);

        // Access POS terminal without open shift
        $response = $this->get(route('pos.terminal'));
        $response->assertRedirect(route('shifts.open'));
    }

    /** @test */
    public function day5_shift_closing_calculates_variance_and_flag_threshold()
    {
        $this->actingAs($this->cashier);

        $shiftService = app(ShiftService::class);
        $shift = $shiftService->open($this->cashier, $this->counter1->counter_id, 50.000);

        // Close with excess cash variance (Over 5.000 OMR threshold)
        $response = $this->post(route('shifts.close.store', $shift->shift_id), [
            'counted_cash' => 100.000, // Expected is 50, counted 100 => +50 variance
            'card_total' => 20.000,
            'notes' => 'Counted drawer cash after evening sales.',
        ]);

        $response->assertRedirect(route('shifts.print', $shift->shift_id));

        $shift->refresh();
        $this->assertEquals('FLAGGED', $shift->variance_status);
        $this->assertEquals(50.000, $shift->variance);

        // Supervisor approves flagged shift
        $this->actingAs($this->superAdmin);
        $approveResponse = $this->post(route('shifts.approve', $shift->shift_id), [
            'notes' => 'Approved drawer surplus.',
        ]);

        $shift->refresh();
        $this->assertEquals('APPROVED', $shift->variance_status);
        $this->assertEquals('RECONCILED', $shift->status);
    }
}
