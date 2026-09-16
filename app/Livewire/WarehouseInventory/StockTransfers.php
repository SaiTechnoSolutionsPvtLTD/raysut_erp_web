<?php

namespace App\Livewire\WarehouseInventory;

use App\Models\StockTransfer;
use Livewire\Component;
use Livewire\WithPagination;

class StockTransfers extends Component
{
    use WithPagination;

    public $search = '';

    public function render()
    {
        $transfers = StockTransfer::with(['sourceWarehouse', 'destinationWarehouse', 'requester', 'items.batch.product'])
            ->where('transfer_number', 'like', '%' . $this->search . '%')
            ->orderBy('transfer_id', 'desc')
            ->paginate(10);

        return view('livewire.warehouse-inventory.stock-transfers', [
            'transfers' => $transfers,
        ])->layout('layouts.app');
    }
}
