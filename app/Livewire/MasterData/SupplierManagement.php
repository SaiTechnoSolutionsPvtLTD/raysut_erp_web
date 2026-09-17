<?php

namespace App\Livewire\MasterData;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Livewire\Component;
use Livewire\WithPagination;

class SupplierManagement extends Component
{
    use WithPagination;

    public $search = '';
    public $country_filter = '';
    public $status_filter = '';

    public $showModal = false;
    public $isEdit = false;
    public $supplier_id;

    public $supplier_code;
    public $supplier_name;
    public $country;
    public $custom_country_name = '';
    public $isCustomCountry = false;
    public $currency_code = 'OMR';
    public $payment_terms_days = 30;
    public $import_license_no;
    public $is_active = true;

    // Delete Modal state
    public bool $showItemDeleteModal = false;
    public string $itemToDeleteType = '';
    public int $itemToDeleteId = 0;
    public string $itemToDeleteName = '';

    public function updatedCountry($value): void
    {
        $this->isCustomCountry = ($value === 'ADD_NEW');
        if ($this->isCustomCountry) {
            $this->custom_country_name = '';
        }
    }

    protected function generateSupplierCode(): string
    {
        $nextId = (Supplier::max('supplier_id') ?? 0) + 1;
        do {
            $code = 'SUP-' . str_pad((string)$nextId, 4, '0', STR_PAD_LEFT);
            $exists = Supplier::where('supplier_code', $code)->exists();
            if ($exists) {
                $nextId++;
            }
        } while ($exists);

        return $code;
    }

    public function openCreateModal(): void
    {
        if (! auth()->user()?->hasPermission('Master Data', 'CREATE')) {
            abort(403, 'Unauthorized action: You do not have permission to CREATE suppliers.');
        }

        $this->reset([
            'supplier_id', 'supplier_name', 'country', 'custom_country_name', 'isCustomCountry', 'import_license_no'
        ]);
        $this->supplier_code = $this->generateSupplierCode();
        $this->currency_code = 'OMR';
        $this->payment_terms_days = 30;
        $this->is_active = true;
        $this->isEdit = false;
        $this->showModal = true;
    }

    public function editSupplier($id): void
    {
        if (! auth()->user()?->hasPermission('Master Data', 'EDIT')) {
            abort(403, 'Unauthorized action: You do not have permission to EDIT suppliers.');
        }

        $supplier = Supplier::findOrFail($id);
        $this->supplier_id = $supplier->supplier_id;
        $this->supplier_code = $supplier->supplier_code;
        $this->supplier_name = $supplier->supplier_name;
        $this->country = $supplier->country;
        $this->custom_country_name = '';
        $this->isCustomCountry = false;
        $this->currency_code = $supplier->currency_code ?? 'OMR';
        $this->payment_terms_days = $supplier->payment_terms_days ?? 30;
        $this->import_license_no = $supplier->import_license_no;
        $this->is_active = (bool)$supplier->is_active;

        $this->isEdit = true;
        $this->showModal = true;
    }

    public function saveSupplier(): void
    {
        $requiredAction = $this->supplier_id ? 'EDIT' : 'CREATE';
        if (! auth()->user()?->hasPermission('Master Data', $requiredAction)) {
            abort(403, "Unauthorized action: You do not have permission to {$requiredAction} suppliers.");
        }

        if (!$this->supplier_id && empty($this->supplier_code)) {
            $this->supplier_code = $this->generateSupplierCode();
        }

        $finalCountry = $this->country;
        if ($this->country === 'ADD_NEW' || $this->isCustomCountry) {
            $this->validate(['custom_country_name' => 'required|string|max:60']);
            $finalCountry = trim($this->custom_country_name);
        }
        $this->country = $finalCountry;

        $this->validate([
            'supplier_code' => 'required|string|max:20|unique:suppliers,supplier_code,' . ($this->supplier_id ?? 'NULL') . ',supplier_id',
            'supplier_name' => 'required|string|max:150',
            'country' => 'required|string|max:60',
            'currency_code' => 'required|string|size:3',
            'payment_terms_days' => 'required|integer|min:0|max:365',
            'import_license_no' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);

        Supplier::updateOrCreate(
            ['supplier_id' => $this->supplier_id],
            [
                'supplier_code' => $this->supplier_code,
                'supplier_name' => trim($this->supplier_name),
                'country' => trim($this->country),
                'currency_code' => strtoupper(trim($this->currency_code)),
                'payment_terms_days' => (int)$this->payment_terms_days,
                'import_license_no' => $this->import_license_no ? trim($this->import_license_no) : null,
                'is_active' => $this->is_active,
            ]
        );

        session()->flash('message', $this->isEdit ? 'Supplier updated successfully.' : 'Supplier created successfully.');
        $this->dispatch('notify', type: 'success', title: 'Supplier Saved', message: 'Supplier ' . ($this->isEdit ? 'updated' : 'created') . ' successfully.');
        $this->showModal = false;
    }

    public function confirmDeleteSupplier(int $id, string $name): void
    {
        $this->itemToDeleteType = 'Supplier';
        $this->itemToDeleteId = $id;
        $this->itemToDeleteName = $name;
        $this->showItemDeleteModal = true;
    }

    public function executeDeleteItem(): void
    {
        $this->showItemDeleteModal = false;
        if ($this->itemToDeleteType === 'Supplier') {
            $this->deleteSupplier($this->itemToDeleteId);
        }
        $this->reset(['itemToDeleteType', 'itemToDeleteId', 'itemToDeleteName']);
    }

    public function deleteSupplier($id): void
    {
        if (! auth()->user()?->hasPermission('Master Data', 'DELETE')) {
            abort(403, 'Unauthorized action: You do not have permission to DELETE suppliers.');
        }

        $supplier = Supplier::find($id);
        if ($supplier) {
            if (PurchaseOrder::where('supplier_id', $id)->exists()) {
                $this->dispatch('notify', type: 'error', title: 'Cannot Delete Supplier', message: "Cannot delete supplier '{$supplier->supplier_name}' because purchase orders are linked to it.");
                return;
            }

            try {
                $name = $supplier->supplier_name;
                $supplier->delete();
                $this->dispatch('notify', type: 'warning', title: 'Supplier Removed', message: "Supplier '{$name}' deleted successfully.");
            } catch (\Throwable $e) {
                $this->dispatch('notify', type: 'error', title: 'Cannot Delete Supplier', message: "Cannot delete supplier '{$supplier->supplier_name}' because it is referenced in purchasing or import records.");
            }
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'country_filter', 'status_filter']);
        $this->resetPage();
    }

    public function render()
    {
        $query = Supplier::withCount(['purchaseOrders', 'supplierProducts']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('supplier_name', 'like', '%' . $this->search . '%')
                  ->orWhere('supplier_code', 'like', '%' . $this->search . '%')
                  ->orWhere('import_license_no', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->country_filter) {
            $query->where('country', $this->country_filter);
        }

        if ($this->status_filter !== '') {
            $query->where('is_active', $this->status_filter === 'active');
        }

        $existingCountries = Supplier::select('country')->distinct()->whereNotNull('country')->where('country', '!=', '')->pluck('country')->toArray();
        $defaultCountries = ['Oman', 'United Kingdom', 'Scotland', 'France', 'Netherlands', 'United States', 'United Arab Emirates', 'Ireland', 'Italy', 'Australia', 'Germany', 'Japan', 'Mexico', 'South Africa'];
        $allCountries = array_unique(array_merge($defaultCountries, $existingCountries));
        sort($allCountries);

        return view('livewire.master-data.supplier-management', [
            'suppliers' => $query->orderBy('supplier_id', 'desc')->paginate(10),
            'countries' => $existingCountries,
            'allCountries' => array_values($allCountries),
        ])->layout('layouts.app');
    }
}
