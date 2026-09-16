<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockAdjustment extends Model
{
    use HasFactory, Auditable;

    protected $table = 'stock_adjustments';
    protected $primaryKey = 'adjustment_id';
    public $timestamps = false;

    protected $fillable = [
        'adjustment_number',
        'warehouse_id',
        'count_date',
        'reason_code',
        'approved_by',
    ];

    protected $casts = [
        'count_date' => 'date',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'warehouse_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by', 'user_id');
    }

    public function items()
    {
        return $this->hasMany(StockAdjustmentItem::class, 'adjustment_id', 'adjustment_id');
    }
}
