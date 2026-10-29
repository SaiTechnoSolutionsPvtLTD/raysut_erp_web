<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DutyPaidConversion extends Model
{
    use HasFactory, Auditable;

    protected $table = 'duty_paid_conversions';
    protected $primaryKey = 'conversion_id';
    public $timestamps = false;

    protected $fillable = [
        'conversion_number',
        'warehouse_id',
        'conversion_date',
        'assessment_id',
        'approved_by',
        'status',
    ];

    protected $casts = [
        'conversion_date' => 'date',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'warehouse_id');
    }

    public function dutyAssessment()
    {
        return $this->belongsTo(DutyAssessment::class, 'assessment_id', 'assessment_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by', 'user_id');
    }

    public function items()
    {
        return $this->hasMany(DutyPaidConversionItem::class, 'conversion_id', 'conversion_id');
    }

    public function getBayanDeclarationNoAttribute(): ?string
    {
        return $this->dutyAssessment?->shipment?->bayan_declaration_no
            ?? $this->attributes['bayan_declaration_no']
            ?? null;
    }
}
