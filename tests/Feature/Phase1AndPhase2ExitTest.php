<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\DutyAssessment;
use App\Models\DutyPaidConversion;
use App\Models\DutyPaidConversionItem;
use App\Models\GoodsReceiptItem;
use App\Models\GoodsReceiptNote;
use App\Models\ImportShipment;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Role;
use App\Models\StockBatch;
use App\Models\Supplier;
use App\Models\TaxVatRate;
use App\Models\Uom;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Services\StockLedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase1AndPhase2ExitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /** @test */
    public function phase_1_foundation_and_system_setup_is_fully_configured()
    {
        // 1. Company Setup
        $company = Company::first();
        $this->assertNotNull($company, 'Company setup must exist.');
        $this->assertEquals('Raysut Marketing & Supply Co. LLC (RMSC)', $company->company_name);

        // 2. Branch Setup
        $branches = Branch::all();
        $this->assertGreaterThanOrEqual(3, $branches->count(), 'At least 3 branches must be created.');

        // 3. Warehouses Setup: Verify 3 Duty-Free Warehouses exist!
        $dutyFreeWarehouses = Warehouse::where('warehouse_type', 'DUTY_FREE_BONDED')->get();
        $this->assertGreaterThanOrEqual(3, $dutyFreeWarehouses->count(), 'Must have at least 3 Duty-Free Bonded Warehouses (Muscat, Salalah, Sohar).');

        $mainWarehouses = Warehouse::where('warehouse_type', 'MAIN')->get();
        $this->assertGreaterThanOrEqual(1, $mainWarehouses->count(), 'Must have Main Warehouse(s).');

        // 4. User & Role Creation (RBAC)
        $superAdminUser = User::where('email', 'admin@rmsc.om')->first();
        $this->assertNotNull($superAdminUser);
        $this->assertTrue($superAdminUser->roles->contains('role_name', 'Super Admin'));

        // 5. Core Master Data: Products, Customers, Suppliers, Tax/VAT, UOM
        $this->assertGreaterThan(0, Product::count(), 'Products must exist.');
        $this->assertGreaterThan(0, Customer::count(), 'Customers must exist.');
        $this->assertGreaterThan(0, Supplier::count(), 'Suppliers must exist.');
        $this->assertGreaterThan(0, TaxVatRate::count(), 'Tax/VAT Rates must exist.');
        $this->assertGreaterThan(0, Uom::count(), 'Units of Measure must exist.');
    }

    /** @test */
    public function phase_2_end_to_end_stock_flow_import_to_duty_free_to_duty_paid_to_main_warehouse()
    {
        $user = User::where('email', 'admin@rmsc.om')->first();
        $this->actingAs($user);

        $supplier = Supplier::first();
        $bondedWarehouse = Warehouse::where('warehouse_type', 'DUTY_FREE_BONDED')->first();
        $mainWarehouse = Warehouse::where('warehouse_type', 'MAIN')->first();
        $product = Product::first();

        // 1. Create Purchase Order (Procurement)
        $po = PurchaseOrder::create([
            'po_number' => 'PO-TEST-001',
            'supplier_id' => $supplier->supplier_id,
            'warehouse_id' => $bondedWarehouse->warehouse_id,
            'order_date' => now()->format('Y-m-d'),
            'expected_delivery_date' => now()->addDays(5)->format('Y-m-d'),
            'currency_code' => 'OMR',
            'status' => 'APPROVED',
            'created_by' => $user->user_id,
        ]);

        $poItem = PurchaseOrderItem::create([
            'po_id' => $po->po_id,
            'product_id' => $product->product_id,
            'quantity_ordered' => 200.00,
            'quantity_received' => 200.00,
            'unit_cost' => 15.00,
        ]);

        // 2. Import Shipment & Customs Declaration
        $shipment = ImportShipment::create([
            'shipment_ref' => 'SHIP-TEST-001',
            'po_id' => $po->po_id,
            'bl_container_no' => 'MSCU7788990',
            'port_of_entry' => 'Sohar Port Customs Station',
            'bayan_declaration_no' => 'BAYAN-2026-TEST',
            'stage' => 'DUTY_ASSESSMENT',
            'eta_date' => now()->format('Y-m-d'),
            'actual_arrival_date' => now()->format('Y-m-d'),
        ]);

        // 3. Goods Receipt into 3 Duty-Free Bonded Warehouses
        $grn = GoodsReceiptNote::create([
            'grn_number' => 'GRN-TEST-001',
            'po_id' => $po->po_id,
            'shipment_id' => $shipment->shipment_id,
            'warehouse_id' => $bondedWarehouse->warehouse_id,
            'received_date' => now()->format('Y-m-d'),
            'received_by' => $user->user_id,
        ]);

        $bondedBatch = StockBatch::create([
            'batch_number' => 'BATCH-TEST-BONDED',
            'product_id' => $product->product_id,
            'manufacture_date' => now()->subMonth()->format('Y-m-d'),
            'expiry_date' => now()->addYears(2)->format('Y-m-d'),
            'source_grn_id' => $grn->grn_id,
            'duty_status' => 'DUTY_FREE_BONDED',
        ]);

        GoodsReceiptItem::create([
            'grn_id' => $grn->grn_id,
            'po_item_id' => $poItem->po_item_id,
            'product_id' => $product->product_id,
            'batch_id' => $bondedBatch->batch_id,
            'quantity_received' => 200.00,
            'quantity_ordered' => 200.00,
        ]);

        $stockLedger = app(StockLedgerService::class);
        $stockLedger->recordIntake(
            warehouseId: $bondedWarehouse->warehouse_id,
            productId: $product->product_id,
            batchId: $bondedBatch->batch_id,
            quantity: 200.00
        );

        // Verify stock is held in Duty-Free Bonded Warehouse
        $bondedStock = WarehouseStock::where('warehouse_id', $bondedWarehouse->warehouse_id)
            ->where('batch_id', $bondedBatch->batch_id)
            ->first();

        $this->assertEquals(200.00, (float)$bondedStock->quantity_available);

        // 4. Execute Duty-Paid Conversion (Customs Clearance ➔ Main Warehouse Transfer)
        $conversion = DutyPaidConversion::create([
            'conversion_number' => 'DPC-TEST-001',
            'warehouse_id' => $mainWarehouse->warehouse_id,
            'conversion_date' => now()->format('Y-m-d'),
            'status' => 'POSTED',
            'approved_by' => $user->user_id,
        ]);

        $dpBatch = StockBatch::create([
            'batch_number' => 'BATCH-TEST-DUTYPAID',
            'product_id' => $product->product_id,
            'manufacture_date' => $bondedBatch->manufacture_date,
            'expiry_date' => $bondedBatch->expiry_date,
            'source_grn_id' => $bondedBatch->source_grn_id,
            'duty_status' => 'DUTY_PAID',
        ]);

        DutyPaidConversionItem::create([
            'conversion_id' => $conversion->conversion_id,
            'batch_id' => $bondedBatch->batch_id,
            'quantity_converted' => 200.00,
            'duty_paid_unit_cost' => 15.00,
        ]);

        // Transfer stock ledgers: deduct from Bonded, add to Main
        $stockLedger->deductStock($bondedWarehouse->warehouse_id, $product->product_id, $bondedBatch->batch_id, 200.00);
        $stockLedger->recordIntake($mainWarehouse->warehouse_id, $product->product_id, $dpBatch->batch_id, 200.00);

        // 5. Verify Final End-to-End Stock Ledger Balances
        $bondedStockAfter = WarehouseStock::where('warehouse_id', $bondedWarehouse->warehouse_id)
            ->where('batch_id', $bondedBatch->batch_id)
            ->first();
        $this->assertEquals(0.00, (float)$bondedStockAfter->quantity_available);

        $mainStock = WarehouseStock::where('warehouse_id', $mainWarehouse->warehouse_id)
            ->where('batch_id', $dpBatch->batch_id)
            ->first();
        $this->assertEquals(200.00, (float)$mainStock->quantity_on_hand);
    }
}
