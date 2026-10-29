<?php

namespace App\Livewire\Sales\Retail;

use App\Models\Branch;
use App\Models\PermitVerification;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class PermitManagement extends Component
{
    use AuthorizesRequests, WithPagination;

    // Filters
    public string $search = '';
    public string $branch_filter = '';
    public string $method_filter = '';
    public string $status_filter = '';
    public string $statusFilter = '';
    public string $date_from = '';
    public string $date_to = '';

    // Read-only Detail Modal
    public bool $showDetailModal = false;
    public ?int $selectedVerificationId = null;
    public ?PermitVerification $selectedVerification = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingBranchFilter(): void
    {
        $this->resetPage();
    }

    public function updatingMethodFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
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
        $this->reset(['search', 'branch_filter', 'method_filter', 'status_filter', 'date_from', 'date_to']);
        $this->resetPage();
    }

    public function openDetailModal(int $id): void
    {
        $verification = PermitVerification::with(['retailSale', 'branch', 'counter', 'enteredBy', 'attestedBy'])->findOrFail($id);
        $this->authorize('view', $verification);

        $this->selectedVerificationId = $id;
        $this->selectedVerification = $verification;
        $this->showDetailModal = true;
    }

    public function closeDetailModal(): void
    {
        $this->showDetailModal = false;
        $this->selectedVerificationId = null;
        $this->selectedVerification = null;
    }

    public function render()
    {
        $this->authorize('viewAny', PermitVerification::class);

        $user = auth()->user();
        $query = PermitVerification::with(['retailSale', 'branch', 'counter', 'enteredBy']);

        // Branch Isolation Scope
        if (! $user->hasRole('Super Admin') && ! $user->hasRole('HQ Admin')) {
            $accessibleBranchIds = $user->branches->pluck('branch_id')->push($user->default_branch_id)->filter();
            $query->whereIn('branch_id', $accessibleBranchIds);
        } elseif ($this->branch_filter !== '') {
            $query->where('branch_id', $this->branch_filter);
        }

        // Search Filter
        if ($this->search !== '') {
            $term = trim($this->search);
            $query->where(function ($q) use ($term) {
                $q->where('holder_name', 'like', "%{$term}%")
                  ->orWhere('permit_no', 'like', "%{$term}%")
                  ->orWhereHas('retailSale', fn($s) => $s->where('receipt_no', 'like', "%{$term}%"));

                // Exact Civil ID Hash search
                $hash = PermitVerification::generateHash($term);
                $q->orWhere('civil_id_hash', $hash);

                // Last 4 digits search
                if (strlen($term) <= 4 && ctype_digit($term)) {
                    $q->orWhere('civil_id_last4', $term);
                }
            });
        }

        if ($this->method_filter !== '') {
            $query->where('entry_method', $this->method_filter);
        }

        $status = $this->status_filter !== '' ? $this->status_filter : $this->statusFilter;
        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($this->date_from !== '') {
            $query->whereDate('verified_at', '>=', $this->date_from);
        }

        if ($this->date_to !== '') {
            $query->whereDate('verified_at', '<=', $this->date_to);
        }

        $verifications = $query->orderBy('verified_at', 'desc')->paginate(15);

        $branches = ($user->hasRole('Super Admin') || $user->hasRole('HQ Admin'))
            ? Branch::where('is_active', true)->orderBy('branch_name')->get()
            : $user->branches;

        $canViewFullCivilId = $user->can('viewFullCivilId', PermitVerification::class);

        return view('livewire.sales.retail.permit-management', [
            'verifications' => $verifications,
            'branches' => $branches,
            'canViewFullCivilId' => $canViewFullCivilId,
        ])->layout('layouts.app');
    }
}
