<div x-data="{ open: @entangle('showModal') }" class="space-y-6">
    <!-- Single Row Header Banner (Title, Search, Filters & Action Button) -->
    <div class="flex flex-wrap items-center justify-between gap-3 pb-4 border-b border-slate-200/80">
        <!-- Title & Subtitle -->
        <div>
            <h1 class="font-heading font-bold text-xl text-slate-900 leading-tight">Product Catalog & Master Data</h1>
            <p class="text-[11px] text-slate-500 mt-0.5">Manage RMSC beverage products, SKUs, tax classes, and packaging units</p>
        </div>

        <!-- Controls Group (Search, Filters, Button in 1 single line) -->
        <div class="flex items-center gap-2 shrink-0">
            <!-- Search Bar -->
            <div class="relative w-52">
                <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400 z-10">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search SKU or Name..." style="padding-left: 2.25rem !important;" class="w-52 h-9 pr-3 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white focus:bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-medium text-slate-800 transition">
            </div>



            <!-- Category Filter -->
            <select wire:model.live="category_id" class="h-9 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white focus:bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-medium text-slate-800 transition px-2.5 cursor-pointer">
                <option value="">All Categories</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->category_id }}">{{ $cat->category_name }}</option>
                @endforeach
            </select>

            <!-- Brand Filter -->
            <select wire:model.live="brand_id" class="h-9 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white focus:bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-medium text-slate-800 transition px-2.5 cursor-pointer">
                <option value="">All Brands</option>
                @foreach($brands as $b)
                    <option value="{{ $b->brand_id }}">{{ $b->brand_name }}</option>
                @endforeach
            </select>

            <!-- Add New Product Button -->
            @if(auth()->user()?->hasPermission('Master Data', 'CREATE'))
            <button wire:click="openCreateModal" @click="open = true" type="button" class="h-9 px-3 bg-rmsc-red hover:bg-rmsc-red-dark text-white font-semibold text-xs rounded-xl shadow-xs transition flex items-center space-x-1 shrink-0 cursor-pointer">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Add Product</span>
            </button>
            @endif
        </div>
    </div>




    <!-- Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                    <tr>
                        <th class="px-6 py-4">SKU / Barcode</th>
                        <th class="px-6 py-4">Product Name</th>
                        <th class="px-6 py-4">Category & Brand</th>
                        <th class="px-6 py-4">Base UOM</th>
                        <th class="px-6 py-4">Pack Size</th>
                        <th class="px-6 py-4">Reorder Level</th>
                        <th class="px-6 py-4">Status</th>
                        @if(auth()->user()?->hasPermission('Master Data', 'EDIT'))
                        <th class="px-6 py-4 text-right">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($products as $prod)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="px-6 py-4 font-mono text-xs">
                                <div class="font-bold text-slate-800">{{ $prod->sku_code }}</div>
                                <div class="text-[11px] text-slate-400 font-mono">{{ $prod->barcode ?? 'N/A' }}</div>
                            </td>
                            <td class="px-6 py-4 font-semibold text-slate-900">
                                <span>{{ $prod->product_name }}</span>
                                @if($prod->is_duty_free_eligible)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-sky-50 text-sky-700 border border-sky-200 ml-1.5">
                                        Bonded / Duty Free
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-medium text-slate-800">{{ $prod->category?->category_name ?? '-' }}</div>
                                <div class="text-xs text-slate-400">{{ $prod->brand?->brand_name ?? '-' }}</div>
                            </td>
                            <td class="px-6 py-4 font-mono text-xs text-slate-600">{{ $prod->uom?->uom_code }}</td>
                            <td class="px-6 py-4 text-slate-600">{{ $prod->pack_size ?? '-' }}</td>
                            <td class="px-6 py-4 font-mono font-medium text-slate-700">{{ number_format($prod->reorder_level, 2) }}</td>
                            <td class="px-6 py-4">
                                @if($prod->is_active)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                        Inactive
                                    </span>
                                @endif
                            </td>
                            @if(auth()->user()?->hasPermission('Master Data', 'EDIT'))
                            <td class="px-6 py-4 text-right">
                                <button wire:click="editProduct({{ $prod->product_id }})" @click="open = true" class="p-1.5 text-slate-500 hover:text-rmsc-red hover:bg-red-50 rounded-lg transition" title="Edit Product">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                            </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-8 text-center text-slate-400">No products found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-200/80">
            {{ $products->links() }}
        </div>
    </div>

    <!-- Create/Edit Modal -->
    <template x-teleport="body">
        <div x-show="open" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
             style="display: none;">
            <div @click.away="open = false; $wire.set('showModal', false)" 
                 class="bg-white rounded-2xl max-w-2xl w-full shadow-2xl border border-slate-200 overflow-hidden transform transition-all">
                <div class="px-6 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="font-bold text-slate-900 text-base">{{ $isEdit ? 'Edit Product' : 'Create New Product' }}</h3>
                    <button type="button" @click="open = false; $wire.set('showModal', false)" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form wire:submit.prevent="saveProduct" class="p-6 space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">SKU Code *</label>
                            <input type="text" wire:model="sku_code" readonly class="w-full rounded-xl border-slate-200 bg-slate-100/90 text-slate-600 font-mono text-sm cursor-not-allowed shadow-2xs focus:ring-0 focus:border-slate-200" placeholder="Auto-generated (e.g. SKU-0001)">
                            <p class="text-[11px] text-slate-400 mt-1 flex items-center">
                                <svg class="w-3.5 h-3.5 mr-1 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                <span>System Auto-Generated Code (Read-Only)</span>
                            </p>
                            @error('sku_code') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Barcode</label>
                            <input type="text" wire:model="barcode" class="w-full rounded-xl border border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-sm font-mono" placeholder="e.g. 600123456789">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Product Name *</label>
                        <input type="text" wire:model="product_name" class="w-full rounded-xl border border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-sm" placeholder="e.g. Omani Premium Beverages 1L">
                        @error('product_name') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Category *</label>
                            <select wire:model.live="cat_id" class="w-full rounded-xl border border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-sm">
                                <option value="">Select Category</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->category_id }}">{{ $cat->category_name }}</option>
                                @endforeach
                                <option value="ADD_NEW">+ Add Custom Category...</option>
                            </select>
                            @error('cat_id') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror

                            @if($isCustomCategory || $cat_id === 'ADD_NEW')
                                <div class="mt-2.5 p-3 bg-amber-50/70 border border-amber-200/90 rounded-xl space-y-1.5">
                                    <label class="block text-[11px] font-bold text-amber-900 uppercase tracking-wider">New Category Name *</label>
                                    <input type="text" wire:model="custom_category_name" class="w-full rounded-lg border-amber-200 bg-white focus:border-rmsc-red focus:ring-rmsc-red text-sm font-semibold text-slate-800" placeholder="e.g. Energy Drinks, Scotch">
                                    @error('custom_category_name') <span class="text-xs text-rmsc-red block font-medium">{{ $message }}</span> @enderror
                                </div>
                            @endif
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Brand</label>
                            <select wire:model.live="brd_id" class="w-full rounded-xl border border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-sm">
                                <option value="">Select Brand</option>
                                @foreach($brands as $b)
                                    <option value="{{ $b->brand_id }}">{{ $b->brand_name }}</option>
                                @endforeach
                                <option value="ADD_NEW">+ Add Custom Brand...</option>
                            </select>

                            @if($isCustomBrand || $brd_id === 'ADD_NEW')
                                <div class="mt-2.5 p-3 bg-amber-50/70 border border-amber-200/90 rounded-xl space-y-1.5">
                                    <label class="block text-[11px] font-bold text-amber-900 uppercase tracking-wider">New Brand Name *</label>
                                    <input type="text" wire:model="custom_brand_name" class="w-full rounded-lg border-amber-200 bg-white focus:border-rmsc-red focus:ring-rmsc-red text-sm font-semibold text-slate-800" placeholder="e.g. Macallan, Red Bull">
                                    @error('custom_brand_name') <span class="text-xs text-rmsc-red block font-medium">{{ $message }}</span> @enderror
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Base UOM *</label>
                            <select wire:model.live="base_uom_id" class="w-full rounded-xl border border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-sm">
                                <option value="">Select UOM</option>
                                @foreach($uoms as $u)
                                    <option value="{{ $u->uom_id }}">{{ $u->uom_code }} - {{ $u->uom_name }}</option>
                                @endforeach
                                <option value="ADD_NEW">+ Add Custom UOM...</option>
                            </select>
                            @error('base_uom_id') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror

                            @if($isCustomUom || $base_uom_id === 'ADD_NEW')
                                <div class="mt-2.5 p-3 bg-amber-50/70 border border-amber-200/90 rounded-xl space-y-2">
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-[11px] font-bold text-amber-900 uppercase tracking-wider">UOM Code *</label>
                                            <input type="text" wire:model="custom_uom_code" class="w-full rounded-lg border-amber-200 bg-white focus:border-rmsc-red focus:ring-rmsc-red text-sm uppercase font-mono font-semibold text-slate-800" placeholder="e.g. CAN">
                                            @error('custom_uom_code') <span class="text-xs text-rmsc-red block font-medium">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-bold text-amber-900 uppercase tracking-wider">UOM Name *</label>
                                            <input type="text" wire:model="custom_uom_name" class="w-full rounded-lg border-amber-200 bg-white focus:border-rmsc-red focus:ring-rmsc-red text-sm font-semibold text-slate-800" placeholder="e.g. Aluminium Can">
                                            @error('custom_uom_name') <span class="text-xs text-rmsc-red block font-medium">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Tax Rate</label>
                            <select wire:model.live="tax_rate_id" class="w-full rounded-xl border border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-sm">
                                <option value="">Select Tax</option>
                                @foreach($taxes as $t)
                                    <option value="{{ $t->tax_rate_id }}">{{ $t->tax_name }} ({{ $t->rate_percent }}%)</option>
                                @endforeach
                                <option value="ADD_NEW">+ Add Custom Tax Rate...</option>
                            </select>

                            @if($isCustomTax || $tax_rate_id === 'ADD_NEW')
                                <div class="mt-2.5 p-3 bg-amber-50/70 border border-amber-200/90 rounded-xl space-y-2">
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-[11px] font-bold text-amber-900 uppercase tracking-wider">Tax Name *</label>
                                            <input type="text" wire:model="custom_tax_name" class="w-full rounded-lg border-amber-200 bg-white focus:border-rmsc-red focus:ring-rmsc-red text-sm font-semibold text-slate-800" placeholder="e.g. Excise Duty">
                                            @error('custom_tax_name') <span class="text-xs text-rmsc-red block font-medium">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-bold text-amber-900 uppercase tracking-wider">Rate (%) *</label>
                                            <input type="number" step="0.01" wire:model="custom_tax_rate" class="w-full rounded-lg border-amber-200 bg-white focus:border-rmsc-red focus:ring-rmsc-red text-sm font-mono font-semibold text-slate-800" placeholder="e.g. 5.00">
                                            @error('custom_tax_rate') <span class="text-xs text-rmsc-red block font-medium">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>


                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">HS Code</label>
                            <input type="text" wire:model="hs_code" class="w-full rounded-xl border border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-sm font-mono">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Pack Size</label>
                            <input type="text" wire:model="pack_size" placeholder="12 x 750ml" class="w-full rounded-xl border border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Reorder Level</label>
                            <input type="number" step="0.01" wire:model="reorder_level" class="w-full rounded-xl border border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-sm font-mono">
                        </div>
                    </div>

                    <div class="flex items-center space-x-6 pt-2">
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="checkbox" wire:model="is_duty_free_eligible" class="rounded border-slate-300 text-rmsc-red focus:ring-rmsc-red">
                            <span class="ml-2 text-xs font-semibold text-slate-700">Duty Free / Bonded Eligible</span>
                        </label>
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="checkbox" wire:model="is_active" class="rounded border-slate-300 text-rmsc-red focus:ring-rmsc-red">
                            <span class="ml-2 text-xs font-semibold text-slate-700">Active Status</span>
                        </label>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex justify-end space-x-3">
                        <button type="button" @click="open = false; $wire.set('showModal', false)" class="px-4 py-2 border border-slate-200 text-slate-700 font-semibold text-xs rounded-xl hover:bg-slate-50 transition">Cancel</button>
                        <button type="submit" class="px-5 py-2 bg-rmsc-red hover:bg-rmsc-red-dark text-white font-semibold text-xs rounded-xl shadow-md transition">Save Product</button>
                    </div>
                </form>
            </div>
        </div>
    </template>
</div>

