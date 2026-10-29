<?php

namespace App\Livewire\BranchOperations;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\CashierShift;
use App\Models\Counter;
use App\Models\RetailSale;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class CounterManagement extends Component
{
    use AuthorizesRequests, WithPagination;

    // Filters
    public string $search = '';
    public string $branch_filter = '';
    public string $status_filter = '';

    // Modal state
    public bool $showModal = false;
    public bool $isEdit = false;
    public ?int $counter_id = null;

    public string $counter_code = '';
    public string $counter_name = '';
    public ?int $branch_id = null;
    public string $terminal_id = '';
    public ?int $assigned_user_id = null;
    public bool $is_active = true;

    // Delete modal
    public bool $showItemDeleteModal = false;
    public string $itemToDeleteType = 'POS Counter';
    public ?int $itemToDeleteId = null;
    public string $itemToDeleteName = '';

    protected function rules(): array
    {
        return [
            'counter_code' => ['required', 'string', 'max:50', Rule::unique('counters', 'counter_code')->ignore($this->counter_id, 'counter_id')],
            'counter_name' => ['required', 'string', 'max:100'],
            'branch_id' => ['required', 'exists:branches,branch_id'],
            'terminal_id' => ['nullable', 'string', 'max:100'],
            'assigned_user_id' => ['nullable', 'exists:users,user_id'],
            'is_active' => ['boolean'],
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingBranchFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'branch_filter', 'status_filter']);
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        if (! auth()->user()?->hasPermission('Branch & Shift Operations', 'CREATE')) {
            abort(403, 'Unauthorized action: You do not have permission to CREATE counters.');
        }

        $this->reset(['counter_id', 'counter_code', 'counter_name', 'terminal_id', 'assigned_user_id']);
        $this->isEdit = false;
        $this->is_active = true;

        // Default to active branch or first available branch
        $user = auth()->user();
        if ($activeBranchId = session('active_branch_id')) {
            $this->branch_id = (int) $activeBranchId;
        } elseif ($user->default_branch_id) {
            $this->branch_id = (int) $user->default_branch_id;
        } else {
            $this->branch_id = (int) (Branch::where('is_active', true)->value('branch_id') ?? 1);
        }

        $nextNum = (Counter::max('counter_id') ?? 0) + 1;
        $this->counter_code = 'CNT-' . str_pad((string) $nextNum, 3, '0', STR_PAD_LEFT);
        $this->terminal_id = 'POS-T' . (100 + $nextNum);

        $this->showModal = true;
    }

    public function editCounter(int $id): void
    {
        if (! auth()->user()?->hasPermission('Branch & Shift Operations', 'EDIT')) {
            abort(403, 'Unauthorized action: You do not have permission to EDIT counters.');
        }

        $counter = Counter::findOrFail($id);
        $this->counter_id = $counter->counter_id;
        $this->counter_code = $counter->counter_code;
        $this->counter_name = $counter->counter_name;
        $this->branch_id = $counter->branch_id;
        $this->terminal_id = (string) $counter->terminal_id;
        $this->assigned_user_id = $counter->assigned_user_id;
        $this->is_active = (bool) $counter->is_active;

        $this->isEdit = true;
        $this->showModal = true;
    }

    public function saveCounter(): void
    {
        $action = $this->isEdit ? 'EDIT' : 'CREATE';
        if (! auth()->user()?->hasPermission('Branch & Shift Operations', $action)) {
            abort(403, "Unauthorized action: You do not have permission to {$action} counters.");
        }

        $data = $this->validate();
        $data['terminal_id'] = ! empty($data['terminal_id']) ? trim($data['terminal_id']) : null;
        $data['assigned_user_id'] = ! empty($data['assigned_user_id']) ? (int) $data['assigned_user_id'] : null;

        if ($this->isEdit) {
            $counter = Counter::findOrFail($this->counter_id);
            $counter->update($data);
            $msg = "POS Counter '{$counter->counter_name}' updated successfully.";
        } else {
            $counter = Counter::create($data);
            $msg = "POS Counter '{$counter->counter_name}' created successfully.";
        }

        AuditLog::create([
            'user_id' => auth()->id(),
            'module_name' => 'Branch & Shift Operations',
            'action' => $this->isEdit ? 'UPDATE' : 'CREATE',
            'record_table' => 'counters',
            'record_id' => $counter->counter_id,
            'details_json' => ['code' => $counter->counter_code, 'name' => $counter->counter_name],
            'ip_address' => request()->ip(),
        ]);

        session()->flash('message', $msg);
        $this->dispatch('notify', type: 'success', title: 'Counter Saved', message: $msg);
        $this->showModal = false;
    }

    public function toggleStatus(int $id): void
    {
        if (! auth()->user()?->hasPermission('Branch & Shift Operations', 'EDIT')) {
            abort(403, 'Unauthorized action: You do not have permission to EDIT counters.');
        }

        $counter = Counter::findOrFail($id);
        $counter->is_active = ! $counter->is_active;
        $counter->save();

        AuditLog::create([
            'user_id' => auth()->id(),
            'module_name' => 'Branch & Shift Operations',
            'action' => 'UPDATE',
            'record_table' => 'counters',
            'record_id' => $counter->counter_id,
            'details_json' => ['is_active' => $counter->is_active],
            'ip_address' => request()->ip(),
        ]);

        $statusLabel = $counter->is_active ? 'Active' : 'Inactive';
        session()->flash('message', "Counter {$counter->counter_code} status updated to {$statusLabel}.");
    }

    public function confirmDeleteCounter(int $id, string $name): void
    {
        if (! auth()->user()?->hasPermission('Branch & Shift Operations', 'DELETE')) {
            abort(403, 'Unauthorized action: You do not have permission to DELETE counters.');
        }

        $this->itemToDeleteType = 'POS Counter';
        $this->itemToDeleteId = $id;
        $this->itemToDeleteName = $name;
        $this->showItemDeleteModal = true;
    }

    public function executeDeleteItem(): void
    {
        if ($this->itemToDeleteType === 'POS Counter' && $this->itemToDeleteId) {
            $this->deleteCounter($this->itemToDeleteId);
        }

        $this->showItemDeleteModal = false;
        $this->reset(['itemToDeleteId', 'itemToDeleteType', 'itemToDeleteName']);
    }

    public function deleteCounter(int $id): void
    {
        if (! auth()->user()?->hasPermission('Branch & Shift Operations', 'DELETE')) {
            abort(403, 'Unauthorized action: You do not have permission to DELETE counters.');
        }

        $counter = Counter::find($id);
        if (! $counter) {
            return;
        }

        // Safe deletion checks: refuse if it has cashier_shifts or sales
        $hasShifts = CashierShift::where('counter_id', $id)->exists();
        $hasSales = (Schema::hasTable('retail_sales') && DB::table('retail_sales')->where('counter_id', $id)->exists())
            || (Schema::hasTable('sales') && DB::table('sales')->where('counter_id', $id)->exists());

        if ($hasShifts || $hasSales) {
            $linked = [];
            if ($hasShifts) $linked[] = 'cashier shifts';
            if ($hasSales) $linked[] = 'sales transactions';

            $err = "Cannot delete POS Counter '{$counter->counter_code}' because it has recorded " . implode(', ', $linked) . '. Please deactivate the counter instead.';
            session()->flash('error', $err);
            $this->dispatch('notify', type: 'error', title: 'Cannot Delete Counter', message: $err);
            return;
        }

        $code = $counter->counter_code;
        $name = $counter->counter_name;
        $counter->delete();

        AuditLog::create([
            'user_id' => auth()->id(),
            'module_name' => 'Branch & Shift Operations',
            'action' => 'DELETE',
            'record_table' => 'counters',
            'record_id' => $id,
            'details_json' => ['code' => $code, 'name' => $name],
            'ip_address' => request()->ip(),
        ]);

        session()->flash('message', "POS Counter '{$name}' deleted successfully.");
        $this->dispatch('notify', type: 'warning', title: 'Counter Deleted', message: "Counter '{$name}' deleted successfully.");
    }

    public function render()
    {
        $user = auth()->user();
        $query = Counter::with(['branch', 'assignedCashier'])->withCount('shifts');

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
                $q->where('counter_name', 'like', '%' . $this->search . '%')
                  ->orWhere('counter_code', 'like', '%' . $this->search . '%')
                  ->orWhere('terminal_id', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->branch_filter !== '') {
            $query->where('branch_id', $this->branch_filter);
        }

        if ($this->status_filter !== '') {
            $query->where('is_active', $this->status_filter === 'active');
        }

        $branches = Branch::where('is_active', true)->orderBy('branch_name')->get();
        $cashiers = User::where('is_active', true)->orderBy('full_name')->get();

        return view('livewire.branch-operations.counter-management', [
            'counters' => $query->orderBy('branch_id')->orderBy('counter_code')->paginate(10),
            'branches' => $branches,
            'cashiers' => $cashiers,
        ])->layout('layouts.app');
    }
}
