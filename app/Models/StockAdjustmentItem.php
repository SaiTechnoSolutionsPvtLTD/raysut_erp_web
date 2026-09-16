<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockAdjustmentItem extends Model
{
    use HasFactory, Auditable;

    protected $table = 'stock_adjustment_items';
    protected $primaryKey = 'adjustment_item_id';
    public $timestamps = false;

    protected $fillable = [
        'adjustment_id',
        'batch_id',
        'system_quantity',
        'counted_quantity',
        'variance_quantity',
    ];

    protected $casts = [
        'system_quantity' => 'decimal:2',
        'counted_quantity' => 'decimal:2',
        'variance_quantity' => 'decimal:2',
    ];

    public function adjustment()
    {
        return $this->belongsTo(StockAdjustment::class, 'adjustment_id', 'adjustment_id');
    }

    public function batch()
    {
        return $this->belongsTo(StockBatch::class, 'batch_id', 'batch_id');
    }
}
