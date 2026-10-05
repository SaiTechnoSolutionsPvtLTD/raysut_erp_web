<?php

namespace App\Livewire\PurchaseImport;

use App\Models\DutyAssessment;
use App\Models\GoodsReceiptNote;
use App\Models\ImportShipment;
use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class ImportShipmentManagement extends Component
{
    use WithPagination;

    // Filters
    public string $search = '';
    public string $stage_filter = '';
    public string $port_filter = '';
    public string $po_filter = '';
    public string $date_from = '';
    public string $date_to = '';

    // Create / Edit Modal State
    public bool $showModal = false;
    public bool $isEdit = false;
    public ?int $shipment_id = null;

    // Form fields
    public string $shipment_ref = '';
    public string $po_id = '';
    public string $bl_container_no = '';
    public string $port_of_entry = 'Sohar Port Customs Station';
    public string $custom_port = '';
    public string $bayan_declaration_no = '';
    public string $stage = 'ORDERED';
    public string $eta_date = '';
    public string $actual_arrival_date = '';

    // Selected PO details for reactive preview in modal
    public ?string $selected_po_info = null;

    // View Details Modal State
    public bool $showViewModal = false;
    public ?int $view_shipment_id = null;

    // Delete Confirmation Modal State
    public bool $showItemDeleteModal = false;
    public int $itemToDeleteId = 0;
    public string $itemToDeleteNumber = '';

    public array $standardPorts = [
        'Sohar Port Customs Station',
        'Port Sultan Qaboos (Muscat)',
        'Salalah Port Free Zone Terminal',
        'Muscat International Airport Cargo Hub',
        'Al Wajajah Border Post (UAE / Oman)',
        'Khasab Port Customs Station',
        'Duqm Port Customs Station',
        'Other (Custom Port)',
    ];

    public array $stages = [
        'ORDERED' => 'Ordered (Pending Departure)',
        'IN_TRANSIT' => 'In Transit (Sea / Air)',
        'CUSTOMS_CLEARANCE' => 'Customs Clearance (Port Arrival)',
        'DUTY_ASSESSMENT' => 'Duty Assessment (Bayan Filed)',
        'RECEIVED' => 'Received & Landed at Warehouse',
    ];

    protected function rules(): array
    {
        return [
            'shipment_ref' => 'required|string|max:30|unique:import_shipments,shipment_ref,' . ($this->shipment_id ?? 'NULL') . ',shipment_id',
            'po_id' => 'required|exists:purchase_orders,po_id',
            'bl_container_no' => 'nullable|string|max:50',
            'port_of_entry' => 'required|string|max:100',
            'custom_port' => 'nullable|string|max:100',
            'bayan_declaration_no' => 'nullable|string|max:50',
            'stage' => 'required|in:ORDERED,IN_TRANSIT,CUSTOMS_CLEARANCE,DUTY_ASSESSMENT,RECEIVED',
            'eta_date' => 'nullable|date',
            'actual_arrival_date' => 'nullable|date|after_or_equal:eta_date',
        ];
    }

    protected $messages = [
        'shipment_ref.required' => 'Shipment reference is required.',
        'shipment_ref.unique' => 'This shipment reference has already been created.',
        'po_id.required' => 'Please select an associated Purchase Order.',
        'port_of_entry.required' => 'Please select the port of entry.',
        'stage.required' => 'Please select the shipment customs stage.',
        'actual_arrival_date.after_or_equal' => 'Actual arrival date must be on or after the estimated arrival date (ETA).',
    ];

    public function mount(): void
    {
        $this->eta_date = date('Y-m-d', strtotime('+14 days'));
    }

    public function updatedPoId($value): void
    {
        if (!empty($value)) {
            $po = PurchaseOrder::with(['supplier', 'warehouse'])->find($value);
            if ($po) {
                $this->selected_po_info = "{$po->po_number} · {$po->supplier?->supplier_name} ({$po->supplier?->country}) → {$po->warehouse?->warehouse_name}";
                if (empty($this->eta_date) && $po->expected_delivery_date) {
                    $this->eta_date = $po->expected_delivery_date->format('Y-m-d');
                }
            } else {
                $this->selected_po_info = null;
            }
        } else {
            $this->selected_po_info = null;
        }
        $this->validateOnly('po_id');
    }

    public function updatedEtaDate(): void
    {
        $this->validateOnly('eta_date');
        if (!empty($this->actual_arrival_date)) {
            $this->validateOnly('actual_arrival_date');
        }
    }

    public function updatedActualArrivalDate(): void
    {
        if (!empty($this->actual_arrival_date)) {
            $this->validateOnly('actual_arrival_date');
        }
    }

    protected function generateShipmentRef(): string
    {
        $year = date('Y');
        $prefix = "SHIP-{$year}-";

        $latest = ImportShipment::where('shipment_ref', 'like', $prefix . '%')
            ->orderBy('shipment_id', 'desc')
            ->first();

        if ($latest) {
            $num = (int) substr($latest->shipment_ref, strlen($prefix));
            $nextNum = $num + 1;
        } else {
            $count = ImportShipment::count();
            $nextNum = $count + 1;
        }

        do {
            $code = $prefix . str_pad((string)$nextNum, 4, '0', STR_PAD_LEFT);
            $exists = ImportShipment::where('shipment_ref', $code)->exists();
            if ($exists) {
                $nextNum++;
            }
        } while ($exists);

        return $code;
    }

    public function openCreateModal(): void
    {
        if (! auth()->user()?->hasPermission('Purchase & Import', 'CREATE')) {
            abort(403, 'Unauthorized action: You do not have permission to CREATE import shipments.');
        }

        $this->reset([
            'shipment_id', 'po_id', 'bl_container_no', 'bayan_declaration_no', 'actual_arrival_date', 'selected_po_info', 'custom_port'
        ]);

        $this->shipment_ref = $this->generateShipmentRef();
        $this->port_of_entry = 'Sohar Port Customs Station';
        $this->stage = 'ORDERED';
        $this->eta_date = date('Y-m-d', strtotime('+14 days'));
        $this->isEdit = false;
        $this->showModal = true;
    }

    public function editShipment(int $id): void
    {
        if (! auth()->user()?->hasPermission('Purchase & Import', 'EDIT')) {
            abort(403, 'Unauthorized action: You do not have permission to EDIT import shipments.');
        }

        $shipment = ImportShipment::with(['purchaseOrder.supplier', 'purchaseOrder.warehouse'])->findOrFail($id);

        $this->shipment_id = $shipment->shipment_id;
        $this->shipment_ref = $shipment->shipment_ref;
        $this->po_id = (string)$shipment->po_id;
        $this->bl_container_no = $shipment->bl_container_no ?? '';
        $this->bayan_declaration_no = $shipment->bayan_declaration_no ?? '';
        $this->stage = $shipment->stage ?? 'ORDERED';
        $this->eta_date = $shipment->eta_date ? $shipment->eta_date->format('Y-m-d') : '';
        $this->actual_arrival_date = $shipment->actual_arrival_date ? $shipment->actual_arrival_date->format('Y-m-d') : '';

        if (in_array($shipment->port_of_entry, $this->standardPorts)) {
            $this->port_of_entry = $shipment->port_of_entry;
            $this->custom_port = '';
        } else {
            $this->port_of_entry = 'Other (Custom Port)';
            $this->custom_port = $shipment->port_of_entry ?? '';
        }

        if ($shipment->purchaseOrder) {
            $po = $shipment->purchaseOrder;
            $this->selected_po_info = "{$po->po_number} · {$po->supplier?->supplier_name} ({$po->supplier?->country}) → {$po->warehouse?->warehouse_name}";
        }

        $this->isEdit = true;
        $this->showModal = true;
    }

    public function viewShipment(int $id): void
    {
        $this->view_shipment_id = $id;
        $this->showViewModal = true;
    }

    public function closeViewModal(): void
    {
        $this->showViewModal = false;
        $this->view_shipment_id = null;
    }

    public function saveShipment(): void
    {
        $requiredAction = $this->shipment_id ? 'EDIT' : 'CREATE';
        if (! auth()->user()?->hasPermission('Purchase & Import', $requiredAction)) {
            abort(403, "Unauthorized action: You do not have permission to {$requiredAction} import shipments.");
        }

        if (!$this->shipment_id && empty($this->shipment_ref)) {
            $this->shipment_ref = $this->generateShipmentRef();
        }

        $this->validate();

        $effectivePort = $this->port_of_entry === 'Other (Custom Port)' 
            ? (trim($this->custom_port) ?: 'Custom Port Station') 
            : $this->port_of_entry;

        ImportShipment::updateOrCreate(
            ['shipment_id' => $this->shipment_id],
            [
                'shipment_ref' => trim($this->shipment_ref),
                'po_id' => (int)$this->po_id,
                'bl_container_no' => trim($this->bl_container_no) ?: null,
                'port_of_entry' => $effectivePort,
                'bayan_declaration_no' => trim($this->bayan_declaration_no) ?: null,
                'stage' => $this->stage,
                'eta_date' => $this->eta_date ?: null,
                'actual_arrival_date' => $this->actual_arrival_date ?: null,
            ]
        );

        session()->flash('message', $this->isEdit ? 'Import Shipment updated successfully.' : 'Import Shipment registered successfully.');
        $this->dispatch('notify', type: 'success', title: 'Shipment Saved', message: "Shipment '{$this->shipment_ref}' saved successfully.");
        $this->showModal = false;
    }

    public function advanceStage(int $id): void
    {
        if (! auth()->user()?->hasPermission('Purchase & Import', 'EDIT')) {
            abort(403, 'Unauthorized action: You do not have permission to UPDATE shipment stages.');
        }

        $shipment = ImportShipment::findOrFail($id);
        $stageOrder = ['ORDERED', 'IN_TRANSIT', 'CUSTOMS_CLEARANCE', 'DUTY_ASSESSMENT', 'RECEIVED'];
        $currentIndex = array_search($shipment->stage, $stageOrder);

        if ($currentIndex !== false && $currentIndex < count($stageOrder) - 1) {
            $nextStage = $stageOrder[$currentIndex + 1];
            $shipment->stage = $nextStage;
            if ($nextStage === 'CUSTOMS_CLEARANCE' && empty($shipment->actual_arrival_date)) {
                $shipment->actual_arrival_date = date('Y-m-d');
            }
            $shipment->save();
            $this->dispatch('notify', type: 'success', title: 'Stage Updated', message: "Shipment {$shipment->shipment_ref} moved to stage: " . str_replace('_', ' ', $nextStage));
        }
    }

    public function confirmDeleteShipment(int $id, string $ref): void
    {
        $this->itemToDeleteId = $id;
        $this->itemToDeleteNumber = $ref;
        $this->showItemDeleteModal = true;
    }

    public function executeDeleteItem(): void
    {
        if (! auth()->user()?->hasPermission('Purchase & Import', 'DELETE')) {
            abort(403, 'Unauthorized action: You do not have permission to DELETE import shipments.');
        }

        $this->showItemDeleteModal = false;

        if ($this->itemToDeleteId > 0) {
            $shipment = ImportShipment::find($this->itemToDeleteId);
            if (!$shipment) {
                return;
            }

            if ($shipment->dutyAssessments()->exists() || $shipment->grns()->exists()) {
                $this->dispatch('notify', type: 'error', title: 'Cannot Delete Shipment', message: "Shipment '{$shipment->shipment_ref}' has linked duty assessments or goods receipts.");
                return;
            }

            try {
                $ref = $shipment->shipment_ref;
                $shipment->delete();
                $this->dispatch('notify', type: 'warning', title: 'Shipment Deleted', message: "Shipment '{$ref}' deleted successfully.");
            } catch (\Throwable $e) {
                $this->dispatch('notify', type: 'error', title: 'Cannot Delete Shipment', message: "Cannot delete shipment: " . $e->getMessage());
            }
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'stage_filter', 'port_filter', 'po_filter', 'date_from', 'date_to']);
        $this->resetPage();
    }

    public function render()
    {
        $query = ImportShipment::with([
            'purchaseOrder:po_id,po_number,supplier_id,warehouse_id,order_date,currency_code,status',
            'purchaseOrder.supplier:supplier_id,supplier_name,supplier_code,country,currency_code',
            'purchaseOrder.warehouse:warehouse_id,warehouse_name,warehouse_code',
            'dutyAssessments:assessment_id,shipment_id,cif_value,duty_amount,total_landed_cost',
            'grns:grn_id,shipment_id,grn_number,received_date'
        ]);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('shipment_ref', 'like', '%' . $this->search . '%')
                  ->orWhere('bayan_declaration_no', 'like', '%' . $this->search . '%')
                  ->orWhere('bl_container_no', 'like', '%' . $this->search . '%')
                  ->orWhereHas('purchaseOrder', function ($pq) {
                      $pq->where('po_number', 'like', '%' . $this->search . '%')
                         ->orWhereHas('supplier', function ($sq) {
                             $sq->where('supplier_name', 'like', '%' . $this->search . '%');
                         });
                  });
            });
        }

        if ($this->stage_filter) {
            $query->where('stage', $this->stage_filter);
        }

        if ($this->port_filter) {
            $query->where('port_of_entry', $this->port_filter);
        }

        if ($this->po_filter) {
            $query->where('po_id', $this->po_filter);
        }

        if ($this->date_from) {
            $query->where(function($q) {
                $q->whereDate('actual_arrival_date', '>=', $this->date_from)
                  ->orWhereDate('eta_date', '>=', $this->date_from);
            });
        }

        if ($this->date_to) {
            $query->where(function($q) {
                $q->whereDate('actual_arrival_date', '<=', $this->date_to)
                  ->orWhereDate('eta_date', '<=', $this->date_to);
            });
        }

        // Summary KPI Metrics
        $metrics = [
            'total_shipments' => ImportShipment::count(),
            'in_transit' => ImportShipment::where('stage', 'IN_TRANSIT')->count(),
            'customs_clearance' => ImportShipment::where('stage', 'CUSTOMS_CLEARANCE')->count(),
            'duty_assessment' => ImportShipment::where('stage', 'DUTY_ASSESSMENT')->count(),
            'received' => ImportShipment::where('stage', 'RECEIVED')->count(),
        ];

        $purchaseOrders = PurchaseOrder::where('status', '!=', 'CANCELLED')
            ->with(['supplier:supplier_id,supplier_name,country'])
            ->select(['po_id', 'po_number', 'supplier_id', 'warehouse_id', 'order_date', 'currency_code'])
            ->orderBy('po_id', 'desc')
            ->get();

        $portsList = ImportShipment::whereNotNull('port_of_entry')
            ->distinct()
            ->pluck('port_of_entry')
            ->toArray();

        $selectedShipmentDetails = null;
        if ($this->view_shipment_id) {
            $selectedShipmentDetails = ImportShipment::with([
                'purchaseOrder.supplier',
                'purchaseOrder.warehouse',
                'purchaseOrder.items.product.uom',
                'dutyAssessments.assessor',
                'dutyAssessments.landedCostComponents',
                'grns.receiver'
            ])->find($this->view_shipment_id);
        }

        return view('livewire.purchase-import.import-shipment-management', [
            'shipments' => $query->orderBy('shipment_id', 'desc')->paginate(10),
            'metrics' => $metrics,
            'purchaseOrders' => $purchaseOrders,
            'portsList' => array_unique(array_merge($this->standardPorts, $portsList)),
            'selectedShipmentDetails' => $selectedShipmentDetails,
        ])->layout('layouts.app');
    }
}

