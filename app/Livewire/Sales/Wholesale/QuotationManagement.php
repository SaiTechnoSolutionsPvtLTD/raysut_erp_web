<?php

namespace App\Livewire\Sales\Wholesale;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\WholesaleQuotation;
use App\Services\QuotationService;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class QuotationManagement extends Component
{
    use AuthorizesRequests, WithPagination;

    // Filters
    public string $search = '';
    public string $status_filter = '';
    public string $branch_filter = '';
    public string $date_from = '';
    public string $date_to = '';

    // Modal state (create / edit)
    public bool $showModal = false;
    public bool $isEdit = false;
    public ?int $quotation_id = null;
    public ?string $quote_no = null;

    // Form fields
    public ?int $customer_id = null;
    public ?int $branch_id = null;
    public string $valid_until = '';
    public ?int $payment_terms_days = 30;
    public string $notes = '';

    // Dynamic line items
    public array $items = [];

    // Totals
    public float $subtotal = 0.0;
    public float $vat_total = 0.0;
    public float $grand_total = 0.0;

    // Convert to SO modal
    public bool $showConvertModal = false;
    public ?int $convertQuotationId = null;
    public ?int $targetWarehouseId = null;
    public array $convertWarehouses = [];

    // Reject modal
    public bool $showRejectModal = false;
    public ?int $rejectQuotationId = null;
    public string $rejectReason = '';

    // Delete modal
    public bool $showItemDeleteModal = false;
    public string $itemToDeleteType = 'Wholesale Quotation';
    public ?int $itemToDeleteId = null;
    public string $itemToDeleteName = '';

    protected function rules(): array
    {
        return [
            'customer_id' => ['required', 'exists:customers,customer_id'],
            'branch_id' => ['required', 'exists:branches,branch_id'],
            'valid_until' => ['required', 'date'],
            'payment_terms_days' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,product_id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.vat_rate' => ['nullable', 'numeric', 'min:0'],
            'items.*.uom_id' => ['nullable', 'exists:uom,uom_id'],
        ];
    }

    public function mount(): void
    {
        $user = auth()->user();
        if ($user && ! ($user->hasRole('Super Admin') || $user->hasRole('HQ Admin'))) {
            $this->branch_id = $user->default_branch_id;
        }
    }

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
        try {
            $this->authorize('create', WholesaleQuotation::class);

            $user = auth()->user();
            $this->resetValidation();
            $this->reset(['quotation_id', 'quote_no', 'customer_id', 'notes']);
            $this->isEdit = false;
            $this->valid_until = now()->addDays(30)->toDateString();
            $this->payment_terms_days = 30;

            if ($user->hasRole('Super Admin') || $user->hasRole('HQ Admin')) {
                $this->branch_id = session('active_branch_id') ?: Branch::where('is_active', true)->value('branch_id');
            } else {
                $this->branch_id = $user->default_branch_id;
            }

            $this->items = [];
            $this->addItem();
            $this->showModal = true;
        } catch (AuthorizationException $e) {
            $msg = $e->getMessage() ?: 'You are not authorized to create quotations.';
            session()->flash('error', $msg);
            $this->dispatch('notify', type: 'error', title: 'Unauthorized', message: $msg);
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());
            $this->dispatch('notify', type: 'error', title: 'Action Failed', message: $e->getMessage());
        }
    }

    public function updatedCustomerId($val): void
    {
        if ($val) {
            $cust = Customer::find($val);
            if ($cust && $cust->payment_terms_days !== null) {
                $this->payment_terms_days = (int) $cust->payment_terms_days;
            }
        }
    }

    public function addItem(): void
    {
        $this->items[] = [
            'product_id' => '',
            'product_name' => '',
            'sku_code' => '',
            'quantity' => 1,
            'unit_price' => 0.0,
            'vat_rate' => 5.0,
            'uom_id' => null,
            'uom_code' => '',
            'line_subtotal' => 0.0,
            'line_vat' => 0.0,
            'line_total' => 0.0,
        ];
        $this->recalculateTotals();
    }

    public function removeItem(int $index): void
    {
        if (isset($this->items[$index])) {
            unset($this->items[$index]);
            $this->items = array_values($this->items);
            $this->recalculateTotals();
        }
    }

    public function updatedItemProduct(int $index, $productId): void
    {
        if (! isset($this->items[$index])) return;

        $product = Product::with(['taxRate', 'uom', 'pricingTiers'])->find($productId);
        if ($product) {
            $this->items[$index]['product_id'] = $product->product_id;
            $this->items[$index]['product_name'] = $product->product_name;
            $this->items[$index]['sku_code'] = $product->sku_code;
            $this->items[$index]['uom_id'] = $product->base_uom_id;
            $this->items[$index]['uom_code'] = $product->uom?->uom_code ?? 'PCS';

            // VAT rate
            $this->items[$index]['vat_rate'] = (float) ($product->taxRate?->rate_percent ?? $product->taxRate?->rate_percentage ?? 5.0);

            // Unit price: try WHOLESALE pricing tier first
            $wholesaleTier = $product->pricingTiers->firstWhere('price_tier', 'WHOLESALE') ?? $product->pricingTiers->firstWhere('tier_name', 'WHOLESALE');
            if ($wholesaleTier) {
                $this->items[$index]['unit_price'] = (float) $wholesaleTier->unit_price;
            } else {
                $retailTier = $product->pricingTiers->firstWhere('price_tier', 'RETAIL') ?? $product->pricingTiers->firstWhere('tier_name', 'RETAIL');
                $this->items[$index]['unit_price'] = (float) ($retailTier?->unit_price ?? 0.0);
            }

            $this->calculateLine($index);
        }
    }

    public function updatedItems($value, $key): void
    {
        // $key format: "0.quantity" or "0.unit_price"
        $parts = explode('.', $key);
        if (count($parts) >= 2) {
            $index = (int) $parts[0];
            $field = $parts[1];

            if ($field === 'product_id') {
                $this->updatedItemProduct($index, $value);
            } elseif (in_array($field, ['quantity', 'unit_price', 'vat_rate'])) {
                $this->calculateLine($index);
            }
        }
    }

    protected function calculateLine(int $index): void
    {
        if (! isset($this->items[$index])) return;

        $qty = (float) ($this->items[$index]['quantity'] ?? 0);
        $price = (float) ($this->items[$index]['unit_price'] ?? 0);
        $vatRate = (float) ($this->items[$index]['vat_rate'] ?? 0);

        $lineSubtotal = round($qty * $price, 3);
        $lineVat = round($lineSubtotal * ($vatRate / 100), 3);
        $lineTotal = round($lineSubtotal + $lineVat, 3);

        $this->items[$index]['line_subtotal'] = $lineSubtotal;
        $this->items[$index]['line_vat'] = $lineVat;
        $this->items[$index]['line_total'] = $lineTotal;

        $this->recalculateTotals();
    }

    protected function recalculateTotals(): void
    {
        $sub = 0.0;
        $vat = 0.0;
        $grand = 0.0;

        foreach ($this->items as $item) {
            $sub += (float) ($item['line_subtotal'] ?? 0);
            $vat += (float) ($item['line_vat'] ?? 0);
            $grand += (float) ($item['line_total'] ?? 0);
        }

        $this->subtotal = round($sub, 3);
        $this->vat_total = round($vat, 3);
        $this->grand_total = round($grand, 3);
    }

    public function editQuotation(int $id): void
    {
        try {
            $quotation = WholesaleQuotation::with(['items.product.uom', 'items.uom'])->findOrFail($id);
            $this->authorize('update', $quotation);

            if (in_array($quotation->status, ['CONVERTED', 'REJECTED'])) {
                $err = "Quotation {$quotation->quote_no} is locked and cannot be edited.";
                session()->flash('error', $err);
                $this->dispatch('notify', type: 'error', title: 'Cannot Edit', message: $err);
                return;
            }

            $this->resetValidation();
            $this->quotation_id = $quotation->quotation_id;
            $this->quote_no = $quotation->quote_no;
            $this->customer_id = $quotation->customer_id;
            $this->branch_id = $quotation->branch_id;
            $this->valid_until = $quotation->valid_until ? $quotation->valid_until->format('Y-m-d') : now()->addDays(30)->toDateString();
            $this->payment_terms_days = $quotation->payment_terms_days;
            $this->notes = $quotation->notes ?? '';
            $this->isEdit = true;

            $this->items = [];
            foreach ($quotation->items as $item) {
                $this->items[] = [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product?->product_name ?? '',
                    'sku_code' => $item->product?->sku_code ?? '',
                    'quantity' => (float) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'vat_rate' => (float) $item->vat_rate,
                    'uom_id' => $item->uom_id,
                    'uom_code' => $item->uom?->uom_code ?? $item->product?->uom?->uom_code ?? 'PCS',
                    'line_subtotal' => (float) $item->line_total - (float) $item->vat_amount,
                    'line_vat' => (float) $item->vat_amount,
                    'line_total' => (float) $item->line_total,
                ];
            }

            $this->recalculateTotals();
            $this->showModal = true;
        } catch (AuthorizationException $e) {
            $msg = $e->getMessage() ?: 'You are not authorized to edit this quotation.';
            session()->flash('error', $msg);
            $this->dispatch('notify', type: 'error', title: 'Unauthorized', message: $msg);
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());
            $this->dispatch('notify', type: 'error', title: 'Action Failed', message: $e->getMessage());
        }
    }

    public function saveQuotation(QuotationService $quotationService): void
    {
        $this->validate();

        try {
            $user = auth()->user();

            if ($this->isEdit && $this->quotation_id) {
                $quotation = WholesaleQuotation::findOrFail($this->quotation_id);
                $this->authorize('update', $quotation);

                $quotationService->update($quotation, [
                    'valid_until' => $this->valid_until,
                    'payment_terms_days' => $this->payment_terms_days,
                    'notes' => $this->notes,
                ], $this->items, $user);

                $msg = "Wholesale Quotation {$quotation->quote_no} updated successfully.";
            } else {
                $this->authorize('create', WholesaleQuotation::class);

                $quotation = $quotationService->create([
                    'customer_id' => $this->customer_id,
                    'branch_id' => $this->branch_id,
                    'valid_until' => $this->valid_until,
                    'payment_terms_days' => $this->payment_terms_days,
                    'notes' => $this->notes,
                ], $this->items, $user);

                $msg = "Wholesale Quotation {$quotation->quote_no} created successfully.";
            }

            $this->closeModal();
            session()->flash('message', $msg);
            $this->dispatch('notify', type: 'success', title: 'Quotation Saved', message: $msg);
        } catch (AuthorizationException $e) {
            $msg = $e->getMessage() ?: 'You are not authorized to perform this action.';
            session()->flash('error', $msg);
            $this->dispatch('notify', type: 'error', title: 'Unauthorized', message: $msg);
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());
            $this->dispatch('notify', type: 'error', title: 'Action Failed', message: $e->getMessage());
        }
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetValidation();
        $this->reset(['quotation_id', 'quote_no', 'customer_id', 'notes', 'items', 'subtotal', 'vat_total', 'grand_total']);
        $this->isEdit = false;
    }

    // Status Actions
    public function sendQuotation(int $id, QuotationService $quotationService): void
    {
        try {
            $quotation = WholesaleQuotation::findOrFail($id);
            $this->authorize('update', $quotation);

            $quotationService->send($quotation, auth()->user());
            session()->flash('message', "Quotation {$quotation->quote_no} marked as SENT.");
            $this->dispatch('notify', type: 'info', title: 'Quotation Sent', message: "Quotation {$quotation->quote_no} marked as SENT.");
        } catch (AuthorizationException $e) {
            $msg = $e->getMessage() ?: 'You are not authorized to send this quotation.';
            session()->flash('error', $msg);
            $this->dispatch('notify', type: 'error', title: 'Unauthorized', message: $msg);
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());
            $this->dispatch('notify', type: 'error', title: 'Action Failed', message: $e->getMessage());
        }
    }

    public function acceptQuotation(int $id, QuotationService $quotationService): void
    {
        try {
            $quotation = WholesaleQuotation::findOrFail($id);
            $this->authorize('approve', $quotation);

            $quotationService->accept($quotation, auth()->user());
            session()->flash('message', "Quotation {$quotation->quote_no} marked as ACCEPTED.");
            $this->dispatch('notify', type: 'success', title: 'Quotation Accepted', message: "Quotation {$quotation->quote_no} marked as ACCEPTED.");
        } catch (AuthorizationException $e) {
            $msg = $e->getMessage() ?: 'You are not authorized to approve this quotation.';
            session()->flash('error', $msg);
            $this->dispatch('notify', type: 'error', title: 'Unauthorized', message: $msg);
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());
            $this->dispatch('notify', type: 'error', title: 'Action Failed', message: $e->getMessage());
        }
    }

    public function openRejectModal(int $id): void
    {
        try {
            $quotation = WholesaleQuotation::findOrFail($id);
            $this->authorize('approve', $quotation);

            $this->rejectQuotationId = $id;
            $this->rejectReason = '';
            $this->showRejectModal = true;
        } catch (AuthorizationException $e) {
            $msg = $e->getMessage() ?: 'You are not authorized to reject this quotation.';
            session()->flash('error', $msg);
            $this->dispatch('notify', type: 'error', title: 'Unauthorized', message: $msg);
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());
            $this->dispatch('notify', type: 'error', title: 'Action Failed', message: $e->getMessage());
        }
    }

    public function confirmReject(QuotationService $quotationService): void
    {
        if (! $this->rejectQuotationId) return;

        try {
            $quotation = WholesaleQuotation::findOrFail($this->rejectQuotationId);
            $this->authorize('approve', $quotation);

            $quotationService->reject($quotation, auth()->user(), $this->rejectReason);
            $this->showRejectModal = false;
            session()->flash('message', "Quotation {$quotation->quote_no} has been REJECTED.");
            $this->dispatch('notify', type: 'warning', title: 'Quotation Rejected', message: "Quotation {$quotation->quote_no} has been REJECTED.");
        } catch (AuthorizationException $e) {
            $this->showRejectModal = false;
            $msg = $e->getMessage() ?: 'You are not authorized to reject this quotation.';
            session()->flash('error', $msg);
            $this->dispatch('notify', type: 'error', title: 'Unauthorized', message: $msg);
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());
            $this->dispatch('notify', type: 'error', title: 'Action Failed', message: $e->getMessage());
        }
    }

    public function openConvertModal(int $id): void
    {
        try {
            $quotation = WholesaleQuotation::findOrFail($id);
            $this->authorize('update', $quotation);

            $this->convertQuotationId = $id;
            $this->convertWarehouses = Warehouse::where('branch_id', $quotation->branch_id)
                ->where('is_active', true)
                ->where('warehouse_type', '!=', 'DUTY_FREE_BONDED')
                ->get()
                ->toArray();

            $this->targetWarehouseId = !empty($this->convertWarehouses) ? $this->convertWarehouses[0]['warehouse_id'] : null;
            $this->showConvertModal = true;
        } catch (AuthorizationException $e) {
            $msg = $e->getMessage() ?: 'You are not authorized to convert this quotation.';
            session()->flash('error', $msg);
            $this->dispatch('notify', type: 'error', title: 'Unauthorized', message: $msg);
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());
            $this->dispatch('notify', type: 'error', title: 'Action Failed', message: $e->getMessage());
        }
    }

    public function confirmConvert(QuotationService $quotationService): void
    {
        if (! $this->convertQuotationId || ! $this->targetWarehouseId) return;

        try {
            $quotation = WholesaleQuotation::findOrFail($this->convertQuotationId);
            $this->authorize('update', $quotation);

            $so = $quotationService->convertToSalesOrder($quotation, (int) $this->targetWarehouseId, auth()->user());
            $this->showConvertModal = false;
            session()->flash('message', "Quotation {$quotation->quote_no} converted to Sales Order {$so->so_no}.");
            $this->dispatch('notify', type: 'success', title: 'Converted to SO', message: "Quotation {$quotation->quote_no} converted to Sales Order {$so->so_no}.");
        } catch (AuthorizationException $e) {
            $this->showConvertModal = false;
            $msg = $e->getMessage() ?: 'You are not authorized to convert this quotation.';
            session()->flash('error', $msg);
            $this->dispatch('notify', type: 'error', title: 'Unauthorized', message: $msg);
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());
            $this->dispatch('notify', type: 'error', title: 'Conversion Failed', message: $e->getMessage());
        }
    }

    // Delete item modal
    public function confirmDeleteItem(int $id, string $quoteNo): void
    {
        try {
            $quotation = WholesaleQuotation::findOrFail($id);
            $this->authorize('delete', $quotation);

            $this->itemToDeleteType = 'Wholesale Quotation';
            $this->itemToDeleteId = $id;
            $this->itemToDeleteName = $quoteNo;
            $this->showItemDeleteModal = true;
        } catch (AuthorizationException $e) {
            $msg = $e->getMessage() ?: 'You are not authorized to delete this quotation.';
            session()->flash('error', $msg);
            $this->dispatch('notify', type: 'error', title: 'Unauthorized', message: $msg);
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());
            $this->dispatch('notify', type: 'error', title: 'Action Failed', message: $e->getMessage());
        }
    }

    public function executeDeleteItem(): void
    {
        if ($this->itemToDeleteId) {
            try {
                $quotation = WholesaleQuotation::find($this->itemToDeleteId);
                if ($quotation) {
                    $this->authorize('delete', $quotation);

                    if ($quotation->status === 'CONVERTED' || ($quotation->salesOrder()->exists())) {
                        $err = "Cannot delete Quotation '{$quotation->quote_no}' because it has already been converted to a Sales Order.";
                        session()->flash('error', $err);
                        $this->dispatch('notify', type: 'error', title: 'Cannot Delete', message: $err);
                        $this->showItemDeleteModal = false;
                        return;
                    }

                    $quoteNo = $quotation->quote_no;
                    $quotation->delete();

                    AuditLog::create([
                        'user_id' => auth()->id(),
                        'module_name' => 'Retail & Wholesale Sales',
                        'action' => 'DELETE',
                        'record_table' => 'wholesale_quotations',
                        'record_id' => $this->itemToDeleteId,
                        'details_json' => ['quote_no' => $quoteNo],
                        'ip_address' => request()->ip(),
                    ]);

                    session()->flash('message', "Quotation '{$quoteNo}' deleted successfully.");
                    $this->dispatch('notify', type: 'warning', title: 'Quotation Deleted', message: "Quotation '{$quoteNo}' deleted successfully.");
                }
            } catch (AuthorizationException $e) {
                $msg = $e->getMessage() ?: 'You are not authorized to delete this quotation.';
                session()->flash('error', $msg);
                $this->dispatch('notify', type: 'error', title: 'Unauthorized', message: $msg);
            } catch (Exception $e) {
                session()->flash('error', $e->getMessage());
                $this->dispatch('notify', type: 'error', title: 'Action Failed', message: $e->getMessage());
            }
        }

        $this->showItemDeleteModal = false;
        $this->reset(['itemToDeleteId', 'itemToDeleteType', 'itemToDeleteName']);
    }

    public function render()
    {
        $user = auth()->user();
        $query = WholesaleQuotation::with(['customer', 'branch', 'creator', 'salesOrder']);

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
                $q->where('quote_no', 'like', '%' . $this->search . '%')
                  ->orWhereHas('customer', fn($c) => $c->where('customer_name', 'like', '%' . $this->search . '%'));
            });
        }

        if ($this->status_filter !== '') {
            $query->where('status', $this->status_filter);
        }

        if ($this->branch_filter !== '') {
            $query->where('branch_id', $this->branch_filter);
        }

        if ($this->date_from !== '') {
            $query->whereDate('created_at', '>=', $this->date_from);
        }

        if ($this->date_to !== '') {
            $query->whereDate('created_at', '<=', $this->date_to);
        }

        $customers = Customer::where('is_active', true)->orderBy('customer_name')->get();
        $branches = ($user->hasRole('Super Admin') || $user->hasRole('HQ Admin'))
            ? Branch::where('is_active', true)->orderBy('branch_name')->get()
            : $user->branches;

        $products = Product::where('is_active', true)->with(['taxRate', 'uom', 'pricingTiers'])->orderBy('product_name')->get();

        return view('livewire.sales.wholesale.quotation-management', [
            'quotations' => $query->orderBy('quotation_id', 'desc')->paginate(10),
            'customers' => $customers,
            'branches' => $branches,
            'products' => $products,
        ])->layout('layouts.app');
    }
}
