<?php

namespace App\Livewire\WarehouseInventory;

use App\Models\Product;
use App\Models\StockBatch;
use Carbon\Carbon;
use Exception;
use Livewire\Component;
use Livewire\WithPagination;

class StockBatches extends Component
{
    use WithPagination;

    // Filter properties
    public string $search = '';
    public string $duty_status_filter = '';
    public string $expiry_filter = '';
    public string $product_id_filter = '';

    // Create / Edit Modal State
    public bool $showModal = false;
    public bool $isEdit = false;
    public ?int $editing_batch_id = null;

    // View Details Modal State
    public bool $showViewModal = false;
    public ?int $view_batch_id = null;

    // Form fields
    public string $batch_number = '';
    public string $product_id = '';
    public string $manufacture_date = '';
    public string $expiry_date = '';
    public string $duty_status = 'DUTY_FREE_BONDED';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingDutyStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingExpiryFilter(): void
    {
        $this->resetPage();
    }

    public function updatingProductIdFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->duty_status_filter = '';
        $this->expiry_filter = '';
        $this->product_id_filter = '';
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->resetValidation();
        $this->isEdit = false;
        $this->editing_batch_id = null;

        $this->batch_number = 'BATCH-' . strtoupper(substr(md5(microtime()), 0, 8));
        $this->product_id = '';
        $this->manufacture_date = now()->format('Y-m-d');
        $this->expiry_date = now()->addYears(2)->format('Y-m-d');
        $this->duty_status = 'DUTY_FREE_BONDED';

        $this->showModal = true;
    }

    public function openEditModal(int $batchId): void
    {
        $this->resetValidation();
        $batch = StockBatch::find($batchId);
        if (!$batch) return;

        $this->isEdit = true;
        $this->editing_batch_id = $batch->batch_id;
        $this->batch_number = $batch->batch_number;
        $this->product_id = (string) $batch->product_id;
        $this->manufacture_date = $batch->manufacture_date ? $batch->manufacture_date->format('Y-m-d') : '';
        $this->expiry_date = $batch->expiry_date ? $batch->expiry_date->format('Y-m-d') : '';
        $this->duty_status = $batch->duty_status;

        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->editing_batch_id = null;
    }

    public function saveBatch(): void
    {
        $rules = [
            'batch_number' => 'required|string|max:50|unique:stock_batches,batch_number,' . ($this->editing_batch_id ?? 'NULL') . ',batch_id',
            'product_id' => 'required|exists:products,product_id',
            'manufacture_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after_or_equal:manufacture_date',
            'duty_status' => 'required|in:DUTY_FREE_BONDED,DUTY_PAID',
        ];

        $this->validate($rules, [
            'batch_number.required' => 'Please provide a unique batch serial number.',
            'batch_number.unique' => 'This batch number is already registered in the system.',
            'product_id.required' => 'Please select an associated product.',
            'expiry_date.after_or_equal' => 'Expiry date cannot be prior to manufacture date.',
        ]);

        try {
            if ($this->isEdit && $this->editing_batch_id) {
                $batch = StockBatch::findOrFail($this->editing_batch_id);
                $batch->update([
                    'batch_number' => $this->batch_number,
                    'product_id' => $this->product_id,
                    'manufacture_date' => $this->manufacture_date ?: null,
                    'expiry_date' => $this->expiry_date ?: null,
                    'duty_status' => $this->duty_status,
                ]);

                $this->dispatch('notify', type: 'success', title: 'Batch Updated', message: "Stock batch {$batch->batch_number} records updated successfully.");
            } else {
                $batch = StockBatch::create([
                    'batch_number' => $this->batch_number,
                    'product_id' => $this->product_id,
                    'manufacture_date' => $this->manufacture_date ?: null,
                    'expiry_date' => $this->expiry_date ?: null,
                    'duty_status' => $this->duty_status,
                ]);

                $this->dispatch('notify', type: 'success', title: 'Batch Registered', message: "New stock batch {$batch->batch_number} registered successfully.");
            }

            $this->closeModal();
        } catch (Exception $e) {
            $this->addError('batch_number', 'Failed to save stock batch: ' . $e->getMessage());
        }
    }

    public function openViewModal(int $batchId): void
    {
        $this->view_batch_id = $batchId;
        $this->showViewModal = true;
    }

    public function closeViewModal(): void
    {
        $this->showViewModal = false;
        $this->view_batch_id = null;
    }

    public function render()
    {
        $now = Carbon::now();

        // Calculate KPI Metrics
        $totalBatches = StockBatch::count();
        $activeBatches = StockBatch::whereHas('stocks', function ($q) {
            $q->where('quantity_on_hand', '>', 0);
        })->count();
        $expiringSoonCount = StockBatch::whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [$now->startOfDay(), $now->copy()->addDays(30)->endOfDay()])
            ->count();
        $dutyFreeCount = StockBatch::where('duty_status', 'DUTY_FREE_BONDED')->count();

        // Query Batch List
        $batches = StockBatch::with(['product.category', 'product.uom', 'sourceGrn', 'stocks.warehouse'])
            ->when($this->search, function ($q) {
                $s = '%' . $this->search . '%';
                return $q->where('batch_number', 'like', $s)
                    ->orWhereHas('product', function ($pq) use ($s) {
                        $pq->where('product_name', 'like', $s)
                            ->orWhere('sku_code', 'like', $s);
                    })
                    ->orWhereHas('sourceGrn', function ($gq) use ($s) {
                        $gq->where('grn_number', 'like', $s);
                    });
            })
            ->when($this->duty_status_filter, function ($q) {
                return $q->where('duty_status', $this->duty_status_filter);
            })
            ->when($this->product_id_filter, function ($q) {
                return $q->where('product_id', $this->product_id_filter);
            })
            ->when($this->expiry_filter, function ($q) use ($now) {
                if ($this->expiry_filter === 'expiring_soon') {
                    return $q->whereNotNull('expiry_date')
                        ->whereBetween('expiry_date', [$now->copy()->startOfDay(), $now->copy()->addDays(30)->endOfDay()]);
                } elseif ($this->expiry_filter === 'expired') {
                    return $q->whereNotNull('expiry_date')
                        ->where('expiry_date', '<', $now->copy()->startOfDay());
                } elseif ($this->expiry_filter === 'valid') {
                    return $q->where(function ($eq) use ($now) {
                        $eq->whereNull('expiry_date')
                            ->orWhere('expiry_date', '>', $now->copy()->addDays(30)->endOfDay());
                    });
                }
            })
            ->orderBy('batch_id', 'desc')
            ->paginate(10);

        $products = Product::orderBy('product_name')->get();

        $selectedBatchDetails = $this->view_batch_id 
            ? StockBatch::with(['product.category', 'product.uom', 'sourceGrn.purchaseOrder.supplier', 'stocks.warehouse'])->find($this->view_batch_id) 
            : null;

        return view('livewire.warehouse-inventory.stock-batches', [
            'batches' => $batches,
            'metrics' => [
                'total_batches' => $totalBatches,
                'active_batches' => $activeBatches,
                'expiring_soon' => $expiringSoonCount,
                'duty_free_bonded' => $dutyFreeCount,
            ],
            'products' => $products,
            'selectedBatchDetails' => $selectedBatchDetails,
        ])->layout('layouts.app');
    }
}
