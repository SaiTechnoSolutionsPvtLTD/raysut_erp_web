<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\StockBatch;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Services\StockLedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class Phase2MasterDataAndInventoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_phase2_master_data_seeded_correctly(): void
    {
        $this->assertDatabaseHas('products', ['sku_code' => 'SKU-JW-BLK-750']);
        $this->assertDatabaseHas('suppliers', ['supplier_code' => 'SUP-DIAGEO-UK']);
        $this->assertDatabaseHas('customers', ['customer_code' => 'CUST-HYATT-MCT']);
    }

    public function test_purchase_and_inventory_transaction_flow_seeded_correctly(): void
    {
        $this->assertDatabaseHas('purchase_orders', ['po_number' => 'PO-2026-0001']);
        $this->assertDatabaseHas('import_shipments', ['shipment_ref' => 'SHIP-2026-001']);
        $this->assertDatabaseHas('goods_receipt_notes', ['grn_number' => 'GRN-2026-0001']);
        $this->assertDatabaseHas('stock_batches', ['batch_number' => 'BATCH-JW2026A']);
        $this->assertDatabaseHas('duty_assessments', ['cif_value' => 3125.000]);
    }

    public function test_stock_ledger_service_operations(): void
    {
        $service = app(StockLedgerService::class);
        $wh = Warehouse::where('warehouse_code', 'WH-MCT-BOND')->firstOrFail();
        $prod = Product::where('sku_code', 'SKU-JW-BLK-750')->firstOrFail();
        $batch = StockBatch::where('batch_number', 'BATCH-JW2026A')->firstOrFail();

        $initialStock = WarehouseStock::where('warehouse_id', $wh->warehouse_id)
            ->where('product_id', $prod->product_id)
            ->where('batch_id', $batch->batch_id)
            ->firstOrFail();

        $this->assertEquals(500.00, (float) $initialStock->quantity_on_hand);

        // Test Intake
        $service->recordIntake($wh->warehouse_id, $prod->product_id, $batch->batch_id, 100.00);
        $this->assertEquals(600.00, (float) $initialStock->fresh()->quantity_on_hand);

        // Test Reserve
        $service->reserveStock($wh->warehouse_id, $prod->product_id, $batch->batch_id, 50.00);
        $stockAfterReserve = $initialStock->fresh();
        $this->assertEquals(550.00, (float) $stockAfterReserve->quantity_available);
        $this->assertEquals(50.00, (float) $stockAfterReserve->quantity_reserved);

        // Test Release
        $service->releaseReservation($wh->warehouse_id, $prod->product_id, $batch->batch_id, 20.00);
        $stockAfterRelease = $initialStock->fresh();
        $this->assertEquals(570.00, (float) $stockAfterRelease->quantity_available);
        $this->assertEquals(30.00, (float) $stockAfterRelease->quantity_reserved);

        // Test Deduct
        $service->deductStock($wh->warehouse_id, $prod->product_id, $batch->batch_id, 30.00, true);
        $stockAfterDeduct = $initialStock->fresh();
        $this->assertEquals(570.00, (float) $stockAfterDeduct->quantity_on_hand);
        $this->assertEquals(0.00, (float) $stockAfterDeduct->quantity_reserved);
    }

    public function test_authenticated_user_can_access_phase2_pages(): void
    {
        $user = User::where('email', 'admin@rmsc.om')->firstOrFail();

        $this->actingAs($user)
            ->get(route('master-data.products'))
            ->assertStatus(200);

        $this->actingAs($user)
            ->get(route('purchase-import.purchase-orders'))
            ->assertStatus(200);

        $this->actingAs($user)
            ->get(route('warehouse-inventory.stock'))
            ->assertStatus(200);
    }
}
