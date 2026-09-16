<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductPricingTier extends Model
{
    use HasFactory, Auditable;

    protected $table = 'product_pricing_tiers';
    protected $primaryKey = 'price_id';
    public $timestamps = false;

    protected $fillable = [
        'product_id',
        'price_tier',
        'min_quantity',
        'unit_price',
        'effective_from',
        'effective_to',
    ];

    protected $casts = [
        'min_quantity' => 'decimal:2',
        'unit_price' => 'decimal:3',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }
}
