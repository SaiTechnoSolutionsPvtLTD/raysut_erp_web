<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @deprecated Legacy Oman Permits Quota Ledger.
 *
 * The ERP no longer maintains permit quota ledgers. Quota verification is checked
 * on the Oman government portal and attested at POS billing via PermitVerification.
 * This table is retained for historical audit only. Do not perform new reads or writes from POS.
 */
class OmanPermitTransaction extends Model
{
    use HasFactory;

    protected $table = 'oman_permit_transactions';
    public $timestamps = false;

    protected $fillable = [
        'oman_permit_id',
        'retail_sale_id',
        'amount',
        'balance_after',
        'created_at',
    ];

    protected $casts = [
        'amount' => 'decimal:3',
        'balance_after' => 'decimal:3',
        'created_at' => 'datetime',
    ];

    public function permit(): BelongsTo
    {
        return $this->belongsTo(OmanPermit::class, 'oman_permit_id', 'permit_id');
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(RetailSale::class, 'retail_sale_id', 'id');
    }
}
