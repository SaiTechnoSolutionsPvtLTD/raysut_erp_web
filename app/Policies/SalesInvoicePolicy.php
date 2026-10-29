<?php

namespace App\Policies;

use App\Models\SalesInvoice;
use App\Models\User;

class SalesInvoicePolicy
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

    public function view(User $user, SalesInvoice $invoice): bool
    {
        if ($user->hasRole('HQ Admin')) {
            return true;
        }

        $accessibleBranchIds = $user->branches->pluck('branch_id')->push($user->default_branch_id)->filter();
        return $accessibleBranchIds->contains($invoice->branch_id);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('Retail & Wholesale Sales', 'CREATE')
            || $user->hasRole('HQ Admin')
            || $user->hasRole('Branch Admin');
    }

    public function post(User $user, SalesInvoice $invoice): bool
    {
        if (! $this->view($user, $invoice)) {
            return false;
        }

        return $user->hasPermission('Retail & Wholesale Sales', 'EDIT')
            || $user->hasPermission('Retail & Wholesale Sales', 'APPROVE')
            || $user->hasRole('HQ Admin')
            || $user->hasRole('Branch Admin');
    }

    public function void(User $user, SalesInvoice $invoice): bool
    {
        if (! $this->view($user, $invoice)) {
            return false;
        }

        return $user->hasPermission('Retail & Wholesale Sales', 'APPROVE')
            || $user->hasRole('HQ Admin')
            || $user->hasRole('Branch Admin');
    }

    public function delete(User $user, SalesInvoice $invoice): bool
    {
        if (! $this->view($user, $invoice)) {
            return false;
        }

        return $user->hasPermission('Retail & Wholesale Sales', 'DELETE')
            || $user->hasRole('HQ Admin');
    }

    public function recordPayment(User $user, SalesInvoice $invoice): bool
    {
        if (! $this->view($user, $invoice)) {
            return false;
        }

        return $user->hasPermission('Retail & Wholesale Sales', 'CREATE')
            || $user->hasPermission('Retail & Wholesale Sales', 'EDIT')
            || $user->hasRole('HQ Admin')
            || $user->hasRole('Branch Admin');
    }
}
