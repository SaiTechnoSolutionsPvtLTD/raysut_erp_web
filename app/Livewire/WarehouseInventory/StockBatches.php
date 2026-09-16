<?php

namespace App\Livewire\WarehouseInventory;

use App\Models\StockBatch;
use Livewire\Component;
use Livewire\WithPagination;

class StockBatches extends Component
{
    use WithPagination;

    public $search = '';

    public function render()
    {
        $batches = StockBatch::with(['product', 'sourceGrn'])
            ->where('batch_number', 'like', '%' . $this->search . '%')
            ->orWhereHas('product', function ($q) {
                $q->where('product_name', 'like', '%' . $this->search . '%');
            })
            ->orderBy('batch_id', 'desc')
            ->paginate(10);

        return view('livewire.warehouse-inventory.stock-batches', [
            'batches' => $batches,
        ])->layout('layouts.app');
    }
}
