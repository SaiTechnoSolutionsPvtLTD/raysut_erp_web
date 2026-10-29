<?php

namespace App\Livewire\Sales\Wholesale;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\DeliveryNote;
use App\Models\SalesInvoice;
use App\Models\SalesOrder;
use App\Services\InvoiceService;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class SalesInvoiceManagement extends Component
{
    use AuthorizesRequests, WithPagination;

    // Filters
    public string $search = '';
    public string $status_filter = '';
    public string $branch_filter = '';
    public string $date_from = '';
    public string $date_to = '';

    // Create Invoice Modal (from Delivery Note or Sales Order)
    public bool $showModal = false;
    public string $sourceType = 'DELIVERY_NOTE'; // 'DELIVERY_NOTE' or 'SALES_ORDER'
    public ?int $selectedSourceId = null;

    // Record Payment Modal
    public bool $showPaymentModal = false;
    public ?int $paymentInvoiceId = null;
    public ?SalesInvoice $paymentInvoice = null;
    public float $paymentAmount = 0.0;
    public string $paymentMethod = 'BANK_TRANSFER';
    public string $paymentReference = '';
    public string $paymentNotes = '';

    // Void Invoice Modal
    public bool $showVoidModal = false;
    public ?int $voidInvoiceId = null;
    public ?SalesInvoice $voidInvoice = null;
    public string $voidReason = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingBranchFilter(): void
    {
        $this->resetPage();
    }

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateTo(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'status_filter', 'branch_filter', 'date_from', 'date_to']);
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->authorize('create', SalesInvoice::class);

        $this->resetValidation();
        $this->reset(['sourceType', 'selectedSourceId']);
        $this->showModal = true;
    }

    public function postInvoice(InvoiceService $invoiceService): void
    {
        $this->authorize('create', SalesInvoice::class);

        $this->validate([
            'selectedSourceId' => ['required', 'integer'],
        ], [
            'selectedSourceId.required' => 'Please select a delivery note or sales order to invoice.',
        ]);

        try {
            $user = auth()->user();

            if ($this->sourceType === 'DELIVERY_NOTE') {
                $dn = DeliveryNote::findOrFail($this->selectedSourceId);
                $invoice = $invoiceService->postFromDeliveryNote($dn, $user);
            } else {
                $so = SalesOrder::findOrFail($this->selectedSourceId);
                $invoice = $invoiceService->postFromSalesOrder($so, $user);
            }

            $this->closeModal();
            $msg = "Tax Invoice {$invoice->invoice_no} posted successfully.";
            session()->flash('message', $msg);
            $this->dispatch('notify', type: 'success', title: 'Invoice Posted', message: $msg);
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());
            $this->dispatch('notify', type: 'error', title: 'Action Failed', message: $e->getMessage());
        }
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetValidation();
        $this->reset(['sourceType', 'selectedSourceId']);
    }

    // Payment Flow
    public function openPaymentModal(int $id): void
    {
        $invoice = SalesInvoice::with('customer')->findOrFail($id);
        $this->authorize('recordPayment', $invoice);

        $this->paymentInvoiceId = $id;
        $this->paymentInvoice = $invoice;
        $this->paymentAmount = (float) $invoice->balance_due;
        $this->paymentMethod = 'BANK_TRANSFER';
        $this->paymentReference = '';
        $this->paymentNotes = '';
        $this->showPaymentModal = true;
    }

    public function recordPayment(InvoiceService $invoiceService): void
    {
        if (! $this->paymentInvoiceId) return;

        $invoice = SalesInvoice::findOrFail($this->paymentInvoiceId);
        $this->authorize('recordPayment', $invoice);

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
            session()->flash('message', $msg);
            $this->dispatch('notify', type: 'success', title: 'Payment Recorded', message: $msg);
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());
            $this->dispatch('notify', type: 'error', title: 'Payment Failed', message: $e->getMessage());
        }
    }

    // Void Flow
    public function openVoidModal(int $id): void
    {
        $invoice = SalesInvoice::with('payments')->findOrFail($id);
        $this->authorize('void', $invoice);

        $this->voidInvoiceId = $id;
        $this->voidInvoice = $invoice;
        $this->voidReason = '';
        $this->showVoidModal = true;
    }

    public function executeVoidInvoice(InvoiceService $invoiceService): void
    {
        if (! $this->voidInvoiceId) return;

        $invoice = SalesInvoice::with('payments')->findOrFail($this->voidInvoiceId);
        $this->authorize('void', $invoice);

        if ($invoice->payments->count() > 0) {
            $err = "Cannot void Invoice '{$invoice->invoice_no}' because payment records exist. Reverse payments first.";
            session()->flash('error', $err);
            $this->dispatch('notify', type: 'error', title: 'Void Blocked', message: $err);
            $this->showVoidModal = false;
            return;
        }

        $this->validate([
            'voidReason' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        try {
            $invoiceService->void($invoice, auth()->user(), $this->voidReason);
            $this->showVoidModal = false;
            $msg = "Tax Invoice {$invoice->invoice_no} has been VOIDED and inventory reversed.";
            session()->flash('message', $msg);
            $this->dispatch('notify', type: 'warning', title: 'Invoice Voided', message: $msg);
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());
            $this->dispatch('notify', type: 'error', title: 'Action Failed', message: $e->getMessage());
        }
    }

    public function render()
    {
        $user = auth()->user();
        $query = SalesInvoice::with(['customer', 'branch', 'salesOrder', 'deliveryNote']);

        // Branch Isolation Scope
        if ($user->hasRole('Super Admin') || $user->hasRole('HQ Admin')) {
            if ($activeBranchId = session('active_branch_id')) {
                $query->where('branch_id', $activeBranchId);
            }
        } else {
            $accessibleBranchIds = $user->branches->pluck('branch_id')->push($user->default_branch_id)->filter();
            $query->whereIn('branch_id', $accessibleBranchIds);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('invoice_no', 'like', '%' . $this->search . '%')
                  ->orWhereHas('customer', fn($c) => $c->where('customer_name', 'like', '%' . $this->search . '%'))
                  ->orWhereHas('salesOrder', fn($so) => $so->where('so_no', 'like', '%' . $this->search . '%'));
            });
        }

        if ($this->status_filter !== '') {
            $query->where('status', $this->status_filter);
        }

        if ($this->branch_filter !== '') {
            $query->where('branch_id', $this->branch_filter);
        }

        if ($this->date_from !== '') {
            $query->whereDate('invoice_date', '>=', $this->date_from);
        }

        if ($this->date_to !== '') {
            $query->whereDate('invoice_date', '<=', $this->date_to);
        }

        $branches = ($user->hasRole('Super Admin') || $user->hasRole('HQ Admin'))
            ? Branch::where('is_active', true)->orderBy('branch_name')->get()
            : $user->branches;

        // Dispatched delivery notes ready for invoicing
        $unInvoicedDns = DeliveryNote::with(['salesOrder.customer', 'branch'])
            ->where('status', 'DISPATCHED')
            ->doesntHave('invoice')
            ->orderBy('delivery_note_id', 'desc')
            ->get();

        // Confirmed orders ready for direct invoicing
        $unInvoicedOrders = SalesOrder::with(['customer', 'branch'])
            ->whereIn('status', ['CONFIRMED', 'DISPATCHED'])
            ->doesntHave('invoices')
            ->orderBy('order_id', 'desc')
            ->get();

        return view('livewire.sales.wholesale.sales-invoice-management', [
            'invoices' => $query->orderBy('invoice_id', 'desc')->paginate(10),
            'branches' => $branches,
            'unInvoicedDns' => $unInvoicedDns,
            'unInvoicedOrders' => $unInvoicedOrders,
        ])->layout('layouts.app');
    }
}
