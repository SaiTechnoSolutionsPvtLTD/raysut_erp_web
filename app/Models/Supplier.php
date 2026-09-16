<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory, Auditable;

    protected $table = 'suppliers';
    protected $primaryKey = 'supplier_id';
    public $timestamps = false;

    protected $fillable = [
        'supplier_code',
        'supplier_name',
        'country',
        'currency_code',
        'payment_terms_days',
        'import_license_no',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'payment_terms_days' => 'integer',
    ];

    public function supplierProducts()
    {
        return $this->hasMany(SupplierProduct::class, 'supplier_id', 'supplier_id');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'supplier_products', 'supplier_id', 'product_id')
                    ->withPivot('supplier_sku', 'last_purchase_cost');
    }

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class, 'supplier_id', 'supplier_id');
    }
}
