<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductCategory extends Model
{
    use HasFactory, Auditable;

    protected $table = 'product_categories';
    protected $primaryKey = 'category_id';
    public $timestamps = false;

    protected $fillable = [
        'parent_category_id',
        'category_name',
    ];

    public function parent()
    {
        return $this->belongsTo(ProductCategory::class, 'parent_category_id', 'category_id');
    }

    public function children()
    {
        return $this->hasMany(ProductCategory::class, 'parent_category_id', 'category_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'category_id', 'category_id');
    }
}
