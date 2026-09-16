<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImportShipment extends Model
{
    use HasFactory, Auditable;

    protected $table = 'import_shipments';
    protected $primaryKey = 'shipment_id';
    public $timestamps = false;

    protected $fillable = [
        'shipment_ref',
        'po_id',
        'bl_container_no',
        'port_of_entry',
        'bayan_declaration_no',
        'stage',
        'eta_date',
        'actual_arrival_date',
    ];

    protected $casts = [
        'eta_date' => 'date',
        'actual_arrival_date' => 'date',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'po_id', 'po_id');
    }

    public function dutyAssessments()
    {
        return $this->hasMany(DutyAssessment::class, 'shipment_id', 'shipment_id');
    }

    public function grns()
    {
        return $this->hasMany(GoodsReceiptNote::class, 'shipment_id', 'shipment_id');
    }
}
