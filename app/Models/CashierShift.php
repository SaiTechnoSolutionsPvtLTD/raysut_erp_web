<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\HasBranchScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashierShift extends Model
{
    use HasFactory, HasBranchScope, Auditable;

    protected $table = 'cashier_shifts';
    protected $primaryKey = 'shift_id';

    protected $fillable = [
        'user_id',
        'counter_id',
        'branch_id',
        'opened_at',
        'closed_at',
        'opening_float',
        'expected_cash',
        'counted_cash',
        'card_total',
        'variance',
        'variance_status',
        'approved_by',
        'approved_at',
        'notes',
        'status',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'approved_at' => 'datetime',
        'opening_float' => 'decimal:3',
        'expected_cash' => 'decimal:3',
        'counted_cash' => 'decimal:3',
        'card_total' => 'decimal:3',
        'variance' => 'decimal:3',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function counter(): BelongsTo
    {
        return $this->belongsTo(Counter::class, 'counter_id', 'counter_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'branch_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by', 'user_id');
    }

    public function isOpen(): bool
    {
        return $this->status === 'OPEN';
    }
}
