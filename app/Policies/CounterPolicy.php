<?php

namespace App\Policies;

use App\Models\Counter;
use App\Models\User;

class CounterPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('Branch & Shift Operations', 'VIEW');
    }

    public function view(User $user, Counter $counter): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        return $user->hasPermission('Branch & Shift Operations', 'VIEW') &&
            ($user->default_branch_id == $counter->branch_id || $user->branches->contains('branch_id', $counter->branch_id));
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('Branch & Shift Operations', 'CREATE');
    }

    public function update(User $user, Counter $counter): bool
    {
        return $user->hasPermission('Branch & Shift Operations', 'EDIT');
    }

    public function delete(User $user, Counter $counter): bool
    {
        return $user->hasPermission('Branch & Shift Operations', 'DELETE');
    }
}
