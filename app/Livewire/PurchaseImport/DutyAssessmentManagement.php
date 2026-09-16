<?php

namespace App\Livewire\PurchaseImport;

use App\Models\DutyAssessment;
use Livewire\Component;
use Livewire\WithPagination;

class DutyAssessmentManagement extends Component
{
    use WithPagination;

    public $search = '';

    public function render()
    {
        $assessments = DutyAssessment::with(['shipment.purchaseOrder', 'assessor', 'landedCostComponents'])
            ->orderBy('assessment_id', 'desc')
            ->paginate(10);

        return view('livewire.purchase-import.duty-assessment-management', [
            'assessments' => $assessments,
        ])->layout('layouts.app');
    }
}
