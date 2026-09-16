<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use Auditable;

    protected $table = 'companies';
    protected $primaryKey = 'company_id';
    const UPDATED_AT = null;

    protected $fillable = [
        'company_name',
        'trade_license_no',
        'liquor_license_no',
        'base_currency_code',
        'address',
        'logo_path',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class, 'company_id', 'company_id');
    }

    public function warehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class, 'company_id', 'company_id');
    }
}
