<?php

namespace App\Policies;

use App\Models\PermitVerification;
use App\Models\User;

class PermitVerificationPolicy
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
            || $user->hasRole('HQ Admin')
            || $user->hasRole('Supervisor');
    }

    public function view(User $user, PermitVerification $verification): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Creation is strictly executed via POS checkout service.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Verifications are immutable compliance audit records.
     */
    public function update(User $user, PermitVerification $verification): bool
    {
        return false;
    }

    /**
     * Verifications cannot be deleted.
     */
    public function delete(User $user, PermitVerification $verification): bool
    {
        return false;
    }

    /**
     * Permission to view unmasked civil ID and download/stream screenshot preview.
     */
    public function viewFullCivilId(User $user): bool
    {
        return $user->hasRole('Super Admin')
            || $user->hasRole('HQ Admin')
            || $user->hasRole('Supervisor')
            || $user->hasRole('Branch Admin')
            || $user->hasPermission('Retail & Wholesale Sales', 'APPROVE');
    }

    public function viewScreenshot(User $user, PermitVerification $verification): bool
    {
        return $this->viewFullCivilId($user);
    }
}
