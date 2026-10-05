<?php

namespace App\Policies;

use App\Models\CashierShift;
use App\Models\User;

class ShiftPolicy
{
    /**
     * Perform pre-authorization checks.
     */
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

    public function view(User $user, CashierShift $shift): bool
    {
        if ($user->user_id === $shift->user_id) {
            return true;
        }

        return $user->hasPermission('Branch & Shift Operations', 'VIEW');
    }

    public function open(User $user): bool
    {
        return $user->hasPermission('Branch & Shift Operations', 'CREATE') || $user->hasRole('Cashier') || $user->hasRole('Branch Admin');
    }

    public function close(User $user, CashierShift $shift): bool
    {
        if ($user->user_id === $shift->user_id && $shift->status === 'OPEN') {
            return true;
        }

        return $user->hasPermission('Branch & Shift Operations', 'EDIT');
    }

    public function approve(User $user, CashierShift $shift): bool
    {
        return $user->hasPermission('Branch & Shift Operations', 'APPROVE') || $user->hasRole('Branch Admin') || $user->hasRole('HQ Admin');
    }
}
