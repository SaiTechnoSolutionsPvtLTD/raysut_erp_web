<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoodsReceiptNote extends Model
{
    use HasFactory, Auditable;

    protected $table = 'goods_receipt_notes';
    protected $primaryKey = 'grn_id';
    public $timestamps = false;

    protected $fillable = [
        'grn_number',
        'po_id',
        'shipment_id',
        'warehouse_id',
        'received_date',
        'received_by',
        'has_discrepancy',
    ];

    protected $casts = [
        'received_date' => 'date',
        'has_discrepancy' => 'boolean',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'po_id', 'po_id');
    }

    public function shipment()
    {
        return $this->belongsTo(ImportShipment::class, 'shipment_id', 'shipment_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'warehouse_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by', 'user_id');
    }

    public function items()
    {
        return $this->hasMany(GoodsReceiptItem::class, 'grn_id', 'grn_id');
    }

    public function batches()
    {
        return $this->hasMany(StockBatch::class, 'source_grn_id', 'grn_id');
    }
}
