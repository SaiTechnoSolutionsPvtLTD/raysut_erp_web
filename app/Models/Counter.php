<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Counter extends Model
{
    use Auditable;

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
}
