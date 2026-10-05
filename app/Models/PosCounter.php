<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosCounter extends Model
{
    use HasFactory, Auditable;

    protected $table = 'counters';
    protected $primaryKey = 'counter_id';
    public $timestamps = false;

    protected $fillable = [
        'branch_id',
        'counter_code',
        'counter_name',
        'terminal_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'branch_id');
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(CashierShift::class, 'counter_id', 'counter_id');
    }
}
