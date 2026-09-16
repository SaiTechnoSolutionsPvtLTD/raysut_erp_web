<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DutyAssessment extends Model
{
    use HasFactory, Auditable;

    protected $table = 'duty_assessments';
    protected $primaryKey = 'assessment_id';
    public $timestamps = false;

    protected $fillable = [
        'shipment_id',
        'cif_value',
        'duty_rate_percent',
        'duty_amount',
        'other_charges',
        'total_landed_cost',
        'assessed_by',
        'assessed_date',
    ];

    protected $casts = [
        'cif_value' => 'decimal:3',
        'duty_rate_percent' => 'decimal:3',
        'duty_amount' => 'decimal:3',
        'other_charges' => 'decimal:3',
        'total_landed_cost' => 'decimal:3',
        'assessed_date' => 'date',
    ];

    public function shipment()
    {
        return $this->belongsTo(ImportShipment::class, 'shipment_id', 'shipment_id');
    }

    public function assessor()
    {
        return $this->belongsTo(User::class, 'assessed_by', 'user_id');
    }

    public function landedCostComponents()
    {
        return $this->hasMany(LandedCostComponent::class, 'assessment_id', 'assessment_id');
    }

    public function conversions()
    {
        return $this->hasMany(DutyPaidConversion::class, 'assessment_id', 'assessment_id');
    }
}
