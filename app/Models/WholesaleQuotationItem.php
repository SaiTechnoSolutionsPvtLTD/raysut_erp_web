<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WholesaleQuotationItem extends Model
{
    use HasFactory;

    protected $table = 'wholesale_quotation_items';
    protected $primaryKey = 'item_id';

    protected $fillable = [
        'quotation_id',
        'product_id',
        'uom_id',
        'quantity',
        'unit_price',
        'vat_rate',
        'vat_amount',
        'line_total',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:3',
        'vat_rate' => 'decimal:2',
        'vat_amount' => 'decimal:3',
        'line_total' => 'decimal:3',
    ];

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(WholesaleQuotation::class, 'quotation_id', 'quotation_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(Uom::class, 'uom_id', 'uom_id');
    }
}
