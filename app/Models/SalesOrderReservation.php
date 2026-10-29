<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesOrderReservation extends Model
{
    use HasFactory;

    protected $table = 'sales_order_reservations';
    protected $primaryKey = 'reservation_id';

    protected $fillable = [
        'order_id',
        'order_item_id',
        'warehouse_id',
        'product_id',
        'batch_id',
        'quantity_reserved',
        'quantity_dispatched',
    ];

    protected $casts = [
        'quantity_reserved' => 'decimal:2',
        'quantity_dispatched' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'order_id', 'order_id');
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(SalesOrderItem::class, 'order_item_id', 'item_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'warehouse_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(StockBatch::class, 'batch_id', 'batch_id');
    }

    public function remainingReserved(): float
    {
        return max(0, (float) $this->quantity_reserved - (float) $this->quantity_dispatched);
    }
}
