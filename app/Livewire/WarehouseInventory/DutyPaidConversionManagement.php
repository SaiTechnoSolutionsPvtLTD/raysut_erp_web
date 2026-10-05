<?php

namespace App\Livewire\WarehouseInventory;

use App\Models\AuditLog;
use App\Models\DutyPaidConversion;
use App\Models\DutyPaidConversionItem;
use App\Models\NumberingSeries;
use App\Models\Product;
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
            'dutyAssessment',
            'approver',
            'items.batch.product',
        ])
        ->where('conversion_number', 'like', "%{$this->search}%")
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

            // 1. Create Duty Paid Conversion record
            $conversion = DutyPaidConversion::create([
                'conversion_number' => $ref,
                'warehouse_id' => $this->destination_warehouse_id,
                'conversion_date' => now()->format('Y-m-d'),
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
                quantity: $this->quantity_converted
            );

            // Intake into Destination Main Warehouse
            $stockLedger->recordIntake(
                warehouseId: $this->destination_warehouse_id,
                productId: $bondedBatch->product_id,
                batchId: $convertedBatch->batch_id,
                quantity: $this->quantity_converted
            );

            // Log Audit
            AuditLog::create([
                'user_id' => auth()->id(),
                'action_type' => 'DUTY_PAID_CONVERSION',
                'entity_type' => 'DutyPaidConversion',
                'entity_id' => $conversion->conversion_id,
                'details_json' => json_encode([
                    'ref' => $ref,
                    'quantity' => $this->quantity_converted,
                    'total_duty' => $totalDuty,
                    'bayan_no' => $this->bayan_declaration_no,
                ]),
                'ip_address' => request()->ip(),
            ]);
        });

        $this->showCreateModal = false;
        $this->dispatch('toast', message: "Duty-Paid Conversion executed successfully! Stock transferred to Main Warehouse.", type: 'success');
    }
}
