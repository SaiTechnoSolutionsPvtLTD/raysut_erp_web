<?php

namespace App\Livewire\MasterData;

use App\Models\Customer;
use Livewire\Component;
use Livewire\WithPagination;

class CustomerManagement extends Component
{
    use WithPagination;

    public $search = '';

    public function render()
    {
        $customers = Customer::with('creditTerms')
            ->where('customer_name', 'like', '%' . $this->search . '%')
            ->orWhere('customer_code', 'like', '%' . $this->search . '%')
            ->paginate(10);

        return view('livewire.master-data.customer-management', [
            'customers' => $customers,
        ])->layout('layouts.app');
    }
}
