<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesOrderItem extends Model
{
    use HasFactory;

    protected $table = 'sales_order_items';
    protected $primaryKey = 'item_id';

    protected $fillable = [
        'order_id',
        'product_id',
        'uom_id',
        'quantity',
        'unit_price',
        'vat_rate',
        'vat_amount',
        'line_total',
        'quantity_reserved',
        'quantity_dispatched',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:3',
        'vat_rate' => 'decimal:2',
        'vat_amount' => 'decimal:3',
        'line_total' => 'decimal:3',
        'quantity_reserved' => 'decimal:2',
        'quantity_dispatched' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'order_id', 'order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(Uom::class, 'uom_id', 'uom_id');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(SalesOrderReservation::class, 'order_item_id', 'item_id');
    }

    public function remainingToDispatch(): float
    {
        return max(0, (float) $this->quantity - (float) $this->quantity_dispatched);
    }
}
