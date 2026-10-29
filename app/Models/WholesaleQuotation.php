<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\HasBranchScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class WholesaleQuotation extends Model
{
    use HasFactory, Auditable, HasBranchScope;

    protected $table = 'wholesale_quotations';
    protected $primaryKey = 'quotation_id';

    protected $fillable = [
        'quote_no',
        'customer_id',
        'branch_id',
        'valid_until',
        'status',
        'subtotal',
        'vat_total',
        'grand_total',
        'payment_terms_days',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'valid_until' => 'date',
        'subtotal' => 'decimal:3',
        'vat_total' => 'decimal:3',
        'grand_total' => 'decimal:3',
        'payment_terms_days' => 'integer',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'branch_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(WholesaleQuotationItem::class, 'quotation_id', 'quotation_id');
    }

    public function salesOrder(): HasOne
    {
        return $this->hasOne(SalesOrder::class, 'quotation_id', 'quotation_id');
    }
}
