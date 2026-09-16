<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UomConversion extends Model
{
    use HasFactory, Auditable;

    protected $table = 'uom_conversions';
    protected $primaryKey = 'conversion_id';
    public $timestamps = false;

    protected $fillable = [
        'product_id',
        'from_uom_id',
        'to_uom_id',
        'conversion_factor',
    ];

    protected $casts = [
        'conversion_factor' => 'decimal:4',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    public function fromUom()
    {
        return $this->belongsTo(Uom::class, 'from_uom_id', 'uom_id');
    }

    public function toUom()
    {
        return $this->belongsTo(Uom::class, 'to_uom_id', 'uom_id');
    }
}
