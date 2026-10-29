<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\HasBranchScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesOrder extends Model
{
    use HasFactory, Auditable, HasBranchScope;

    protected $table = 'sales_orders';
    protected $primaryKey = 'order_id';

    protected $fillable = [
        'so_no',
        'quotation_id',
        'customer_id',
        'branch_id',
        'warehouse_id',
        'status',
        'subtotal',
        'vat_total',
        'grand_total',
        'payment_terms_days',
        'credit_check_status',
        'credit_override_by',
        'credit_override_at',
        'credit_override_reason',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'subtotal' => 'decimal:3',
        'vat_total' => 'decimal:3',
        'grand_total' => 'decimal:3',
        'payment_terms_days' => 'integer',
        'credit_override_at' => 'datetime',
    ];

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(WholesaleQuotation::class, 'quotation_id', 'quotation_id');
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

    public function creditOverriddenBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'credit_override_by', 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class, 'order_id', 'order_id');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(SalesOrderReservation::class, 'order_id', 'order_id');
    }

    public function deliveryNotes(): HasMany
    {
        return $this->hasMany(DeliveryNote::class, 'sales_order_id', 'order_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(SalesInvoice::class, 'sales_order_id', 'order_id');
    }

    public function getRemainingDispatchableQty(): float
    {
        return (float) $this->items->sum(function ($item) {
            return max(0, (float) $item->quantity - (float) $item->quantity_dispatched);
        });
    }
}
