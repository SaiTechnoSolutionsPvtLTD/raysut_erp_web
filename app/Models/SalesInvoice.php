<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\HasBranchScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesInvoice extends Model
{
    use HasFactory, Auditable, HasBranchScope;

    protected $table = 'sales_invoices';
    protected $primaryKey = 'invoice_id';

    protected $fillable = [
        'invoice_no',
        'sales_order_id',
        'delivery_note_id',
        'customer_id',
        'branch_id',
        'warehouse_id',
        'invoice_date',
        'due_date',
        'payment_terms_days',
        'subtotal',
        'vat_total',
        'grand_total',
        'amount_paid',
        'status',
        'posted_at',
        'voided_at',
        'void_reason',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'payment_terms_days' => 'integer',
        'subtotal' => 'decimal:3',
        'vat_total' => 'decimal:3',
        'grand_total' => 'decimal:3',
        'amount_paid' => 'decimal:3',
        'posted_at' => 'datetime',
        'voided_at' => 'datetime',
    ];

    protected $appends = [
        'balance_due',
    ];

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id', 'order_id');
    }

    public function deliveryNote(): BelongsTo
    {
        return $this->belongsTo(DeliveryNote::class, 'delivery_note_id', 'delivery_note_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'branch_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'warehouse_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesInvoiceItem::class, 'invoice_id', 'invoice_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(CustomerPayment::class, 'invoice_id', 'invoice_id');
    }

    public function getBalanceDueAttribute(): float
    {
        return max(0, round((float) $this->grand_total - (float) $this->amount_paid, 3));
    }

    public function isOverdue(int $graceDays = 0): bool
    {
        if ($this->status !== 'POSTED' && $this->status !== 'PARTIALLY_PAID') {
            return false;
        }

        if ($this->balance_due <= 0.001) {
            return false;
        }

        $cutoff = $this->due_date->copy()->addDays($graceDays)->endOfDay();
        return now()->isAfter($cutoff);
    }
}
