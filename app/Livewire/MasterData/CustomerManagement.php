<?php

namespace App\Livewire\MasterData;

use App\Models\Customer;
use App\Models\CustomerCreditTerm;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class CustomerManagement extends Component
{
    use WithPagination;

    public $search = '';
    public $segment_filter = '';
    public $status_filter = '';

    public $showModal = false;
    public $isEdit = false;
    public $customer_id;

    public $customer_code;
    public $customer_name;
    public $customer_segment = 'HORECA';
    public $custom_segment_name = '';
    public $isCustomSegment = false;
    public $phone;
    public $email;
    public $oman_card_no;
    public $credit_limit = 5000.00;
    public $payment_terms_days = 30;
    public $outstanding_balance = 0.00;
    public $is_active = true;

    // Delete Modal state
    public bool $showItemDeleteModal = false;
    public string $itemToDeleteType = '';
    public int $itemToDeleteId = 0;
    public string $itemToDeleteName = '';

    public function updatedCustomerSegment($value): void
    {
        $this->isCustomSegment = ($value === 'ADD_NEW');
        if ($this->isCustomSegment) {
            $this->custom_segment_name = '';
        }
    }

    protected function generateCustomerCode(): string
    {
        $nextId = (Customer::max('customer_id') ?? 0) + 1;
        do {
            $code = 'CUST-' . str_pad((string)$nextId, 4, '0', STR_PAD_LEFT);
            $exists = Customer::where('customer_code', $code)->exists();
            if ($exists) {
                $nextId++;
            }
        } while ($exists);

        return $code;
    }

    public function openCreateModal(): void
    {
        if (! auth()->user()?->hasPermission('Master Data', 'CREATE')) {
            abort(403, 'Unauthorized action: You do not have permission to CREATE customer accounts.');
        }

        $this->reset([
            'customer_id', 'customer_name', 'customer_segment', 'custom_segment_name', 'isCustomSegment',
            'phone', 'email', 'oman_card_no'
        ]);
        $this->customer_code = $this->generateCustomerCode();
        $this->customer_segment = 'HORECA';
        $this->credit_limit = 5000.00;
        $this->payment_terms_days = 30;
        $this->outstanding_balance = 0.00;
        $this->is_active = true;
        $this->isEdit = false;
        $this->showModal = true;
    }

    public function editCustomer($id): void
    {
        if (! auth()->user()?->hasPermission('Master Data', 'EDIT')) {
            abort(403, 'Unauthorized action: You do not have permission to EDIT customer accounts.');
        }

        $customer = Customer::with('creditTerms')->findOrFail($id);
        $this->customer_id = $customer->customer_id;
        $this->customer_code = $customer->customer_code;
        $this->customer_name = $customer->customer_name;
        $this->customer_segment = $customer->customer_segment ?? 'HORECA';
        $this->custom_segment_name = '';
        $this->isCustomSegment = false;
        $this->phone = $customer->phone;
        $this->email = $customer->email;
        $this->oman_card_no = $customer->oman_card_no;
        $this->is_active = (bool)$customer->is_active;

        $credit = $customer->creditTerms;
        $this->credit_limit = $credit?->credit_limit ?? 0.00;
        $this->payment_terms_days = $credit?->payment_terms_days ?? 30;
        $this->outstanding_balance = $credit?->outstanding_balance ?? 0.00;

        $this->isEdit = true;
        $this->showModal = true;
    }

    public function saveCustomer(): void
    {
        $requiredAction = $this->customer_id ? 'EDIT' : 'CREATE';
        if (! auth()->user()?->hasPermission('Master Data', $requiredAction)) {
            abort(403, "Unauthorized action: You do not have permission to {$requiredAction} customer accounts.");
        }

        if (!$this->customer_id && empty($this->customer_code)) {
            $this->customer_code = $this->generateCustomerCode();
        }

        $finalSegment = $this->customer_segment;
        if ($this->customer_segment === 'ADD_NEW' || $this->isCustomSegment) {
            $this->validate(['custom_segment_name' => 'required|string|max:50']);
            $finalSegment = strtoupper(trim($this->custom_segment_name));
        }
        $this->customer_segment = $finalSegment;

        $this->validate([
            'customer_code' => 'required|string|max:30|unique:customers,customer_code,' . ($this->customer_id ?? 'NULL') . ',customer_id',
            'customer_name' => 'required|string|max:150',
            'customer_segment' => 'required|string|max:50',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:100',
            'oman_card_no' => 'nullable|string|max:50',
            'credit_limit' => 'required|numeric|min:0',
            'payment_terms_days' => 'required|integer|min:0|max:365',
            'outstanding_balance' => 'required|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        DB::transaction(function () {
            $customer = Customer::updateOrCreate(
                ['customer_id' => $this->customer_id],
                [
                    'customer_code' => $this->customer_code,
                    'customer_name' => trim($this->customer_name),
                    'customer_segment' => strtoupper(trim($this->customer_segment)),
                    'phone' => $this->phone ? trim($this->phone) : null,
                    'email' => $this->email ? strtolower(trim($this->email)) : null,
                    'oman_card_no' => $this->oman_card_no ? trim($this->oman_card_no) : null,
                    'is_active' => $this->is_active,
                ]
            );

            CustomerCreditTerm::updateOrCreate(
                ['customer_id' => $customer->customer_id],
                [
                    'credit_limit' => (float)$this->credit_limit,
                    'payment_terms_days' => (int)$this->payment_terms_days,
                    'outstanding_balance' => (float)$this->outstanding_balance,
                ]
            );
        });

        $this->dispatch('notify', type: 'success', title: 'Customer Account Saved', message: 'Customer account ' . ($this->isEdit ? 'updated' : 'created') . ' successfully.');
        $this->showModal = false;
    }

    public function confirmDeleteCustomer(int $id, string $name): void
    {
        $this->itemToDeleteType = 'Customer';
        $this->itemToDeleteId = $id;
        $this->itemToDeleteName = $name;
        $this->showItemDeleteModal = true;
    }

    public function executeDeleteItem(): void
    {
        $this->showItemDeleteModal = false;
        if ($this->itemToDeleteType === 'Customer') {
            $this->deleteCustomer($this->itemToDeleteId);
        }
        $this->reset(['itemToDeleteType', 'itemToDeleteId', 'itemToDeleteName']);
    }

    public function deleteCustomer($id): void
    {
        if (! auth()->user()?->hasPermission('Master Data', 'DELETE')) {
            abort(403, 'Unauthorized action: You do not have permission to DELETE customer accounts.');
        }

        $customer = Customer::find($id);
        if ($customer) {
            try {
                $name = $customer->customer_name;
                DB::transaction(function () use ($customer, $id) {
                    CustomerCreditTerm::where('customer_id', $id)->delete();
                    $customer->delete();
                });
                $this->dispatch('notify', type: 'warning', title: 'Customer Account Removed', message: "Customer account '{$name}' deleted successfully.");
            } catch (\Throwable $e) {
                $this->dispatch('notify', type: 'error', title: 'Cannot Delete Customer', message: "Cannot delete customer account '{$customer->customer_name}' because it is referenced in sales transactions or active orders.");
            }
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'segment_filter', 'status_filter']);
        $this->resetPage();
    }

    public function render()
    {
        $query = Customer::with('creditTerms');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('customer_name', 'like', '%' . $this->search . '%')
                  ->orWhere('customer_code', 'like', '%' . $this->search . '%')
                  ->orWhere('phone', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%')
                  ->orWhere('oman_card_no', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->segment_filter) {
            $query->where('customer_segment', $this->segment_filter);
        }

        if ($this->status_filter !== '') {
            $query->where('is_active', $this->status_filter === 'active');
        }

        $existingSegments = Customer::select('customer_segment')->distinct()->whereNotNull('customer_segment')->pluck('customer_segment')->toArray();
        $defaultSegments = ['HORECA', 'HARBOR', 'RETAIL', 'OFFICE', 'SPECIAL'];
        $allSegments = array_unique(array_merge($defaultSegments, $existingSegments));
        sort($allSegments);

        return view('livewire.master-data.customer-management', [
            'customers' => $query->orderBy('customer_id', 'desc')->paginate(10),
            'allSegments' => array_values($allSegments),
        ])->layout('layouts.app');
    }
}
