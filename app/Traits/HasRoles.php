<?php

namespace App\Traits;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait HasRoles
{
    /**
     * User roles relationship.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles', 'user_id', 'role_id');
    }

    /**
     * Check if user has a specific role by name.
     */
    public function hasRole(string|array $roles): bool
    {
        if (is_array($roles)) {
            return $this->roles->pluck('role_name')->intersect($roles)->isNotEmpty();
        }

        return $this->roles->contains('role_name', $roles);
    }

    /**
     * Check if user has a specific permission by module_name and action_code.
     */
    public function hasPermission(string $moduleName, string $actionCode): bool
    {
        // Super Admin role bypasses granular checks
        if ($this->hasRole('Super Admin')) {
            return true;
        }

        foreach ($this->roles as $role) {
            foreach ($role->permissions as $permission) {
                if ($permission->module_name === $moduleName && $permission->action_code === $actionCode) {
                    return true;
                }
            }
        }

        return false;
    }
}
