<?php

namespace App\Policies;

use App\Models\SalesOrder;
use App\Models\User;

class SalesOrderPolicy
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

    public function view(User $user, SalesOrder $order): bool
    {
        if ($user->hasRole('HQ Admin')) {
            return true;
        }

        $accessibleBranchIds = $user->branches->pluck('branch_id')->push($user->default_branch_id)->filter();
        return $accessibleBranchIds->contains($order->branch_id);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('Retail & Wholesale Sales', 'CREATE')
            || $user->hasRole('HQ Admin')
            || $user->hasRole('Branch Admin');
    }

    public function update(User $user, SalesOrder $order): bool
    {
        if (! $this->view($user, $order)) {
            return false;
        }

        return $user->hasPermission('Retail & Wholesale Sales', 'EDIT')
            || $user->hasRole('HQ Admin')
            || $user->hasRole('Branch Admin');
    }

    public function confirm(User $user, SalesOrder $order): bool
    {
        if (! $this->view($user, $order)) {
            return false;
        }

        return $user->hasPermission('Retail & Wholesale Sales', 'EDIT')
            || $user->hasPermission('Retail & Wholesale Sales', 'APPROVE')
            || $user->hasRole('HQ Admin')
            || $user->hasRole('Branch Admin');
    }

    public function override(User $user, SalesOrder $order): bool
    {
        return $user->hasPermission('Retail & Wholesale Sales', 'APPROVE')
            || $user->hasRole('HQ Admin')
            || $user->hasRole('Branch Admin');
    }

    public function cancel(User $user, SalesOrder $order): bool
    {
        if (! $this->view($user, $order)) {
            return false;
        }

        return $user->hasPermission('Retail & Wholesale Sales', 'EDIT')
            || $user->hasPermission('Retail & Wholesale Sales', 'APPROVE')
            || $user->hasRole('HQ Admin')
            || $user->hasRole('Branch Admin');
    }

    public function delete(User $user, SalesOrder $order): bool
    {
        if (! $this->view($user, $order)) {
            return false;
        }

        return $user->hasPermission('Retail & Wholesale Sales', 'DELETE')
            || $user->hasRole('HQ Admin')
            || $user->hasRole('Branch Admin');
    }
}
