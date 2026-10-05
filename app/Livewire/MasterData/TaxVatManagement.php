<?php

namespace App\Livewire\MasterData;

use App\Models\AuditLog;
use App\Models\TaxVatRate;
use Livewire\Component;
use Livewire\WithPagination;

class TaxVatManagement extends Component
{
    use WithPagination;

    public $search = '';
    public $showCreateModal = false;
    public $showDeleteModal = false;

    // Form fields
    public $tax_rate_id;
    public $tax_name = '';
    public $tax_type = 'VAT';
    public $rate_percent = 5.0;
    public $effective_from = '';
    public $effective_to = '';
    public $is_active = true;

    // Delete state
    public $taxToDelete = null;

    protected $rules = [
        'tax_name' => 'required|string|max:100',
        'tax_type' => 'required|in:VAT,EXCISE,CUSTOMS_DUTY,OTHER',
        'rate_percent' => 'required|numeric|min:0|max:500',
        'effective_from' => 'required|date',
        'effective_to' => 'nullable|date|after_or_equal:effective_from',
        'is_active' => 'boolean',
    ];

    public function mount()
    {
        $this->effective_from = now()->format('Y-m-d');
    }

    public function render()
    {
        $taxRates = TaxVatRate::where('tax_name', 'like', "%{$this->search}%")
            ->orWhere('tax_type', 'like', "%{$this->search}%")
            ->orderBy('rate_percent', 'desc')
            ->paginate(10);

        return view('livewire.master-data.tax-vat-management', [
            'taxRates' => $taxRates,
        ])->layout('layouts.app');
    }

    public function openCreateModal()
    {
        $this->reset(['tax_rate_id', 'tax_name', 'tax_type', 'rate_percent', 'effective_to']);
        $this->tax_type = 'VAT';
        $this->rate_percent = 5.0;
        $this->effective_from = now()->format('Y-m-d');
        $this->is_active = true;
        $this->showCreateModal = true;
    }

    public function editTax($id)
    {
        $tax = TaxVatRate::findOrFail($id);
        $this->tax_rate_id = $tax->tax_rate_id;
        $this->tax_name = $tax->tax_name;
        $this->tax_type = $tax->tax_type;
        $this->rate_percent = (float)$tax->rate_percent;
        $this->effective_from = $tax->effective_from;
        $this->effective_to = $tax->effective_to;
        $this->is_active = (bool)$tax->is_active;
        $this->showCreateModal = true;
    }

    public function saveTax()
    {
        $this->validate();

        $isEdit = !empty($this->tax_rate_id);

        $tax = TaxVatRate::updateOrCreate(
            ['tax_rate_id' => $this->tax_rate_id],
            [
                'tax_name' => $this->tax_name,
                'tax_type' => $this->tax_type,
                'rate_percent' => $this->rate_percent,
                'effective_from' => $this->effective_from,
                'effective_to' => $this->effective_to ?: null,
                'is_active' => $this->is_active,
            ]
        );

        AuditLog::create([
            'user_id' => auth()->id(),
            'action_type' => $isEdit ? 'UPDATE' : 'CREATE',
            'entity_type' => 'TaxVatRate',
            'entity_id' => $tax->tax_rate_id,
            'details_json' => json_encode(['name' => $tax->tax_name, 'rate' => $tax->rate_percent]),
            'ip_address' => request()->ip(),
        ]);

        $this->showCreateModal = false;
        $this->dispatch('toast', message: $isEdit ? "Tax rate updated successfully." : "Tax rate created successfully.", type: 'success');
    }

    public function confirmDeleteTax($id)
    {
        $this->taxToDelete = TaxVatRate::findOrFail($id);
        $this->showDeleteModal = true;
    }

    public function deleteTax()
    {
        if ($this->taxToDelete) {
            $name = $this->taxToDelete->tax_name;

            AuditLog::create([
                'user_id' => auth()->id(),
                'action_type' => 'DELETE',
                'entity_type' => 'TaxVatRate',
                'entity_id' => $this->taxToDelete->tax_rate_id,
                'details_json' => json_encode(['name' => $name]),
                'ip_address' => request()->ip(),
            ]);

            $this->taxToDelete->delete();
            $this->showDeleteModal = false;
            $this->taxToDelete = null;

            $this->dispatch('toast', message: "Tax rate '{$name}' deleted successfully.", type: 'warning');
        }
    }
}
