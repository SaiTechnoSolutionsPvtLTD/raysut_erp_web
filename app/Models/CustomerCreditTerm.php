<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerCreditTerm extends Model
{
    use HasFactory, Auditable;

    protected $table = 'customer_credit_terms';
    protected $primaryKey = 'customer_id';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'customer_id',
        'credit_limit',
        'payment_terms_days',
        'outstanding_balance',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:3',
        'outstanding_balance' => 'decimal:3',
        'payment_terms_days' => 'integer',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }
}
