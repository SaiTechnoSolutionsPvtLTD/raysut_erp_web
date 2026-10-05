<?php

namespace App\Livewire\MasterData;

use App\Models\AuditLog;
use App\Models\Product;
use App\Models\Uom;
use App\Models\UomConversion;
use Livewire\Component;
use Livewire\WithPagination;

class UomManagement extends Component
{
    use WithPagination;

    public $search = '';
    public $showCreateModal = false;
    public $showDeleteModal = false;

    // Form fields for UOM
    public $uom_id;
    public $uom_code = '';
    public $uom_name = '';

    // UOM Conversion form fields
    public $showConversionModal = false;
    public $conversion_id;
    public $product_id = '';
    public $from_uom_id = '';
    public $to_uom_id = '';
    public $conversion_factor = 1.0;

    // Delete state
    public $uomToDelete = null;

    protected $rules = [
        'uom_code' => 'required|string|max:10',
        'uom_name' => 'required|string|max:50',
    ];

    public function render()
    {
        $uoms = Uom::where('uom_name', 'like', "%{$this->search}%")
            ->orWhere('uom_code', 'like', "%{$this->search}%")
            ->orderBy('uom_code')
            ->paginate(10);

        $conversions = UomConversion::with(['product', 'fromUom', 'toUom'])
            ->orderBy('conversion_id', 'desc')
            ->get();

        $products = Product::orderBy('product_name')->get();

        return view('livewire.master-data.uom-management', [
            'uoms' => $uoms,
            'conversions' => $conversions,
            'products' => $products,
        ])->layout('layouts.app');
    }

    public function openCreateModal()
    {
        $this->reset(['uom_id', 'uom_code', 'uom_name']);
        $this->showCreateModal = true;
    }

    public function editUom($id)
    {
        $uom = Uom::findOrFail($id);
        $this->uom_id = $uom->uom_id;
        $this->uom_code = $uom->uom_code;
        $this->uom_name = $uom->uom_name;
        $this->showCreateModal = true;
    }

    public function saveUom()
    {
        $this->validate();

        $isEdit = !empty($this->uom_id);

        $uom = Uom::updateOrCreate(
            ['uom_id' => $this->uom_id],
            [
                'uom_code' => strtoupper($this->uom_code),
                'uom_name' => $this->uom_name,
            ]
        );

        AuditLog::create([
            'user_id' => auth()->id(),
            'action_type' => $isEdit ? 'UPDATE' : 'CREATE',
            'entity_type' => 'Uom',
            'entity_id' => $uom->uom_id,
            'details_json' => json_encode(['code' => $uom->uom_code, 'name' => $uom->uom_name]),
            'ip_address' => request()->ip(),
        ]);

        $this->showCreateModal = false;
        $this->dispatch('toast', message: $isEdit ? "UOM updated successfully." : "UOM created successfully.", type: 'success');
    }

    public function confirmDeleteUom($id)
    {
        $this->uomToDelete = Uom::findOrFail($id);
        $this->showDeleteModal = true;
    }

    public function deleteUom()
    {
        if ($this->uomToDelete) {
            $name = $this->uomToDelete->uom_name;
            
            AuditLog::create([
                'user_id' => auth()->id(),
                'action_type' => 'DELETE',
                'entity_type' => 'Uom',
                'entity_id' => $this->uomToDelete->uom_id,
                'details_json' => json_encode(['name' => $name]),
                'ip_address' => request()->ip(),
            ]);

            $this->uomToDelete->delete();
            $this->showDeleteModal = false;
            $this->uomToDelete = null;

            $this->dispatch('toast', message: "UOM '{$name}' deleted successfully.", type: 'warning');
        }
    }

    public function openConversionModal()
    {
        $this->reset(['conversion_id', 'product_id', 'from_uom_id', 'to_uom_id', 'conversion_factor']);
        $this->showConversionModal = true;
    }

    public function saveConversion()
    {
        $this->validate([
            'product_id' => 'required|exists:products,product_id',
            'from_uom_id' => 'required|exists:uoms,uom_id',
            'to_uom_id' => 'required|exists:uoms,uom_id|different:from_uom_id',
            'conversion_factor' => 'required|numeric|min:0.0001',
        ]);

        UomConversion::updateOrCreate(
            ['conversion_id' => $this->conversion_id],
            [
                'product_id' => $this->product_id,
                'from_uom_id' => $this->from_uom_id,
                'to_uom_id' => $this->to_uom_id,
                'conversion_factor' => $this->conversion_factor,
            ]
        );

        $this->showConversionModal = false;
        $this->dispatch('toast', message: "UOM conversion saved successfully.", type: 'success');
    }
}
