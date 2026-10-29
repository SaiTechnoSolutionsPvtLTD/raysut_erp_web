<?php

namespace App\Livewire\Sales\Wholesale;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\SalesInvoice;
use App\Services\CreditService;
use App\Services\InvoiceService;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class CustomerCreditManagement extends Component
{
    use AuthorizesRequests, WithPagination;

    // Filters
    public string $search = '';
    public string $credit_status_filter = '';
    public string $customer_type_filter = '';

    // Edit Terms Modal
    public bool $showModal = false;
    public ?int $selectedCustomerId = null;
    public string $customerName = '';
    public string $customerCode = '';
    public float $creditLimit = 0.0;
    public int $paymentTermsDays = 30;
    public string $creditStatus = 'ACTIVE';

    // Record Payment Modal
    public bool $showPaymentModal = false;
    public ?int $paymentCustomerId = null;
    public ?Customer $paymentCustomer = null;
    public ?int $paymentInvoiceId = null;
    public array $unpaidInvoices = [];
    public float $paymentAmount = 0.0;
    public string $paymentMethod = 'BANK_TRANSFER';
    public string $paymentReference = '';
    public string $paymentNotes = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCreditStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingCustomerTypeFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'credit_status_filter', 'customer_type_filter']);
        $this->resetPage();
    }

    public function editCreditTerms(int $id): void
    {
        try {
            $customer = Customer::findOrFail($id);
            $this->authorize('view', $customer);
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());
            $this->dispatch('notify', type: 'error', title: 'Action Failed', message: $e->getMessage());
            return;
        }

        $user = auth()->user();
        $canApprove = $user->hasRole('Super Admin')
            || $user->hasRole('HQ Admin')
            || $user->hasRole('Branch Admin')
            || $user->hasPermission('Retail & Wholesale Sales', 'APPROVE');

        if (! $canApprove) {
            session()->flash('error', 'Unauthorized: Only supervisors can modify credit limit or payment terms.');
            $this->dispatch('notify', type: 'error', title: 'Action Failed', message: 'Unauthorized: Only supervisors can modify credit limit or payment terms.');
            return;
        }

        $this->resetValidation();
        $this->selectedCustomerId = $id;
        $this->customerName = $customer->customer_name;
        $this->customerCode = $customer->customer_code;
        $this->creditLimit = (float) $customer->getEffectiveCreditLimit();
        $this->paymentTermsDays = (int) $customer->getEffectivePaymentTermsDays();
        $this->creditStatus = $customer->credit_status ?? 'ACTIVE';

        $this->showModal = true;
    }

    public function saveCreditTerms(): void
    {
        if (! $this->selectedCustomerId) return;

        $user = auth()->user();
        $canApprove = $user->hasRole('Super Admin')
            || $user->hasRole('HQ Admin')
            || $user->hasRole('Branch Admin')
            || $user->hasPermission('Retail & Wholesale Sales', 'APPROVE');

        if (! $canApprove) {
            session()->flash('error', 'Unauthorized: Only supervisors can modify credit limit or payment terms.');
            $this->dispatch('notify', type: 'error', title: 'Action Failed', message: 'Unauthorized: Only supervisors can modify credit limit or payment terms.');
            return;
        }

        $this->validate([
            'creditLimit' => ['required', 'numeric', 'min:0'],
            'paymentTermsDays' => ['required', 'integer', 'min:0'],
            'creditStatus' => ['required', 'in:ACTIVE,ON_HOLD'],
        ]);

        $customer = Customer::findOrFail($this->selectedCustomerId);
        $customer->credit_limit = $this->creditLimit;
        $customer->payment_terms_days = $this->paymentTermsDays;
        $customer->credit_status = $this->creditStatus;
        $customer->save();

        if ($customer->creditTerms) {
            $customer->creditTerms->credit_limit = $this->creditLimit;
            $customer->creditTerms->payment_terms_days = $this->paymentTermsDays;
            $customer->creditTerms->save();
        }

        AuditLog::create([
            'user_id' => $user->user_id,
            'module_name' => 'Retail & Wholesale Sales',
            'action' => 'UPDATE_CREDIT_TERMS',
            'record_table' => 'customers',
            'record_id' => $customer->customer_id,
            'details_json' => [
                'customer' => $customer->customer_name,
                'credit_limit' => $customer->credit_limit,
                'payment_terms_days' => $customer->payment_terms_days,
                'credit_status' => $customer->credit_status,
            ],
            'ip_address' => request()->ip(),
        ]);

        $this->showModal = false;
        $msg = "Credit terms for {$customer->customer_name} updated successfully.";
        session()->flash('message', $msg);
        $this->dispatch('notify', type: 'success', title: 'Credit Terms Updated', message: $msg);
    }

    public function toggleCreditHold(int $id): void
    {
        $user = auth()->user();
        $canApprove = $user->hasRole('Super Admin')
            || $user->hasRole('HQ Admin')
            || $user->hasRole('Branch Admin')
            || $user->hasPermission('Retail & Wholesale Sales', 'APPROVE');

        if (! $canApprove) {
            session()->flash('error', 'Unauthorized: Only supervisors can change customer credit status.');
            $this->dispatch('notify', type: 'error', title: 'Action Failed', message: 'Unauthorized: Only supervisors can change customer credit status.');
            return;
        }

        $customer = Customer::findOrFail($id);
        $customer->credit_status = ($customer->credit_status === 'ACTIVE') ? 'ON_HOLD' : 'ACTIVE';
        $customer->save();

        AuditLog::create([
            'user_id' => $user->user_id,
            'module_name' => 'Retail & Wholesale Sales',
            'action' => 'TOGGLE_CREDIT_STATUS',
            'record_table' => 'customers',
            'record_id' => $customer->customer_id,
            'details_json' => [
                'customer' => $customer->customer_name,
                'new_status' => $customer->credit_status,
            ],
            'ip_address' => request()->ip(),
        ]);

        $msg = "Customer {$customer->customer_name} credit status updated to {$customer->credit_status}.";
        session()->flash('message', $msg);
        $this->dispatch('notify', type: $customer->credit_status === 'ACTIVE' ? 'success' : 'warning', title: 'Status Changed', message: $msg);
    }

    // Payment Flow
    public function openPaymentModal(int $id): void
    {
        try {
            $customer = Customer::findOrFail($id);
            $this->authorize('view', $customer);
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());
            $this->dispatch('notify', type: 'error', title: 'Action Failed', message: $e->getMessage());
            return;
        }

        $this->paymentCustomerId = $id;
        $this->paymentCustomer = $customer;

        $invoices = SalesInvoice::where('customer_id', $id)
            ->whereIn('status', ['POSTED', 'PARTIALLY_PAID'])
            ->orderBy('due_date', 'asc')
            ->get();

        $this->unpaidInvoices = $invoices->map(function (SalesInvoice $inv) {
            return [
                'invoice_id' => $inv->invoice_id,
                'invoice_no' => $inv->invoice_no,
                'balance_due' => (float) $inv->balance_due,
                'due_date' => $inv->due_date ? $inv->due_date->format('Y-m-d') : 'N/A',
                'grand_total' => (float) $inv->grand_total,
                'amount_paid' => (float) $inv->amount_paid,
            ];
        })->values()->toArray();

        $firstInvoice = $invoices->first();
        $this->paymentInvoiceId = $firstInvoice?->invoice_id;
        $this->paymentAmount = $firstInvoice ? (float) $firstInvoice->balance_due : 0.0;
        $this->paymentMethod = 'BANK_TRANSFER';
        $this->paymentReference = '';
        $this->paymentNotes = '';

        $this->showPaymentModal = true;
    }

    public function updatedPaymentInvoiceId($val): void
    {
        if ($val) {
            $inv = SalesInvoice::find($val);
            $this->paymentAmount = $inv ? (float) $inv->balance_due : 0.0;
        } else {
            $this->paymentAmount = 0.0;
        }
    }

    public function saveCustomerPayment(InvoiceService $invoiceService): void
    {
        if (! $this->paymentInvoiceId) {
            $this->addError('paymentInvoiceId', 'Please select an unpaid invoice to apply payment.');
            return;
        }

        try {
            $invoice = SalesInvoice::findOrFail($this->paymentInvoiceId);
            $this->authorize('recordPayment', $invoice);
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());
            $this->dispatch('notify', type: 'error', title: 'Action Failed', message: $e->getMessage());
            return;
        }

        $this->validate([
            'paymentAmount' => ['required', 'numeric', 'min:0.001', 'max:' . $invoice->balance_due],
            'paymentMethod' => ['required', 'in:CASH,CARD,BANK_TRANSFER,CHEQUE'],
            'paymentReference' => ['nullable', 'string', 'max:100'],
            'paymentNotes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $invoiceService->recordPayment($invoice, [
                'amount' => $this->paymentAmount,
                'method' => $this->paymentMethod,
                'reference' => $this->paymentReference,
                'notes' => $this->paymentNotes,
            ], auth()->user());

            $this->showPaymentModal = false;
            $msg = "Payment of " . number_format($this->paymentAmount, 3) . " OMR recorded successfully for invoice {$invoice->invoice_no}.";
            $this->reset(['paymentInvoiceId', 'paymentAmount', 'paymentReference', 'paymentNotes', 'unpaidInvoices']);
            session()->flash('message', $msg);
            $this->dispatch('notify', type: 'success', title: 'Payment Recorded', message: $msg);
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());
            $this->dispatch('notify', type: 'error', title: 'Payment Failed', message: $e->getMessage());
        }
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetValidation();
        $this->reset(['selectedCustomerId', 'customerName', 'customerCode']);
    }

    public function render(CreditService $creditService)
    {
        $this->authorize('viewAny', Customer::class);

        $query = Customer::where('is_active', true);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('customer_name', 'like', '%' . $this->search . '%')
                  ->orWhere('customer_code', 'like', '%' . $this->search . '%')
                  ->orWhere('phone', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->credit_status_filter !== '') {
            $query->where('credit_status', $this->credit_status_filter);
        }

        if ($this->customer_type_filter !== '') {
            $query->where('customer_type', $this->customer_type_filter);
        }

        $customers = $query->orderBy('customer_name')->paginate(10);

        // Precompute metrics
        foreach ($customers as $c) {
            $c->computed_limit = $c->getEffectiveCreditLimit();
            $c->computed_outstanding = $creditService->getOutstandingBalance($c);
            $c->computed_available = max(0.0, $c->computed_limit - $c->computed_outstanding);
            $c->has_overdue = $creditService->hasOverdueInvoices($c);
        }

        return view('livewire.sales.wholesale.customer-credit-management', [
            'customers' => $customers,
        ])->layout('layouts.app');
    }
}
