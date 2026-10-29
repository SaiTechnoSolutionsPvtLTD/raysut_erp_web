<?php

namespace App\Policies;

use App\Models\RetailSale;
use App\Models\User;

class RetailSalePolicy
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

    public function view(User $user, RetailSale $sale): bool
    {
        if ($user->hasRole('HQ Admin')) {
            return true;
        }

        // Branch-scoped: cashier or staff sees sales in their assigned branches
        $accessibleBranchIds = $user->branches->pluck('branch_id')->push($user->default_branch_id)->filter();
        if ($accessibleBranchIds->contains($sale->branch_id)) {
            return true;
        }

        return (int) $user->user_id === (int) $sale->user_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('Retail & Wholesale Sales', 'CREATE')
            || $user->hasRole('Cashier')
            || $user->hasRole('Branch Admin');
    }

    public function void(User $user, RetailSale $sale): bool
    {
        return $user->hasPermission('Retail & Wholesale Sales', 'APPROVE')
            || $user->hasPermission('Branch & Shift Operations', 'APPROVE')
            || $user->hasRole('Supervisor')
            || $user->hasRole('Branch Admin')
            || $user->hasRole('HQ Admin');
    }
}
