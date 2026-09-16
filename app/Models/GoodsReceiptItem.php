<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoodsReceiptItem extends Model
{
    use HasFactory, Auditable;

    protected $table = 'goods_receipt_items';
    protected $primaryKey = 'grn_item_id';
    public $timestamps = false;

    protected $fillable = [
        'grn_id',
        'po_item_id',
        'product_id',
        'batch_id',
        'quantity_received',
        'quantity_ordered',
    ];

    protected $casts = [
        'quantity_received' => 'decimal:2',
        'quantity_ordered' => 'decimal:2',
    ];

    public function grn()
    {
        return $this->belongsTo(GoodsReceiptNote::class, 'grn_id', 'grn_id');
    }

    public function poItem()
    {
        return $this->belongsTo(PurchaseOrderItem::class, 'po_item_id', 'po_item_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    public function batch()
    {
        return $this->belongsTo(StockBatch::class, 'batch_id', 'batch_id');
    }
}
