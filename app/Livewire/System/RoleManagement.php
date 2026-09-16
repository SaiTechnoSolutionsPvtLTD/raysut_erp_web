<?php

namespace App\Livewire\System;

use App\Models\Permission;
use App\Models\Role;
use Livewire\Component;

class RoleManagement extends Component
{
    public bool $isModalOpen = false;
    public ?int $editingRoleId = null;

    public string $role_name = '';
    public string $description = '';
    public array $selectedPermissionIds = [];

    // Delete Modal Properties
    public bool $isDeleteModalOpen = false;
    public ?int $deletingRoleId = null;
    public string $deletingRoleName = '';

    protected $rules = [
        'role_name' => 'required|string|max:100',
        'description' => 'nullable|string|max:255',
    ];

    public function create(): void
    {
        if (! auth()->user()?->hasPermission('System, Security & Setup', 'CREATE')) {
            abort(403, 'Unauthorized action: You do not have permission to CREATE roles.');
        }

        $this->reset(['editingRoleId', 'role_name', 'description', 'selectedPermissionIds']);
        $this->isModalOpen = true;
    }

    public function edit(int $roleId): void
    {
        if (! auth()->user()?->hasPermission('System, Security & Setup', 'EDIT')) {
            abort(403, 'Unauthorized action: You do not have permission to EDIT roles.');
        }

        $role = Role::with('permissions')->findOrFail($roleId);
        $this->editingRoleId = $role->role_id;
        $this->role_name = $role->role_name;
        $this->description = $role->description ?? '';
        $this->selectedPermissionIds = $role->permissions->pluck('permission_id')->toArray();

        $this->isModalOpen = true;
    }

    public function save(): void
    {
        $requiredAction = $this->editingRoleId ? 'EDIT' : 'CREATE';
        if (! auth()->user()?->hasPermission('System, Security & Setup', $requiredAction)) {
            abort(403, "Unauthorized action: You do not have permission to {$requiredAction} roles.");
        }

        $this->validate();

        if ($this->editingRoleId) {
            $role = Role::findOrFail($this->editingRoleId);
        } else {
            $role = new Role();
        }

        $role->fill([
            'role_name' => $this->role_name,
            'description' => $this->description,
        ]);
        $role->save();

        $role->permissions()->sync($this->selectedPermissionIds);

        session()->flash('message', 'Role ' . ($this->editingRoleId ? 'updated' : 'created') . ' successfully.');
        $this->dispatch('notify', type: 'success', title: 'Role Saved', message: 'Role ' . ($this->editingRoleId ? 'updated' : 'created') . ' successfully.');
        $this->isModalOpen = false;
    }

    public function confirmDelete(int $roleId): void
    {
        if (! auth()->user()?->hasPermission('System, Security & Setup', 'DELETE')) {
            abort(403, 'Unauthorized action: You do not have permission to DELETE roles.');
        }

        $role = Role::findOrFail($roleId);
        $this->deletingRoleId = $role->role_id;
        $this->deletingRoleName = $role->role_name;
        $this->isDeleteModalOpen = true;
    }

    public function deleteRole(): void
    {
        if (! auth()->user()?->hasPermission('System, Security & Setup', 'DELETE')) {
            abort(403, 'Unauthorized action: You do not have permission to DELETE roles.');
        }

        if ($this->deletingRoleId) {
            $name = $this->deletingRoleName ?: 'Role';
            Role::destroy($this->deletingRoleId);
            session()->flash('message', "Role '{$name}' deleted successfully.");
            $this->dispatch('notify', type: 'warning', title: 'Role Removed', message: "Role '{$name}' deleted successfully.");
            $this->isDeleteModalOpen = false;
        }
    }

    public function render()
    {
        return view('livewire.system.role-management', [
            'roles' => Role::with('permissions')->get(),
            'permissionsGrouped' => Permission::all()->groupBy('module_name'),
        ])->layout('layouts.app');
    }
}
