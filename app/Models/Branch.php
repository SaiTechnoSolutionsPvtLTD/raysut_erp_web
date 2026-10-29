<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    use Auditable;

    protected $table = 'branches';
    protected $primaryKey = 'branch_id';
    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'branch_code',
        'branch_name',
        'branch_type',
        'city',
        'address',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'company_id');
    }

    public function warehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class, 'branch_id', 'branch_id');
    }

    public function counters(): HasMany
    {
        return $this->hasMany(Counter::class, 'branch_id', 'branch_id');
    }
}
