<?php

namespace App\Livewire\Sales\Wholesale;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\Warehouse;
use App\Services\CreditService;
use App\Services\SalesOrderService;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class SalesOrderManagement extends Component
{
    use AuthorizesRequests, WithPagination;

    // Filters
    public string $search = '';
    public string $status_filter = '';
    public string $branch_filter = '';
    public string $date_from = '';
    public string $date_to = '';

    // Modal state (create)
    public bool $showModal = false;
    public bool $isEdit = false;
    public ?int $order_id = null;
    public ?string $so_no = null;

    // Form fields
    public ?int $customer_id = null;
    public ?int $branch_id = null;
    public ?int $warehouse_id = null;
    public ?int $payment_terms_days = 30;
    public string $notes = '';

    // Dynamic line items
    public array $items = [];

    // Totals
    public float $subtotal = 0.0;
    public float $vat_total = 0.0;
    public float $grand_total = 0.0;

    // Confirm / Override Modal
    public bool $showConfirmModal = false;
    public ?int $confirmOrderId = null;
    public ?SalesOrder $confirmOrder = null;
    public ?array $creditCheckResult = null;
    public bool $requiresOverride = false;
    public string $overrideReason = '';

    // Cancel modal
    public bool $showCancelModal = false;
    public ?int $cancelOrderId = null;

    // Delete modal
    public bool $showItemDeleteModal = false;
    public string $itemToDeleteType = 'Sales Order';
    public ?int $itemToDeleteId = null;
    public string $itemToDeleteName = '';

    protected function rules(): array
    {
        return [
            'customer_id' => ['required', 'exists:customers,customer_id'],
            'branch_id' => ['required', 'exists:branches,branch_id'],
            'warehouse_id' => ['required', 'exists:warehouses,warehouse_id'],
            'payment_terms_days' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,product_id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
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
            $this->authorize('create', SalesOrder::class);

            $user = auth()->user();
            $this->resetValidation();
            $this->reset(['order_id', 'so_no', 'customer_id', 'notes']);
            $this->isEdit = false;
            $this->payment_terms_days = 30;

            if ($user->hasRole('Super Admin') || $user->hasRole('HQ Admin')) {
                $this->branch_id = session('active_branch_id') ?: Branch::where('is_active', true)->value('branch_id');
            } else {
                $this->branch_id = $user->default_branch_id;
            }

            // Set default non-bonded warehouse for selected branch
            $this->updateWarehousesForBranch();

            $this->items = [];
            $this->addItem();
            $this->showModal = true;
        } catch (AuthorizationException $e) {
            $msg = $e->getMessage() ?: 'You are not authorized to create sales orders.';
            session()->flash('error', $msg);
            $this->dispatch('notify', type: 'error', title: 'Unauthorized', message: $msg);
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());
            $this->dispatch('notify', type: 'error', title: 'Action Failed', message: $e->getMessage());
        }
    }

    public function updatedBranchId(): void
    {
        $this->updateWarehousesForBranch();
    }

    protected function updateWarehousesForBranch(): void
    {
        if ($this->branch_id) {
            $firstWh = Warehouse::where('branch_id', $this->branch_id)
                ->where('is_active', true)
                ->where('warehouse_type', '!=', 'DUTY_FREE_BONDED')
                ->first();

            $this->warehouse_id = $firstWh?->warehouse_id;
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

            $this->items[$index]['vat_rate'] = (float) ($product->taxRate?->rate_percent ?? $product->taxRate?->rate_percentage ?? 5.0);

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

    public function saveSalesOrder(SalesOrderService $salesOrderService): void
    {
        $this->validate();

        try {
            $user = auth()->user();
            $this->authorize('create', SalesOrder::class);

            $order = $salesOrderService->create([
                'customer_id' => $this->customer_id,
                'branch_id' => $this->branch_id,
                'warehouse_id' => $this->warehouse_id,
                'payment_terms_days' => $this->payment_terms_days,
                'notes' => $this->notes,
            ], $this->items, $user);

            $this->closeModal();
            $msg = "Sales Order {$order->so_no} created successfully.";
            session()->flash('message', $msg);
            $this->dispatch('notify', type: 'success', title: 'Sales Order Created', message: $msg);
        } catch (AuthorizationException $e) {
            $msg = $e->getMessage() ?: 'You are not authorized to create sales orders.';
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
        $this->reset(['order_id', 'so_no', 'customer_id', 'notes', 'items', 'subtotal', 'vat_total', 'grand_total']);
        $this->isEdit = false;
    }

    // Confirm Order Flow
    public function openConfirmModal(int $id, CreditService $creditService): void
    {
        try {
            $order = SalesOrder::with(['customer'])->findOrFail($id);
            $this->authorize('confirm', $order);

            $this->confirmOrderId = $id;
            $this->confirmOrder = $order;
            $this->overrideReason = '';

            // Run Credit Check
            $check = $creditService->check($order->customer, (float) $order->grand_total);
            $this->creditCheckResult = $check;
            $this->requiresOverride = ! $check['allowed'];
            $this->showConfirmModal = true;
        } catch (AuthorizationException $e) {
            $msg = $e->getMessage() ?: 'You are not authorized to confirm this sales order.';
            session()->flash('error', $msg);
            $this->dispatch('notify', type: 'error', title: 'Unauthorized', message: $msg);
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());
            $this->dispatch('notify', type: 'error', title: 'Action Failed', message: $e->getMessage());
        }
    }

    public function executeConfirmOrder(SalesOrderService $salesOrderService): void
    {
        if (! $this->confirmOrderId) return;

        try {
            $order = SalesOrder::findOrFail($this->confirmOrderId);
            $this->authorize('confirm', $order);

            $user = auth()->user();

            if ($this->requiresOverride) {
                $canApprove = $user->hasRole('Super Admin') || $user->hasRole('HQ Admin') || $user->hasRole('Branch Admin') || $user->hasPermission('Retail & Wholesale Sales', 'APPROVE');
                if (! $canApprove) {
                    session()->flash('error', 'Supervisor approval permission is required to override credit limit.');
                    $this->dispatch('notify', type: 'error', title: 'Permission Denied', message: 'Supervisor approval permission is required to override credit limit.');
                    return;
                }

                if (empty(trim($this->overrideReason))) {
                    $this->addError('overrideReason', 'Override reason is required when credit check is blocked.');
                    return;
                }
            }

            $salesOrderService->confirm(
                $order,
                $user,
                $this->requiresOverride,
                $this->requiresOverride ? $this->overrideReason : null
            );

            $this->showConfirmModal = false;
            $msg = "Sales Order {$order->so_no} confirmed and stock reserved successfully.";
            session()->flash('message', $msg);
            $this->dispatch('notify', type: 'success', title: 'Order Confirmed', message: $msg);
        } catch (AuthorizationException $e) {
            $this->showConfirmModal = false;
            $msg = $e->getMessage() ?: 'You are not authorized to confirm this sales order.';
            session()->flash('error', $msg);
            $this->dispatch('notify', type: 'error', title: 'Unauthorized', message: $msg);
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());
            $this->dispatch('notify', type: 'error', title: 'Confirmation Failed', message: $e->getMessage());
        }
    }

    // Cancel Order Flow
    public function confirmCancelOrder(int $id): void
    {
        try {
            $order = SalesOrder::findOrFail($id);
            $this->authorize('cancel', $order);

            $this->cancelOrderId = $id;
            $this->showCancelModal = true;
        } catch (AuthorizationException $e) {
            $msg = $e->getMessage() ?: 'You are not authorized to cancel this sales order.';
            session()->flash('error', $msg);
            $this->dispatch('notify', type: 'error', title: 'Unauthorized', message: $msg);
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());
            $this->dispatch('notify', type: 'error', title: 'Action Failed', message: $e->getMessage());
        }
    }

    public function executeCancelOrder(SalesOrderService $salesOrderService): void
    {
        if (! $this->cancelOrderId) return;

        try {
            $order = SalesOrder::findOrFail($this->cancelOrderId);
            $this->authorize('cancel', $order);

            $salesOrderService->cancel($order, auth()->user());
            $this->showCancelModal = false;
            $msg = "Sales Order {$order->so_no} cancelled and stock reservations released.";
            session()->flash('message', $msg);
            $this->dispatch('notify', type: 'warning', title: 'Order Cancelled', message: $msg);
        } catch (AuthorizationException $e) {
            $this->showCancelModal = false;
            $msg = $e->getMessage() ?: 'You are not authorized to cancel this sales order.';
            session()->flash('error', $msg);
            $this->dispatch('notify', type: 'error', title: 'Unauthorized', message: $msg);
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());
            $this->dispatch('notify', type: 'error', title: 'Action Failed', message: $e->getMessage());
        }
    }

    // Delete item modal
    public function confirmDeleteItem(int $id, string $soNo): void
    {
        try {
            $order = SalesOrder::findOrFail($id);
            $this->authorize('delete', $order);

            $this->itemToDeleteType = 'Sales Order';
            $this->itemToDeleteId = $id;
            $this->itemToDeleteName = $soNo;
            $this->showItemDeleteModal = true;
        } catch (AuthorizationException $e) {
            $msg = $e->getMessage() ?: 'You are not authorized to delete this sales order.';
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
                $order = SalesOrder::find($this->itemToDeleteId);
                if ($order) {
                    $this->authorize('delete', $order);

                    if ($order->deliveryNotes()->exists() || $order->invoices()->exists()) {
                        $err = "Cannot delete Sales Order '{$order->so_no}' because linked delivery notes or invoices exist.";
                        session()->flash('error', $err);
                        $this->dispatch('notify', type: 'error', title: 'Cannot Delete', message: $err);
                        $this->showItemDeleteModal = false;
                        return;
                    }

                    $soNo = $order->so_no;
                    $order->delete();

                    AuditLog::create([
                        'user_id' => auth()->id(),
                        'module_name' => 'Retail & Wholesale Sales',
                        'action' => 'DELETE',
                        'record_table' => 'sales_orders',
                        'record_id' => $this->itemToDeleteId,
                        'details_json' => ['so_no' => $soNo],
                        'ip_address' => request()->ip(),
                    ]);

                    session()->flash('message', "Sales Order '{$soNo}' deleted successfully.");
                    $this->dispatch('notify', type: 'warning', title: 'Order Deleted', message: "Sales Order '{$soNo}' deleted successfully.");
                }
            } catch (AuthorizationException $e) {
                $msg = $e->getMessage() ?: 'You are not authorized to delete this sales order.';
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
        $query = SalesOrder::with(['customer', 'branch', 'warehouse', 'creator', 'items']);

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
                $q->where('so_no', 'like', '%' . $this->search . '%')
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

        $targetBranchId = $this->branch_id ?: ($branches->first()?->branch_id);
        $warehouses = Warehouse::where('is_active', true)
            ->where('branch_id', $targetBranchId)
            ->where('warehouse_type', '!=', 'DUTY_FREE_BONDED')
            ->get();

        $products = Product::where('is_active', true)->with(['taxRate', 'uom', 'pricingTiers'])->orderBy('product_name')->get();

        return view('livewire.sales.wholesale.sales-order-management', [
            'orders' => $query->orderBy('order_id', 'desc')->paginate(10),
            'customers' => $customers,
            'branches' => $branches,
            'warehouses' => $warehouses,
            'products' => $products,
        ])->layout('layouts.app');
    }
}
