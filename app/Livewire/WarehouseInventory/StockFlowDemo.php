<?php

namespace App\Livewire\WarehouseInventory;

use App\Models\AuditLog;
use App\Models\DutyAssessment;
use App\Models\DutyPaidConversion;
use App\Models\DutyPaidConversionItem;
use App\Models\GoodsReceiptItem;
use App\Models\GoodsReceiptNote;
use App\Models\ImportShipment;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\StockBatch;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Services\StockLedgerService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class StockFlowDemo extends Component
{
    public $simulatedSteps = [];
    public $isSimulating = false;
    public $lastSimulatedRef = null;

    public function render()
    {
        // Fetch current live metrics for Phase 2 demonstration
        $dutyFreeWarehousesCount = Warehouse::where('warehouse_type', 'DUTY_FREE_BONDED')->count();
        $bondedStockTotal = WarehouseStock::whereHas('batch', function($q) {
            $q->where('duty_status', 'DUTY_FREE_BONDED');
        })->sum('quantity_available');

        $dutyPaidConversionsCount = DutyPaidConversion::count();
        $dutyPaidTotalPaid = DutyPaidConversion::sum('total_duty_paid');

        $mainWarehouseStockTotal = WarehouseStock::whereHas('warehouse', function($q) {
            $q->where('warehouse_type', 'MAIN');
        })->sum('quantity_on_hand');

        $recentConversions = DutyPaidConversion::with(['bondedWarehouse', 'destinationWarehouse', 'items.bondedBatch.product'])
            ->orderBy('conversion_id', 'desc')
            ->take(5)
            ->get();

        $warehousesList = Warehouse::withCount('stocks')->get();

        return view('livewire.warehouse-inventory.stock-flow-demo', [
            'dutyFreeWarehousesCount' => $dutyFreeWarehousesCount,
            'bondedStockTotal' => $bondedStockTotal,
            'dutyPaidConversionsCount' => $dutyPaidConversionsCount,
            'dutyPaidTotalPaid' => $dutyPaidTotalPaid,
            'mainWarehouseStockTotal' => $mainWarehouseStockTotal,
            'recentConversions' => $recentConversions,
            'warehousesList' => $warehousesList,
        ])->layout('layouts.app');
    }

    public function runEndToEndSimulation()
    {
        $this->isSimulating = true;
        $this->simulatedSteps = [];

        try {
            DB::transaction(function () {
                $user = auth()->user();
                $supplier = Supplier::first() ?? Supplier::create([
                    'supplier_code' => 'SUP-DEMO',
                    'supplier_name' => 'Demo International Spirits Ltd',
                    'country' => 'United Kingdom',
                    'currency_code' => 'GBP',
                ]);

                $bondedWh = Warehouse::where('warehouse_type', 'DUTY_FREE_BONDED')->first();
                $mainWh = Warehouse::where('warehouse_type', 'MAIN')->first();
                $product = Product::first();

                if (!$bondedWh || !$mainWh || !$product) {
                    throw new \Exception("System requirements missing for demonstration.");
                }

                $suffix = rand(1000, 9999);

                // STEP 1: Purchase Order & Inbound Container Shipment
                $poNumber = "PO-DEMO-{$suffix}";
                $po = PurchaseOrder::create([
                    'po_number' => $poNumber,
                    'supplier_id' => $supplier->supplier_id,
                    'warehouse_id' => $bondedWh->warehouse_id,
                    'order_date' => now()->format('Y-m-d'),
                    'expected_delivery_date' => now()->addDays(7)->format('Y-m-d'),
                    'currency_code' => 'OMR',
                    'status' => 'APPROVED',
                    'created_by' => $user?->user_id,
                    'approved_by' => $user?->user_id,
                ]);

                $poItem = PurchaseOrderItem::create([
                    'po_id' => $po->po_id,
                    'product_id' => $product->product_id,
                    'quantity_ordered' => 100.00,
                    'quantity_received' => 100.00,
                    'unit_cost' => 10.00,
                ]);

                $shipmentRef = "SHIP-DEMO-{$suffix}";
                $containerNo = "MSCU{$suffix}99";
                $bayanNo = "BAYAN-2026-{$suffix}";

                $shipment = ImportShipment::create([
                    'shipment_ref' => $shipmentRef,
                    'po_id' => $po->po_id,
                    'bl_container_no' => $containerNo,
                    'port_of_entry' => 'Sohar Port Customs Terminal',
                    'bayan_declaration_no' => $bayanNo,
                    'stage' => 'CUSTOMS_CLEARANCE',
                    'eta_date' => now()->format('Y-m-d'),
                    'actual_arrival_date' => now()->format('Y-m-d'),
                ]);

                $this->simulatedSteps[] = [
                    'step' => 1,
                    'title' => 'Import & Shipment Registered',
                    'desc' => "PO {$poNumber} & Container {$containerNo} arrived at Sohar Port Customs.",
                    'status' => 'completed',
                    'badge' => 'IMPORT'
                ];

                // STEP 2: Duty-Free Bonded Warehouse Ingestion
                $grnNumber = "GRN-DEMO-{$suffix}";
                $grn = GoodsReceiptNote::create([
                    'grn_number' => $grnNumber,
                    'po_id' => $po->po_id,
                    'shipment_id' => $shipment->shipment_id,
                    'warehouse_id' => $bondedWh->warehouse_id,
                    'received_date' => now()->format('Y-m-d'),
                    'received_by' => $user?->user_id,
                    'has_discrepancy' => false,
                ]);

                $batchNumber = "BATCH-DF-{$suffix}";
                $bondedBatch = StockBatch::create([
                    'batch_number' => $batchNumber,
                    'product_id' => $product->product_id,
                    'manufacture_date' => now()->subMonths(2)->format('Y-m-d'),
                    'expiry_date' => now()->addYears(3)->format('Y-m-d'),
                    'source_grn_id' => $grn->grn_id,
                    'duty_status' => 'DUTY_FREE_BONDED',
                ]);

                GoodsReceiptItem::create([
                    'grn_id' => $grn->grn_id,
                    'po_item_id' => $poItem->po_item_id,
                    'product_id' => $product->product_id,
                    'batch_id' => $bondedBatch->batch_id,
                    'quantity_received' => 100.00,
                    'quantity_ordered' => 100.00,
                ]);

                $stockLedger = app(StockLedgerService::class);
                $stockLedger->recordIntake(
                    warehouseId: $bondedWh->warehouse_id,
                    productId: $product->product_id,
                    batchId: $bondedBatch->batch_id,
                    quantity: 100.00
                );

                $this->simulatedSteps[] = [
                    'step' => 2,
                    'title' => 'Ingested to Duty-Free Warehouse',
                    'desc' => "GRN {$grnNumber} ingested 100 units into {$bondedWh->warehouse_name} (Batch: {$batchNumber}).",
                    'status' => 'completed',
                    'badge' => 'DUTY-FREE BONDED'
                ];

                // STEP 3: Duty Payment & Duty-Paid Conversion
                $cifValue = 1000.000;
                $dutyAmount = 1000.000; // 100% duty
                $totalLanded = 1100.000;

                DutyAssessment::create([
                    'shipment_id' => $shipment->shipment_id,
                    'cif_value' => $cifValue,
                    'duty_rate_percent' => 100.00,
                    'duty_amount' => $dutyAmount,
                    'other_charges' => 100.00,
                    'total_landed_cost' => $totalLanded,
                    'assessed_by' => $user?->user_id,
                    'assessed_date' => now()->format('Y-m-d'),
                ]);

                $conversionRef = "DPC-DEMO-{$suffix}";
                $conversion = DutyPaidConversion::create([
                    'conversion_number' => $conversionRef,
                    'warehouse_id' => $mainWh->warehouse_id,
                    'conversion_date' => now()->format('Y-m-d'),
                    'status' => 'POSTED',
                    'approved_by' => $user?->user_id,
                ]);

                $dpBatchNumber = "{$batchNumber}-DP";
                $convertedBatch = StockBatch::create([
                    'batch_number' => $dpBatchNumber,
                    'product_id' => $product->product_id,
                    'manufacture_date' => $bondedBatch->manufacture_date,
                    'expiry_date' => $bondedBatch->expiry_date,
                    'source_grn_id' => $bondedBatch->source_grn_id,
                    'duty_status' => 'DUTY_PAID',
                ]);

                DutyPaidConversionItem::create([
                    'conversion_id' => $conversion->conversion_id,
                    'batch_id' => $bondedBatch->batch_id,
                    'quantity_converted' => 100.00,
                    'duty_paid_unit_cost' => 10.00,
                ]);

                $this->simulatedSteps[] = [
                    'step' => 3,
                    'title' => 'Duty-Paid Conversion Executed',
                    'desc' => "ROP Bayan declaration {$bayanNo} assessed OMR {$dutyAmount} duty. Converted batch to {$dpBatchNumber}.",
                    'status' => 'completed',
                    'badge' => 'DUTY PAID'
                ];

                // STEP 4: Stock Transfer to Main Warehouse & Real-time Ledger Update
                $stockLedger->deductStock(
                    warehouseId: $bondedWh->warehouse_id,
                    productId: $product->product_id,
                    batchId: $bondedBatch->batch_id,
                    quantity: 100.00
                );

                $stockLedger->recordIntake(
                    warehouseId: $mainWh->warehouse_id,
                    productId: $product->product_id,
                    batchId: $convertedBatch->batch_id,
                    quantity: 100.00
                );

                $this->simulatedSteps[] = [
                    'step' => 4,
                    'title' => 'Main Warehouse Stock Updated',
                    'desc' => "100 units transferred into {$mainWh->warehouse_name}. Real-time inventory balance updated!",
                    'status' => 'completed',
                    'badge' => 'MAIN WAREHOUSE'
                ];

                $this->lastSimulatedRef = $conversionRef;

                AuditLog::create([
                    'user_id' => auth()->id(),
                    'module_name' => 'Warehouse & Inventory',
                    'action' => 'END_TO_END_DEMO_RUN',
                    'record_table' => 'duty_paid_conversions',
                    'record_id' => $conversion->conversion_id,
                    'details_json' => ['ref' => $conversionRef],
                    'ip_address' => request()->ip(),
                ]);
            });

            $this->dispatch('toast', message: "End-to-End Stock Flow Demonstration completed successfully!", type: 'success');
        } catch (\Exception $e) {
            $this->addError('demo', $e->getMessage());
        }

        $this->isSimulating = false;
    }
}
