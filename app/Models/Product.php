<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory, Auditable;

    protected $table = 'products';
    protected $primaryKey = 'product_id';
    public $timestamps = false;

    protected $fillable = [
        'sku_code',
        'barcode',
        'product_name',
        'category_id',
        'brand_id',
        'base_uom_id',
        'tax_rate_id',
        'hs_code',
        'pack_size',
        'reorder_level',
        'is_duty_free_eligible',
        'image_path',
        'is_active',
    ];

    protected $casts = [
        'is_duty_free_eligible' => 'boolean',
        'is_active' => 'boolean',
        'reorder_level' => 'decimal:2',
    ];

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'category_id', 'category_id');
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id', 'brand_id');
    }

    public function uom()
    {
        return $this->belongsTo(Uom::class, 'base_uom_id', 'uom_id');
    }

    public function taxRate()
    {
        return $this->belongsTo(TaxVatRate::class, 'tax_rate_id', 'tax_rate_id');
    }

    public function pricingTiers()
    {
        return $this->hasMany(ProductPricingTier::class, 'product_id', 'product_id');
    }

    public function uomConversions()
    {
        return $this->hasMany(UomConversion::class, 'product_id', 'product_id');
    }

    public function supplierProducts()
    {
        return $this->hasMany(SupplierProduct::class, 'product_id', 'product_id');
    }

    public function suppliers()
    {
        return $this->belongsToMany(Supplier::class, 'supplier_products', 'product_id', 'supplier_id')
                    ->withPivot('supplier_sku', 'last_purchase_cost');
    }

    public function stocks()
    {
        return $this->hasMany(WarehouseStock::class, 'product_id', 'product_id');
    }

    public function batches()
    {
        return $this->hasMany(StockBatch::class, 'product_id', 'product_id');
    }
}
