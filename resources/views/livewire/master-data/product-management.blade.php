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
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 z-10">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search SKU or Name..." style="padding-left: 2.5rem !important;" class="w-52 h-9 !pl-10 pr-8 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white focus:bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-medium text-slate-800 transition">
                @if($search)
                    <button wire:click="$set('search', '')" type="button" class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600 transition cursor-pointer" title="Clear search">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                @endif
            </div>

            <!-- Category Filter -->
            <select wire:model.live="category_id" class="h-9 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white focus:bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-medium text-slate-800 transition pl-3 pr-8 cursor-pointer">
                <option value="">All Categories</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->category_id }}">{{ $cat->category_name }}</option>
                @endforeach
            </select>

            <!-- Brand Filter -->
            <select wire:model.live="brand_id" class="h-9 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white focus:bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-medium text-slate-800 transition pl-3 pr-8 cursor-pointer">
                <option value="">All Brands</option>
                @foreach($brands as $b)
                    <option value="{{ $b->brand_id }}">{{ $b->brand_name }}</option>
                @endforeach
            </select>

            <!-- Clear Filters Button -->
            @if($search || $category_id || $brand_id)
                <button wire:click="clearFilters" type="button" class="h-9 px-2.5 bg-slate-100 hover:bg-slate-200/80 text-slate-600 hover:text-slate-900 text-xs font-medium rounded-xl transition flex items-center space-x-1 cursor-pointer shrink-0" title="Clear search & filters">
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    <span>Clear</span>
                </button>
            @endif

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
                        <th class="px-6 py-4">Retail Price</th>
                        <th class="px-6 py-4">Pack Size</th>
                        <th class="px-6 py-4">Reorder Level</th>
                        <th class="px-6 py-4">Status</th>
                        @if(auth()->user()?->hasPermission('Master Data', 'EDIT') || auth()->user()?->hasPermission('Master Data', 'DELETE'))
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
                            <td class="px-6 py-4 font-mono text-xs font-bold text-emerald-700">
                                @if($retailTier = $prod->pricingTiers->firstWhere('price_tier', 'RETAIL'))
                                    OMR {{ number_format($retailTier->unit_price, 3) }}
                                @else
                                    <span class="text-slate-400 font-normal">-</span>
                                @endif
                            </td>
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
                            @if(auth()->user()?->hasPermission('Master Data', 'EDIT') || auth()->user()?->hasPermission('Master Data', 'DELETE'))
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end space-x-1">
                                    @if(auth()->user()?->hasPermission('Master Data', 'EDIT'))
                                    <button wire:click="editProduct({{ $prod->product_id }})" @click="open = true" class="p-1.5 text-slate-500 hover:text-rmsc-red hover:bg-red-50 rounded-lg transition" title="Edit Product">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                    @endif
                                    @if(auth()->user()?->hasPermission('Master Data', 'DELETE'))
                                    <button type="button" wire:click="confirmDeleteItem('Product', {{ $prod->product_id }}, '{{ addslashes($prod->product_name) }}')" class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition" title="Delete Product">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                    @endif
                                </div>
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
            <div @click.away="if (!$wire.showItemDeleteModal) { open = false; $wire.set('showModal', false); }" 
                 class="bg-white rounded-2xl max-w-2xl w-full shadow-2xl border border-slate-200 overflow-hidden transform transition-all relative">
                <div class="px-6 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between rounded-t-2xl">
                    <h3 class="font-bold text-slate-900 text-base">{{ $isEdit ? 'Edit Product' : 'Create New Product' }}</h3>
                    <button type="button" @click="open = false; $wire.set('showModal', false)" class="text-slate-400 hover:text-slate-600 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form wire:submit.prevent="saveProduct" class="p-6 space-y-4">
                    @if(session()->has('message'))
                        <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-xs font-semibold text-emerald-800 flex items-center justify-between">
                            <span>{{ session('message') }}</span>
                        </div>
                    @endif
                    @if(session()->has('error'))
                        <div class="p-3 bg-red-50 border border-red-200 rounded-xl text-xs font-semibold text-rmsc-red flex items-center justify-between">
                            <span>{{ session('error') }}</span>
                        </div>
                    @endif

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
                            <input type="text" wire:model.blur="barcode" class="w-full rounded-xl border border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-sm font-mono" placeholder="e.g. 600123456789">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Product Name *</label>
                        <input type="text" wire:model.blur="product_name" class="w-full rounded-xl border border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-sm" placeholder="e.g. Omani Premium Beverages 1L">
                        @error('product_name') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Category Dropdown -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Category *</label>
                            <div x-data="{ open: false }" class="relative">
                                <button type="button" @click="open = !open" class="w-full flex items-center justify-between rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-left font-medium text-slate-800 focus:outline-none focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 transition shadow-2xs">
                                    <span class="truncate">
                                        @if($cat_id === 'ADD_NEW')
                                            <span class="text-amber-700 font-semibold">+ Add Custom Category...</span>
                                        @elseif($cat_id && ($selectedCat = $categories->firstWhere('category_id', $cat_id)))
                                            {{ $selectedCat->category_name }}
                                        @else
                                            <span class="text-slate-400">Select Category</span>
                                        @endif
                                    </span>
                                    <svg class="w-4 h-4 text-slate-400 shrink-0 ml-1 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </button>

                                <div x-show="open" @click.outside="open = false" x-transition class="absolute z-50 mt-1 w-full rounded-xl bg-white shadow-xl border border-slate-200 py-1 max-h-48 overflow-y-auto overscroll-contain shadow-2xl [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden" style="display: none;">
                                    <div @click="$wire.set('cat_id', ''); open = false" class="px-3 py-2 text-xs font-medium text-slate-400 hover:bg-slate-50 cursor-pointer border-b border-slate-100">
                                        Select Category
                                    </div>

                                    @foreach($categories as $cat)
                                        <div class="flex items-center justify-between px-3 py-2 hover:bg-slate-50 transition cursor-pointer group {{ $cat_id == $cat->category_id ? 'bg-red-50/50 font-semibold text-rmsc-red' : 'text-slate-700 text-xs' }}">
                                            <span @click="$wire.set('cat_id', '{{ $cat->category_id }}'); open = false" class="flex-1 truncate pr-2 py-0.5 text-xs">
                                                {{ $cat->category_name }}
                                            </span>
                                            <button type="button" 
                                                    wire:click.stop="confirmDeleteItem('Category', {{ $cat->category_id }}, '{{ addslashes($cat->category_name) }}')" 
                                                    class="p-1 text-slate-400 hover:text-red-600 hover:bg-red-100/70 rounded-lg transition" 
                                                    title="Delete Category">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </div>
                                    @endforeach

                                    <div @click="$wire.set('cat_id', 'ADD_NEW'); open = false" class="px-3 py-2 text-xs font-semibold text-amber-700 hover:bg-amber-50 cursor-pointer border-t border-slate-100 flex items-center">
                                        <span>+ Add Custom Category...</span>
                                    </div>
                                </div>
                            </div>
                            @error('cat_id') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror

                            @if($isCustomCategory || $cat_id === 'ADD_NEW')
                                <div class="mt-2.5 p-3 bg-amber-50/70 border border-amber-200/90 rounded-xl space-y-1.5">
                                    <label class="block text-[11px] font-bold text-amber-900 uppercase tracking-wider">New Category Name *</label>
                                    <input type="text" wire:model.blur="custom_category_name" class="w-full rounded-lg border-amber-200 bg-white focus:border-rmsc-red focus:ring-rmsc-red text-sm font-semibold text-slate-800" placeholder="e.g. Energy Drinks, Scotch">
                                    @error('custom_category_name') <span class="text-xs text-rmsc-red block font-medium">{{ $message }}</span> @enderror
                                </div>
                            @endif
                        </div>

                        <!-- Brand Dropdown -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Brand</label>
                            <div x-data="{ open: false }" class="relative">
                                <button type="button" @click="open = !open" class="w-full flex items-center justify-between rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-left font-medium text-slate-800 focus:outline-none focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 transition shadow-2xs">
                                    <span class="truncate">
                                        @if($brd_id === 'ADD_NEW')
                                            <span class="text-amber-700 font-semibold">+ Add Custom Brand...</span>
                                        @elseif($brd_id && ($selectedBrd = $brands->firstWhere('brand_id', $brd_id)))
                                            {{ $selectedBrd->brand_name }}
                                        @else
                                            <span class="text-slate-400">Select Brand</span>
                                        @endif
                                    </span>
                                    <svg class="w-4 h-4 text-slate-400 shrink-0 ml-1 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </button>

                                <div x-show="open" @click.outside="open = false" x-transition class="absolute z-50 mt-1 w-full rounded-xl bg-white shadow-xl border border-slate-200 py-1 max-h-48 overflow-y-auto overscroll-contain shadow-2xl [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden" style="display: none;">
                                    <div @click="$wire.set('brd_id', ''); open = false" class="px-3 py-2 text-xs font-medium text-slate-400 hover:bg-slate-50 cursor-pointer border-b border-slate-100">
                                        Select Brand
                                    </div>

                                    @foreach($brands as $b)
                                        <div class="flex items-center justify-between px-3 py-2 hover:bg-slate-50 transition cursor-pointer group {{ $brd_id == $b->brand_id ? 'bg-red-50/50 font-semibold text-rmsc-red' : 'text-slate-700 text-xs' }}">
                                            <span @click="$wire.set('brd_id', '{{ $b->brand_id }}'); open = false" class="flex-1 truncate pr-2 py-0.5 text-xs">
                                                {{ $b->brand_name }}
                                            </span>
                                            <button type="button" 
                                                    wire:click.stop="confirmDeleteItem('Brand', {{ $b->brand_id }}, '{{ addslashes($b->brand_name) }}')" 
                                                    class="p-1 text-slate-400 hover:text-red-600 hover:bg-red-100/70 rounded-lg transition" 
                                                    title="Delete Brand">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </div>
                                    @endforeach

                                    <div @click="$wire.set('brd_id', 'ADD_NEW'); open = false" class="px-3 py-2 text-xs font-semibold text-amber-700 hover:bg-amber-50 cursor-pointer border-t border-slate-100 flex items-center">
                                        <svg class="w-3.5 h-3.5 mr-1.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                        <span>+ Add Custom Brand...</span>
                                    </div>
                                </div>
                            </div>

                            @if($isCustomBrand || $brd_id === 'ADD_NEW')
                                <div class="mt-2.5 p-3 bg-amber-50/70 border border-amber-200/90 rounded-xl space-y-1.5">
                                    <label class="block text-[11px] font-bold text-amber-900 uppercase tracking-wider">New Brand Name *</label>
                                    <input type="text" wire:model.blur="custom_brand_name" class="w-full rounded-lg border-amber-200 bg-white focus:border-rmsc-red focus:ring-rmsc-red text-sm font-semibold text-slate-800" placeholder="e.g. Macallan, Red Bull">
                                    @error('custom_brand_name') <span class="text-xs text-rmsc-red block font-medium">{{ $message }}</span> @enderror
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Base UOM Dropdown -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Base UOM *</label>
                            <div x-data="{ open: false }" class="relative">
                                <button type="button" @click="open = !open" class="w-full flex items-center justify-between rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-left font-medium text-slate-800 focus:outline-none focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 transition shadow-2xs">
                                    <span class="truncate">
                                        @if($base_uom_id === 'ADD_NEW')
                                            <span class="text-amber-700 font-semibold">+ Add Custom UOM...</span>
                                        @elseif($base_uom_id && ($selectedUom = $uoms->firstWhere('uom_id', $base_uom_id)))
                                            {{ $selectedUom->uom_code }} - {{ $selectedUom->uom_name }}
                                        @else
                                            <span class="text-slate-400">Select UOM</span>
                                        @endif
                                    </span>
                                    <svg class="w-4 h-4 text-slate-400 shrink-0 ml-1 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </button>

                                <div x-show="open" @click.outside="open = false" x-transition class="absolute z-50 mt-1 w-full rounded-xl bg-white shadow-xl border border-slate-200 py-1 max-h-48 overflow-y-auto overscroll-contain shadow-2xl [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden" style="display: none;">
                                    <div @click="$wire.set('base_uom_id', ''); open = false" class="px-3 py-2 text-xs font-medium text-slate-400 hover:bg-slate-50 cursor-pointer border-b border-slate-100">
                                        Select UOM
                                    </div>

                                    @foreach($uoms as $u)
                                        <div class="flex items-center justify-between px-3 py-2 hover:bg-slate-50 transition cursor-pointer group {{ $base_uom_id == $u->uom_id ? 'bg-red-50/50 font-semibold text-rmsc-red' : 'text-slate-700 text-xs' }}">
                                            <span @click="$wire.set('base_uom_id', '{{ $u->uom_id }}'); open = false" class="flex-1 truncate pr-2 py-0.5 text-xs">
                                                {{ $u->uom_code }} - {{ $u->uom_name }}
                                            </span>
                                            <button type="button" 
                                                    wire:click.stop="confirmDeleteItem('Base UOM', {{ $u->uom_id }}, '{{ addslashes($u->uom_name) }}')" 
                                                    class="p-1 text-slate-400 hover:text-red-600 hover:bg-red-100/70 rounded-lg transition" 
                                                    title="Delete UOM">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </div>
                                    @endforeach

                                    <div @click="$wire.set('base_uom_id', 'ADD_NEW'); open = false" class="px-3 py-2 text-xs font-semibold text-amber-700 hover:bg-amber-50 cursor-pointer border-t border-slate-100 flex items-center">
                                        <svg class="w-3.5 h-3.5 mr-1.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                        <span>+ Add Custom UOM...</span>
                                    </div>
                                </div>
                            </div>
                            @error('base_uom_id') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror

                            @if($isCustomUom || $base_uom_id === 'ADD_NEW')
                                <div class="mt-2.5 p-3 bg-amber-50/70 border border-amber-200/90 rounded-xl space-y-2">
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-[11px] font-bold text-amber-900 uppercase tracking-wider">UOM Code *</label>
                                            <input type="text" wire:model.blur="custom_uom_code" class="w-full rounded-lg border-amber-200 bg-white focus:border-rmsc-red focus:ring-rmsc-red text-sm uppercase font-mono font-semibold text-slate-800" placeholder="e.g. CAN">
                                            @error('custom_uom_code') <span class="text-xs text-rmsc-red block font-medium">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-bold text-amber-900 uppercase tracking-wider">UOM Name *</label>
                                            <input type="text" wire:model.blur="custom_uom_name" class="w-full rounded-lg border-amber-200 bg-white focus:border-rmsc-red focus:ring-rmsc-red text-sm font-semibold text-slate-800" placeholder="e.g. Aluminium Can">
                                            @error('custom_uom_name') <span class="text-xs text-rmsc-red block font-medium">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- Tax Rate Dropdown -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Tax Rate</label>
                            <div x-data="{ open: false }" class="relative">
                                <button type="button" @click="open = !open" class="w-full flex items-center justify-between rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-left font-medium text-slate-800 focus:outline-none focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 transition shadow-2xs">
                                    <span class="truncate">
                                        @if($tax_rate_id === 'ADD_NEW')
                                            <span class="text-amber-700 font-semibold">+ Add Custom Tax Rate...</span>
                                        @elseif($tax_rate_id && ($selectedTax = $taxes->firstWhere('tax_rate_id', $tax_rate_id)))
                                            {{ $selectedTax->tax_name }} ({{ $selectedTax->rate_percent }}%)
                                        @else
                                            <span class="text-slate-400">Select Tax</span>
                                        @endif
                                    </span>
                                    <svg class="w-4 h-4 text-slate-400 shrink-0 ml-1 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </button>

                                <div x-show="open" @click.outside="open = false" x-transition class="absolute z-50 mt-1 w-full rounded-xl bg-white shadow-xl border border-slate-200 py-1 max-h-48 overflow-y-auto overscroll-contain shadow-2xl [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden" style="display: none;">
                                    <div @click="$wire.set('tax_rate_id', ''); open = false" class="px-3 py-2 text-xs font-medium text-slate-400 hover:bg-slate-50 cursor-pointer border-b border-slate-100">
                                        Select Tax
                                    </div>

                                    @foreach($taxes as $t)
                                        <div class="flex items-center justify-between px-3 py-2 hover:bg-slate-50 transition cursor-pointer group {{ $tax_rate_id == $t->tax_rate_id ? 'bg-red-50/50 font-semibold text-rmsc-red' : 'text-slate-700 text-xs' }}">
                                            <span @click="$wire.set('tax_rate_id', '{{ $t->tax_rate_id }}'); open = false" class="flex-1 truncate pr-2 py-0.5 text-xs">
                                                {{ $t->tax_name }} ({{ $t->rate_percent }}%)
                                            </span>
                                            <button type="button" 
                                                    wire:click.stop="confirmDeleteItem('Tax Rate', {{ $t->tax_rate_id }}, '{{ addslashes($t->tax_name) }}')" 
                                                    class="p-1 text-slate-400 hover:text-red-600 hover:bg-red-100/70 rounded-lg transition" 
                                                    title="Delete Tax Rate">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </div>
                                    @endforeach

                                    <div @click="$wire.set('tax_rate_id', 'ADD_NEW'); open = false" class="px-3 py-2 text-xs font-semibold text-amber-700 hover:bg-amber-50 cursor-pointer border-t border-slate-100 flex items-center">
                                        <svg class="w-3.5 h-3.5 mr-1.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                        <span>+ Add Custom Tax Rate...</span>
                                    </div>
                                </div>
                            </div>

                            @if($isCustomTax || $tax_rate_id === 'ADD_NEW')
                                <div class="mt-2.5 p-3 bg-amber-50/70 border border-amber-200/90 rounded-xl space-y-2">
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-[11px] font-bold text-amber-900 uppercase tracking-wider">Tax Name *</label>
                                            <input type="text" wire:model.blur="custom_tax_name" class="w-full rounded-lg border-amber-200 bg-white focus:border-rmsc-red focus:ring-rmsc-red text-sm font-semibold text-slate-800" placeholder="e.g. Excise Duty">
                                            @error('custom_tax_name') <span class="text-xs text-rmsc-red block font-medium">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-bold text-amber-900 uppercase tracking-wider">Rate (%) *</label>
                                            <input type="number" step="0.01" wire:model.blur="custom_tax_rate" class="w-full rounded-lg border-amber-200 bg-white focus:border-rmsc-red focus:ring-rmsc-red text-sm font-mono font-semibold text-slate-800" placeholder="e.g. 5.00">
                                            @error('custom_tax_rate') <span class="text-xs text-rmsc-red block font-medium">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>


                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Retail Price (OMR)</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-xs font-bold text-slate-400 pointer-events-none">OMR</span>
                                <input type="number" step="0.001" min="0" wire:model.blur="unit_price" class="w-full pl-12 rounded-xl border border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-sm font-mono font-bold text-emerald-700" placeholder="0.000">
                            </div>
                            @error('unit_price') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Reorder Level</label>
                            <input type="number" step="0.01" wire:model.blur="reorder_level" class="w-full rounded-xl border border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-sm font-mono" placeholder="0.00">
                            @error('reorder_level') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">HS Code</label>
                            <input type="text" wire:model.blur="hs_code" class="w-full rounded-xl border border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-sm font-mono" placeholder="e.g. 2208.30">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Pack Size</label>
                            <input type="text" wire:model.blur="pack_size" placeholder="e.g. 12 x 750ml" class="w-full rounded-xl border border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-sm">
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

    <!-- Item Delete Confirmation Modal -->
    <template x-teleport="body">
        <div x-show="$wire.showItemDeleteModal" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
             style="display: none;">
            <div @click.away="$wire.set('showItemDeleteModal', false)" 
                 class="bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden transform transition-all w-full mx-auto"
                 style="max-width: 400px;">
                <div class="p-6 text-center">
                    <div class="w-12 h-12 rounded-full bg-red-100 text-red-600 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 mb-1">Delete {{ $itemToDeleteType }}?</h3>
                    <p class="text-xs text-slate-500 mb-6">Are you sure you want to delete <span class="font-semibold text-slate-800">"{{ $itemToDeleteName }}"</span>? This action cannot be undone.</p>

                    <div class="flex items-center justify-center space-x-3">
                        <button type="button" @click="$wire.set('showItemDeleteModal', false)" class="px-4 py-2 border border-slate-200 text-slate-700 font-semibold text-xs rounded-xl hover:bg-slate-50 transition">Cancel</button>
                        <button type="button" wire:click="executeDeleteItem" class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold text-xs rounded-xl shadow-md transition">Delete {{ $itemToDeleteType }}</button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
</div>

