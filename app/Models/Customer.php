<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Customer extends Model
{
    use HasFactory, Auditable;

    protected $table = 'customers';
    protected $primaryKey = 'customer_id';
    public $timestamps = false;

    protected $fillable = [
        'customer_code',
        'customer_name',
        'customer_segment',
        'phone',
        'email',
        'oman_card_no',
        'credit_limit',
        'payment_terms_days',
        'credit_status',
        'customer_type',
        'vat_number',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'credit_limit' => 'decimal:3',
        'payment_terms_days' => 'integer',
    ];

    public function creditTerms(): HasOne
    {
        return $this->hasOne(CustomerCreditTerm::class, 'customer_id', 'customer_id');
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(WholesaleQuotation::class, 'customer_id', 'customer_id');
    }

    public function salesOrders(): HasMany
    {
        return $this->hasMany(SalesOrder::class, 'customer_id', 'customer_id');
    }

    public function salesInvoices(): HasMany
    {
        return $this->hasMany(SalesInvoice::class, 'customer_id', 'customer_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(CustomerPayment::class, 'customer_id', 'customer_id');
    }

    public function getEffectiveCreditLimit(): float
    {
        if ((float) $this->credit_limit > 0) {
            return (float) $this->credit_limit;
        }

        if ($this->creditTerms && (float) $this->creditTerms->credit_limit > 0) {
            return (float) $this->creditTerms->credit_limit;
        }

        return (float) ($this->credit_limit ?? 0);
    }

    public function getEffectivePaymentTermsDays(): int
    {
        if ($this->payment_terms_days) {
            return (int) $this->payment_terms_days;
        }

        if ($this->creditTerms && $this->creditTerms->payment_terms_days) {
            return (int) $this->creditTerms->payment_terms_days;
        }

        return 30;
    }
}
