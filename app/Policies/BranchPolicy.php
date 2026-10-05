<?php

namespace App\Policies;

use App\Models\Branch;
use App\Models\User;

class BranchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('Branch & Shift Operations', 'VIEW');
    }

    public function view(User $user, Branch $branch): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        return $user->hasPermission('Branch & Shift Operations', 'VIEW') &&
            ($user->default_branch_id == $branch->branch_id || $user->branches->contains('branch_id', $branch->branch_id));
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('Branch & Shift Operations', 'CREATE');
    }

    public function update(User $user, Branch $branch): bool
    {
        return $user->hasPermission('Branch & Shift Operations', 'EDIT');
    }

    public function delete(User $user, Branch $branch): bool
    {
        return $user->hasPermission('Branch & Shift Operations', 'DELETE');
    }
}
