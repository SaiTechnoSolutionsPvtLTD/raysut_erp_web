<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerPayment extends Model
{
    use HasFactory, Auditable;

    protected $table = 'customer_payments';
    protected $primaryKey = 'payment_id';

    protected $fillable = [
        'invoice_id',
        'customer_id',
        'amount',
        'method',
        'reference',
        'received_by',
        'received_at',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:3',
        'received_at' => 'datetime',
    ];

    public function getPaymentMethodAttribute(): ?string
    {
        return $this->attributes['method'] ?? null;
    }

    public function setPaymentMethodAttribute($value): void
    {
        $this->attributes['method'] = $value;
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class, 'invoice_id', 'invoice_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by', 'user_id');
    }
}
