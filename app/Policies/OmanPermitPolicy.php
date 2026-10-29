<?php

namespace App\Policies;

use App\Models\OmanPermit;
use App\Models\User;

class OmanPermitPolicy
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
        return $user->hasPermission('Retail & Wholesale Sales', 'VIEW')
            || $user->hasRole('Cashier')
            || $user->hasRole('Branch Admin')
            || $user->hasRole('HQ Admin');
    }

    public function view(User $user, OmanPermit $permit): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('Retail & Wholesale Sales', 'CREATE')
            || $user->hasRole('HQ Admin');
    }

    public function update(User $user, OmanPermit $permit): bool
    {
        if ($user->hasRole('Cashier') && ! ($user->hasRole('Branch Admin') || $user->hasRole('HQ Admin'))) {
            return false;
        }

        return $user->hasPermission('Retail & Wholesale Sales', 'EDIT')
            || $user->hasRole('Branch Admin')
            || $user->hasRole('HQ Admin');
    }

    public function delete(User $user, OmanPermit $permit): bool
    {
        if ($user->hasRole('Cashier') && ! ($user->hasRole('Branch Admin') || $user->hasRole('HQ Admin'))) {
            return false;
        }

        return $user->hasPermission('Retail & Wholesale Sales', 'DELETE')
            || $user->hasRole('HQ Admin');
    }
}
