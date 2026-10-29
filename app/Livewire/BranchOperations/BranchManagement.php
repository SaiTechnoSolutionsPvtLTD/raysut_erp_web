<?php

namespace App\Livewire\BranchOperations;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\CashierShift;
use App\Models\Counter;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class BranchManagement extends Component
{
    use AuthorizesRequests, WithPagination;

    public const TYPES = ['RETAIL', 'WHOLESALE', 'HARBOR', 'GROCERY', 'MIXED', 'LOGISTICS'];

    // Filters
    public string $search = '';
    public string $type_filter = '';
    public string $status_filter = '';

    // Create / edit modal
    public bool $showModal = false;
    public bool $isEdit = false;
    #[Locked]
    public ?int $branchId = null;

    public string $branch_code = '';
    public string $branch_name = '';
    public string $branch_type = 'RETAIL';
    public string $city = '';
    public string $address = '';
    public bool $is_active = true;

    // Quick-add counter (edit modal only)
    public string $new_counter_code = '';
    public string $new_counter_name = '';
    public string $new_counter_terminal_id = '';

    // Delete confirmation modal (shared by branches and counters)
    public bool $showItemDeleteModal = false;
    #[Locked]
    public ?int $itemToDeleteId = null;
    #[Locked]
    public string $itemToDeleteType = '';
    #[Locked]
    public string $itemToDeleteName = '';

    protected function rules(): array
    {
        return [
            'branch_code' => ['required', 'string', 'max:20', Rule::unique(Branch::class, 'branch_code')->ignore($this->branchId, 'branch_id')],
            'branch_name' => ['required', 'string', 'max:255'],
            'branch_type' => ['required', Rule::in(self::TYPES)],
            'city'        => ['nullable', 'string', 'max:100'],
            'address'     => ['nullable', 'string', 'max:500'],
            'is_active'   => ['boolean'],
        ];
    }

    /* ---------------------------------------------------------------
     | Scoping (same HQ / branch-locked rules as the old controller)
     | Used for the list AND for every id coming back from the browser,
     | so a branch user cannot act on a branch outside their access.
     --------------------------------------------------------------- */
    protected function scopedQuery()
    {
        $user = auth()->user();
        $query = Branch::query();

        if ($user->hasRole('Super Admin') || $user->hasRole('HQ Admin')) {
            if ($activeBranchId = session('active_branch_id')) {
                $query->where('branch_id', $activeBranchId);
            }
        } else {
            $accessibleBranchIds = $user->branches->pluck('branch_id')->push($user->default_branch_id)->filter();
            $query->whereIn('branch_id', $accessibleBranchIds);
        }

        return $query;
    }

    protected function findBranch(int $id): Branch
    {
        return $this->scopedQuery()->findOrFail($id);
    }

    /* ---------------------------- Filters --------------------------- */
    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'type_filter', 'status_filter']);
        $this->resetPage();
    }

    /* ------------------------ Create / Edit modal ------------------- */
    public function openCreateModal(): void
    {
        $this->authorize('create', Branch::class);

        $this->resetForm();
        $this->isEdit = false;

        $nextId = (Branch::max('branch_id') ?? 0) + 1;
        $this->branch_code = 'BR-' . str_pad((string) $nextId, 3, '0', STR_PAD_LEFT);

        $this->showModal = true;
    }

    public function editBranch(int $id): void
    {
        $branch = $this->findBranch($id);
        $this->authorize('update', $branch);

        $this->resetForm();
        $this->isEdit = true;
        $this->branchId = $branch->branch_id;
        $this->branch_code = (string) $branch->branch_code;
        $this->branch_name = (string) $branch->branch_name;
        $this->branch_type = (string) $branch->branch_type;
        $this->city = (string) $branch->city;
        $this->address = (string) $branch->address;
        $this->is_active = (bool) $branch->is_active;

        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $this->reset([
            'isEdit', 'branchId', 'branch_code', 'branch_name', 'branch_type', 'city', 'address', 'is_active',
            'new_counter_code', 'new_counter_name', 'new_counter_terminal_id',
        ]);
        $this->resetValidation();
    }

    public function saveBranch(): void
    {
        if ($this->isEdit) {
            $branch = $this->findBranch((int) $this->branchId);
            $this->authorize('update', $branch);
        } else {
            $this->authorize('create', Branch::class);
        }

        $data = $this->validate();
        $data['city'] = $data['city'] !== '' ? $data['city'] : null;
        $data['address'] = $data['address'] !== '' ? $data['address'] : null;

        if ($this->isEdit) {
            $branch->update($data);
            $action = 'UPDATE';
            $message = 'Branch updated successfully.';
        } else {
            $data['company_id'] = \App\Models\Company::first()?->company_id ?? 1;
            $branch = Branch::create($data);
            $action = 'CREATE';
            $message = 'Branch created successfully.';
        }

        $this->audit($action, $branch->branch_id, ['code' => $branch->branch_code, 'name' => $branch->branch_name]);

        session()->flash('message', $message);
        $this->closeModal();
    }

    /* ----------------------------- Status --------------------------- */
    public function toggleStatus(int $id): void
    {
        $branch = $this->findBranch($id);
        $this->authorize('update', $branch);

        $branch->is_active = ! $branch->is_active;
        $branch->save();

        $this->audit('UPDATE', $branch->branch_id, ['is_active' => $branch->is_active]);

        session()->flash('message', 'Branch status updated to ' . ($branch->is_active ? 'Active' : 'Inactive') . '.');
    }

    /* ------------------------- POS counters ------------------------- */
    public function addCounter(): void
    {
        if (! $this->isEdit || ! $this->branchId) {
            return;
        }

        $branch = $this->findBranch($this->branchId);
        $this->authorize('update', $branch);

        $data = $this->validate([
            'new_counter_code'        => ['required', 'string', 'max:50'],
            'new_counter_name'        => ['required', 'string', 'max:100'],
            'new_counter_terminal_id' => ['nullable', 'string', 'max:100'],
        ]);

        if ($branch->counters()->where('counter_code', $data['new_counter_code'])->exists()) {
            $this->addError('new_counter_code', 'This counter code already exists for this branch.');
            return;
        }

        $counter = new Counter();
        $counter->forceFill([
            'branch_id'    => $branch->branch_id,
            'counter_code' => $data['new_counter_code'],
            'counter_name' => $data['new_counter_name'],
            'terminal_id'  => $data['new_counter_terminal_id'] ?: null,
            'is_active'    => true,
        ])->save();

        $this->reset(['new_counter_code', 'new_counter_name', 'new_counter_terminal_id']);
        $this->resetValidation();
        session()->flash('counter_message', "Counter {$counter->counter_code} added.");
    }

    public function toggleCounter(int $counterId): void
    {
        if (! $this->isEdit || ! $this->branchId) {
            return;
        }

        $branch = $this->findBranch($this->branchId);
        $this->authorize('update', $branch);

        $counter = Counter::where('branch_id', $branch->branch_id)->findOrFail($counterId);
        $counter->is_active = ! $counter->is_active;
        $counter->save();
    }

    /* ------------------------- Delete flow -------------------------- */
    public function confirmDeleteBranch(int $id): void
    {
        $branch = $this->findBranch($id);
        $this->authorize('delete', $branch);

        $this->itemToDeleteId = $branch->branch_id;
        $this->itemToDeleteType = 'Branch';
        $this->itemToDeleteName = $branch->branch_name;
        $this->showItemDeleteModal = true;
    }

    public function confirmDeleteCounter(int $counterId): void
    {
        if (! $this->isEdit || ! $this->branchId) {
            return;
        }

        $branch = $this->findBranch($this->branchId);
        $counter = Counter::where('branch_id', $branch->branch_id)->findOrFail($counterId);
        $this->authorize('delete', $counter);

        $this->itemToDeleteId = $counter->counter_id;
        $this->itemToDeleteType = 'Counter';
        $this->itemToDeleteName = $counter->counter_code;
        $this->showItemDeleteModal = true;
    }

    public function executeDeleteItem(): void
    {
        if ($this->itemToDeleteType === 'Branch') {
            $this->deleteBranch((int) $this->itemToDeleteId);
        } elseif ($this->itemToDeleteType === 'Counter') {
            $this->deleteCounter((int) $this->itemToDeleteId);
        }

        $this->showItemDeleteModal = false;
        $this->reset(['itemToDeleteId', 'itemToDeleteType', 'itemToDeleteName']);
    }

    protected function deleteBranch(int $id): void
    {
        $branch = $this->findBranch($id);
        $this->authorize('delete', $branch);

        $name = $branch->branch_name;
        $code = $branch->branch_code;

        // Safe deletion checks: refuse if it has counters, users, cashier_shifts, warehouse links or sales
        $hasCounters = $branch->counters()->exists();
        $hasWarehouses = $branch->warehouses()->exists();
        $hasUsers = User::where('default_branch_id', $id)->exists()
            || DB::table('user_branch_access')->where('branch_id', $id)->exists();
        $hasShifts = CashierShift::where('branch_id', $id)->exists();
        $hasSales = Schema::hasTable('sales') && DB::table('sales')->where('branch_id', $id)->exists();

        if ($hasCounters || $hasWarehouses || $hasUsers || $hasShifts || $hasSales) {
            $linked = [];
            if ($hasCounters) $linked[] = 'POS counters';
            if ($hasWarehouses) $linked[] = 'warehouses';
            if ($hasUsers) $linked[] = 'assigned staff members';
            if ($hasShifts) $linked[] = 'cashier shifts';
            if ($hasSales) $linked[] = 'sales transactions';

            session()->flash('error', "Cannot delete branch '{$name}' because it has linked " . implode(', ', $linked) . '. Please deactivate the branch instead.');
            return;
        }

        DB::transaction(function () use ($branch, $id, $code, $name) {
            $branch->delete();
            $this->audit('DELETE', $id, ['code' => $code, 'name' => $name]);
        });

        session()->flash('message', "Branch '{$name}' deleted successfully.");
    }

    protected function deleteCounter(int $counterId): void
    {
        $branch = $this->findBranch((int) $this->branchId);
        $counter = Counter::where('branch_id', $branch->branch_id)->findOrFail($counterId);
        $this->authorize('delete', $counter);

        $code = $counter->counter_code;

        try {
            $counter->delete();
        } catch (QueryException $e) {
            session()->flash('counter_error', "Cannot delete counter {$code} because it has linked records (e.g. cashier shifts). Disable it instead.");
            return;
        }

        session()->flash('counter_message', "Counter {$code} deleted.");
    }

    /* ------------------------------ Misc ---------------------------- */
    protected function audit(string $action, int $recordId, array $details): void
    {
        AuditLog::create([
            'user_id'      => auth()->id(),
            'module_name'  => 'Branch & Shift Operations',
            'action'       => $action,
            'record_table' => 'branches',
            'record_id'    => $recordId,
            'details_json' => $details,
            'ip_address'   => request()->ip(),
        ]);
    }

    public function render()
    {
        $search = trim($this->search);

        $branches = $this->scopedQuery()
            ->withCount('counters')
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($w) use ($search) {
                    $w->where('branch_name', 'like', "%{$search}%")
                      ->orWhere('branch_code', 'like', "%{$search}%")
                      ->orWhere('city', 'like', "%{$search}%");
                });
            })
            ->when($this->type_filter !== '', fn ($q) => $q->where('branch_type', $this->type_filter))
            ->when($this->status_filter === 'active', fn ($q) => $q->where('is_active', true))
            ->when($this->status_filter === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy('branch_code')
            ->paginate(10);

        $counters = ($this->isEdit && $this->branchId)
            ? Counter::where('branch_id', $this->branchId)->orderBy('counter_code')->get()
            : collect();

        return view('livewire.branch-operations.branch-management', compact('branches', 'counters'))->layout('layouts.app');
    }
}