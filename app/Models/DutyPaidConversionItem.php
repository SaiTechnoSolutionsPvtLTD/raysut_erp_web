<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DutyPaidConversionItem extends Model
{
    use HasFactory, Auditable;

    protected $table = 'duty_paid_conversion_items';
    protected $primaryKey = 'conversion_item_id';
    public $timestamps = false;

    protected $fillable = [
        'conversion_id',
        'batch_id',
        'quantity_converted',
        'duty_paid_unit_cost',
    ];

    protected $casts = [
        'quantity_converted' => 'decimal:2',
        'duty_paid_unit_cost' => 'decimal:3',
    ];

    public function conversion()
    {
        return $this->belongsTo(DutyPaidConversion::class, 'conversion_id', 'conversion_id');
    }

    public function batch()
    {
        return $this->belongsTo(StockBatch::class, 'batch_id', 'batch_id');
    }
}
