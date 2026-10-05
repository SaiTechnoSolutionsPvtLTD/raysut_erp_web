<?php

namespace Database\Seeders;

use App\Models\DutyAssessment;
use App\Models\GoodsReceiptItem;
use App\Models\GoodsReceiptNote;
use App\Models\ImportShipment;
use App\Models\LandedCostComponent;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\StockBatch;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockLedgerService;
use Illuminate\Database\Seeder;

class PurchaseAndInventorySeeder extends Seeder
{
    public function run(): void
    {
        $supplier = Supplier::where('supplier_code', 'SUP-DIAGEO-UK')->first();
        $warehouseBonded = Warehouse::where('warehouse_code', 'WH-MCT-BOND')->first();
        $warehouseSllBonded = Warehouse::where('warehouse_code', 'WH-SLL-DEPOT')->first();
        $warehouseSohBonded = Warehouse::where('warehouse_code', 'WH-SOH-BOND')->first();
        $warehouseMctMain = Warehouse::where('warehouse_code', 'WH-MCT-MAIN')->first();
        $userAdmin = User::where('email', 'admin@rmsc.om')->first();
        $productJW = Product::where('sku_code', 'SKU-JW-BLK-750')->first();
        $productHein = Product::where('sku_code', 'SKU-HEIN-CAN-330')->first();

        if (!$supplier || !$warehouseBonded || !$productJW) {
            return;
        }

        // 1. Purchase Order
        $po = PurchaseOrder::create([
            'po_number' => 'PO-2026-0001',
            'supplier_id' => $supplier->supplier_id,
            'warehouse_id' => $warehouseBonded->warehouse_id,
            'order_date' => '2026-01-10',
            'expected_delivery_date' => '2026-02-15',
            'currency_code' => 'OMR',
            'status' => 'APPROVED',
            'created_by' => $userAdmin?->user_id,
            'approved_by' => $userAdmin?->user_id,
        ]);

        $poItem = PurchaseOrderItem::create([
            'po_id' => $po->po_id,
            'product_id' => $productJW->product_id,
            'quantity_ordered' => 500.00,
            'quantity_received' => 500.00,
            'unit_cost' => 6.250,
        ]);

        // 2. Import Shipment
        $shipment = ImportShipment::create([
            'shipment_ref' => 'SHIP-2026-001',
            'po_id' => $po->po_id,
            'bl_container_no' => 'MSCU9812345',
            'port_of_entry' => 'Sohar Port Customs Station',
            'bayan_declaration_no' => 'BAYAN-2026-88910',
            'stage' => 'DUTY_ASSESSMENT',
            'eta_date' => '2026-02-10',
            'actual_arrival_date' => '2026-02-12',
        ]);

        // 3. Goods Receipt Note (In Bonded Duty-Free Warehouse)
        $grn = GoodsReceiptNote::create([
            'grn_number' => 'GRN-2026-0001',
            'po_id' => $po->po_id,
            'shipment_id' => $shipment->shipment_id,
            'warehouse_id' => $warehouseBonded->warehouse_id,
            'received_date' => '2026-02-14',
            'received_by' => $userAdmin?->user_id,
            'has_discrepancy' => false,
        ]);

        // 4. Stock Batch (Duty-Free Bonded)
        $batch = StockBatch::create([
            'batch_number' => 'BATCH-JW2026A',
            'product_id' => $productJW->product_id,
            'manufacture_date' => '2025-11-01',
            'expiry_date' => '2030-11-01',
            'source_grn_id' => $grn->grn_id,
            'duty_status' => 'DUTY_FREE_BONDED',
        ]);

        // 5. Goods Receipt Item
        GoodsReceiptItem::create([
            'grn_id' => $grn->grn_id,
            'po_item_id' => $poItem->po_item_id,
            'product_id' => $productJW->product_id,
            'batch_id' => $batch->batch_id,
            'quantity_received' => 500.00,
            'quantity_ordered' => 500.00,
        ]);

        // 6. Duty Assessment
        $cifValue = 3125.000; // 500 * 6.250
        $dutyRate = 100.000;
        $dutyAmount = 3125.000;
        $otherCharges = 150.000;
        $landedCost = $cifValue + $dutyAmount + $otherCharges;

        $assessment = DutyAssessment::create([
            'shipment_id' => $shipment->shipment_id,
            'cif_value' => $cifValue,
            'duty_rate_percent' => $dutyRate,
            'duty_amount' => $dutyAmount,
            'other_charges' => $otherCharges,
            'total_landed_cost' => $landedCost,
            'assessed_by' => $userAdmin?->user_id,
            'assessed_date' => '2026-02-15',
        ]);

        LandedCostComponent::create([
            'assessment_id' => $assessment->assessment_id,
            'charge_type' => 'ROP Customs Duty (100%)',
            'amount' => $dutyAmount,
        ]);
        LandedCostComponent::create([
            'assessment_id' => $assessment->assessment_id,
            'charge_type' => 'Bayan Port Handling & Demurrage',
            'amount' => 150.000,
        ]);

        // 7. Stock Intake via StockLedgerService into Duty-Free Warehouses
        $stockService = app(StockLedgerService::class);
        $stockService->recordIntake(
            warehouseId: $warehouseBonded->warehouse_id,
            productId: $productJW->product_id,
            batchId: $batch->batch_id,
            quantity: 500.00
        );

        if ($warehouseSllBonded) {
            $batchSalalah = StockBatch::create([
                'batch_number' => 'BATCH-SLL-DF01',
                'product_id' => $productJW->product_id,
                'manufacture_date' => '2025-12-01',
                'expiry_date' => '2030-12-01',
                'source_grn_id' => $grn->grn_id,
                'duty_status' => 'DUTY_FREE_BONDED',
            ]);

            $stockService->recordIntake(
                warehouseId: $warehouseSllBonded->warehouse_id,
                productId: $productJW->product_id,
                batchId: $batchSalalah->batch_id,
                quantity: 200.00
            );
        }

        if ($warehouseSohBonded && $productHein) {
            $batchSohar = StockBatch::create([
                'batch_number' => 'BATCH-SOH-DF02',
                'product_id' => $productHein->product_id,
                'manufacture_date' => '2026-01-01',
                'expiry_date' => '2028-01-01',
                'source_grn_id' => $grn->grn_id,
                'duty_status' => 'DUTY_FREE_BONDED',
            ]);

            $stockService->recordIntake(
                warehouseId: $warehouseSohBonded->warehouse_id,
                productId: $productHein->product_id,
                batchId: $batchSohar->batch_id,
                quantity: 350.00
            );
        }

        if ($warehouseMctMain) {
            $batchMain = StockBatch::create([
                'batch_number' => 'BATCH-MCT-DP01',
                'product_id' => $productJW->product_id,
                'manufacture_date' => '2025-10-01',
                'expiry_date' => '2030-10-01',
                'source_grn_id' => $grn->grn_id,
                'duty_status' => 'DUTY_PAID',
            ]);

            $stockService->recordIntake(
                warehouseId: $warehouseMctMain->warehouse_id,
                productId: $productJW->product_id,
                batchId: $batchMain->batch_id,
                quantity: 150.00
            );
        }
    }
}
