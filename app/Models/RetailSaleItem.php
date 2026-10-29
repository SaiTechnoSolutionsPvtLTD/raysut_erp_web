<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RetailSaleItem extends Model
{
    use HasFactory;

    protected $table = 'retail_sale_items';

    protected $fillable = [
        'retail_sale_id',
        'product_id',
        'batch_id',
        'uom_id',
        'quantity',
        'unit_price',
        'vat_rate',
        'vat_amount',
        'line_total',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:3',
        'vat_rate' => 'decimal:3',
        'vat_amount' => 'decimal:3',
        'line_total' => 'decimal:3',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(RetailSale::class, 'retail_sale_id', 'id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(StockBatch::class, 'batch_id', 'batch_id');
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(Uom::class, 'uom_id', 'uom_id');
    }
}
