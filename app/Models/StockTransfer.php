<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockTransfer extends Model
{
    use HasFactory, Auditable;

    protected $table = 'stock_transfers';
    protected $primaryKey = 'transfer_id';
    public $timestamps = false;

    protected $fillable = [
        'transfer_number',
        'source_warehouse_id',
        'destination_warehouse_id',
        'transfer_date',
        'status',
        'requested_by',
    ];

    protected $casts = [
        'transfer_date' => 'date',
    ];

    public function sourceWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'source_warehouse_id', 'warehouse_id');
    }

    public function destinationWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'destination_warehouse_id', 'warehouse_id');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by', 'user_id');
    }

    public function items()
    {
        return $this->hasMany(StockTransferItem::class, 'transfer_id', 'transfer_id');
    }
}
