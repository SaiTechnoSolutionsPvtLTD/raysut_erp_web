<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
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
            || $user->hasPermission('Master Data', 'VIEW')
            || $user->hasRole('HQ Admin')
            || $user->hasRole('Branch Admin');
    }

    public function view(User $user, Customer $customer): bool
    {
        return $user->hasPermission('Retail & Wholesale Sales', 'VIEW')
            || $user->hasPermission('Master Data', 'VIEW')
            || $user->hasRole('HQ Admin')
            || $user->hasRole('Branch Admin');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('Retail & Wholesale Sales', 'CREATE')
            || $user->hasPermission('Master Data', 'CREATE')
            || $user->hasRole('HQ Admin')
            || $user->hasRole('Branch Admin');
    }

    public function update(User $user, Customer $customer): bool
    {
        return $user->hasPermission('Retail & Wholesale Sales', 'EDIT')
            || $user->hasPermission('Master Data', 'EDIT')
            || $user->hasRole('HQ Admin')
            || $user->hasRole('Branch Admin');
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $user->hasPermission('Retail & Wholesale Sales', 'DELETE')
            || $user->hasPermission('Master Data', 'DELETE')
            || $user->hasRole('HQ Admin')
            || $user->hasRole('Branch Admin');
    }
}
