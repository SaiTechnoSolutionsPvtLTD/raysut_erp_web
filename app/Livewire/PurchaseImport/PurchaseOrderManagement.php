<?php

namespace App\Livewire\PurchaseImport;

use App\Models\PurchaseOrder;
use Livewire\Component;
use Livewire\WithPagination;

class PurchaseOrderManagement extends Component
{
    use WithPagination;

    public $search = '';

    public function render()
    {
        $pos = PurchaseOrder::with(['supplier', 'warehouse', 'items.product', 'creator'])
            ->where('po_number', 'like', '%' . $this->search . '%')
            ->orderBy('po_id', 'desc')
            ->paginate(10);

        return view('livewire.purchase-import.purchase-order-management', [
            'purchaseOrders' => $pos,
        ])->layout('layouts.app');
    }
}
