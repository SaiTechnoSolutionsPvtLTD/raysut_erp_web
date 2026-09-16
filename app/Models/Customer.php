<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function creditTerms()
    {
        return $this->hasOne(CustomerCreditTerm::class, 'customer_id', 'customer_id');
    }
}
