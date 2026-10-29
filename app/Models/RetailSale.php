<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\HasBranchScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RetailSale extends Model
{
    use HasFactory, HasBranchScope, Auditable;

    protected $table = 'retail_sales';

    protected $fillable = [
        'receipt_no',
        'branch_id',
        'counter_id',
        'cashier_shift_id',
        'user_id',
        'oman_permit_id',
        'customer_civil_id',
        'subtotal',
        'vat_total',
        'grand_total',
        'payment_method',
        'cash_amount',
        'card_amount',
        'card_reference',
        'change_given',
        'status',
    ];

    protected $casts = [
        'subtotal' => 'decimal:3',
        'vat_total' => 'decimal:3',
        'grand_total' => 'decimal:3',
        'cash_amount' => 'decimal:3',
        'card_amount' => 'decimal:3',
        'change_given' => 'decimal:3',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(RetailSaleItem::class, 'retail_sale_id', 'id');
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

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function permit(): BelongsTo
    {
        return $this->belongsTo(OmanPermit::class, 'oman_permit_id', 'permit_id');
    }

    public function cashierShift(): BelongsTo
    {
        return $this->belongsTo(CashierShift::class, 'cashier_shift_id', 'shift_id');
    }

    public function permitTransaction(): HasOne
    {
        return $this->hasOne(OmanPermitTransaction::class, 'retail_sale_id', 'id');
    }

    public function permitVerification(): HasOne
    {
        return $this->hasOne(PermitVerification::class, 'retail_sale_id', 'id');
    }

    public function stockLedgerEntries(): HasMany
    {
        return $this->hasMany(StockLedger::class, 'reference_id', 'id')
            ->where('reference_type', 'retail_sale');
    }

    /**
     * Get masked civil ID (last 4 digits visible).
     */
    public function getMaskedCivilIdAttribute(): ?string
    {
        if (empty($this->customer_civil_id)) {
            return null;
        }

        $raw = trim($this->customer_civil_id);
        $len = strlen($raw);
        if ($len <= 4) {
            return str_repeat('*', $len);
        }

        return str_repeat('*', $len - 4) . substr($raw, -4);
    }
}
