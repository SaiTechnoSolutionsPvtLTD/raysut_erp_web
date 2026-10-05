<?php

namespace Tests\Feature;

use App\Livewire\WarehouseInventory\StockOnHand;
use App\Models\Product;
use App\Models\StockBatch;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Services\StockLedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StockOnHandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_stock_on_hand_screen_renders_with_metrics_and_table(): void
    {
        $user = User::where('email', 'admin@rmsc.om')->firstOrFail();

        Livewire::actingAs($user)
            ->test(StockOnHand::class)
            ->assertStatus(200)
            ->assertSee('Total On-Hand Units')
            ->assertSee('Available For Allocation')
            ->assertSee('Reserved / On Hold')
            ->assertSee('Duty-Free Bonded Stock');
    }

    public function test_can_filter_stocks_by_search_warehouse_duty_status_and_stock_level(): void
    {
        $user = User::where('email', 'admin@rmsc.om')->firstOrFail();
        $warehouse = Warehouse::firstOrFail();

        Livewire::actingAs($user)
            ->test(StockOnHand::class)
            ->set('search', 'Steel')
            ->assertStatus(200)
            ->set('warehouse_id', (string) $warehouse->warehouse_id)
            ->assertStatus(200)
            ->set('duty_status_filter', 'DUTY_FREE_BONDED')
            ->assertStatus(200)
            ->set('stock_filter', 'available')
            ->assertStatus(200);
    }

    public function test_can_clear_filters(): void
    {
        $user = User::where('email', 'admin@rmsc.om')->firstOrFail();
        $warehouse = Warehouse::firstOrFail();

        Livewire::actingAs($user)
            ->test(StockOnHand::class)
            ->set('search', 'TestSearch')
            ->set('warehouse_id', (string) $warehouse->warehouse_id)
            ->set('duty_status_filter', 'DUTY_PAID')
            ->set('stock_filter', 'reserved')
            ->call('clearFilters')
            ->assertSet('search', '')
            ->assertSet('warehouse_id', '')
            ->assertSet('duty_status_filter', '')
            ->assertSet('stock_filter', '');
    }

    public function test_can_open_and_close_view_details_modal(): void
    {
        $user = User::where('email', 'admin@rmsc.om')->firstOrFail();
        $stock = WarehouseStock::firstOrFail();

        Livewire::actingAs($user)
            ->test(StockOnHand::class)
            ->call('openViewModal', $stock->stock_id)
            ->assertSet('showViewModal', true)
            ->assertSet('view_stock_id', $stock->stock_id)
            ->call('closeViewModal')
            ->assertSet('showViewModal', false)
            ->assertSet('view_stock_id', null);
    }

    public function test_can_reserve_stock_and_update_availabilities(): void
    {
        $user = User::where('email', 'admin@rmsc.om')->firstOrFail();
        $stock = WarehouseStock::firstOrFail();

        $initialAvailable = (float) $stock->quantity_available;
        $initialReserved = (float) $stock->quantity_reserved;

        Livewire::actingAs($user)
            ->test(StockOnHand::class)
            ->call('openActionModal', $stock->stock_id, 'reserve')
            ->set('action_quantity', '5.00')
            ->call('executeStockAction')
            ->assertHasNoErrors()
            ->assertDispatched('notify');

        $stock->refresh();
        $this->assertEquals($initialAvailable - 5.00, (float) $stock->quantity_available);
        $this->assertEquals($initialReserved + 5.00, (float) $stock->quantity_reserved);
    }

    public function test_can_release_reserved_stock(): void
    {
        $user = User::where('email', 'admin@rmsc.om')->firstOrFail();
        $stock = WarehouseStock::firstOrFail();

        // First reserve some stock
        app(StockLedgerService::class)->reserveStock(
            $stock->warehouse_id,
            $stock->product_id,
            $stock->batch_id,
            10.00
        );

        $stock->refresh();
        $reservedBefore = (float) $stock->quantity_reserved;
        $availableBefore = (float) $stock->quantity_available;

        Livewire::actingAs($user)
            ->test(StockOnHand::class)
            ->call('openActionModal', $stock->stock_id, 'release')
            ->set('action_quantity', '4.00')
            ->call('executeStockAction')
            ->assertHasNoErrors()
            ->assertDispatched('notify');

        $stock->refresh();
        $this->assertEquals($reservedBefore - 4.00, (float) $stock->quantity_reserved);
        $this->assertEquals($availableBefore + 4.00, (float) $stock->quantity_available);
    }
}
