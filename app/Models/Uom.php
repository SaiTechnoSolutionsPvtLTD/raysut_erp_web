<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Uom extends Model
{
    use HasFactory, Auditable;

    protected $table = 'uom';
    protected $primaryKey = 'uom_id';
    public $timestamps = false;

    protected $fillable = [
        'uom_code',
        'uom_name',
    ];

    public function products()
    {
        return $this->hasMany(Product::class, 'base_uom_id', 'uom_id');
    }
}
