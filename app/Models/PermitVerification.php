<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\HasBranchScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PermitVerification extends Model
{
    use HasFactory, HasBranchScope, Auditable;

    protected $table = 'permit_verifications';

    protected $fillable = [
        'retail_sale_id',
        'branch_id',
        'counter_id',
        'cashier_shift_id',
        'entered_by',
        'civil_id',
        'civil_id_hash',
        'civil_id_last4',
        'holder_name',
        'permit_no',
        'annual_limit',
        'used_amount',
        'remaining_amount',
        'valid_from',
        'valid_to',
        'verified_at',
        'entry_method',
        'screenshot_path',
        'extraction_confidence',
        'extraction_raw',
        'fields_edited_after_extraction',
        'controlled_total',
        'status',
        'attested_at',
        'attested_by',
        'attestation_text',
    ];

    protected $casts = [
        'civil_id' => 'encrypted',
        'annual_limit' => 'decimal:3',
        'used_amount' => 'decimal:3',
        'remaining_amount' => 'decimal:3',
        'controlled_total' => 'decimal:3',
        'extraction_confidence' => 'decimal:4',
        'extraction_raw' => 'array',
        'fields_edited_after_extraction' => 'array',
        'valid_from' => 'date',
        'valid_to' => 'date',
        'verified_at' => 'datetime',
        'attested_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (PermitVerification $model) {
            if (! empty($model->civil_id)) {
                $raw = trim($model->civil_id);
                $model->civil_id_hash = static::generateHash($raw);
                $model->civil_id_last4 = strlen($raw) >= 4 ? substr($raw, -4) : str_pad($raw, 4, '*', STR_PAD_LEFT);
            }
        });
    }

    /**
     * Compute deterministic HMAC-SHA256 of civil ID using app key.
     */
    public static function generateHash(string $civilId): string
    {
        $key = config('app.key');
        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }

        return hash_hmac('sha256', trim($civilId), $key);
    }

    /**
     * Masked civil ID accessor (showing only last 4 digits).
     */
    public function getMaskedCivilIdAttribute(): string
    {
        $raw = $this->civil_id_last4 ?? '';
        if (empty($raw) && ! empty($this->civil_id)) {
            $raw = substr(trim($this->civil_id), -4);
        }

        return '******' . $raw;
    }

    public function retailSale(): BelongsTo
    {
        return $this->belongsTo(RetailSale::class, 'retail_sale_id', 'id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'branch_id');
    }

    public function counter(): BelongsTo
    {
        return $this->belongsTo(Counter::class, 'counter_id', 'counter_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(CashierShift::class, 'cashier_shift_id', 'shift_id');
    }

    public function enteredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'entered_by', 'user_id');
    }

    public function attestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'attested_by', 'user_id');
    }
}
