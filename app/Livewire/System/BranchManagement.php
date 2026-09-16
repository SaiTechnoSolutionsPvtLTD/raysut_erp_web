<?php

namespace App\Livewire\System;

use App\Models\Branch;
use App\Models\Company;
use Livewire\Component;

class BranchManagement extends Component
{
    public bool $isModalOpen = false;
    public ?int $editingBranchId = null;

    public string $branch_code = '';
    public string $branch_name = '';
    public string $branch_type = 'RETAIL';
    public string $custom_branch_type = '';
    public bool $isCustomType = false;
    public string $city = '';
    public bool $is_active = true;

    protected function generateBranchCode(): string
    {
        $nextId = (Branch::max('branch_id') ?? 0) + 1;
        do {
            $code = 'BR-' . str_pad((string)$nextId, 3, '0', STR_PAD_LEFT);
            $exists = Branch::where('branch_code', $code)->exists();
            if ($exists) {
                $nextId++;
            }
        } while ($exists);

        return $code;
    }

    public function create(): void
    {
        if (! auth()->user()?->hasPermission('Branch & Shift Operations', 'CREATE')) {
            abort(403, 'Unauthorized action: You do not have permission to CREATE branches.');
        }

        $this->reset(['editingBranchId', 'branch_name', 'city', 'custom_branch_type']);
        $this->branch_code = $this->generateBranchCode();
        $this->branch_type = 'RETAIL';
        $this->isCustomType = false;
        $this->is_active = true;
        $this->isModalOpen = true;
    }

    public function updatedBranchType($value): void
    {
        if ($value === 'ADD_NEW') {
            $this->isCustomType = true;
            $this->custom_branch_type = '';
        } else {
            $this->isCustomType = false;
        }
    }

    public function edit(int $branchId): void
    {
        if (! auth()->user()?->hasPermission('Branch & Shift Operations', 'EDIT')) {
            abort(403, 'Unauthorized action: You do not have permission to EDIT branches.');
        }

        $branch = Branch::findOrFail($branchId);
        $this->editingBranchId = $branch->branch_id;
        $this->branch_code = $branch->branch_code;
        $this->branch_name = $branch->branch_name;
        $this->city = $branch->city ?? '';
        $this->is_active = (bool)$branch->is_active;

        $defaultTypes = ['RETAIL', 'WHOLESALE', 'HARBOR', 'GROCERY', 'MIXED'];
        $dbTypes = Branch::distinct()->pluck('branch_type')->filter()->toArray();
        $allTypes = array_values(array_unique(array_merge($defaultTypes, $dbTypes)));

        if (in_array($branch->branch_type, $allTypes)) {
            $this->branch_type = $branch->branch_type;
            $this->isCustomType = false;
        } else {
            $this->branch_type = 'ADD_NEW';
            $this->isCustomType = true;
            $this->custom_branch_type = $branch->branch_type;
        }

        $this->isModalOpen = true;
    }

    public function save(): void
    {
        $requiredAction = $this->editingBranchId ? 'EDIT' : 'CREATE';
        if (! auth()->user()?->hasPermission('Branch & Shift Operations', $requiredAction)) {
            abort(403, "Unauthorized action: You do not have permission to {$requiredAction} branches.");
        }

        $finalBranchType = $this->branch_type;

        if ($this->branch_type === 'ADD_NEW' || $this->isCustomType) {
            $this->validate([
                'custom_branch_type' => 'required|string|max:50',
            ]);
            $finalBranchType = strtoupper(trim($this->custom_branch_type));
        }

        $this->validate([
            'branch_code' => 'required|string|max:20',
            'branch_name' => 'required|string|max:150',
            'city' => 'nullable|string|max:100',
            'is_active' => 'boolean',
        ]);

        $company = Company::first();
        if (!$company) {
            $company = Company::create([
                'company_name' => 'Raysut Marketing & Supply Co. LLC (RMSC)',
                'base_currency_code' => 'OMR',
                'is_active' => true,
            ]);
        }

        if ($this->editingBranchId) {
            $branch = Branch::findOrFail($this->editingBranchId);
        } else {
            $branch = new Branch();
            $branch->company_id = $company->company_id;
            if (empty($this->branch_code)) {
                $this->branch_code = $this->generateBranchCode();
            }
        }

        $branch->fill([
            'branch_code' => $this->branch_code,
            'branch_name' => $this->branch_name,
            'branch_type' => $finalBranchType,
            'city' => $this->city,
            'is_active' => $this->is_active,
        ]);

        $branch->save();

        session()->flash('message', 'Branch ' . ($this->editingBranchId ? 'updated' : 'created') . ' successfully.');
        $this->dispatch('notify', type: 'success', title: 'Branch Saved', message: 'Branch ' . ($this->editingBranchId ? 'updated' : 'created') . ' successfully.');
        $this->isModalOpen = false;
    }

    public bool $isDeleteModalOpen = false;
    public ?int $deletingBranchId = null;
    public string $deletingBranchName = '';

    public function confirmDelete(int $branchId): void
    {
        if (! auth()->user()?->hasPermission('Branch & Shift Operations', 'DELETE')) {
            abort(403, 'Unauthorized action: You do not have permission to DELETE branches.');
        }

        $branch = Branch::findOrFail($branchId);
        $this->deletingBranchId = $branch->branch_id;
        $this->deletingBranchName = $branch->branch_name;
        $this->isDeleteModalOpen = true;
    }

    public function deleteBranch(): void
    {
        if (! auth()->user()?->hasPermission('Branch & Shift Operations', 'DELETE')) {
            abort(403, 'Unauthorized action: You do not have permission to DELETE branches.');
        }

        if ($this->deletingBranchId) {
            $name = $this->deletingBranchName ?: 'Branch';
            Branch::destroy($this->deletingBranchId);
            session()->flash('message', "Branch '{$name}' deleted successfully.");
            $this->dispatch('notify', type: 'warning', title: 'Branch Deleted', message: "Branch '{$name}' was deleted successfully.");
            $this->isDeleteModalOpen = false;
            $this->reset(['deletingBranchId', 'deletingBranchName']);
        }
    }

    public function delete(int $branchId): void
    {
        $this->confirmDelete($branchId);
    }

    public function render()
    {
        $defaultTypes = ['RETAIL', 'WHOLESALE', 'HARBOR', 'GROCERY', 'MIXED'];
        $dbTypes = Branch::distinct()->pluck('branch_type')->filter()->toArray();
        $branchTypes = array_values(array_unique(array_merge($defaultTypes, $dbTypes)));

        return view('livewire.system.branch-management', [
            'branches' => Branch::with('company')->get(),
            'branchTypes' => $branchTypes,
        ])->layout('layouts.app');
    }
}
