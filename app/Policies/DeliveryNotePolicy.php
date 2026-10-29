<?php

namespace App\Policies;

use App\Models\DeliveryNote;
use App\Models\User;

class DeliveryNotePolicy
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
            || $user->hasRole('HQ Admin')
            || $user->hasRole('Branch Admin');
    }

    public function view(User $user, DeliveryNote $dn): bool
    {
        if ($user->hasRole('HQ Admin')) {
            return true;
        }

        $accessibleBranchIds = $user->branches->pluck('branch_id')->push($user->default_branch_id)->filter();
        return $accessibleBranchIds->contains($dn->branch_id);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('Retail & Wholesale Sales', 'CREATE')
            || $user->hasRole('HQ Admin')
            || $user->hasRole('Branch Admin');
    }

    public function dispatch(User $user, DeliveryNote $dn): bool
    {
        if (! $this->view($user, $dn)) {
            return false;
        }

        return $user->hasPermission('Retail & Wholesale Sales', 'EDIT')
            || $user->hasPermission('Retail & Wholesale Sales', 'APPROVE')
            || $user->hasRole('HQ Admin')
            || $user->hasRole('Branch Admin');
    }

    public function cancel(User $user, DeliveryNote $dn): bool
    {
        if (! $this->view($user, $dn)) {
            return false;
        }

        return $user->hasPermission('Retail & Wholesale Sales', 'EDIT')
            || $user->hasPermission('Retail & Wholesale Sales', 'DELETE')
            || $user->hasRole('HQ Admin')
            || $user->hasRole('Branch Admin');
    }

    public function delete(User $user, DeliveryNote $dn): bool
    {
        if (! $this->view($user, $dn)) {
            return false;
        }

        return $user->hasPermission('Retail & Wholesale Sales', 'DELETE')
            || $user->hasPermission('Retail & Wholesale Sales', 'EDIT')
            || $user->hasRole('HQ Admin')
            || $user->hasRole('Branch Admin');
    }
}
