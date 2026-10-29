<?php

namespace App\Livewire\Sales\Wholesale;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\DeliveryNote;
use App\Models\SalesOrder;
use App\Services\DeliveryNoteService;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class DeliveryNoteManagement extends Component
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
    public ?int $selected_sales_order_id = null;
    public ?SalesOrder $selectedOrder = null;

    // Dispatch metadata
    public string $vehicle_no = '';
    public string $driver_name = '';
    public string $notes = '';

    // Dispatch items
    public array $items = [];

    // Delete modal
    public bool $showItemDeleteModal = false;
    public string $itemToDeleteType = 'Delivery Note';
    public ?int $itemToDeleteId = null;
    public string $itemToDeleteName = '';

    public function mount(): void
    {
        $soId = request()->query('sales_order_id');
        if ($soId) {
            $this->openCreateModal((int) $soId);
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

    public function openCreateModal(?int $soId = null): void
    {
        try {
            $this->authorize('create', DeliveryNote::class);

            $this->resetValidation();
            $this->reset(['selected_sales_order_id', 'vehicle_no', 'driver_name', 'notes', 'items']);
            $this->selectedOrder = null;

            if ($soId) {
                $this->selected_sales_order_id = $soId;
                $this->loadOrderItems();
            }

            $this->showModal = true;
        } catch (AuthorizationException $e) {
            $msg = $e->getMessage() ?: 'You are not authorized to create delivery notes.';
            session()->flash('error', $msg);
            $this->dispatch('notify', type: 'error', title: 'Unauthorized', message: $msg);
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());
            $this->dispatch('notify', type: 'error', title: 'Action Failed', message: $e->getMessage());
        }
    }

    public function updatedSelectedSalesOrderId(): void
    {
        $this->loadOrderItems();
    }

    public function loadOrderItems(): void
    {
        if (! $this->selected_sales_order_id) {
            $this->selectedOrder = null;
            $this->items = [];
            return;
        }

        $order = SalesOrder::with(['items.product.uom', 'items.uom', 'items.reservations.batch', 'customer', 'branch', 'warehouse', 'deliveryNotes.items'])
            ->whereIn('status', ['CONFIRMED', 'PARTIALLY_DISPATCHED'])
            ->find($this->selected_sales_order_id);

        if (! $order) {
            $this->selectedOrder = null;
            $this->items = [];
            return;
        }

        $this->selectedOrder = $order;
        $this->items = [];

        foreach ($order->items as $item) {
            $alreadyDispatched = (float) $item->dispatched_quantity;
            $remaining = max(0, (float) $item->quantity - $alreadyDispatched);

            if ($remaining > 0) {
                // Determine batch from reservations if available
                $firstReservation = $item->reservations->first();
                $batchId = $firstReservation?->batch_id;
                $batchNo = $firstReservation?->batch?->batch_number ?? 'Auto (FEFO)';

                $this->items[] = [
                    'order_item_id' => $item->item_id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product?->product_name ?? '',
                    'sku_code' => $item->product?->sku_code ?? '',
                    'uom_code' => $item->uom?->uom_code ?? $item->product?->uom?->uom_code ?? 'PCS',
                    'ordered_quantity' => (float) $item->quantity,
                    'dispatched_quantity' => $alreadyDispatched,
                    'remaining_quantity' => $remaining,
                    'quantity' => $remaining, // default to remaining
                    'batch_id' => $batchId,
                    'batch_number' => $batchNo,
                ];
            }
        }
    }

    public function saveDeliveryNote(DeliveryNoteService $deliveryNoteService): void
    {
        $this->validate([
            'selected_sales_order_id' => ['required', 'exists:sales_orders,order_id'],
            'vehicle_no' => ['nullable', 'string', 'max:50'],
            'driver_name' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.order_item_id' => ['required', 'exists:sales_order_items,item_id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
        ]);

        // Filter items with quantity > 0
        $itemsToDispatch = array_filter($this->items, fn($i) => (float)($i['quantity'] ?? 0) > 0);

        if (empty($itemsToDispatch)) {
            $this->addError('items', 'At least one item must have a dispatch quantity greater than 0.');
            return;
        }

        try {
            $this->authorize('create', DeliveryNote::class);

            $order = SalesOrder::findOrFail($this->selected_sales_order_id);
            $dn = $deliveryNoteService->createAndDispatch(
                $order,
                $itemsToDispatch,
                [
                    'vehicle_no' => $this->vehicle_no,
                    'driver_name' => $this->driver_name,
                    'notes' => $this->notes,
                ],
                auth()->user()
            );

            $this->closeModal();
            $msg = "Delivery Note {$dn->dn_no} created and dispatched successfully.";
            session()->flash('message', $msg);
            $this->dispatch('notify', type: 'success', title: 'Delivery Dispatched', message: $msg);
        } catch (AuthorizationException $e) {
            $msg = $e->getMessage() ?: 'You are not authorized to create delivery notes.';
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
        $this->reset(['selected_sales_order_id', 'vehicle_no', 'driver_name', 'notes', 'items']);
        $this->selectedOrder = null;
    }

    // Delete item modal
    public function confirmDeleteItem(int $id, string $dnNo): void
    {
        try {
            $dn = DeliveryNote::findOrFail($id);
            $this->authorize('delete', $dn);

            $this->itemToDeleteType = 'Delivery Note';
            $this->itemToDeleteId = $id;
            $this->itemToDeleteName = $dnNo;
            $this->showItemDeleteModal = true;
        } catch (AuthorizationException $e) {
            $msg = $e->getMessage() ?: 'You are not authorized to delete this delivery note.';
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
                $dn = DeliveryNote::find($this->itemToDeleteId);
                if ($dn) {
                    $this->authorize('delete', $dn);

                    if ($dn->status === 'DISPATCHED' && $dn->invoice()->exists()) {
                        $err = "Cannot delete Delivery Note '{$dn->dn_no}' because an invoice has already been issued.";
                        session()->flash('error', $err);
                        $this->dispatch('notify', type: 'error', title: 'Cannot Delete', message: $err);
                        $this->showItemDeleteModal = false;
                        return;
                    }

                    $dnNo = $dn->dn_no;
                    $dn->delete();

                    AuditLog::create([
                        'user_id' => auth()->id(),
                        'module_name' => 'Retail & Wholesale Sales',
                        'action' => 'DELETE',
                        'record_table' => 'delivery_notes',
                        'record_id' => $this->itemToDeleteId,
                        'details_json' => ['dn_no' => $dnNo],
                        'ip_address' => request()->ip(),
                    ]);

                    session()->flash('message', "Delivery Note '{$dnNo}' deleted successfully.");
                    $this->dispatch('notify', type: 'warning', title: 'Delivery Note Deleted', message: "Delivery Note '{$dnNo}' deleted successfully.");
                }
            } catch (AuthorizationException $e) {
                $msg = $e->getMessage() ?: 'You are not authorized to delete this delivery note.';
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
        $query = DeliveryNote::with(['salesOrder.customer', 'branch', 'warehouse', 'dispatcher', 'items']);

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
                $q->where('dn_no', 'like', '%' . $this->search . '%')
                  ->orWhereHas('salesOrder', function ($so) {
                      $so->where('so_no', 'like', '%' . $this->search . '%')
                         ->orWhereHas('customer', fn($c) => $c->where('customer_name', 'like', '%' . $this->search . '%'));
                  });
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

        $branches = ($user->hasRole('Super Admin') || $user->hasRole('HQ Admin'))
            ? Branch::where('is_active', true)->orderBy('branch_name')->get()
            : $user->branches;

        // Confirmed orders available for dispatch
        $confirmedOrdersQuery = SalesOrder::with(['customer', 'branch'])
            ->whereIn('status', ['CONFIRMED', 'PARTIALLY_DISPATCHED']);

        if (! ($user->hasRole('Super Admin') || $user->hasRole('HQ Admin'))) {
            $confirmedOrdersQuery->whereIn('branch_id', $user->branches->pluck('branch_id')->push($user->default_branch_id));
        }

        $confirmedOrders = $confirmedOrdersQuery->orderBy('order_id', 'desc')->get();

        return view('livewire.sales.wholesale.delivery-note-management', [
            'deliveryNotes' => $query->orderBy('delivery_note_id', 'desc')->paginate(10),
            'branches' => $branches,
            'confirmedOrders' => $confirmedOrders,
        ])->layout('layouts.app');
    }
}
