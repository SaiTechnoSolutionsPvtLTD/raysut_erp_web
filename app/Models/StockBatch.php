<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockBatch extends Model
{
    use HasFactory, Auditable;

    protected $table = 'stock_batches';
    protected $primaryKey = 'batch_id';
    public $timestamps = false;

    protected $fillable = [
        'batch_number',
        'product_id',
        'manufacture_date',
        'expiry_date',
        'source_grn_id',
        'duty_status',
    ];

    protected $casts = [
        'manufacture_date' => 'date',
        'expiry_date' => 'date',
    ];

    public function getBatchNoAttribute(): ?string
    {
        return $this->batch_number;
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    public function sourceGrn()
    {
        return $this->belongsTo(GoodsReceiptNote::class, 'source_grn_id', 'grn_id');
    }

    public function stocks()
    {
        return $this->hasMany(WarehouseStock::class, 'batch_id', 'batch_id');
    }

    public function grnItems()
    {
        return $this->hasMany(GoodsReceiptItem::class, 'batch_id', 'batch_id');
    }

    public function conversionItems()
    {
        return $this->hasMany(DutyPaidConversionItem::class, 'batch_id', 'batch_id');
    }

    public function transferItems()
    {
        return $this->hasMany(StockTransferItem::class, 'batch_id', 'batch_id');
    }

    public function adjustmentItems()
    {
        return $this->hasMany(StockAdjustmentItem::class, 'batch_id', 'batch_id');
    }
}
