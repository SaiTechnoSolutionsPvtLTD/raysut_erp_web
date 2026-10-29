<?php

namespace App\Livewire\WarehouseInventory;

use App\Models\AuditLog;
use App\Models\DutyAssessment;
use App\Models\DutyPaidConversion;
use App\Models\DutyPaidConversionItem;
use App\Models\ImportShipment;
use App\Models\NumberingSeries;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\StockBatch;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Services\StockLedgerService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class DutyPaidConversionManagement extends Component
{
    use WithPagination;

    public $search = '';
    public $showCreateModal = false;

    // Form fields
    public $bonded_warehouse_id = '';
    public $destination_warehouse_id = '';
    public $bonded_batch_id = '';
    public $quantity_converted = 1.0;
    public $bayan_declaration_no = '';
    public $duty_amount_per_unit = 0.0;
    public $notes = '';

    public function render()
    {
        $conversions = DutyPaidConversion::with([
            'warehouse',
            'dutyAssessment.shipment',
            'approver',
            'items.batch.product',
        ])
        ->when($this->search, function ($query) {
            $term = trim($this->search);
            $query->where(function ($q) use ($term) {
                $q->where('conversion_number', 'like', "%{$term}%")
                  ->orWhereHas('dutyAssessment.shipment', function ($sq) use ($term) {
                      $sq->where('bayan_declaration_no', 'like', "%{$term}%");
                  })
                  ->orWhereHas('warehouse', function ($wq) use ($term) {
                      $wq->where('warehouse_name', 'like', "%{$term}%")
                         ->orWhere('warehouse_code', 'like', "%{$term}%");
                  })
                  ->orWhereHas('items.batch.product', function ($pq) use ($term) {
                      $pq->where('product_name', 'like', "%{$term}%")
                         ->orWhere('sku_code', 'like', "%{$term}%");
                  });
            });
        })
        ->orderBy('conversion_id', 'desc')
        ->paginate(10);

        // Warehouses
        $bondedWarehouses = Warehouse::where('warehouse_type', 'DUTY_FREE_BONDED')->where('is_active', true)->get();
        $mainWarehouses = Warehouse::where('warehouse_type', 'MAIN')->where('is_active', true)->get();

        // Available Bonded Batches in selected Bonded Warehouse
        $availableBatches = collect();
        if ($this->bonded_warehouse_id) {
            $availableBatches = WarehouseStock::with(['batch.product', 'warehouse'])
                ->where('warehouse_id', $this->bonded_warehouse_id)
                ->where('quantity_available', '>', 0)
                ->whereHas('batch', function($q) {
                    $q->where('duty_status', 'DUTY_FREE_BONDED');
                })
                ->get();
        }

        return view('livewire.warehouse-inventory.duty-paid-conversion-management', [
            'conversions' => $conversions,
            'bondedWarehouses' => $bondedWarehouses,
            'mainWarehouses' => $mainWarehouses,
            'availableBatches' => $availableBatches,
        ])->layout('layouts.app');
    }

    public function openCreateModal()
    {
        $this->reset(['bonded_warehouse_id', 'destination_warehouse_id', 'bonded_batch_id', 'quantity_converted', 'bayan_declaration_no', 'duty_amount_per_unit', 'notes']);
        
        $firstBonded = Warehouse::where('warehouse_type', 'DUTY_FREE_BONDED')->first();
        $firstMain = Warehouse::where('warehouse_type', 'MAIN')->first();

        if ($firstBonded) $this->bonded_warehouse_id = $firstBonded->warehouse_id;
        if ($firstMain) $this->destination_warehouse_id = $firstMain->warehouse_id;

        $this->bayan_declaration_no = 'BAYAN-DP-' . date('Y') . '-' . rand(10000, 99999);
        $this->showCreateModal = true;
    }

    public function updatedBondedBatchId($batchId)
    {
        if ($batchId) {
            $stock = WarehouseStock::where('warehouse_id', $this->bonded_warehouse_id)
                ->where('batch_id', $batchId)
                ->first();

            if ($stock) {
                $this->quantity_converted = (float) $stock->quantity_available;
                
                // Calculate default duty estimation based on product cost (e.g., 100% duty)
                $product = $stock->product;
                if ($product) {
                    $supplierCost = $product->suppliers()->first()?->pivot?->last_purchase_cost ?? 6.25;
                    $this->duty_amount_per_unit = round($supplierCost * 1.0, 3); // 100% duty rate
                }
            }
        }
    }

    public function executeConversion()
    {
        $this->validate([
            'bonded_warehouse_id' => 'required|exists:warehouses,warehouse_id',
            'destination_warehouse_id' => 'required|exists:warehouses,warehouse_id|different:bonded_warehouse_id',
            'bonded_batch_id' => 'required|exists:stock_batches,batch_id',
            'quantity_converted' => 'required|numeric|min:0.001',
            'bayan_declaration_no' => 'required|string|max:50',
            'duty_amount_per_unit' => 'required|numeric|min:0',
        ]);

        $stock = WarehouseStock::where('warehouse_id', $this->bonded_warehouse_id)
            ->where('batch_id', $this->bonded_batch_id)
            ->firstOrFail();

        if ((float)$stock->quantity_available < (float)$this->quantity_converted) {
            $this->addError('quantity_converted', "Insufficient available stock in duty-free warehouse. Available: {$stock->quantity_available}");
            return;
        }

        DB::transaction(function () use ($stock) {
            $bondedBatch = StockBatch::findOrFail($this->bonded_batch_id);

            // Generate Ref
            $ref = 'DPC-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $totalDuty = (float)$this->quantity_converted * (float)$this->duty_amount_per_unit;

            // Link or create shipment and duty assessment for Bayan declaration
            $shipment = ImportShipment::firstOrCreate(
                ['bayan_declaration_no' => $this->bayan_declaration_no],
                [
                    'shipment_ref' => 'SHIP-DP-' . date('Ymd-His') . '-' . rand(100, 999),
                    'po_id' => PurchaseOrder::first()?->po_id ?? 1,
                    'port_of_entry' => 'Customs Clearance Station',
                    'stage' => 'CUSTOMS_CLEARANCE',
                    'eta_date' => now()->format('Y-m-d'),
                    'actual_arrival_date' => now()->format('Y-m-d'),
                ]
            );

            $assessment = DutyAssessment::firstOrCreate(
                ['shipment_id' => $shipment->shipment_id],
                [
                    'cif_value' => $totalDuty,
                    'duty_rate_percent' => 100.000,
                    'duty_amount' => $totalDuty,
                    'other_charges' => 0.000,
                    'total_landed_cost' => $totalDuty,
                    'assessed_by' => auth()->id(),
                    'assessed_date' => now()->format('Y-m-d'),
                ]
            );

            // 1. Create Duty Paid Conversion record
            $conversion = DutyPaidConversion::create([
                'conversion_number' => $ref,
                'warehouse_id' => $this->destination_warehouse_id,
                'conversion_date' => now()->format('Y-m-d'),
                'assessment_id' => $assessment->assessment_id,
                'status' => 'POSTED',
                'approved_by' => auth()->id(),
            ]);

            // 2. Create or find converted Duty-Paid batch
            $dpBatchNumber = $bondedBatch->batch_number . '-DP';
            $convertedBatch = StockBatch::firstOrCreate(
                [
                    'batch_number' => $dpBatchNumber,
                    'product_id' => $bondedBatch->product_id,
                ],
                [
                    'manufacture_date' => $bondedBatch->manufacture_date,
                    'expiry_date' => $bondedBatch->expiry_date,
                    'source_grn_id' => $bondedBatch->source_grn_id,
                    'duty_status' => 'DUTY_PAID',
                ]
            );

            // 3. Create Conversion Item line
            DutyPaidConversionItem::create([
                'conversion_id' => $conversion->conversion_id,
                'batch_id' => $bondedBatch->batch_id,
                'quantity_converted' => $this->quantity_converted,
                'duty_paid_unit_cost' => $this->duty_amount_per_unit,
            ]);

            // 4. Update Stock Ledgers
            $stockLedger = app(StockLedgerService::class);
            
            // Deduct from Duty-Free Bonded Warehouse
            $stockLedger->deductStock(
                warehouseId: $this->bonded_warehouse_id,
                productId: $bondedBatch->product_id,
                batchId: $bondedBatch->batch_id,
                quantity: $this->quantity_converted,
                fromReserved: false,
                saleType: null,
                meta: [
                    'movement_type' => 'TRANSFER_OUT',
                    'reference_type' => 'duty_paid_conversion',
                    'reference_id' => $conversion->conversion_id,
                    'reference_no' => $ref,
                    'notes' => 'Duty-paid conversion transfer to Main Warehouse (Bayan: ' . $this->bayan_declaration_no . ')',
                    'user_id' => auth()->id(),
                ]
            );

            // Intake into Destination Main Warehouse
            $stockLedger->recordIntake(
                warehouseId: $this->destination_warehouse_id,
                productId: $bondedBatch->product_id,
                batchId: $convertedBatch->batch_id,
                quantity: $this->quantity_converted,
                meta: [
                    'movement_type' => 'TRANSFER_IN',
                    'reference_type' => 'duty_paid_conversion',
                    'reference_id' => $conversion->conversion_id,
                    'reference_no' => $ref,
                    'notes' => 'Duty-paid stock received from conversion ' . $ref,
                    'user_id' => auth()->id(),
                ]
            );

            // Log Audit
            AuditLog::create([
                'user_id' => auth()->id(),
                'module_name' => 'Warehouse & Inventory',
                'action' => 'DUTY_PAID_CONVERSION',
                'record_table' => 'duty_paid_conversions',
                'record_id' => $conversion->conversion_id,
                'details_json' => [
                    'ref' => $ref,
                    'conversion_number' => $ref,
                    'source_warehouse_id' => $this->bonded_warehouse_id,
                    'destination_warehouse_id' => $this->destination_warehouse_id,
                    'quantity' => (float) $this->quantity_converted,
                    'total_duty' => (float) $totalDuty,
                    'bayan_no' => $this->bayan_declaration_no,
                ],
                'ip_address' => request()->ip(),
            ]);
        });

        $this->showCreateModal = false;
        $this->dispatch('toast', message: "Duty-Paid Conversion executed successfully! Stock transferred to Main Warehouse.", type: 'success');
    }
}
