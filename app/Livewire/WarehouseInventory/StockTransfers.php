<?php

namespace App\Livewire\WarehouseInventory;

use App\Models\StockBatch;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Services\StockLedgerService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class StockTransfers extends Component
{
    use WithPagination;

    // Filter properties
    public string $search = '';
    public string $source_warehouse_filter = '';
    public string $dest_warehouse_filter = '';
    public string $status_filter = '';

    // Create / Edit Modal State
    public bool $showModal = false;
    public bool $isEdit = false;
    public ?int $editing_transfer_id = null;

    // View Details Modal State
    public bool $showViewModal = false;
    public ?int $view_transfer_id = null;

    // Form fields
    public string $transfer_number = '';
    public string $source_warehouse_id = '';
    public string $destination_warehouse_id = '';
    public string $transfer_date = '';
    public string $status = 'DRAFT';

    // Transfer Items
    public array $items = [];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingSourceWarehouseFilter(): void
    {
        $this->resetPage();
    }

    public function updatingDestWarehouseFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->source_warehouse_filter = '';
        $this->dest_warehouse_filter = '';
        $this->status_filter = '';
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->resetValidation();
        $this->isEdit = false;
        $this->editing_transfer_id = null;

        $this->transfer_number = 'STR-' . date('Y') . '-' . strtoupper(substr(md5(microtime()), 0, 5));
        $warehouses = Warehouse::orderBy('warehouse_name')->get();
        $this->source_warehouse_id = $warehouses->first()?->warehouse_id ? (string) $warehouses->first()->warehouse_id : '';
        $this->destination_warehouse_id = $warehouses->skip(1)->first()?->warehouse_id ? (string) $warehouses->skip(1)->first()->warehouse_id : '';
        $this->transfer_date = now()->format('Y-m-d');
        $this->status = 'DRAFT';

        $this->items = [
            ['batch_id' => '', 'quantity' => '1.00']
        ];

        $this->showModal = true;
    }

    public function addItem(): void
    {
        $this->items[] = ['batch_id' => '', 'quantity' => '1.00'];
    }

    public function removeItem(int $index): void
    {
        if (count($this->items) > 1) {
            unset($this->items[$index]);
            $this->items = array_values($this->items);
        }
    }

    public function openEditModal(int $transferId): void
    {
        $this->resetValidation();
        $transfer = StockTransfer::with(['items.batch'])->find($transferId);
        if (!$transfer || $transfer->status !== 'DRAFT') {
            return;
        }

        $this->isEdit = true;
        $this->editing_transfer_id = $transfer->transfer_id;
        $this->transfer_number = $transfer->transfer_number;
        $this->source_warehouse_id = (string) $transfer->source_warehouse_id;
        $this->destination_warehouse_id = (string) $transfer->destination_warehouse_id;
        $this->transfer_date = $transfer->transfer_date ? $transfer->transfer_date->format('Y-m-d') : '';
        $this->status = $transfer->status;

        $this->items = [];
        foreach ($transfer->items as $item) {
            $this->items[] = [
                'batch_id' => (string) $item->batch_id,
                'quantity' => number_format((float)$item->quantity, 2, '.', ''),
            ];
        }

        if (empty($this->items)) {
            $this->items[] = ['batch_id' => '', 'quantity' => '1.00'];
        }

        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->editing_transfer_id = null;
    }

    public function saveTransfer(): void
    {
        $this->validate([
            'transfer_number' => 'required|string|max:30',
            'source_warehouse_id' => 'required|exists:warehouses,warehouse_id',
            'destination_warehouse_id' => 'required|exists:warehouses,warehouse_id|different:source_warehouse_id',
            'transfer_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.batch_id' => 'required|exists:stock_batches,batch_id',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ], [
            'destination_warehouse_id.different' => 'Destination warehouse must be different from source warehouse.',
            'items.min' => 'Please add at least one item line to transfer.',
            'items.*.batch_id.required' => 'Please select a batch for all item lines.',
            'items.*.quantity.min' => 'Item quantity must be greater than zero.',
        ]);

        try {
            DB::transaction(function () {
                if ($this->isEdit && $this->editing_transfer_id) {
                    $transfer = StockTransfer::findOrFail($this->editing_transfer_id);
                    $transfer->update([
                        'source_warehouse_id' => $this->source_warehouse_id,
                        'destination_warehouse_id' => $this->destination_warehouse_id,
                        'transfer_date' => $this->transfer_date,
                    ]);

                    $transfer->items()->delete();
                    foreach ($this->items as $item) {
                        StockTransferItem::create([
                            'transfer_id' => $transfer->transfer_id,
                            'batch_id' => $item['batch_id'],
                            'quantity' => (float) $item['quantity'],
                        ]);
                    }

                    $this->dispatch('notify', type: 'success', title: 'Transfer Updated', message: "Stock transfer voucher {$transfer->transfer_number} updated.");
                } else {
                    $transfer = StockTransfer::create([
                        'transfer_number' => $this->transfer_number,
                        'source_warehouse_id' => $this->source_warehouse_id,
                        'destination_warehouse_id' => $this->destination_warehouse_id,
                        'transfer_date' => $this->transfer_date,
                        'status' => 'DRAFT',
                        'requested_by' => Auth::id(),
                    ]);

                    foreach ($this->items as $item) {
                        StockTransferItem::create([
                            'transfer_id' => $transfer->transfer_id,
                            'batch_id' => $item['batch_id'],
                            'quantity' => (float) $item['quantity'],
                        ]);
                    }

                    $this->dispatch('notify', type: 'success', title: 'Transfer Created', message: "Stock transfer request {$transfer->transfer_number} saved as Draft.");
                }
            });

            $this->closeModal();
        } catch (Exception $e) {
            $this->addError('items', 'Failed to save transfer: ' . $e->getMessage());
        }
    }

    public function dispatchTransfer(int $transferId): void
    {
        $transfer = StockTransfer::find($transferId);
        if (!$transfer || $transfer->status !== 'DRAFT') return;

        $transfer->update(['status' => 'IN_TRANSIT']);
        $this->dispatch('notify', type: 'info', title: 'Transfer Dispatched', message: "Transfer voucher {$transfer->transfer_number} marked as IN_TRANSIT.");
    }

    public function receiveTransfer(int $transferId): void
    {
        $transfer = StockTransfer::with(['items.batch'])->find($transferId);
        if (!$transfer || $transfer->status === 'RECEIVED' || $transfer->status === 'CANCELLED') {
            return;
        }

        try {
            $stockService = app(StockLedgerService::class);

            DB::transaction(function () use ($transfer, $stockService) {
                foreach ($transfer->items as $item) {
                    $stockService->transferStock(
                        sourceWarehouseId: $transfer->source_warehouse_id,
                        destWarehouseId: $transfer->destination_warehouse_id,
                        productId: $item->batch->product_id,
                        batchId: $item->batch_id,
                        quantity: (float) $item->quantity
                    );
                }

                $transfer->update(['status' => 'RECEIVED']);
            });

            $this->dispatch('notify', type: 'success', title: 'Transfer Completed', message: "Stock transfer {$transfer->transfer_number} received & warehouse stock ledgers updated.");
        } catch (Exception $e) {
            $this->dispatch('notify', type: 'error', title: 'Intake Error', message: 'Failed to process stock transfer intake: ' . $e->getMessage());
        }
    }

    public function cancelTransfer(int $transferId): void
    {
        $transfer = StockTransfer::find($transferId);
        if (!$transfer || $transfer->status === 'RECEIVED') return;

        $transfer->update(['status' => 'CANCELLED']);
        $this->dispatch('notify', type: 'warning', title: 'Transfer Cancelled', message: "Transfer voucher {$transfer->transfer_number} marked as CANCELLED.");
    }

    public function openViewModal(int $transferId): void
    {
        $this->view_transfer_id = $transferId;
        $this->showViewModal = true;
    }

    public function closeViewModal(): void
    {
        $this->showViewModal = false;
        $this->view_transfer_id = null;
    }

    public function render()
    {
        // Calculate Metrics
        $totalTransfers = StockTransfer::count();
        $inTransitCount = StockTransfer::where('status', 'IN_TRANSIT')->count();
        $completedCount = StockTransfer::where('status', 'RECEIVED')->count();
        $draftCount = StockTransfer::where('status', 'DRAFT')->count();

        // Query Transfer List
        $transfers = StockTransfer::with(['sourceWarehouse', 'destinationWarehouse', 'requester', 'items.batch.product.uom'])
            ->when($this->search, function ($q) {
                $s = '%' . $this->search . '%';
                return $q->where('transfer_number', 'like', $s)
                    ->orWhereHas('sourceWarehouse', fn($wq) => $wq->where('warehouse_name', 'like', $s)->orWhere('warehouse_code', 'like', $s))
                    ->orWhereHas('destinationWarehouse', fn($wq) => $wq->where('warehouse_name', 'like', $s)->orWhere('warehouse_code', 'like', $s))
                    ->orWhereHas('items.batch', fn($bq) => $bq->where('batch_number', 'like', $s));
            })
            ->when($this->source_warehouse_filter, function ($q) {
                return $q->where('source_warehouse_id', $this->source_warehouse_filter);
            })
            ->when($this->dest_warehouse_filter, function ($q) {
                return $q->where('destination_warehouse_id', $this->dest_warehouse_filter);
            })
            ->when($this->status_filter, function ($q) {
                return $q->where('status', $this->status_filter);
            })
            ->orderBy('transfer_id', 'desc')
            ->paginate(10);

        $warehouses = Warehouse::select(['warehouse_id', 'warehouse_name', 'warehouse_code'])->orderBy('warehouse_name')->get();

        // Load available batches only when Create/Edit modal is open to maximize performance
        $batches = $this->showModal
            ? StockBatch::with('product.uom')->select(['batch_id', 'batch_number', 'product_id'])->orderBy('batch_id', 'desc')->get()
            : collect([]);

        $selectedTransferDetails = ($this->showViewModal && $this->view_transfer_id)
            ? StockTransfer::with(['sourceWarehouse', 'destinationWarehouse', 'requester', 'items.batch.product.category', 'items.batch.product.uom'])->find($this->view_transfer_id) 
            : null;

        return view('livewire.warehouse-inventory.stock-transfers', [
            'transfers' => $transfers,
            'metrics' => [
                'total_transfers' => $totalTransfers,
                'in_transit' => $inTransitCount,
                'completed' => $completedCount,
                'draft' => $draftCount,
            ],
            'warehouses' => $warehouses,
            'availableBatches' => $batches,
            'selectedTransferDetails' => $selectedTransferDetails,
        ])->layout('layouts.app');
    }
}
