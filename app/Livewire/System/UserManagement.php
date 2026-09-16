<?php

namespace App\Livewire\System;

use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class UserManagement extends Component
{
    public bool $isModalOpen = false;
    public ?int $editingUserId = null;

    public string $username = '';
    public string $full_name = '';
    public string $email = '';
    public string $password = '';
    public ?int $default_branch_id = null;
    public string $preferred_language = 'EN';
    public bool $is_active = true;

    public array $selectedRoleIds = [];
    public array $selectedBranchIds = [];

    // Delete Modal Properties
    public bool $isDeleteModalOpen = false;
    public ?int $deletingUserId = null;
    public string $deletingUserName = '';

    public function create(): void
    {
        if (! auth()->user()?->hasPermission('System, Security & Setup', 'CREATE')) {
            abort(403, 'Unauthorized action: You do not have permission to CREATE user accounts.');
        }

        $this->reset(['editingUserId', 'username', 'full_name', 'email', 'password', 'selectedRoleIds', 'selectedBranchIds']);
        $firstBranch = Branch::first();
        $this->default_branch_id = $firstBranch?->branch_id;
        $this->is_active = true;
        $this->isModalOpen = true;
    }

    public function edit(int $userId): void
    {
        if (! auth()->user()?->hasPermission('System, Security & Setup', 'EDIT')) {
            abort(403, 'Unauthorized action: You do not have permission to EDIT user accounts.');
        }

        $user = User::with(['roles', 'branches'])->findOrFail($userId);
        $this->editingUserId = $user->user_id;
        $this->username = $user->username;
        $this->full_name = $user->full_name;
        $this->email = $user->email ?? '';
        $this->password = '';
        $this->default_branch_id = $user->default_branch_id;
        $this->preferred_language = $user->preferred_language;
        $this->is_active = (bool)$user->is_active;

        $this->selectedRoleIds = $user->roles->pluck('role_id')->toArray();
        $this->selectedBranchIds = $user->branches->pluck('branch_id')->toArray();

        $this->isModalOpen = true;
    }

    public function save(): void
    {
        $requiredAction = $this->editingUserId ? 'EDIT' : 'CREATE';
        if (! auth()->user()?->hasPermission('System, Security & Setup', $requiredAction)) {
            abort(403, "Unauthorized action: You do not have permission to {$requiredAction} user accounts.");
        }

        $rules = [
            'username' => 'required|string|max:50|unique:users,username,' . ($this->editingUserId ?? 'NULL') . ',user_id',
            'full_name' => 'required|string|max:150',
            'email' => 'nullable|email|max:150',
            'default_branch_id' => 'nullable|exists:branches,branch_id',
            'preferred_language' => 'required|in:EN,AR',
            'is_active' => 'boolean',
        ];

        if (! $this->editingUserId || ! empty($this->password)) {
            $rules['password'] = 'required|string|min:6';
        }

        $this->validate($rules);

        if ($this->editingUserId) {
            $user = User::findOrFail($this->editingUserId);
        } else {
            $user = new User();
        }

        $userData = [
            'username' => $this->username,
            'full_name' => $this->full_name,
            'email' => $this->email ?: null,
            'default_branch_id' => $this->default_branch_id ?: null,
            'preferred_language' => $this->preferred_language,
            'is_active' => $this->is_active,
        ];

        if (! empty($this->password)) {
            $userData['password_hash'] = Hash::make($this->password);
        }

        $user->fill($userData);
        $user->save();

        // Sync Roles & Branch Access
        $user->roles()->sync($this->selectedRoleIds);
        $user->branches()->sync($this->selectedBranchIds);

        session()->flash('message', 'User ' . ($this->editingUserId ? 'updated' : 'created') . ' successfully.');
        $this->dispatch('notify', type: 'success', title: 'User Account Saved', message: 'User ' . ($this->editingUserId ? 'updated' : 'created') . ' successfully.');
        $this->isModalOpen = false;
    }

    public function confirmDelete(int $userId): void
    {
        if (! auth()->user()?->hasPermission('System, Security & Setup', 'DELETE')) {
            abort(403, 'Unauthorized action: You do not have permission to DELETE user accounts.');
        }

        $user = User::findOrFail($userId);
        $this->deletingUserId = $user->user_id;
        $this->deletingUserName = $user->full_name;
        $this->isDeleteModalOpen = true;
    }

    public function deleteUser(): void
    {
        if (! auth()->user()?->hasPermission('System, Security & Setup', 'DELETE')) {
            abort(403, 'Unauthorized action: You do not have permission to DELETE user accounts.');
        }

        if ($this->deletingUserId) {
            $name = $this->deletingUserName ?: 'User';
            User::destroy($this->deletingUserId);
            session()->flash('message', "User account '{$name}' deleted.");
            $this->dispatch('notify', type: 'warning', title: 'User Removed', message: "User account '{$name}' deleted successfully.");
            $this->isDeleteModalOpen = false;
        }
    }

    public function render()
    {
        return view('livewire.system.user-management', [
            'users' => User::with(['roles', 'defaultBranch', 'branches'])->get(),
            'roles' => Role::all(),
            'branches' => Branch::where('is_active', true)->get(),
        ])->layout('layouts.app');
    }
}
