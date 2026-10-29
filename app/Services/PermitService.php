<?php

namespace App\Services;

/**
 * @deprecated Legacy Oman Permit service.
 * Permit master CRUD, quota ledgers, and quota consumption logic have been removed.
 * Oman government permit verification is now entered at POS billing manually or via
 * screenshot extraction and stored in permit_verifications.
 *
 * @see \App\Services\Permit\PermitVerificationService
 * @see \App\Services\PosCheckoutService
 */
class PermitService
{
    /**
     * @deprecated Permit lookup is no longer supported; permit master data is not stored in the ERP.
     */
    public function lookup(string $civilId): void
    {
        // Removed per change request: ERP no longer owns permit master data.
    }
}
