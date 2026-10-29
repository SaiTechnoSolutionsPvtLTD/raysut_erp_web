<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @deprecated Legacy Oman Permits Master Table.
 *
 * The ERP no longer owns permit master data or quota ledgers. Permit verification
 * is now captured at POS billing via PermitVerification (manual entry or screenshot OCR).
 * This table is retained for historical data preservation only. Do not perform new reads or writes from POS.
 */
class OmanPermit extends Model
{
    use HasFactory, Auditable;

    protected $table = 'oman_permits';
    protected $primaryKey = 'permit_id';

    protected $fillable = [
        'civil_id',
        'holder_name',
        'phone',
        'permit_no',
        'annual_limit',
        'used_quota',
        'valid_from',
        'valid_to',
        'status',
    ];

    protected $casts = [
        'annual_limit' => 'decimal:3',
        'used_quota' => 'decimal:3',
        'valid_from' => 'date',
        'valid_to' => 'date',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(OmanPermitTransaction::class, 'oman_permit_id', 'permit_id');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(RetailSale::class, 'oman_permit_id', 'permit_id');
    }

    public function getRemainingQuotaAttribute(): float
    {
        return max(0, (float) $this->annual_limit - (float) $this->used_quota);
    }

    public function getMaskedCivilIdAttribute(): ?string
    {
        if (empty($this->civil_id)) {
            return null;
        }

        $raw = trim($this->civil_id);
        $len = strlen($raw);
        if ($len <= 4) {
            return str_repeat('*', $len);
        }

        return str_repeat('*', $len - 4) . substr($raw, -4);
    }

    public function isValid(): bool
    {
        if ($this->status !== 'ACTIVE') {
            return false;
        }

        $today = now()->toDateString();
        $from = $this->valid_from ? $this->valid_from->toDateString() : null;
        $to = $this->valid_to ? $this->valid_to->toDateString() : null;

        if ($from && $from > $today) {
            return false;
        }

        if ($to && $to < $today) {
            return false;
        }

        return true;
    }

    public function isQuotaAvailable(float $amount): bool
    {
        if (! $this->isValid()) {
            return false;
        }

        return ((float) $this->used_quota + $amount) <= (float) $this->annual_limit;
    }
}
