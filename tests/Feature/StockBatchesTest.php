<?php

namespace Tests\Feature;

use App\Livewire\WarehouseInventory\StockBatches;
use App\Models\Product;
use App\Models\StockBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StockBatchesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_stock_batches_screen_renders_with_metrics_and_table(): void
    {
        $user = User::where('email', 'admin@rmsc.om')->firstOrFail();

        Livewire::actingAs($user)
            ->test(StockBatches::class)
            ->assertStatus(200)
            ->assertSee('Total Registered Batches')
            ->assertSee('Active Batches In Stock')
            ->assertSee('Expiring Soon (30 Days)')
            ->assertSee('Duty-Free Bonded Batches');
    }

    public function test_can_filter_batches_by_search_duty_status_expiry_and_product(): void
    {
        $user = User::where('email', 'admin@rmsc.om')->firstOrFail();
        $product = Product::firstOrFail();

        Livewire::actingAs($user)
            ->test(StockBatches::class)
            ->set('search', 'BATCH')
            ->assertStatus(200)
            ->set('duty_status_filter', 'DUTY_FREE_BONDED')
            ->assertStatus(200)
            ->set('expiry_filter', 'valid')
            ->assertStatus(200)
            ->set('product_id_filter', (string) $product->product_id)
            ->assertStatus(200);
    }

    public function test_can_clear_filters(): void
    {
        $user = User::where('email', 'admin@rmsc.om')->firstOrFail();
        $product = Product::firstOrFail();

        Livewire::actingAs($user)
            ->test(StockBatches::class)
            ->set('search', 'TestFilter')
            ->set('duty_status_filter', 'DUTY_PAID')
            ->set('expiry_filter', 'expiring_soon')
            ->set('product_id_filter', (string) $product->product_id)
            ->call('clearFilters')
            ->assertSet('search', '')
            ->assertSet('duty_status_filter', '')
            ->assertSet('expiry_filter', '')
            ->assertSet('product_id_filter', '');
    }

    public function test_can_create_new_stock_batch(): void
    {
        $user = User::where('email', 'admin@rmsc.om')->firstOrFail();
        $product = Product::firstOrFail();
        $batchNum = 'BATCH-TEST-' . rand(1000, 9999);

        Livewire::actingAs($user)
            ->test(StockBatches::class)
            ->call('openCreateModal')
            ->set('batch_number', $batchNum)
            ->set('product_id', (string) $product->product_id)
            ->set('manufacture_date', '2026-01-01')
            ->set('expiry_date', '2028-01-01')
            ->set('duty_status', 'DUTY_FREE_BONDED')
            ->call('saveBatch')
            ->assertHasNoErrors()
            ->assertDispatched('notify');

        $this->assertDatabaseHas('stock_batches', [
            'batch_number' => $batchNum,
            'product_id' => $product->product_id,
            'duty_status' => 'DUTY_FREE_BONDED',
        ]);
    }

    public function test_can_edit_stock_batch(): void
    {
        $user = User::where('email', 'admin@rmsc.om')->firstOrFail();
        $batch = StockBatch::firstOrFail();

        $updatedBatchNum = 'BATCH-EDITED-' . rand(1000, 9999);

        Livewire::actingAs($user)
            ->test(StockBatches::class)
            ->call('openEditModal', $batch->batch_id)
            ->assertSet('isEdit', true)
            ->set('batch_number', $updatedBatchNum)
            ->set('duty_status', 'DUTY_PAID')
            ->call('saveBatch')
            ->assertHasNoErrors()
            ->assertDispatched('notify');

        $this->assertDatabaseHas('stock_batches', [
            'batch_id' => $batch->batch_id,
            'batch_number' => $updatedBatchNum,
            'duty_status' => 'DUTY_PAID',
        ]);
    }

    public function test_can_open_and_close_view_details_modal(): void
    {
        $user = User::where('email', 'admin@rmsc.om')->firstOrFail();
        $batch = StockBatch::firstOrFail();

        Livewire::actingAs($user)
            ->test(StockBatches::class)
            ->call('openViewModal', $batch->batch_id)
            ->assertSet('showViewModal', true)
            ->assertSet('view_batch_id', $batch->batch_id)
            ->call('closeViewModal')
            ->assertSet('showViewModal', false)
            ->assertSet('view_batch_id', null);
    }
}
