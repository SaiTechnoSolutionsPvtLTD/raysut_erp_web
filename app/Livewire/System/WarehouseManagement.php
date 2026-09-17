<?php

namespace App\Livewire\System;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Warehouse;
use Livewire\Component;

class WarehouseManagement extends Component
{
    public bool $isModalOpen = false;
    public ?int $editingWarehouseId = null;

    public ?int $branch_id = null;
    public string $warehouse_code = '';
    public string $warehouse_name = '';
    public string $warehouse_type = 'DUTY_FREE_BONDED';
    public string $custom_warehouse_type = '';
    public bool $isCustomType = false;
    public string $customs_bond_ref = '';
    public bool $is_active = true;

    // Delete Modal Properties
    public bool $isDeleteModalOpen = false;
    public ?int $deletingWarehouseId = null;
    public string $deletingWarehouseName = '';

    protected function generateWarehouseCode(): string
    {
        $nextId = (Warehouse::max('warehouse_id') ?? 0) + 1;
        do {
            $code = 'WH-' . str_pad((string)$nextId, 3, '0', STR_PAD_LEFT);
            $exists = Warehouse::where('warehouse_code', $code)->exists();
            if ($exists) {
                $nextId++;
            }
        } while ($exists);

        return $code;
    }

    public function create(): void
    {
        if (! auth()->user()?->hasPermission('Warehouse & Inventory', 'CREATE')) {
            abort(403, 'Unauthorized action: You do not have permission to CREATE warehouses.');
        }

        $this->reset(['editingWarehouseId', 'branch_id', 'warehouse_name', 'customs_bond_ref', 'custom_warehouse_type']);
        $this->warehouse_code = $this->generateWarehouseCode();
        $this->warehouse_type = 'DUTY_FREE_BONDED';
        $this->isCustomType = false;
        $this->is_active = true;
        $this->isModalOpen = true;
    }

    public function updatedWarehouseType($value): void
    {
        if ($value === 'ADD_NEW') {
            $this->isCustomType = true;
            $this->custom_warehouse_type = '';
        } else {
            $this->isCustomType = false;
        }
    }

    public bool $showItemDeleteModal = false;
    public string $itemToDeleteType = '';
    public string $itemToDeleteName = '';

    public function confirmDeleteWarehouseType(string $type): void
    {
        $this->itemToDeleteType = 'Warehouse Type';
        $this->itemToDeleteName = $type;
        $this->showItemDeleteModal = true;
    }

    public function executeDeleteItem(): void
    {
        $this->showItemDeleteModal = false;
        if ($this->itemToDeleteType === 'Warehouse Type') {
            $this->deleteWarehouseType($this->itemToDeleteName);
        }
        $this->reset(['itemToDeleteType', 'itemToDeleteName']);
    }

    public function deleteWarehouseType(string $type): void
    {
        $count = Warehouse::where('warehouse_type', $type)->count();
        if ($count > 0) {
            session()->flash('error', "Cannot delete warehouse type '{$type}' because {$count} warehouse(s) are using it.");
            return;
        }
        $this->warehouse_type = 'DUTY_FREE_BONDED';
        session()->flash('message', "Warehouse type '{$type}' removed.");
    }

    public function edit(int $warehouseId): void
    {
        if (! auth()->user()?->hasPermission('Warehouse & Inventory', 'EDIT')) {
            abort(403, 'Unauthorized action: You do not have permission to EDIT warehouses.');
        }

        $wh = Warehouse::findOrFail($warehouseId);
        $this->editingWarehouseId = $wh->warehouse_id;
        $this->branch_id = $wh->branch_id;
        $this->warehouse_code = $wh->warehouse_code;
        $this->warehouse_name = $wh->warehouse_name;
        $this->customs_bond_ref = $wh->customs_bond_ref ?? '';
        $this->is_active = (bool)$wh->is_active;

        $defaultTypes = ['DUTY_FREE_BONDED', 'DUTY_PAID', 'MAIN', 'BRANCH_STORE', 'TRANSIT', 'COLD_STORAGE'];
        $dbTypes = Warehouse::distinct()->pluck('warehouse_type')->filter()->toArray();
        $allTypes = array_values(array_unique(array_merge($defaultTypes, $dbTypes)));

        if (in_array($wh->warehouse_type, $allTypes)) {
            $this->warehouse_type = $wh->warehouse_type;
            $this->isCustomType = false;
        } else {
            $this->warehouse_type = 'ADD_NEW';
            $this->isCustomType = true;
            $this->custom_warehouse_type = $wh->warehouse_type;
        }

        $this->isModalOpen = true;
    }

    public function save(): void
    {
        $requiredAction = $this->editingWarehouseId ? 'EDIT' : 'CREATE';
        if (! auth()->user()?->hasPermission('Warehouse & Inventory', $requiredAction)) {
            abort(403, "Unauthorized action: You do not have permission to {$requiredAction} warehouses.");
        }

        $finalType = $this->warehouse_type;

        if ($this->warehouse_type === 'ADD_NEW' || $this->isCustomType) {
            $this->validate([
                'custom_warehouse_type' => 'required|string|max:50',
            ]);
            $finalType = strtoupper(trim(str_replace(' ', '_', $this->custom_warehouse_type)));
        }

        $this->validate([
            'branch_id' => 'nullable|exists:branches,branch_id',
            'warehouse_code' => 'required|string|max:20',
            'warehouse_name' => 'required|string|max:150',
            'customs_bond_ref' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);

        $companyId = Company::first()?->company_id ?? 1;

        if ($this->editingWarehouseId) {
            $wh = Warehouse::findOrFail($this->editingWarehouseId);
        } else {
            $wh = new Warehouse();
            $wh->company_id = $companyId;
            if (empty($this->warehouse_code)) {
                $this->warehouse_code = $this->generateWarehouseCode();
            }
        }

        $wh->fill([
            'branch_id' => $this->branch_id ?: null,
            'warehouse_code' => $this->warehouse_code,
            'warehouse_name' => $this->warehouse_name,
            'warehouse_type' => $finalType,
            'customs_bond_ref' => $this->customs_bond_ref,
            'is_active' => $this->is_active,
        ]);

        $wh->save();

        session()->flash('message', 'Warehouse ' . ($this->editingWarehouseId ? 'updated' : 'created') . ' successfully.');
        $this->dispatch('notify', type: 'success', title: 'Warehouse Saved', message: 'Warehouse ' . ($this->editingWarehouseId ? 'updated' : 'created') . ' successfully.');
        $this->isModalOpen = false;
    }

    public function confirmDelete(int $warehouseId): void
    {
        if (! auth()->user()?->hasPermission('Warehouse & Inventory', 'DELETE')) {
            abort(403, 'Unauthorized action: You do not have permission to DELETE warehouses.');
        }

        $wh = Warehouse::findOrFail($warehouseId);
        $this->deletingWarehouseId = $wh->warehouse_id;
        $this->deletingWarehouseName = $wh->warehouse_name;
        $this->isDeleteModalOpen = true;
    }

    public function deleteWarehouse(): void
    {
        if (! auth()->user()?->hasPermission('Warehouse & Inventory', 'DELETE')) {
            abort(403, 'Unauthorized action: You do not have permission to DELETE warehouses.');
        }

        if ($this->deletingWarehouseId) {
            $name = $this->deletingWarehouseName ?: 'Warehouse';
            Warehouse::destroy($this->deletingWarehouseId);
            session()->flash('message', "Warehouse '{$name}' deleted successfully.");
            $this->dispatch('notify', type: 'warning', title: 'Warehouse Removed', message: "Warehouse '{$name}' deleted successfully.");
            $this->isDeleteModalOpen = false;
        }
    }

    public function render()
    {
        $defaultTypes = ['DUTY_FREE_BONDED', 'DUTY_PAID', 'MAIN', 'BRANCH_STORE', 'TRANSIT', 'COLD_STORAGE'];
        $dbTypes = Warehouse::distinct()->pluck('warehouse_type')->filter()->toArray();
        $warehouseTypes = array_values(array_unique(array_merge($defaultTypes, $dbTypes)));

        return view('livewire.system.warehouse-management', [
            'warehouses' => Warehouse::with(['company', 'branch'])->get(),
            'branches' => Branch::where('is_active', true)->get(),
            'warehouseTypes' => $warehouseTypes,
        ])->layout('layouts.app');
    }
}
