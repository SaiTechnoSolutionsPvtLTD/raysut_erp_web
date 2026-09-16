<?php

namespace App\Livewire\PurchaseImport;

use App\Models\GoodsReceiptNote;
use Livewire\Component;
use Livewire\WithPagination;

class GoodsReceiptManagement extends Component
{
    use WithPagination;

    public $search = '';

    public function render()
    {
        $grns = GoodsReceiptNote::with(['purchaseOrder', 'shipment', 'warehouse', 'receiver', 'items.product', 'items.batch'])
            ->where('grn_number', 'like', '%' . $this->search . '%')
            ->orderBy('grn_id', 'desc')
            ->paginate(10);

        return view('livewire.purchase-import.goods-receipt-management', [
            'grns' => $grns,
        ])->layout('layouts.app');
    }
}
