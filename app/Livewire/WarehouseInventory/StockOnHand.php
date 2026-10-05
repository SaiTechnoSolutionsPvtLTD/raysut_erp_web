<?php

namespace App\Livewire\WarehouseInventory;

use App\Models\StockBatch;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Services\StockLedgerService;
use Exception;
use Livewire\Component;
use Livewire\WithPagination;

class StockOnHand extends Component
{
    use WithPagination;

    // Filter properties
    public string $search = '';
    public string $warehouse_id = '';
    public string $duty_status_filter = '';
    public string $stock_filter = '';

    // View Details Modal State
    public bool $showViewModal = false;
    public ?int $view_stock_id = null;

    // Reserve / Release Action Modal State
    public bool $showActionModal = false;
    public ?int $action_stock_id = null;
    public string $action_type = 'reserve'; // 'reserve' or 'release'
    public string $action_quantity = '1.00';
    public string $action_reason = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingWarehouseId(): void
    {
        $this->resetPage();
    }

    public function updatingDutyStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStockFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->warehouse_id = '';
        $this->duty_status_filter = '';
        $this->stock_filter = '';
        $this->resetPage();
    }

    public function openViewModal(int $stockId): void
    {
        $this->view_stock_id = $stockId;
        $this->showViewModal = true;
    }

    public function closeViewModal(): void
    {
        $this->showViewModal = false;
        $this->view_stock_id = null;
    }

    public function openActionModal(int $stockId, string $type = 'reserve'): void
    {
        $this->resetValidation();
        $this->action_stock_id = $stockId;
        $this->action_type = $type;
        $this->action_quantity = '1.00';
        $this->action_reason = '';
        $this->showActionModal = true;
    }

    public function executeStockAction(): void
    {
        $this->validate([
            'action_quantity' => 'required|numeric|min:0.01',
            'action_type' => 'required|in:reserve,release',
        ], [
            'action_quantity.required' => 'Please enter a valid quantity.',
            'action_quantity.min' => 'Quantity must be greater than zero.',
        ]);

        $stock = WarehouseStock::find($this->action_stock_id);
        if (!$stock) {
            $this->showActionModal = false;
            return;
        }

        $stockService = app(StockLedgerService::class);
        $qty = (float) $this->action_quantity;

        try {
            if ($this->action_type === 'reserve') {
                $stockService->reserveStock(
                    warehouseId: $stock->warehouse_id,
                    productId: $stock->product_id,
                    batchId: $stock->batch_id,
                    quantity: $qty
                );
                $msg = "Reserved {$qty} units of {$stock->product?->product_name} at {$stock->warehouse?->warehouse_name}.";
                $this->dispatch('notify', type: 'success', title: 'Stock Reserved', message: $msg);
            } else {
                $stockService->releaseReservation(
                    warehouseId: $stock->warehouse_id,
                    productId: $stock->product_id,
                    batchId: $stock->batch_id,
                    quantity: $qty
                );
                $msg = "Released {$qty} reserved units back to available stock.";
                $this->dispatch('notify', type: 'success', title: 'Reservation Released', message: $msg);
            }

            $this->showActionModal = false;
        } catch (Exception $e) {
            $this->addError('action_quantity', $e->getMessage());
        }
    }

    public function render()
    {
        // Calculate Metrics KPIs
        $totalOnHand = (float) WarehouseStock::sum('quantity_on_hand');
        $totalAvailable = (float) WarehouseStock::sum('quantity_available');
        $totalReserved = (float) WarehouseStock::sum('quantity_reserved');
        $dutyFreeCount = WarehouseStock::whereHas('batch', function ($q) {
            $q->where('duty_status', 'DUTY_FREE_BONDED');
        })->sum('quantity_on_hand');

        // Query list
        $stocks = WarehouseStock::with(['warehouse', 'product.category', 'product.uom', 'batch.sourceGrn'])
            ->when($this->search, function ($q) {
                $s = '%' . $this->search . '%';
                return $q->whereHas('product', function ($pq) use ($s) {
                    $pq->where('product_name', 'like', $s)
                        ->orWhere('sku_code', 'like', $s)
                        ->orWhereHas('category', function ($cq) use ($s) {
                            $cq->where('category_name', 'like', $s);
                        });
                })->orWhereHas('batch', function ($bq) use ($s) {
                    $bq->where('batch_number', 'like', $s);
                })->orWhereHas('warehouse', function ($wq) use ($s) {
                    $wq->where('warehouse_name', 'like', $s)->orWhere('warehouse_code', 'like', $s);
                });
            })
            ->when($this->warehouse_id, function ($q) {
                return $q->where('warehouse_id', $this->warehouse_id);
            })
            ->when($this->duty_status_filter, function ($q) {
                return $q->whereHas('batch', function ($bq) {
                    $bq->where('duty_status', $this->duty_status_filter);
                });
            })
            ->when($this->stock_filter, function ($q) {
                if ($this->stock_filter === 'available') {
                    return $q->where('quantity_available', '>', 0);
                } elseif ($this->stock_filter === 'reserved') {
                    return $q->where('quantity_reserved', '>', 0);
                } elseif ($this->stock_filter === 'zero') {
                    return $q->where('quantity_on_hand', '<=', 0);
                }
            })
            ->orderBy('stock_id', 'desc')
            ->paginate(10);

        $warehouses = Warehouse::orderBy('warehouse_name')->get();

        $selectedStockDetails = $this->view_stock_id 
            ? WarehouseStock::with(['warehouse', 'product.category', 'product.uom', 'batch.sourceGrn'])->find($this->view_stock_id) 
            : null;

        $actionStock = $this->action_stock_id ? WarehouseStock::with(['warehouse', 'product', 'batch'])->find($this->action_stock_id) : null;

        return view('livewire.warehouse-inventory.stock-on-hand', [
            'stocks' => $stocks,
            'metrics' => [
                'total_on_hand' => $totalOnHand,
                'total_available' => $totalAvailable,
                'total_reserved' => $totalReserved,
                'duty_free_bonded' => $dutyFreeCount,
            ],
            'warehouses' => $warehouses,
            'selectedStockDetails' => $selectedStockDetails,
            'actionStock' => $actionStock,
        ])->layout('layouts.app');
    }
}
