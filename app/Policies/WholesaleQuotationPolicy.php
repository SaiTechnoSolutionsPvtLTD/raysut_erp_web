<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WholesaleQuotation;

class WholesaleQuotationPolicy
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

    public function view(User $user, WholesaleQuotation $quotation): bool
    {
        if ($user->hasRole('HQ Admin')) {
            return true;
        }

        $accessibleBranchIds = $user->branches->pluck('branch_id')->push($user->default_branch_id)->filter();
        return $accessibleBranchIds->contains($quotation->branch_id);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('Retail & Wholesale Sales', 'CREATE')
            || $user->hasRole('HQ Admin')
            || $user->hasRole('Branch Admin');
    }

    public function update(User $user, WholesaleQuotation $quotation): bool
    {
        if (! $this->view($user, $quotation)) {
            return false;
        }

        return $user->hasPermission('Retail & Wholesale Sales', 'EDIT')
            || $user->hasRole('HQ Admin')
            || $user->hasRole('Branch Admin');
    }

    public function delete(User $user, WholesaleQuotation $quotation): bool
    {
        if (! $this->view($user, $quotation)) {
            return false;
        }

        return $user->hasPermission('Retail & Wholesale Sales', 'DELETE')
            || $user->hasRole('HQ Admin')
            || $user->hasRole('Branch Admin');
    }

    public function approve(User $user, WholesaleQuotation $quotation): bool
    {
        if (! $this->view($user, $quotation)) {
            return false;
        }

        return $user->hasPermission('Retail & Wholesale Sales', 'APPROVE')
            || $user->hasRole('HQ Admin')
            || $user->hasRole('Branch Admin');
    }
}
