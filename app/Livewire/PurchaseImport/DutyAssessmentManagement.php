<?php

namespace App\Livewire\PurchaseImport;

use App\Models\DutyAssessment;
use App\Models\ImportShipment;
use App\Models\LandedCostComponent;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class DutyAssessmentManagement extends Component
{
    use WithPagination;

    // Filter properties
    public string $search = '';
    public string $date_from = '';
    public string $date_to = '';

    // Create / Edit Modal State
    public bool $showModal = false;
    public bool $isEdit = false;
    public ?int $assessment_id = null;

    // Form fields
    public string $shipment_id = '';
    public string $cif_value = '0.000';
    public string $duty_rate_percent = '100.000';
    public string $duty_amount = '0.000';
    public string $other_charges = '0.000';
    public string $total_landed_cost = '0.000';
    public string $assessed_by = '';
    public string $assessed_date = '';

    // Dynamic Landed Cost Components
    public array $cost_components = [];

    // Selected Shipment Info Banner
    public ?string $selected_shipment_info = null;

    // View Details Modal State
    public bool $showViewModal = false;
    public ?int $view_assessment_id = null;

    // Delete Confirmation Modal State
    public bool $showItemDeleteModal = false;
    public int $itemToDeleteId = 0;
    public string $itemToDeleteNumber = '';

    // Standard Charge Type Presets
    public array $standardChargeTypes = [
        'Bayan Port Handling & Demurrage',
        'Customs Clearance & Brokerage Fee',
        'Freight & Marine Transport',
        'Storage, Warehousing & Security',
        'Customs Inspection & Stamp Duty',
        'Other Cargo Administrative Fees',
    ];

    protected function rules(): array
    {
        return [
            'shipment_id' => 'required|exists:import_shipments,shipment_id',
            'cif_value' => 'required|numeric|min:0',
            'duty_rate_percent' => 'required|numeric|min:0|max:1000',
            'duty_amount' => 'required|numeric|min:0',
            'assessed_date' => 'required|date',
            'cost_components' => 'array',
            'cost_components.*.charge_type' => 'required|string|max:100',
            'cost_components.*.amount' => 'required|numeric|min:0',
        ];
    }

    protected array $messages = [
        'shipment_id.required' => 'Please select an associated Import Shipment.',
        'cif_value.required' => 'CIF Value (Cost, Insurance & Freight) is required.',
        'cif_value.min' => 'CIF Value cannot be negative.',
        'duty_rate_percent.required' => 'Please enter the Customs Duty Rate percentage.',
        'duty_amount.required' => 'Duty Amount is required.',
        'assessed_date.required' => 'Please enter the assessment date.',
        'cost_components.*.charge_type.required' => 'Charge type description is required for all components.',
        'cost_components.*.amount.required' => 'Amount is required for all components.',
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateTo(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->date_from = '';
        $this->date_to = '';
        $this->resetPage();
    }

    public function mount(): void
    {
        $this->assessed_date = date('Y-m-d');
        $this->assessed_by = (string) (Auth::id() ?? User::first()?->user_id ?? 1);
    }

    public function updatedShipmentId($value): void
    {
        if (!$value) {
            $this->selected_shipment_info = null;
            return;
        }

        $shipment = ImportShipment::with(['purchaseOrder.supplier', 'purchaseOrder.items'])->find($value);
        if (!$shipment) {
            $this->selected_shipment_info = null;
            return;
        }

        $po = $shipment->purchaseOrder;
        $poNumber = $po ? $po->po_number : 'N/A';
        $supplierName = $po && $po->supplier ? $po->supplier->supplier_name : 'N/A';
        $bayanRef = $shipment->bayan_declaration_no ? "Bayan: {$shipment->bayan_declaration_no}" : "Port: {$shipment->port_of_entry}";

        $this->selected_shipment_info = "{$shipment->shipment_ref} • PO: {$poNumber} • Supplier: {$supplierName} • {$bayanRef}";

        // Calculate estimated CIF from PO line items if cif_value is zero or default
        if ((float) $this->cif_value <= 0 && $po && $po->items->count()) {
            $calcCif = 0;
            foreach ($po->items as $item) {
                $calcCif += (float) $item->quantity_ordered * (float) $item->unit_cost;
            }
            $this->cif_value = number_format($calcCif, 3, '.', '');
        }

        $this->recalculateTotals();
    }

    public function updatedCifValue(): void
    {
        $this->recalculateTotals();
    }

    public function updatedDutyRatePercent(): void
    {
        $this->recalculateTotals();
    }

    public function updatedCostComponents(): void
    {
        $this->recalculateTotals();
    }

    public function updated($propertyName): void
    {
        if (str_starts_with($propertyName, 'cost_components')) {
            $this->recalculateTotals();
        }
    }

    public function recalculateTotals(): void
    {
        $cif = (float) $this->cif_value;
        $rate = (float) $this->duty_rate_percent;
        
        $duty = $cif * ($rate / 100);
        $this->duty_amount = number_format($duty, 3, '.', '');

        $otherSum = 0;
        foreach ($this->cost_components as $comp) {
            $otherSum += (float) ($comp['amount'] ?? 0);
        }
        $this->other_charges = number_format($otherSum, 3, '.', '');

        $totalLanded = $cif + $duty + $otherSum;
        $this->total_landed_cost = number_format($totalLanded, 3, '.', '');
    }

    public function addCostComponent(string $defaultType = 'Bayan Port Handling & Demurrage'): void
    {
        $this->cost_components[] = [
            'charge_type' => $defaultType,
            'amount' => '0.000',
        ];
        $this->recalculateTotals();
    }

    public function removeCostComponent(int $index): void
    {
        if (isset($this->cost_components[$index])) {
            array_splice($this->cost_components, $index, 1);
            $this->recalculateTotals();
        }
    }

    public function openCreateModal(): void
    {
        $this->resetValidation();
        $this->isEdit = false;
        $this->assessment_id = null;
        $this->shipment_id = '';
        $this->cif_value = '0.000';
        $this->duty_rate_percent = '100.000';
        $this->duty_amount = '0.000';
        $this->other_charges = '0.000';
        $this->total_landed_cost = '0.000';
        $this->assessed_date = date('Y-m-d');
        $this->assessed_by = (string) (Auth::id() ?? User::first()?->user_id ?? 1);
        $this->selected_shipment_info = null;
        
        $this->cost_components = [
            ['charge_type' => 'Bayan Port Handling & Demurrage', 'amount' => '150.000'],
        ];

        $this->recalculateTotals();
        $this->showModal = true;
    }

    public function openEditModal(int $id): void
    {
        $this->resetValidation();
        $assessment = DutyAssessment::with(['shipment.purchaseOrder.supplier', 'landedCostComponents'])->find($id);
        if (!$assessment) return;

        $this->assessment_id = $assessment->assessment_id;
        $this->isEdit = true;
        $this->shipment_id = (string) $assessment->shipment_id;
        $this->cif_value = number_format((float) $assessment->cif_value, 3, '.', '');
        $this->duty_rate_percent = number_format((float) $assessment->duty_rate_percent, 1, '.', '');
        $this->duty_amount = number_format((float) $assessment->duty_amount, 3, '.', '');
        $this->other_charges = number_format((float) $assessment->other_charges, 3, '.', '');
        $this->total_landed_cost = number_format((float) $assessment->total_landed_cost, 3, '.', '');
        $this->assessed_date = $assessment->assessed_date ? $assessment->assessed_date->format('Y-m-d') : date('Y-m-d');
        $this->assessed_by = (string) ($assessment->assessed_by ?? Auth::id() ?? 1);

        $shipment = $assessment->shipment;
        if ($shipment) {
            $po = $shipment->purchaseOrder;
            $poNumber = $po ? $po->po_number : 'N/A';
            $supplierName = $po && $po->supplier ? $po->supplier->supplier_name : 'N/A';
            $this->selected_shipment_info = "{$shipment->shipment_ref} • PO: {$poNumber} • Supplier: {$supplierName}";
        }

        $this->cost_components = [];
        foreach ($assessment->landedCostComponents as $comp) {
            $this->cost_components[] = [
                'charge_type' => $comp->charge_type,
                'amount' => number_format((float) $comp->amount, 3, '.', ''),
            ];
        }

        $this->recalculateTotals();
        $this->showModal = true;
    }

    public function saveAssessment(): void
    {
        $this->validate();
        $this->recalculateTotals();

        DB::transaction(function () {
            $assessmentData = [
                'shipment_id' => (int) $this->shipment_id,
                'cif_value' => (float) $this->cif_value,
                'duty_rate_percent' => (float) $this->duty_rate_percent,
                'duty_amount' => (float) $this->duty_amount,
                'other_charges' => (float) $this->other_charges,
                'total_landed_cost' => (float) $this->total_landed_cost,
                'assessed_by' => $this->assessed_by ?: Auth::id(),
                'assessed_date' => $this->assessed_date,
            ];

            if ($this->isEdit && $this->assessment_id) {
                $assessment = DutyAssessment::findOrFail($this->assessment_id);
                $assessment->update($assessmentData);
                LandedCostComponent::where('assessment_id', $assessment->assessment_id)->delete();
            } else {
                $assessment = DutyAssessment::create($assessmentData);
            }

            foreach ($this->cost_components as $comp) {
                $amt = (float) ($comp['amount'] ?? 0);
                if ($amt > 0 || !empty($comp['charge_type'])) {
                    LandedCostComponent::create([
                        'assessment_id' => $assessment->assessment_id,
                        'charge_type' => trim($comp['charge_type']),
                        'amount' => $amt,
                    ]);
                }
            }
        });

        $msg = $this->isEdit ? 'Customs Duty Assessment updated successfully.' : 'Customs Duty Assessment registered & landed costs calculated successfully.';
        session()->flash('message', $msg);
        $this->dispatch('notify', type: 'success', title: $this->isEdit ? 'Assessment Updated' : 'Assessment Created', message: "Duty Assessment for shipment saved successfully.");
        $this->showModal = false;
    }

    public function openViewModal(int $id): void
    {
        $this->view_assessment_id = $id;
        $this->showViewModal = true;
    }

    public function closeViewModal(): void
    {
        $this->showViewModal = false;
        $this->view_assessment_id = null;
    }

    public function confirmDelete(int $id): void
    {
        $assessment = DutyAssessment::with('shipment')->find($id);
        if (!$assessment) return;

        $this->itemToDeleteId = $assessment->assessment_id;
        $this->itemToDeleteNumber = '#ASSESS-' . str_pad((string) $assessment->assessment_id, 4, '0', STR_PAD_LEFT);
        $this->showItemDeleteModal = true;
    }

    public function executeDeleteItem(): void
    {
        DB::transaction(function () {
            $assessment = DutyAssessment::find($this->itemToDeleteId);
            if ($assessment) {
                LandedCostComponent::where('assessment_id', $assessment->assessment_id)->delete();
                $assessment->delete();
            }
        });

        $msg = "Customs Duty Assessment {$this->itemToDeleteNumber} deleted successfully.";
        session()->flash('message', $msg);
        $this->dispatch('notify', type: 'success', title: 'Assessment Deleted', message: $msg);
        $this->showItemDeleteModal = false;
    }

    public function render()
    {
        // Metric KPIs
        $totalAssessmentsCount = DutyAssessment::count();
        $totalCifValue = (float) DutyAssessment::sum('cif_value');
        $totalDutyAmount = (float) DutyAssessment::sum('duty_amount');
        $totalLandedCost = (float) DutyAssessment::sum('total_landed_cost');

        // Query list
        $assessments = DutyAssessment::with(['shipment.purchaseOrder.supplier', 'assessor', 'landedCostComponents'])
            ->when($this->search, function ($q) {
                $s = '%' . $this->search . '%';
                return $q->where('assessment_id', 'like', $s)
                    ->orWhereHas('shipment', function ($sq) use ($s) {
                        $sq->where('shipment_ref', 'like', $s)
                            ->orWhere('bayan_declaration_no', 'like', $s)
                            ->orWhereHas('purchaseOrder', function ($pq) use ($s) {
                                $pq->where('po_number', 'like', $s)
                                    ->orWhereHas('supplier', function ($supq) use ($s) {
                                        $supq->where('supplier_name', 'like', $s);
                                    });
                            });
                    });
            })
            ->when($this->date_from, function ($q) {
                return $q->whereDate('assessed_date', '>=', $this->date_from);
            })
            ->when($this->date_to, function ($q) {
                return $q->whereDate('assessed_date', '<=', $this->date_to);
            })
            ->orderBy('assessment_id', 'desc')
            ->paginate(10);

        $shipments = ImportShipment::with(['purchaseOrder.supplier'])
            ->orderBy('shipment_id', 'desc')
            ->get();

        $users = User::orderBy('full_name')->get();

        $selectedAssessmentDetails = $this->view_assessment_id 
            ? DutyAssessment::with(['shipment.purchaseOrder.supplier', 'shipment.purchaseOrder.warehouse', 'assessor', 'landedCostComponents'])->find($this->view_assessment_id) 
            : null;

        return view('livewire.purchase-import.duty-assessment-management', [
            'assessments' => $assessments,
            'metrics' => [
                'total_assessments' => $totalAssessmentsCount,
                'total_cif' => $totalCifValue,
                'total_duty' => $totalDutyAmount,
                'total_landed' => $totalLandedCost,
            ],
            'shipments' => $shipments,
            'users' => $users,
            'selectedAssessmentDetails' => $selectedAssessmentDetails,
        ])->layout('layouts.app');
    }
}
