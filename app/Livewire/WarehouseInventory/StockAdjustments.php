<?php

namespace App\Livewire\WarehouseInventory;

use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\StockBatch;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Services\StockLedgerService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class StockAdjustments extends Component
{
    use WithPagination;

    // Filter Properties
    public string $search = '';
    public string $warehouse_id_filter = '';
    public string $reason_code_filter = '';
    public string $variance_type_filter = '';

    // Create / Edit Modal State
    public bool $showModal = false;
    public bool $isEdit = false;
    public ?int $editing_adjustment_id = null;

    // View Details Modal State
    public bool $showViewModal = false;
    public ?int $view_adjustment_id = null;

    // Form fields
    public string $adjustment_number = '';
    public string $warehouse_id = '';
    public string $count_date = '';
    public string $reason_code = 'RECOUNT';
    public string $approved_by = '';

    // Line Items array: [['batch_id' => '', 'system_quantity' => 0.00, 'counted_quantity' => 0.00, 'variance_quantity' => 0.00]]
    public array $items = [];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingWarehouseIdFilter(): void
    {
        $this->resetPage();
    }

    public function updatingReasonCodeFilter(): void
    {
        $this->resetPage();
    }

    public function updatingVarianceTypeFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->warehouse_id_filter = '';
        $this->reason_code_filter = '';
        $this->variance_type_filter = '';
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->resetValidation();
        $this->isEdit = false;
        $this->editing_adjustment_id = null;

        $this->adjustment_number = 'ADJ-' . date('Y') . '-' . strtoupper(substr(md5(microtime()), 0, 5));
        $firstWarehouse = Warehouse::orderBy('warehouse_name')->first();
        $this->warehouse_id = $firstWarehouse ? (string) $firstWarehouse->warehouse_id : '';
        $this->count_date = now()->format('Y-m-d');
        $this->reason_code = 'RECOUNT';
        $this->approved_by = (string) Auth::id();

        $this->items = [
            ['batch_id' => '', 'system_quantity' => '0.00', 'counted_quantity' => '0.00', 'variance_quantity' => '0.00']
        ];

        $this->showModal = true;
    }

    public function updatedWarehouseId(): void
    {
        // Re-fetch system quantities for selected batch IDs when warehouse changes
        foreach ($this->items as $index => $item) {
            if (!empty($item['batch_id'])) {
                $this->updateItemSystemQuantity($index, $item['batch_id']);
            }
        }
    }

    public function updatedItems($value, $key): void
    {
        // Example key: "0.batch_id" or "0.counted_quantity"
        $parts = explode('.', $key);
        if (count($parts) === 2) {
            $index = (int) $parts[0];
            $field = $parts[1];

            if ($field === 'batch_id' && !empty($value)) {
                $this->updateItemSystemQuantity($index, $value);
            }

            if ($field === 'counted_quantity' || $field === 'batch_id') {
                $sysQty = (float) ($this->items[$index]['system_quantity'] ?? 0);
                $cntQty = (float) ($this->items[$index]['counted_quantity'] ?? 0);
                $this->items[$index]['variance_quantity'] = number_format($cntQty - $sysQty, 2, '.', '');
            }
        }
    }

    private function updateItemSystemQuantity(int $index, string $batchId): void
    {
        if (!$this->warehouse_id || !$batchId) {
            $this->items[$index]['system_quantity'] = '0.00';
            return;
        }

        $stock = WarehouseStock::where('warehouse_id', $this->warehouse_id)
            ->where('batch_id', $batchId)
            ->first();

        $sysQty = $stock ? (float) $stock->quantity_on_hand : 0.00;
        $this->items[$index]['system_quantity'] = number_format($sysQty, 2, '.', '');
        
        $cntQty = (float) ($this->items[$index]['counted_quantity'] ?? 0);
        $this->items[$index]['variance_quantity'] = number_format($cntQty - $sysQty, 2, '.', '');
    }

    public function addItem(): void
    {
        $this->items[] = [
            'batch_id' => '',
            'system_quantity' => '0.00',
            'counted_quantity' => '0.00',
            'variance_quantity' => '0.00'
        ];
    }

    public function removeItem(int $index): void
    {
        if (count($this->items) > 1) {
            unset($this->items[$index]);
            $this->items = array_values($this->items);
        }
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->editing_adjustment_id = null;
    }

    public function saveAdjustment(): void
    {
        $this->validate([
            'adjustment_number' => 'required|string|max:30',
            'warehouse_id' => 'required|exists:warehouses,warehouse_id',
            'count_date' => 'required|date',
            'reason_code' => 'required|string|max:50',
            'items' => 'required|array|min:1',
            'items.*.batch_id' => 'required|exists:stock_batches,batch_id',
            'items.*.counted_quantity' => 'required|numeric|min:0',
        ], [
            'items.min' => 'Please add at least one line item to adjust.',
            'items.*.batch_id.required' => 'Please select a batch for all line items.',
            'items.*.counted_quantity.required' => 'Physical count quantity is required.',
        ]);

        try {
            $stockService = app(StockLedgerService::class);

            DB::transaction(function () use ($stockService) {
                $adjustment = StockAdjustment::create([
                    'adjustment_number' => $this->adjustment_number,
                    'warehouse_id' => $this->warehouse_id,
                    'count_date' => $this->count_date,
                    'reason_code' => $this->reason_code,
                    'approved_by' => Auth::id(),
                ]);

                foreach ($this->items as $item) {
                    $batch = StockBatch::find($item['batch_id']);
                    $sysQty = (float) $item['system_quantity'];
                    $cntQty = (float) $item['counted_quantity'];
                    $variance = $cntQty - $sysQty;

                    StockAdjustmentItem::create([
                        'adjustment_id' => $adjustment->adjustment_id,
                        'batch_id' => $item['batch_id'],
                        'system_quantity' => $sysQty,
                        'counted_quantity' => $cntQty,
                        'variance_quantity' => $variance,
                    ]);

                    // Execute live ledger adjustment for warehouse stock
                    if ($batch) {
                        $stockService->adjustStock(
                            warehouseId: (int) $this->warehouse_id,
                            productId: $batch->product_id,
                            batchId: $batch->batch_id,
                            newOnHandsQuantity: $cntQty
                        );
                    }
                }

                $this->dispatch('notify', type: 'success', title: 'Stock Adjustment Posted', message: "Physical count voucher {$adjustment->adjustment_number} posted & stock ledgers updated.");
            });

            $this->closeModal();
        } catch (Exception $e) {
            $this->addError('items', 'Failed to save stock adjustment: ' . $e->getMessage());
        }
    }

    public function openViewModal(int $adjustmentId): void
    {
        $this->view_adjustment_id = $adjustmentId;
        $this->showViewModal = true;
    }

    public function closeViewModal(): void
    {
        $this->showViewModal = false;
        $this->view_adjustment_id = null;
    }

    public function render()
    {
        // Calculate Metrics
        $totalAdjustments = StockAdjustment::count();
        $totalVarianceUnits = (float) StockAdjustmentItem::sum('variance_quantity');
        $surplusCount = StockAdjustmentItem::where('variance_quantity', '>', 0)->count();
        $deficitCount = StockAdjustmentItem::where('variance_quantity', '<', 0)->count();

        // Query Adjustments List
        $adjustments = StockAdjustment::with(['warehouse', 'approver', 'items.batch.product.uom'])
            ->when($this->search, function ($q) {
                $s = '%' . $this->search . '%';
                return $q->where('adjustment_number', 'like', $s)
                    ->orWhere('reason_code', 'like', $s)
                    ->orWhereHas('warehouse', fn($wq) => $wq->where('warehouse_name', 'like', $s)->orWhere('warehouse_code', 'like', $s))
                    ->orWhereHas('items.batch', fn($bq) => $bq->where('batch_number', 'like', $s)->orWhereHas('product', fn($pq) => $pq->where('product_name', 'like', $s)));
            })
            ->when($this->warehouse_id_filter, function ($q) {
                return $q->where('warehouse_id', $this->warehouse_id_filter);
            })
            ->when($this->reason_code_filter, function ($q) {
                return $q->where('reason_code', $this->reason_code_filter);
            })
            ->when($this->variance_type_filter, function ($q) {
                if ($this->variance_type_filter === 'surplus') {
                    return $q->whereHas('items', fn($iq) => $iq->where('variance_quantity', '>', 0));
                } elseif ($this->variance_type_filter === 'deficit') {
                    return $q->whereHas('items', fn($iq) => $iq->where('variance_quantity', '<', 0));
                } elseif ($this->variance_type_filter === 'exact') {
                    return $q->whereHas('items', fn($iq) => $iq->where('variance_quantity', 0));
                }
            })
            ->orderBy('adjustment_id', 'desc')
            ->paginate(10);

        $warehouses = Warehouse::select(['warehouse_id', 'warehouse_name', 'warehouse_code'])->orderBy('warehouse_name')->get();

        // Load available batches only when Create modal is active to maximize speed
        $availableBatches = $this->showModal
            ? StockBatch::with('product')->select(['batch_id', 'batch_number', 'product_id'])->orderBy('batch_id', 'desc')->get()
            : collect([]);

        $selectedAdjustmentDetails = ($this->showViewModal && $this->view_adjustment_id)
            ? StockAdjustment::with(['warehouse', 'approver', 'items.batch.product.uom'])->find($this->view_adjustment_id)
            : null;

        return view('livewire.warehouse-inventory.stock-adjustments', [
            'adjustments' => $adjustments,
            'metrics' => [
                'total_adjustments' => $totalAdjustments,
                'total_variance_units' => $totalVarianceUnits,
                'surplus_count' => $surplusCount,
                'deficit_count' => $deficitCount,
            ],
            'warehouses' => $warehouses,
            'availableBatches' => $availableBatches,
            'selectedAdjustmentDetails' => $selectedAdjustmentDetails,
        ])->layout('layouts.app');
    }
}
