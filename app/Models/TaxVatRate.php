<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxVatRate extends Model
{
    use HasFactory, Auditable;

    protected $table = 'tax_vat_rates';
    protected $primaryKey = 'tax_rate_id';
    public $timestamps = false;

    protected $fillable = [
        'tax_name',
        'tax_type',
        'rate_percent',
        'effective_from',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'effective_from' => 'date',
        'rate_percent' => 'decimal:3',
    ];

    public function products()
    {
        return $this->hasMany(Product::class, 'tax_rate_id', 'tax_rate_id');
    }
}
