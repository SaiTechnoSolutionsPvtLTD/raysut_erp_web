<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LandedCostComponent extends Model
{
    use HasFactory, Auditable;

    protected $table = 'landed_cost_components';
    protected $primaryKey = 'component_id';
    public $timestamps = false;

    protected $fillable = [
        'assessment_id',
        'charge_type',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:3',
    ];

    public function dutyAssessment()
    {
        return $this->belongsTo(DutyAssessment::class, 'assessment_id', 'assessment_id');
    }
}
