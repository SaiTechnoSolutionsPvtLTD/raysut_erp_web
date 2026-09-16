<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierProduct extends Model
{
    use HasFactory, Auditable;

    protected $table = 'supplier_products';
    protected $primaryKey = 'supplier_id';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'supplier_id',
        'product_id',
        'supplier_sku',
        'last_purchase_cost',
    ];

    protected $casts = [
        'last_purchase_cost' => 'decimal:3',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'supplier_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }
}
