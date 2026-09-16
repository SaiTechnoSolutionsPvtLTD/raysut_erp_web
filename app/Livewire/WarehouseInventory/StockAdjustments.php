<?php

namespace App\Livewire\WarehouseInventory;

use App\Models\StockAdjustment;
use Livewire\Component;
use Livewire\WithPagination;

class StockAdjustments extends Component
{
    use WithPagination;

    public $search = '';

    public function render()
    {
        $adjustments = StockAdjustment::with(['warehouse', 'approver', 'items.batch.product'])
            ->where('adjustment_number', 'like', '%' . $this->search . '%')
            ->orderBy('adjustment_id', 'desc')
            ->paginate(10);

        return view('livewire.warehouse-inventory.stock-adjustments', [
            'adjustments' => $adjustments,
        ])->layout('layouts.app');
    }
}
