<?php

namespace App\Livewire\BranchOperations;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\CashierShift;
use App\Models\Counter;
use App\Models\User;
use App\Services\ShiftService;
use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class ShiftManagement extends Component
{
    use AuthorizesRequests, WithPagination;

    // Filters
    public string $search = '';
    public string $status_filter = '';
    public string $branch_filter = '';
    public string $date_filter = '';

    // Open Shift Modal
    public bool $showOpenModal = false;
    public string $openBranchId = '';
    public ?int $openCounterId = null;
    public ?int $openCashierId = null;
    public float $openFloat = 50.000;
    public string $openNotes = '';

    // Close Shift Modal
    public bool $showCloseModal = false;
    public ?int $closeShiftId = null;
    public ?CashierShift $closeShift = null;
    public float $countedCash = 0.000;
    public float $cardTotal = 0.000;
    public string $closeNotes = '';

    // Supervisor Approve/Reject Modal
    public bool $showApproveModal = false;
    public bool $showRejectModal = false;
    public ?int $reviewShiftId = null;
    public ?CashierShift $reviewShift = null;
    public string $supervisorNotes = '';

    // View Details Modal
    public bool $showDetailsModal = false;
    public ?int $selectedShiftId = null;
    public ?CashierShift $selectedShift = null;

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

    public function updatingDateFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'status_filter', 'branch_filter', 'date_filter']);
        $this->resetPage();
    }

    public function updatedOpenBranchId(): void
    {
        $user = auth()->user();
        $counter = Counter::where('is_active', true)
            ->when($this->openBranchId !== '', fn($q) => $q->where('branch_id', $this->openBranchId))
            ->where(function ($q) use ($user) {
                if (! $user->hasRole('Super Admin') && ! $user->hasRole('HQ Admin')) {
                    $q->whereNull('assigned_user_id')
                      ->orWhere('assigned_user_id', $user->user_id);
                }
            })
            ->first();

        $this->openCounterId = $counter?->counter_id;
        $this->syncCashierWithCounter($counter);
    }

    public function updatedOpenCounterId($counterId): void
    {
        if (! $counterId) {
            return;
        }

        $counter = Counter::find($counterId);
        $this->syncCashierWithCounter($counter);
    }

    protected function syncCashierWithCounter(?Counter $counter): void
    {
        $user = auth()->user();
        if ($user->hasRole('Super Admin') || $user->hasRole('HQ Admin')) {
            if ($counter && $counter->assigned_user_id) {
                $this->openCashierId = (int) $counter->assigned_user_id;
            } else {
                $this->openCashierId = (int) $user->user_id;
            }
        } else {
            $this->openCashierId = (int) $user->user_id;
        }
    }

    // Open Shift Flow
    public function openShiftModal(): void
    {
        $user = auth()->user();
        $currentShift = app(ShiftService::class)->getCurrentShift($user);
        if ($currentShift) {
            $this->openCloseModal($currentShift->shift_id);
            return;
        }

        $this->resetValidation();
        $this->openFloat = 50.000;
        $this->openNotes = '';

        if (! $user->hasRole('Super Admin') && ! $user->hasRole('HQ Admin')) {
            $this->openBranchId = (string) ($user->default_branch_id ?? '');
        } else {
            $this->openBranchId = (string) (session('active_branch_id') ?: $this->branch_filter);
        }

        // Auto-select first available counter for this user
        $counter = Counter::where('is_active', true)
            ->when($this->openBranchId !== '', fn($q) => $q->where('branch_id', $this->openBranchId))
            ->where(function ($q) use ($user) {
                if (! $user->hasRole('Super Admin') && ! $user->hasRole('HQ Admin')) {
                    $q->whereNull('assigned_user_id')
                      ->orWhere('assigned_user_id', $user->user_id);
                }
            })
            ->first();

        $this->openCounterId = $counter?->counter_id;
        $this->syncCashierWithCounter($counter);
        $this->showOpenModal = true;
    }

    public function executeOpenShift(ShiftService $shiftService): void
    {
        $this->validate([
            'openCounterId' => ['required', 'exists:counters,counter_id'],
            'openFloat' => ['required', 'numeric', 'min:0'],
            'openNotes' => ['nullable', 'string', 'max:500'],
        ]);

        $user = auth()->user();
        $targetUser = $user;
        if (($user->hasRole('Super Admin') || $user->hasRole('HQ Admin')) && $this->openCashierId) {
            $targetUser = User::find($this->openCashierId) ?? $user;
        }

        try {
            $shift = $shiftService->open(
                $targetUser,
                (int) $this->openCounterId,
                (float) $this->openFloat,
                $this->openNotes ?: null
            );

            AuditLog::create([
                'user_id' => $user->user_id,
                'module_name' => 'Branch & Shift Operations',
                'action' => 'SHIFT_OPEN',
                'record_table' => 'cashier_shifts',
                'record_id' => $shift->shift_id,
                'details_json' => [
                    'counter_id' => $shift->counter_id,
                    'float' => $shift->opening_float,
                    'shift_user_id' => $targetUser->user_id,
                    'opened_by' => $user->user_id,
                ],
                'ip_address' => request()->ip(),
            ]);

            $this->showOpenModal = false;
            $msg = "Shift #{$shift->shift_id} opened successfully for {$targetUser->full_name} on counter {$shift->counter?->counter_code} with float OMR " . number_format($shift->opening_float, 3);
            session()->flash('message', $msg);
            $this->dispatch('notify', type: 'success', title: 'Shift Opened', message: $msg);
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());
            $this->dispatch('notify', type: 'error', title: 'Action Failed', message: $e->getMessage());
        }
    }

    // Close Shift Flow
    public function openCloseModal(int $id): void
    {
        $shift = CashierShift::with(['counter', 'branch'])->findOrFail($id);
        $this->authorize('close', $shift);

        $this->closeShiftId = $id;
        $this->closeShift = $shift;
        $this->countedCash = (float) $shift->expected_cash;
        $this->cardTotal = (float) $shift->card_total;
        $this->closeNotes = '';
        $this->showCloseModal = true;
    }

    public function executeCloseShift(ShiftService $shiftService): void
    {
        if (! $this->closeShiftId) return;

        $shift = CashierShift::findOrFail($this->closeShiftId);
        $this->authorize('close', $shift);

        $this->validate([
            'countedCash' => ['required', 'numeric', 'min:0'],
            'cardTotal' => ['required', 'numeric', 'min:0'],
            'closeNotes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $closedShift = $shiftService->close(
                $shift,
                (float) $this->countedCash,
                (float) $this->cardTotal,
                $this->closeNotes ?: null
            );

            AuditLog::create([
                'user_id' => auth()->id(),
                'module_name' => 'Branch & Shift Operations',
                'action' => 'SHIFT_CLOSE',
                'record_table' => 'cashier_shifts',
                'record_id' => $closedShift->shift_id,
                'details_json' => [
                    'counted_cash' => $closedShift->counted_cash,
                    'variance' => $closedShift->variance,
                    'variance_status' => $closedShift->variance_status,
                ],
                'ip_address' => request()->ip(),
            ]);

            $this->showCloseModal = false;
            $msg = "Shift #{$closedShift->shift_id} closed successfully. Variance: OMR " . number_format($closedShift->variance, 3);
            session()->flash('message', $msg);
            $this->dispatch('notify', type: 'success', title: 'Shift Closed', message: $msg);
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());
            $this->dispatch('notify', type: 'error', title: 'Action Failed', message: $e->getMessage());
        }
    }

    // Supervisor Approve Variance
    public function openApproveModal(int $id): void
    {
        $shift = CashierShift::findOrFail($id);
        $this->authorize('approve', $shift);

        $this->reviewShiftId = $id;
        $this->reviewShift = $shift;
        $this->supervisorNotes = '';
        $this->showApproveModal = true;
    }

    public function executeApproveShift(ShiftService $shiftService): void
    {
        if (! $this->reviewShiftId) return;

        $shift = CashierShift::findOrFail($this->reviewShiftId);
        $this->authorize('approve', $shift);

        try {
            $approvedShift = $shiftService->approve($shift, auth()->user(), $this->supervisorNotes ?: null);

            AuditLog::create([
                'user_id' => auth()->id(),
                'module_name' => 'Branch & Shift Operations',
                'action' => 'SHIFT_APPROVE',
                'record_table' => 'cashier_shifts',
                'record_id' => $approvedShift->shift_id,
                'details_json' => ['approved_by' => auth()->id(), 'variance' => $approvedShift->variance],
                'ip_address' => request()->ip(),
            ]);

            $this->showApproveModal = false;
            $msg = "Shift #{$approvedShift->shift_id} variance approved and reconciled successfully.";
            session()->flash('message', $msg);
            $this->dispatch('notify', type: 'success', title: 'Variance Approved', message: $msg);
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());
            $this->dispatch('notify', type: 'error', title: 'Action Failed', message: $e->getMessage());
        }
    }

    // Supervisor Reject Variance
    public function openRejectModal(int $id): void
    {
        $shift = CashierShift::findOrFail($id);
        $this->authorize('reject', $shift);

        $this->reviewShiftId = $id;
        $this->reviewShift = $shift;
        $this->supervisorNotes = '';
        $this->showRejectModal = true;
    }

    public function executeRejectShift(ShiftService $shiftService): void
    {
        if (! $this->reviewShiftId) return;

        $shift = CashierShift::findOrFail($this->reviewShiftId);
        $this->authorize('reject', $shift);

        $this->validate([
            'supervisorNotes' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        try {
            $rejectedShift = $shiftService->reject($shift, auth()->user(), $this->supervisorNotes);

            AuditLog::create([
                'user_id' => auth()->id(),
                'module_name' => 'Branch & Shift Operations',
                'action' => 'SHIFT_REJECT',
                'record_table' => 'cashier_shifts',
                'record_id' => $rejectedShift->shift_id,
                'details_json' => ['approved_by' => auth()->id(), 'variance' => $rejectedShift->variance, 'notes' => $this->supervisorNotes],
                'ip_address' => request()->ip(),
            ]);

            $this->showRejectModal = false;
            $msg = "Shift #{$rejectedShift->shift_id} variance rejected by supervisor.";
            session()->flash('message', $msg);
            $this->dispatch('notify', type: 'warning', title: 'Variance Rejected', message: $msg);
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());
            $this->dispatch('notify', type: 'error', title: 'Action Failed', message: $e->getMessage());
        }
    }

    public function openDetailsModal(int $id): void
    {
        $this->selectedShiftId = $id;
        $this->selectedShift = CashierShift::with(['user', 'counter', 'branch', 'approver'])->find($id);
        $this->showDetailsModal = true;
    }

    public function closeDetailsModal(): void
    {
        $this->showDetailsModal = false;
        $this->selectedShiftId = null;
        $this->selectedShift = null;
    }

    public function render(ShiftService $shiftService)
    {
        $this->authorize('viewAny', CashierShift::class);

        $user = auth()->user();
        $query = CashierShift::with(['user', 'counter', 'branch', 'approver']);

        // Scoping
        if (! $user->hasRole('Super Admin') && ! $user->hasRole('HQ Admin')) {
            $accessibleBranchIds = $user->branches->pluck('branch_id')->push($user->default_branch_id)->filter();
            $query->whereIn('branch_id', $accessibleBranchIds);
        } elseif ($this->branch_filter !== '') {
            $query->where('branch_id', $this->branch_filter);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->whereHas('user', fn($u) => $u->where('full_name', 'like', '%' . $this->search . '%'))
                  ->orWhereHas('counter', fn($c) => $c->where('counter_code', 'like', '%' . $this->search . '%')
                                                     ->orWhere('counter_name', 'like', '%' . $this->search . '%'));
            });
        }

        if ($this->status_filter !== '') {
            $query->where('status', $this->status_filter);
        }

        if ($this->date_filter !== '') {
            $query->whereDate('opened_at', $this->date_filter);
        }

        $branches = ($user->hasRole('Super Admin') || $user->hasRole('HQ Admin'))
            ? Branch::where('is_active', true)->orderBy('branch_name')->get()
            : $user->branches;

        $counterQuery = Counter::with(['branch', 'assignedCashier'])
            ->where('is_active', true);

        if (! $user->hasRole('Super Admin') && ! $user->hasRole('HQ Admin')) {
            $accessibleBranchIds = $user->branches->pluck('branch_id')->push($user->default_branch_id)->filter();
            $counterQuery->whereIn('branch_id', $accessibleBranchIds);
        } elseif ($this->openBranchId !== '') {
            $counterQuery->where('branch_id', $this->openBranchId);
        }

        $availableCounters = $counterQuery->orderBy('branch_id')->orderBy('counter_code')->get();

        $cashiers = User::where('is_active', true)
            ->when($this->openBranchId !== '', function($q) {
                $q->where(function($sub) {
                    $sub->where('default_branch_id', $this->openBranchId)
                        ->orWhereHas('roles', fn($r) => $r->whereIn('role_name', ['Super Admin', 'HQ Admin']));
                });
            })
            ->orderBy('full_name')
            ->get();

        $currentShift = $shiftService->getCurrentShift($user);

        return view('livewire.branch-operations.shift-management', [
            'shifts' => $query->orderBy('opened_at', 'desc')->paginate(10),
            'branches' => $branches,
            'availableCounters' => $availableCounters,
            'cashiers' => $cashiers,
            'currentShift' => $currentShift,
        ])->layout('layouts.app');
    }
}
