<?php

namespace Tests\Feature;

use App\Livewire\WarehouseInventory\StockTransfers;
use App\Models\StockBatch;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StockTransfersTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Warehouse $sourceWh;
    protected Warehouse $destWh;
    protected StockBatch $batch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->user = User::where('email', 'admin@rmsc.om')->firstOrFail();
        
        $warehouses = Warehouse::all();
        $this->sourceWh = $warehouses->first();
        $this->destWh = $warehouses->skip(1)->first();

        $this->batch = StockBatch::firstOrFail();

        // Ensure stock exists in source warehouse for testing transfer intake
        WarehouseStock::updateOrCreate(
            [
                'warehouse_id' => $this->sourceWh->warehouse_id,
                'product_id' => $this->batch->product_id,
                'batch_id' => $this->batch->batch_id,
            ],
            [
                'quantity_on_hand' => 500,
                'quantity_reserved' => 0,
                'quantity_available' => 500,
            ]
        );
    }

    public function test_stock_transfers_component_renders_successfully(): void
    {
        Livewire::actingAs($this->user)
            ->test(StockTransfers::class)
            ->assertStatus(200)
            ->assertSee('Total Transfers')
            ->assertSee('In-Transit')
            ->assertSee('All Source Warehouses');
    }

    public function test_stock_transfers_can_be_filtered(): void
    {
        $transfer = StockTransfer::create([
            'transfer_number' => 'STR-TEST-99',
            'source_warehouse_id' => $this->sourceWh->warehouse_id,
            'destination_warehouse_id' => $this->destWh->warehouse_id,
            'transfer_date' => now()->format('Y-m-d'),
            'status' => 'DRAFT',
            'requested_by' => $this->user->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(StockTransfers::class)
            ->set('search', 'STR-TEST-99')
            ->assertSee('STR-TEST-99')
            ->set('search', 'NONEXISTENT')
            ->assertDontSee('STR-TEST-99')
            ->call('clearFilters')
            ->assertSet('search', '')
            ->assertSee('STR-TEST-99');
    }

    public function test_can_create_new_stock_transfer(): void
    {
        Livewire::actingAs($this->user)
            ->test(StockTransfers::class)
            ->call('openCreateModal')
            ->set('source_warehouse_id', (string) $this->sourceWh->warehouse_id)
            ->set('destination_warehouse_id', (string) $this->destWh->warehouse_id)
            ->set('transfer_date', now()->format('Y-m-d'))
            ->set('items', [
                ['batch_id' => (string) $this->batch->batch_id, 'quantity' => '50.00']
            ])
            ->call('saveTransfer')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('stock_transfers', [
            'source_warehouse_id' => $this->sourceWh->warehouse_id,
            'destination_warehouse_id' => $this->destWh->warehouse_id,
            'status' => 'DRAFT',
        ]);
    }

    public function test_can_dispatch_and_receive_stock_transfer_updating_ledgers(): void
    {
        $transfer = StockTransfer::create([
            'transfer_number' => 'STR-DISPATCH-01',
            'source_warehouse_id' => $this->sourceWh->warehouse_id,
            'destination_warehouse_id' => $this->destWh->warehouse_id,
            'transfer_date' => now()->format('Y-m-d'),
            'status' => 'DRAFT',
            'requested_by' => $this->user->id,
        ]);

        StockTransferItem::create([
            'transfer_id' => $transfer->transfer_id,
            'batch_id' => $this->batch->batch_id,
            'quantity' => 100,
        ]);

        Livewire::actingAs($this->user)
            ->test(StockTransfers::class)
            ->call('dispatchTransfer', $transfer->transfer_id)
            ->assertHasNoErrors();

        $this->assertEquals('IN_TRANSIT', $transfer->fresh()->status);

        Livewire::actingAs($this->user)
            ->test(StockTransfers::class)
            ->call('receiveTransfer', $transfer->transfer_id)
            ->assertHasNoErrors();

        $this->assertEquals('RECEIVED', $transfer->fresh()->status);

        // Source warehouse stock should be 400 (500 - 100)
        $this->assertDatabaseHas('warehouse_stock', [
            'warehouse_id' => $this->sourceWh->warehouse_id,
            'batch_id' => $this->batch->batch_id,
            'quantity_on_hand' => 400,
        ]);

        // Destination warehouse stock should be 100
        $this->assertDatabaseHas('warehouse_stock', [
            'warehouse_id' => $this->destWh->warehouse_id,
            'batch_id' => $this->batch->batch_id,
            'quantity_on_hand' => 100,
        ]);
    }
}
