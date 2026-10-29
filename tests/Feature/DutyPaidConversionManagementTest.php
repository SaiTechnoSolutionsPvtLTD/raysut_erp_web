<?php

namespace Tests\Feature;

use App\Livewire\WarehouseInventory\DutyPaidConversionManagement;
use App\Models\AuditLog;
use App\Models\DutyPaidConversion;
use App\Models\StockBatch;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Services\StockLedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DutyPaidConversionManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_duty_paid_conversions_screen_renders_successfully(): void
    {
        $user = User::where('email', 'admin@rmsc.om')->firstOrFail();

        Livewire::actingAs($user)
            ->test(DutyPaidConversionManagement::class)
            ->assertStatus(200)
            ->assertSee('Duty-Paid Conversions')
            ->assertSee('New Conversion');
    }

    public function test_duty_paid_conversions_can_be_searched_by_conversion_number_or_bayan(): void
    {
        $user = User::where('email', 'admin@rmsc.om')->firstOrFail();

        Livewire::actingAs($user)
            ->test(DutyPaidConversionManagement::class)
            ->set('search', 'DPC-2026-0001')
            ->assertSee('DPC-2026-0001')
            ->set('search', 'BAYAN-2026-88910')
            ->assertSee('BAYAN-2026-88910')
            ->set('search', 'NON_EXISTENT_REF')
            ->assertDontSee('BAYAN-2026-88910');
    }

    public function test_can_execute_duty_paid_conversion_successfully_with_audit_log(): void
    {
        $user = User::where('email', 'admin@rmsc.om')->firstOrFail();

        $bondedWh = Warehouse::where('warehouse_code', 'WH-MCT-BOND')->firstOrFail();
        $mainWh = Warehouse::where('warehouse_code', 'WH-MCT-MAIN')->firstOrFail();

        // Create a test bonded batch with stock in bonded warehouse
        $batch = StockBatch::create([
            'batch_number' => 'BATCH-TEST-CONV-01',
            'product_id' => 1,
            'manufacture_date' => '2025-01-01',
            'expiry_date' => '2030-01-01',
            'duty_status' => 'DUTY_FREE_BONDED',
        ]);

        app(StockLedgerService::class)->recordIntake(
            warehouseId: $bondedWh->warehouse_id,
            productId: 1,
            batchId: $batch->batch_id,
            quantity: 50.0,
            meta: ['movement_type' => 'INTAKE_GRN', 'user_id' => $user->user_id]
        );

        Livewire::actingAs($user)
            ->test(DutyPaidConversionManagement::class)
            ->set('bonded_warehouse_id', (string) $bondedWh->warehouse_id)
            ->set('destination_warehouse_id', (string) $mainWh->warehouse_id)
            ->set('bonded_batch_id', (string) $batch->batch_id)
            ->set('quantity_converted', 25.0)
            ->set('bayan_declaration_no', 'BAYAN-TEST-99881')
            ->set('duty_amount_per_unit', 5.500)
            ->call('executeConversion')
            ->assertHasNoErrors()
            ->assertDispatched('toast');

        // Verify conversion record created
        $this->assertDatabaseHas('duty_paid_conversions', [
            'warehouse_id' => $mainWh->warehouse_id,
            'status' => 'POSTED',
        ]);

        // Verify Audit Log created with correct module_name and details
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->user_id,
            'module_name' => 'Warehouse & Inventory',
            'action' => 'DUTY_PAID_CONVERSION',
            'record_table' => 'duty_paid_conversions',
        ]);

        // Verify source stock deducted
        $sourceStock = WarehouseStock::where('warehouse_id', $bondedWh->warehouse_id)
            ->where('batch_id', $batch->batch_id)
            ->firstOrFail();
        $this->assertEquals(25.0, (float) $sourceStock->quantity_on_hand);

        // Verify destination stock received
        $dpBatch = StockBatch::where('batch_number', 'BATCH-TEST-CONV-01-DP')->firstOrFail();
        $destStock = WarehouseStock::where('warehouse_id', $mainWh->warehouse_id)
            ->where('batch_id', $dpBatch->batch_id)
            ->firstOrFail();
        $this->assertEquals(25.0, (float) $destStock->quantity_on_hand);
    }

    public function test_cannot_execute_conversion_with_insufficient_stock(): void
    {
        $user = User::where('email', 'admin@rmsc.om')->firstOrFail();

        $bondedWh = Warehouse::where('warehouse_code', 'WH-MCT-BOND')->firstOrFail();
        $mainWh = Warehouse::where('warehouse_code', 'WH-MCT-MAIN')->firstOrFail();

        $batch = StockBatch::create([
            'batch_number' => 'BATCH-TEST-INSUFF-01',
            'product_id' => 1,
            'manufacture_date' => '2025-01-01',
            'expiry_date' => '2030-01-01',
            'duty_status' => 'DUTY_FREE_BONDED',
        ]);

        app(StockLedgerService::class)->recordIntake(
            warehouseId: $bondedWh->warehouse_id,
            productId: 1,
            batchId: $batch->batch_id,
            quantity: 10.0,
            meta: ['movement_type' => 'INTAKE_GRN', 'user_id' => $user->user_id]
        );

        Livewire::actingAs($user)
            ->test(DutyPaidConversionManagement::class)
            ->set('bonded_warehouse_id', (string) $bondedWh->warehouse_id)
            ->set('destination_warehouse_id', (string) $mainWh->warehouse_id)
            ->set('bonded_batch_id', (string) $batch->batch_id)
            ->set('quantity_converted', 100.0) // Exceeds available 10.0
            ->set('bayan_declaration_no', 'BAYAN-TEST-99882')
            ->set('duty_amount_per_unit', 5.500)
            ->call('executeConversion')
            ->assertHasErrors(['quantity_converted']);
    }
}
