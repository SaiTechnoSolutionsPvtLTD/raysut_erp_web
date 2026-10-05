<?php

namespace Tests\Feature;

use App\Livewire\PurchaseImport\PurchaseOrderManagement;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PurchaseOrderManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_purchase_orders_screen_renders_with_metrics_and_table(): void
    {
        $user = User::where('email', 'admin@rmsc.om')->firstOrFail();

        Livewire::actingAs($user)
            ->test(PurchaseOrderManagement::class)
            ->assertStatus(200)
            ->assertSee('Total POs')
            ->assertSee('PO-2026-0001');
    }

    public function test_currency_auto_populates_when_supplier_is_selected(): void
    {
        $user = User::where('email', 'admin@rmsc.om')->firstOrFail();
        $supplier = Supplier::where('supplier_code', 'SUP-DIAGEO-UK')->firstOrFail();

        Livewire::actingAs($user)
            ->test(PurchaseOrderManagement::class)
            ->call('openCreateModal')
            ->set('supplier_id', (string)$supplier->supplier_id)
            ->assertSet('currency_code', $supplier->currency_code);
    }

    public function test_can_create_purchase_order_with_line_items(): void
    {
        $user = User::where('email', 'admin@rmsc.om')->firstOrFail();
        $supplier = Supplier::firstOrFail();
        $warehouse = Warehouse::firstOrFail();
        $product = Product::firstOrFail();

        Livewire::actingAs($user)
            ->test(PurchaseOrderManagement::class)
            ->call('openCreateModal')
            ->set('supplier_id', (string)$supplier->supplier_id)
            ->set('warehouse_id', (string)$warehouse->warehouse_id)
            ->set('order_date', '2026-03-01')
            ->set('expected_delivery_date', '2026-03-25')
            ->set('items', [
                [
                    'po_item_id' => null,
                    'product_id' => (string)$product->product_id,
                    'quantity_ordered' => 250,
                    'quantity_received' => 0,
                    'unit_cost' => 12.500,
                ]
            ])
            ->call('savePurchaseOrder')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('purchase_orders', [
            'supplier_id' => $supplier->supplier_id,
            'warehouse_id' => $warehouse->warehouse_id,
            'status' => 'DRAFT',
        ]);

        $this->assertDatabaseHas('purchase_order_items', [
            'product_id' => $product->product_id,
            'quantity_ordered' => 250,
            'unit_cost' => 12.500,
        ]);
    }

    public function test_can_quick_approve_draft_purchase_order(): void
    {
        $user = User::where('email', 'admin@rmsc.om')->firstOrFail();
        $po = PurchaseOrder::where('status', '!=', 'APPROVED')->first();
        if (!$po) {
            $po = PurchaseOrder::firstOrFail();
            $po->status = 'DRAFT';
            $po->save();
        }

        Livewire::actingAs($user)
            ->test(PurchaseOrderManagement::class)
            ->call('quickApprovePO', $po->po_id);

        $this->assertEquals('APPROVED', $po->fresh()->status);
        $this->assertNotNull($po->fresh()->approved_by);
    }

    public function test_can_delete_unlinked_purchase_order(): void
    {
        $user = User::where('email', 'admin@rmsc.om')->firstOrFail();
        $supplier = Supplier::firstOrFail();
        $warehouse = Warehouse::firstOrFail();

        $po = PurchaseOrder::create([
            'po_number' => 'PO-TEST-DEL-01',
            'supplier_id' => $supplier->supplier_id,
            'warehouse_id' => $warehouse->warehouse_id,
            'order_date' => '2026-03-01',
            'currency_code' => 'OMR',
            'status' => 'DRAFT',
            'created_by' => $user->user_id,
        ]);

        Livewire::actingAs($user)
            ->test(PurchaseOrderManagement::class)
            ->call('deletePurchaseOrder', $po->po_id);

        $this->assertDatabaseMissing('purchase_orders', [
            'po_id' => $po->po_id,
        ]);
    }

    public function test_expected_delivery_date_must_be_on_or_after_order_date(): void
    {
        $user = User::where('email', 'admin@rmsc.om')->firstOrFail();
        $supplier = Supplier::firstOrFail();
        $warehouse = Warehouse::firstOrFail();
        $product = Product::firstOrFail();

        // Should fail when expected_delivery_date is before order_date
        Livewire::actingAs($user)
            ->test(PurchaseOrderManagement::class)
            ->call('openCreateModal')
            ->set('supplier_id', (string)$supplier->supplier_id)
            ->set('warehouse_id', (string)$warehouse->warehouse_id)
            ->set('order_date', '2026-03-15')
            ->set('expected_delivery_date', '2026-03-10')
            ->set('items', [
                [
                    'po_item_id' => null,
                    'product_id' => (string)$product->product_id,
                    'quantity_ordered' => 10,
                    'quantity_received' => 0,
                    'unit_cost' => 5.000,
                ]
            ])
            ->call('savePurchaseOrder')
            ->assertHasErrors(['expected_delivery_date']);

        // Should succeed when expected_delivery_date is on or after order_date
        Livewire::actingAs($user)
            ->test(PurchaseOrderManagement::class)
            ->call('openCreateModal')
            ->set('supplier_id', (string)$supplier->supplier_id)
            ->set('warehouse_id', (string)$warehouse->warehouse_id)
            ->set('order_date', '2026-03-15')
            ->set('expected_delivery_date', '2026-03-20')
            ->set('items', [
                [
                    'po_item_id' => null,
                    'product_id' => (string)$product->product_id,
                    'quantity_ordered' => 10,
                    'quantity_received' => 0,
                    'unit_cost' => 5.000,
                ]
            ])
            ->call('savePurchaseOrder')
            ->assertHasNoErrors();
    }

    public function test_can_view_purchase_order_details(): void
    {
        $user = User::where('email', 'admin@rmsc.om')->firstOrFail();
        $po = PurchaseOrder::firstOrFail();

        Livewire::actingAs($user)
            ->test(PurchaseOrderManagement::class)
            ->call('viewPurchaseOrder', $po->po_id)
            ->assertSet('showViewModal', true)
            ->assertSet('view_po_id', $po->po_id)
            ->assertSee($po->po_number)
            ->assertSee('Ordered Inventory Items');
    }

    public function test_quick_ship_po_creates_import_shipment(): void
    {
        $user = User::where('email', 'admin@rmsc.om')->firstOrFail();
        $supplier = Supplier::firstOrFail();
        $warehouse = Warehouse::firstOrFail();

        $po = PurchaseOrder::create([
            'po_number' => 'PO-TEST-SHIP-99',
            'supplier_id' => $supplier->supplier_id,
            'warehouse_id' => $warehouse->warehouse_id,
            'order_date' => '2026-03-01',
            'expected_delivery_date' => '2026-03-25',
            'currency_code' => 'OMR',
            'status' => 'APPROVED',
            'created_by' => $user->user_id,
        ]);

        Livewire::actingAs($user)
            ->test(PurchaseOrderManagement::class)
            ->call('quickShipPO', $po->po_id);

        $this->assertEquals('SHIPPED', $po->fresh()->status);
        $this->assertDatabaseHas('import_shipments', [
            'po_id' => $po->po_id,
            'stage' => 'IN_TRANSIT',
        ]);
    }
}
