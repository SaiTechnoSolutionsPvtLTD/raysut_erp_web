<?php

namespace App\Livewire\PurchaseImport;

use App\Models\ImportShipment;
use Livewire\Component;
use Livewire\WithPagination;

class ImportShipmentManagement extends Component
{
    use WithPagination;

    public $search = '';

    public function render()
    {
        $shipments = ImportShipment::with(['purchaseOrder.supplier', 'dutyAssessments'])
            ->where('shipment_ref', 'like', '%' . $this->search . '%')
            ->orWhere('bayan_declaration_no', 'like', '%' . $this->search . '%')
            ->orWhere('bl_container_no', 'like', '%' . $this->search . '%')
            ->orderBy('shipment_id', 'desc')
            ->paginate(10);

        return view('livewire.purchase-import.import-shipment-management', [
            'shipments' => $shipments,
        ])->layout('layouts.app');
    }
}
