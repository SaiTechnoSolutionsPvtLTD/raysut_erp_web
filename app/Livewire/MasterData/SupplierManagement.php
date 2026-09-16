<?php

namespace App\Livewire\MasterData;

use App\Models\Supplier;
use Livewire\Component;
use Livewire\WithPagination;

class SupplierManagement extends Component
{
    use WithPagination;

    public $search = '';

    public function render()
    {
        $suppliers = Supplier::where('supplier_name', 'like', '%' . $this->search . '%')
            ->orWhere('supplier_code', 'like', '%' . $this->search . '%')
            ->paginate(10);

        return view('livewire.master-data.supplier-management', [
            'suppliers' => $suppliers,
        ])->layout('layouts.app');
    }
}
