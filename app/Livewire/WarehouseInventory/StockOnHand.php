<?php

namespace App\Livewire\WarehouseInventory;

use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Livewire\Component;
use Livewire\WithPagination;

class StockOnHand extends Component
{
    use WithPagination;

    public $search = '';
    public $warehouse_id = '';

    public function render()
    {
        $query = WarehouseStock::with(['warehouse', 'product.category', 'batch']);

        if ($this->search) {
            $query->whereHas('product', function ($q) {
                $q->where('product_name', 'like', '%' . $this->search . '%')
                  ->orWhere('sku_code', 'like', '%' . $this->search . '%');
            })->orWhereHas('batch', function ($q) {
                $q->where('batch_number', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->warehouse_id) {
            $query->where('warehouse_id', $this->warehouse_id);
        }

        return view('livewire.warehouse-inventory.stock-on-hand', [
            'stocks' => $query->paginate(10),
            'warehouses' => Warehouse::all(),
        ])->layout('layouts.app');
    }
}
