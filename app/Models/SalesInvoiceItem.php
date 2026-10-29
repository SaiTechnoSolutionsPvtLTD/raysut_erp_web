<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesInvoiceItem extends Model
{
    use HasFactory;

    protected $table = 'sales_invoice_items';
    protected $primaryKey = 'item_id';

    protected $fillable = [
        'invoice_id',
        'order_item_id',
        'delivery_note_item_id',
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
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:3',
        'vat_rate' => 'decimal:2',
        'vat_amount' => 'decimal:3',
        'line_total' => 'decimal:3',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class, 'invoice_id', 'invoice_id');
    }

    public function salesOrderItem(): BelongsTo
    {
        return $this->belongsTo(SalesOrderItem::class, 'order_item_id', 'item_id');
    }

    public function deliveryNoteItem(): BelongsTo
    {
        return $this->belongsTo(DeliveryNoteItem::class, 'delivery_note_item_id', 'item_id');
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
