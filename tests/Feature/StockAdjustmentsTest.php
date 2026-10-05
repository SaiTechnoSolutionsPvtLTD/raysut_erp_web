<?php

namespace Tests\Feature;

use App\Livewire\WarehouseInventory\StockAdjustments;
use App\Models\StockAdjustment;
use App\Models\StockBatch;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StockAdjustmentsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Warehouse $warehouse;
    protected StockBatch $batch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->user = User::where('email', 'admin@rmsc.om')->firstOrFail();
        $this->warehouse = Warehouse::firstOrFail();
        $this->batch = StockBatch::firstOrFail();

        // Seed initial warehouse stock for testing physical count adjustments
        WarehouseStock::updateOrCreate(
            [
                'warehouse_id' => $this->warehouse->warehouse_id,
                'product_id' => $this->batch->product_id,
                'batch_id' => $this->batch->batch_id,
            ],
            [
                'quantity_on_hand' => 100,
                'quantity_reserved' => 0,
                'quantity_available' => 100,
            ]
        );
    }

    public function test_stock_adjustments_component_renders_successfully(): void
    {
        Livewire::actingAs($this->user)
            ->test(StockAdjustments::class)
            ->assertStatus(200)
            ->assertSee('Total Audits')
            ->assertSee('Physical Count Adjustments Register');
    }

    public function test_stock_adjustments_can_be_filtered(): void
    {
        $adj = StockAdjustment::create([
            'adjustment_number' => 'ADJ-TEST-88',
            'warehouse_id' => $this->warehouse->warehouse_id,
            'count_date' => now()->format('Y-m-d'),
            'reason_code' => 'RECOUNT',
            'approved_by' => $this->user->user_id ?? $this->user->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(StockAdjustments::class)
            ->set('search', 'ADJ-TEST-88')
            ->assertSee('ADJ-TEST-88')
            ->set('search', 'NONEXISTENT')
            ->assertDontSee('ADJ-TEST-88')
            ->call('clearFilters')
            ->assertSet('search', '')
            ->assertSee('ADJ-TEST-88');
    }

    public function test_can_create_new_stock_adjustment_and_update_inventory_ledgers(): void
    {
        Livewire::actingAs($this->user)
            ->test(StockAdjustments::class)
            ->call('openCreateModal')
            ->set('warehouse_id', (string) $this->warehouse->warehouse_id)
            ->set('count_date', now()->format('Y-m-d'))
            ->set('reason_code', 'RECOUNT')
            ->set('items', [
                [
                    'batch_id' => (string) $this->batch->batch_id,
                    'system_quantity' => '100.00',
                    'counted_quantity' => '120.00',
                    'variance_quantity' => '20.00'
                ]
            ])
            ->call('saveAdjustment')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('stock_adjustments', [
            'warehouse_id' => $this->warehouse->warehouse_id,
            'reason_code' => 'RECOUNT',
        ]);

        // Verify warehouse stock ledger was updated to 120 (100 + 20 surplus)
        $this->assertDatabaseHas('warehouse_stock', [
            'warehouse_id' => $this->warehouse->warehouse_id,
            'batch_id' => $this->batch->batch_id,
            'quantity_on_hand' => 120,
        ]);
    }

    public function test_can_open_and_close_view_details_modal(): void
    {
        $adj = StockAdjustment::create([
            'adjustment_number' => 'ADJ-VIEW-77',
            'warehouse_id' => $this->warehouse->warehouse_id,
            'count_date' => now()->format('Y-m-d'),
            'reason_code' => 'BREAKAGE',
            'approved_by' => $this->user->user_id ?? $this->user->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(StockAdjustments::class)
            ->call('openViewModal', $adj->adjustment_id)
            ->assertSet('showViewModal', true)
            ->assertSet('view_adjustment_id', $adj->adjustment_id)
            ->call('closeViewModal')
            ->assertSet('showViewModal', false)
            ->assertSet('view_adjustment_id', null);
    }
}
