<?php

namespace App\Livewire\System;

use App\Models\Company;
use Livewire\Component;

class CompanyManagement extends Component
{
    public ?Company $company = null;

    public string $company_name = '';
    public string $trade_license_no = '';
    public string $liquor_license_no = '';
    public string $base_currency_code = 'OMR';
    public string $address = '';
    public bool $is_active = true;

    public bool $isEditing = false;

    public function mount(): void
    {
        $this->company = Company::first();

        if ($this->company) {
            $this->company_name = $this->company->company_name;
            $this->trade_license_no = $this->company->trade_license_no ?? '';
            $this->liquor_license_no = $this->company->liquor_license_no ?? '';
            $this->base_currency_code = $this->company->base_currency_code ?? 'OMR';
            $this->address = $this->company->address ?? '';
            $this->is_active = (bool)$this->company->is_active;
        }
    }

    public function toggleEdit(): void
    {
        if (! auth()->user()?->hasPermission('System, Security & Setup', 'EDIT')) {
            abort(403, 'Unauthorized action: You do not have permission to EDIT Company Profile.');
        }

        $this->isEditing = true;
    }

    public function cancelEdit(): void
    {
        $this->mount();
        $this->isEditing = false;
    }

    public function save(): void
    {
        if (! auth()->user()?->hasPermission('System, Security & Setup', 'EDIT')) {
            abort(403, 'Unauthorized action: You do not have permission to EDIT Company Profile.');
        }

        $this->validate([
            'company_name' => 'required|string|max:150',
            'trade_license_no' => 'nullable|string|max:50',
            'liquor_license_no' => 'nullable|string|max:50',
            'base_currency_code' => 'required|string|size:3',
            'address' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        if (! $this->company) {
            $this->company = new Company();
        }

        $this->company->fill([
            'company_name' => $this->company_name,
            'trade_license_no' => $this->trade_license_no,
            'liquor_license_no' => $this->liquor_license_no,
            'base_currency_code' => $this->base_currency_code,
            'address' => $this->address,
            'is_active' => $this->is_active,
        ]);

        $this->company->save();

        session()->flash('message', 'Company Profile saved successfully.');
        $this->dispatch('notify', type: 'success', title: 'Company Profile Saved', message: 'Company profile details updated successfully.');
        $this->isEditing = false;
    }

    public function render()
    {
        return view('livewire.system.company-management')->layout('layouts.app');
    }
}
