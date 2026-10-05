<?php

namespace App\Livewire\Components;

use App\Models\Branch;
use Illuminate\Support\Facades\Session;
use Livewire\Component;

class BranchSwitcher extends Component
{
    public ?int $selectedBranchId = null;

    public function mount(): void
    {
        $this->selectedBranchId = Session::get('active_branch_id');
    }

    public function switchBranch($branchId): void
    {
        if ($branchId === 'all' || empty($branchId)) {
            Session::forget('active_branch_id');
            $this->selectedBranchId = null;
        } else {
            Session::put('active_branch_id', (int)$branchId);
            $this->selectedBranchId = (int)$branchId;
        }

        $this->dispatch('branch-switched', branchId: $this->selectedBranchId);
        $this->redirect(request()->header('Referer', route('dashboard')));
    }

    public function render()
    {
        $user = auth()->user();
        $isHqOrSuperAdmin = $user && ($user->hasRole('Super Admin') || $user->hasRole('HQ Admin'));
        $branches = $isHqOrSuperAdmin ? Branch::where('is_active', true)->get() : collect();

        return view('livewire.components.branch-switcher', compact('branches', 'isHqOrSuperAdmin'));
    }
}
