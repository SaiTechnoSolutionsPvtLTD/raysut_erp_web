<?php

namespace Tests\Feature;

use App\Livewire\PurchaseImport\GoodsReceiptManagement;
use App\Models\GoodsReceiptNote;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GoodsReceiptManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_goods_receipt_screen_renders_with_metrics_and_table(): void
    {
        $user = User::where('email', 'admin@rmsc.om')->firstOrFail();

        Livewire::actingAs($user)
            ->test(GoodsReceiptManagement::class)
            ->assertStatus(200)
            ->assertSee('Goods Receipt Notes (GRN)')
            ->assertSee('Total GRNs Posted')
            ->assertSee('GRN-2026-0001');
    }

    public function test_can_create_new_goods_receipt_note_with_items_and_batches(): void
    {
        $user = User::where('email', 'admin@rmsc.om')->firstOrFail();
        $po = PurchaseOrder::with('items')->firstOrFail();
        $warehouse = Warehouse::firstOrFail();

        Livewire::actingAs($user)
            ->test(GoodsReceiptManagement::class)
            ->call('openCreateModal')
            ->set('po_id', (string) $po->po_id)
            ->set('warehouse_id', (string) $warehouse->warehouse_id)
            ->set('received_date', '2026-04-15')
            ->set('received_by', (string) $user->user_id)
            ->call('saveGrn')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('goods_receipt_notes', [
            'po_id' => $po->po_id,
            'warehouse_id' => $warehouse->warehouse_id,
            'received_date' => '2026-04-15 00:00:00',
        ]);
    }

    public function test_auto_flags_discrepancy_when_received_quantity_differs_from_ordered(): void
    {
        $user = User::where('email', 'admin@rmsc.om')->firstOrFail();
        $po = PurchaseOrder::with('items')->firstOrFail();
        $warehouse = Warehouse::firstOrFail();

        $component = Livewire::actingAs($user)
            ->test(GoodsReceiptManagement::class)
            ->call('openCreateModal')
            ->set('po_id', (string) $po->po_id);

        $items = $component->get('items');
        if (count($items) > 0) {
            $items[0]['quantity_received'] = (float) $items[0]['quantity_ordered'] - 10.0;
        }

        $component->set('items', $items)
            ->call('saveGrn')
            ->assertHasNoErrors();

        $lastGrn = GoodsReceiptNote::latest('grn_id')->first();
        $this->assertTrue((bool) $lastGrn->has_discrepancy);
    }

    public function test_can_view_goods_receipt_details(): void
    {
        $user = User::where('email', 'admin@rmsc.om')->firstOrFail();
        $grn = GoodsReceiptNote::firstOrFail();

        Livewire::actingAs($user)
            ->test(GoodsReceiptManagement::class)
            ->call('openViewModal', $grn->grn_id)
            ->assertSet('showViewModal', true)
            ->assertSet('view_grn_id', $grn->grn_id)
            ->assertSee($grn->grn_number);
    }

    public function test_can_edit_goods_receipt_note(): void
    {
        $user = User::where('email', 'admin@rmsc.om')->firstOrFail();
        $grn = GoodsReceiptNote::firstOrFail();

        Livewire::actingAs($user)
            ->test(GoodsReceiptManagement::class)
            ->call('openEditModal', $grn->grn_id)
            ->assertSet('isEdit', true)
            ->set('received_date', '2026-05-01')
            ->call('saveGrn')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('goods_receipt_notes', [
            'grn_id' => $grn->grn_id,
            'received_date' => '2026-05-01 00:00:00',
        ]);
    }

    public function test_can_delete_goods_receipt_note(): void
    {
        $user = User::where('email', 'admin@rmsc.om')->firstOrFail();
        $grn = GoodsReceiptNote::firstOrFail();

        Livewire::actingAs($user)
            ->test(GoodsReceiptManagement::class)
            ->call('confirmDelete', $grn->grn_id)
            ->assertSet('showItemDeleteModal', true)
            ->call('executeDeleteItem')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('goods_receipt_notes', [
            'grn_id' => $grn->grn_id,
        ]);
    }
}
