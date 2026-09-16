<?php

namespace App\Livewire\MasterData;

use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\TaxVatRate;
use App\Models\Uom;
use Livewire\Component;
use Livewire\WithPagination;

class ProductManagement extends Component
{
    use WithPagination;

    public $search = '';
    public $category_id = '';
    public $brand_id = '';

    public $showModal = false;
    public $isEdit = false;
    public $product_id;

    public $sku_code;
    public $barcode;
    public $product_name;
    public $cat_id;
    public $brd_id;
    public $base_uom_id;
    public $tax_rate_id;
    public $hs_code;
    public $pack_size;
    public $reorder_level = 0;
    public $is_duty_free_eligible = false;
    public $is_active = true;

    // Dynamic Add New properties
    public $custom_category_name = '';
    public $isCustomCategory = false;

    public $custom_brand_name = '';
    public $isCustomBrand = false;

    public $custom_uom_code = '';
    public $custom_uom_name = '';
    public $isCustomUom = false;

    public $custom_tax_name = '';
    public $custom_tax_rate = '';
    public $isCustomTax = false;

    protected function generateSkuCode(): string
    {
        $nextId = (Product::max('product_id') ?? 0) + 1;
        do {
            $code = 'SKU-' . str_pad((string)$nextId, 4, '0', STR_PAD_LEFT);
            $exists = Product::where('sku_code', $code)->exists();
            if ($exists) {
                $nextId++;
            }
        } while ($exists);

        return $code;
    }

    public function updatedCatId($value): void
    {
        $this->isCustomCategory = ($value === 'ADD_NEW');
        if ($this->isCustomCategory) {
            $this->custom_category_name = '';
        }
    }

    public function updatedBrdId($value): void
    {
        $this->isCustomBrand = ($value === 'ADD_NEW');
        if ($this->isCustomBrand) {
            $this->custom_brand_name = '';
        }
    }

    public function updatedBaseUomId($value): void
    {
        $this->isCustomUom = ($value === 'ADD_NEW');
        if ($this->isCustomUom) {
            $this->custom_uom_code = '';
            $this->custom_uom_name = '';
        }
    }

    public function updatedTaxRateId($value): void
    {
        $this->isCustomTax = ($value === 'ADD_NEW');
        if ($this->isCustomTax) {
            $this->custom_tax_name = '';
            $this->custom_tax_rate = '';
        }
    }

    protected function rules()
    {
        return [
            'sku_code' => 'required|string|max:50|unique:products,sku_code,' . ($this->product_id ?? 'NULL') . ',product_id',
            'barcode' => 'nullable|string|max:50',
            'product_name' => 'required|string|max:200',
            'cat_id' => 'required',
            'brd_id' => 'nullable',
            'base_uom_id' => 'required',
            'tax_rate_id' => 'nullable',
            'hs_code' => 'nullable|string|max:20',
            'pack_size' => 'nullable|string|max:50',
            'reorder_level' => 'required|numeric|min:0',
            'is_duty_free_eligible' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function openCreateModal()
    {
        if (! auth()->user()?->hasPermission('Master Data', 'CREATE')) {
            abort(403, 'Unauthorized action: You do not have permission to CREATE products.');
        }

        $this->reset([
            'product_id', 'barcode', 'product_name', 'cat_id', 'brd_id', 
            'base_uom_id', 'tax_rate_id', 'hs_code', 'pack_size', 'reorder_level', 
            'is_duty_free_eligible', 'custom_category_name', 'isCustomCategory', 
            'custom_brand_name', 'isCustomBrand', 'custom_uom_code', 'custom_uom_name', 
            'isCustomUom', 'custom_tax_name', 'custom_tax_rate', 'isCustomTax'
        ]);
        
        $this->sku_code = $this->generateSkuCode();
        $this->is_active = true;
        $this->isEdit = false;
        $this->showModal = true;
    }

    public function editProduct($id)
    {
        if (! auth()->user()?->hasPermission('Master Data', 'EDIT')) {
            abort(403, 'Unauthorized action: You do not have permission to EDIT products.');
        }

        $product = Product::findOrFail($id);
        $this->product_id = $product->product_id;
        $this->sku_code = $product->sku_code;
        $this->barcode = $product->barcode;
        $this->product_name = $product->product_name;
        $this->cat_id = $product->category_id;
        $this->brd_id = $product->brand_id;
        $this->base_uom_id = $product->base_uom_id;
        $this->tax_rate_id = $product->tax_rate_id;
        $this->hs_code = $product->hs_code;
        $this->pack_size = $product->pack_size;
        $this->reorder_level = $product->reorder_level;
        $this->is_duty_free_eligible = $product->is_duty_free_eligible;
        $this->is_active = $product->is_active;

        $this->isCustomCategory = false;
        $this->isCustomBrand = false;
        $this->isCustomUom = false;
        $this->isCustomTax = false;

        $this->isEdit = true;
        $this->showModal = true;
    }

    public function saveProduct()
    {
        $requiredAction = $this->product_id ? 'EDIT' : 'CREATE';
        if (! auth()->user()?->hasPermission('Master Data', $requiredAction)) {
            abort(403, "Unauthorized action: You do not have permission to {$requiredAction} products.");
        }

        if (!$this->product_id && empty($this->sku_code)) {
            $this->sku_code = $this->generateSkuCode();
        }

        // Handle Custom Category
        $finalCatId = $this->cat_id;
        if ($this->cat_id === 'ADD_NEW' || $this->isCustomCategory) {
            $this->validate(['custom_category_name' => 'required|string|max:100']);
            $newCat = ProductCategory::create([
                'category_name' => trim($this->custom_category_name),
            ]);
            $finalCatId = $newCat->category_id;
        }

        // Handle Custom Brand
        $finalBrdId = $this->brd_id ?: null;
        if ($this->brd_id === 'ADD_NEW' || $this->isCustomBrand) {
            $this->validate(['custom_brand_name' => 'required|string|max:100']);
            $newBrd = Brand::create([
                'brand_name' => trim($this->custom_brand_name),
            ]);
            $finalBrdId = $newBrd->brand_id;
        }

        // Handle Custom UOM
        $finalUomId = $this->base_uom_id;
        if ($this->base_uom_id === 'ADD_NEW' || $this->isCustomUom) {
            $this->validate([
                'custom_uom_code' => 'required|string|max:20',
                'custom_uom_name' => 'required|string|max:50',
            ]);
            $newUom = Uom::create([
                'uom_code' => strtoupper(trim($this->custom_uom_code)),
                'uom_name' => trim($this->custom_uom_name),
            ]);
            $finalUomId = $newUom->uom_id;
        }

        // Handle Custom Tax Rate
        $finalTaxId = $this->tax_rate_id ?: null;
        if ($this->tax_rate_id === 'ADD_NEW' || $this->isCustomTax) {
            $this->validate([
                'custom_tax_name' => 'required|string|max:100',
                'custom_tax_rate' => 'required|numeric|min:0|max:100',
            ]);
            $newTax = TaxVatRate::create([
                'tax_name' => trim($this->custom_tax_name),
                'tax_type' => 'VAT',
                'rate_percent' => $this->custom_tax_rate,
                'effective_from' => now(),
                'is_active' => true,
            ]);
            $finalTaxId = $newTax->tax_rate_id;
        }

        $this->cat_id = $finalCatId;
        $this->brd_id = $finalBrdId;
        $this->base_uom_id = $finalUomId;
        $this->tax_rate_id = $finalTaxId;

        $this->validate();

        Product::updateOrCreate(
            ['product_id' => $this->product_id],
            [
                'sku_code' => $this->sku_code,
                'barcode' => $this->barcode,
                'product_name' => $this->product_name,
                'category_id' => $finalCatId,
                'brand_id' => $finalBrdId,
                'base_uom_id' => $finalUomId,
                'tax_rate_id' => $finalTaxId,
                'hs_code' => $this->hs_code,
                'pack_size' => $this->pack_size,
                'reorder_level' => $this->reorder_level,
                'is_duty_free_eligible' => $this->is_duty_free_eligible,
                'is_active' => $this->is_active,
            ]
        );

        session()->flash('message', $this->isEdit ? 'Product updated successfully.' : 'Product created successfully.');
        $this->dispatch('notify', type: 'success', title: 'Product Saved', message: 'Product ' . ($this->isEdit ? 'updated' : 'created') . ' successfully.');
        $this->showModal = false;
    }

    public function render()
    {
        $query = Product::with(['category', 'brand', 'uom', 'taxRate']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('product_name', 'like', '%' . $this->search . '%')
                  ->orWhere('sku_code', 'like', '%' . $this->search . '%')
                  ->orWhere('barcode', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->category_id) {
            $query->where('category_id', $this->category_id);
        }

        if ($this->brand_id) {
            $query->where('brand_id', $this->brand_id);
        }

        return view('livewire.master-data.product-management', [
            'products' => $query->paginate(10),
            'categories' => ProductCategory::all(),
            'brands' => Brand::all(),
            'uoms' => Uom::all(),
            'taxes' => TaxVatRate::all(),
        ])->layout('layouts.app');
    }
}

