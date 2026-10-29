<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\HasBranchScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DeliveryNote extends Model
{
    use HasFactory, Auditable, HasBranchScope;

    protected $table = 'delivery_notes';
    protected $primaryKey = 'delivery_note_id';

    protected $fillable = [
        'dn_no',
        'sales_order_id',
        'branch_id',
        'warehouse_id',
        'dispatched_by',
        'dispatched_at',
        'vehicle_no',
        'driver_name',
        'status',
        'notes',
    ];

    protected $casts = [
        'dispatched_at' => 'datetime',
    ];

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id', 'order_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'branch_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'warehouse_id');
    }

    public function dispatcher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispatched_by', 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(DeliveryNoteItem::class, 'delivery_note_id', 'delivery_note_id');
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(SalesInvoice::class, 'delivery_note_id', 'delivery_note_id');
    }
}
