<div x-data="{ openView: @entangle('showViewModal'), openAction: @entangle('showActionModal') }" x-effect="document.body.style.overflow = (openView || openAction) ? 'hidden' : ''">
    <!-- Page Header Slot -->
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="font-heading font-bold text-2xl text-slate-900 tracking-tight">Stock On Hand &amp; Ledger</h1>
                <p class="text-xs text-slate-500 mt-1 font-medium">Real-time warehouse physical stock levels, batch serial tracking, reservation holds, and duty status ledger</p>
            </div>
            <div class="flex items-center space-x-3">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs text-slate-600 font-medium shadow-2xs">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Live Sync as of {{ now()->format('d M Y · H:i') }}</span>
                </div>
                <button wire:click="$refresh" class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold rounded-lg transition flex items-center space-x-1.5 border border-slate-200 shadow-2xs cursor-pointer">
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    <span>Refresh</span>
                </button>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Top KPI Row (4 Stat Cards) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Stat 1: Total On-Hand Stock -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-2xs flex flex-col justify-between hover:border-slate-300 transition">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total On-Hand Units</span>
                    <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="font-heading font-extrabold text-2xl text-slate-900">{{ number_format($metrics['total_on_hand'], 2) }}</div>
                    <p class="text-[11px] text-slate-500 font-medium mt-1">Physical units across all warehouses</p>
                </div>
            </div>

            <!-- Stat 2: Available Units -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-2xs flex flex-col justify-between hover:border-slate-300 transition">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Available For Allocation</span>
                    <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="font-heading font-extrabold text-2xl text-slate-900">{{ number_format($metrics['total_available'], 2) }}</div>
                    <p class="text-[11px] text-emerald-700 font-medium mt-1">Unreserved &amp; ready for dispatch</p>
                </div>
            </div>

            <!-- Stat 3: Reserved Units -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-2xs flex flex-col justify-between hover:border-slate-300 transition">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Reserved / On Hold</span>
                    <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="font-heading font-extrabold text-2xl text-slate-900">{{ number_format($metrics['total_reserved'], 2) }}</div>
                    <p class="text-[11px] text-amber-700 font-medium mt-1">Allocated to pending sales/transfers</p>
                </div>
            </div>

            <!-- Stat 4: Duty Free Bonded Units -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-2xs flex flex-col justify-between hover:border-slate-300 transition">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Duty-Free Bonded Stock</span>
                    <div class="w-8 h-8 rounded-lg bg-sky-50 text-sky-600 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="font-heading font-extrabold text-2xl text-slate-900">{{ number_format($metrics['duty_free_bonded'], 2) }}</div>
                    <p class="text-[11px] text-sky-700 font-medium mt-1">Units under customs clearance hold</p>
                </div>
            </div>
        </div>

        <!-- Stock Ledger Register Panel -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
            <!-- Panel Header -->
            <div class="px-6 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3 bg-slate-50/50">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 rounded-lg bg-rmsc-red/10 text-rmsc-red flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </div>
                    <div>
                        <div class="flex items-center space-x-2">
                            <h3 class="font-heading font-bold text-slate-900 text-base">Stock Ledger Register</h3>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-200 text-slate-700 font-mono">{{ $stocks->total() }}</span>
                        </div>
                        <p class="text-xs text-slate-500 font-medium">Live stock levels by warehouse, batch, duty status and reservation holds</p>
                    </div>
                </div>
            </div>

            <div class="p-6" style="display: flex; flex-direction: column; gap: 1.25rem;">
                <!-- Filters Toolbar -->
                <div class="flex flex-wrap items-center gap-2">
                    <!-- Search Bar -->
                    <div class="relative" style="width: 20rem;">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 z-10">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search product, SKU, batch, warehouse..." style="padding-left: 2.5rem !important;" class="w-full h-9 !pl-10 pr-8 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white focus:bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-medium text-slate-800 transition">
                        @if($search)
                            <button wire:click="$set('search', '')" type="button" class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600 transition cursor-pointer" title="Clear search">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        @endif
                    </div>

                    <!-- Warehouse Select -->
                    <select wire:model.live="warehouse_id" class="h-9 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white focus:bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-medium text-slate-800 transition pl-3 pr-8 cursor-pointer max-w-[200px]">
                        <option value="">All Warehouses</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->warehouse_id }}">{{ $wh->warehouse_name }} ({{ $wh->warehouse_code }})</option>
                        @endforeach
                    </select>

                    <!-- Duty Status Select -->
                    <select wire:model.live="duty_status_filter" class="h-9 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white focus:bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-medium text-slate-800 transition pl-3 pr-8 cursor-pointer">
                        <option value="">All Duty Statuses</option>
                        <option value="DUTY_FREE_BONDED">Duty Free Bonded</option>
                        <option value="DUTY_PAID">Duty Paid</option>
                    </select>

                    <!-- Stock Filter Select -->
                    <select wire:model.live="stock_filter" class="h-9 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white focus:bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-medium text-slate-800 transition pl-3 pr-8 cursor-pointer">
                        <option value="">All Stock Levels</option>
                        <option value="available">In Stock (Available &gt; 0)</option>
                        <option value="reserved">Has Reserved Holds</option>
                        <option value="zero">Out of Stock / Zero Qty</option>
                    </select>

                    <!-- Clear Filters Button -->
                    @if($search !== '' || $warehouse_id !== '' || $duty_status_filter !== '' || $stock_filter !== '')
                        <button type="button" wire:click="clearFilters" class="h-9 px-2.5 bg-slate-100 hover:bg-slate-200/80 text-slate-600 hover:text-slate-900 text-xs font-medium rounded-xl transition flex items-center space-x-1 cursor-pointer shrink-0" title="Clear all filters">
                            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            <span>Clear</span>
                        </button>
                    @endif
                </div>

                <!-- Stock Table -->
                <div class="border border-slate-200 rounded-xl overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead class="bg-slate-50/80 border-b border-slate-200 text-slate-500 font-semibold">
                                <tr>
                                    <th class="py-2.5 px-4">Warehouse</th>
                                    <th class="py-2.5 px-4">Product / SKU</th>
                                    <th class="py-2.5 px-4">Batch Number</th>
                                    <th class="py-2.5 px-4">Duty Status</th>
                                    <th class="py-2.5 px-4 text-right">On-Hand Qty</th>
                                    <th class="py-2.5 px-4 text-right">Reserved Qty</th>
                                    <th class="py-2.5 px-4 text-right">Available Qty</th>
                                    <th class="py-2.5 px-4 text-right">Valuation (OMR)</th>
                                    <th class="py-2.5 px-4 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700">
                                @forelse($stocks as $stock)
                                    <tr class="hover:bg-slate-50/80 transition">
                                        <!-- Warehouse -->
                                        <td class="py-3 px-4 whitespace-nowrap">
                                            <div class="font-semibold text-slate-900">{{ $stock->warehouse?->warehouse_name ?? 'N/A' }}</div>
                                            <span class="font-mono text-[10px] font-bold px-2 py-0.5 border border-slate-200 rounded text-slate-700 bg-white inline-block mt-0.5">{{ $stock->warehouse?->warehouse_code ?? '-' }}</span>
                                        </td>

                                        <!-- Product / SKU -->
                                        <td class="py-3 px-4 whitespace-nowrap">
                                            <span class="font-bold text-slate-900 block">{{ $stock->product?->product_name ?? 'Unassigned' }}</span>
                                            <span class="text-[10px] text-slate-500 font-mono block mt-0.5">SKU: {{ $stock->product?->sku_code ?? '-' }}</span>
                                        </td>

                                        <!-- Batch -->
                                        <td class="py-3 px-4 whitespace-nowrap">
                                            <span class="font-mono text-rmsc-red font-bold block">{{ $stock->batch?->batch_number ?? 'No Batch' }}</span>
                                            <span class="text-[10px] text-slate-400 font-mono block mt-0.5">Exp: {{ $stock->batch?->expiry_date ? \Carbon\Carbon::parse($stock->batch->expiry_date)->format('d M Y') : 'N/A' }}</span>
                                        </td>

                                        <!-- Duty Status -->
                                        <td class="py-3 px-4 whitespace-nowrap">
                                            @if(($stock->batch?->duty_status ?? '') === 'DUTY_FREE_BONDED')
                                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-sky-100 text-sky-800">Duty Free Bonded</span>
                                            @else
                                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-emerald-100 text-emerald-800">Duty Paid</span>
                                            @endif
                                        </td>

                                        <!-- On-Hand -->
                                        <td class="py-3 px-4 text-right font-mono font-bold text-slate-900 whitespace-nowrap">
                                            {{ number_format((float)$stock->quantity_on_hand, 2) }}
                                            <span class="text-[10px] text-slate-400 font-normal ml-0.5">{{ $stock->product?->uom?->uom_code ?? '' }}</span>
                                        </td>

                                        <!-- Reserved -->
                                        <td class="py-3 px-4 text-right font-mono font-bold text-amber-600 whitespace-nowrap">
                                            {{ number_format((float)$stock->quantity_reserved, 2) }}
                                        </td>

                                        <!-- Available -->
                                        <td class="py-3 px-4 text-right font-mono font-bold whitespace-nowrap">
                                            @if((float)$stock->quantity_available > 0)
                                                <span class="text-emerald-600">{{ number_format((float)$stock->quantity_available, 2) }}</span>
                                            @else
                                                <span class="text-slate-400">0.00</span>
                                            @endif
                                        </td>

                                        <!-- Valuation -->
                                        <td class="py-3 px-4 text-right font-mono font-bold text-slate-900 whitespace-nowrap">
                                            @php
                                                $unitCost = (float) ($stock->batch?->unit_cost ?? 0);
                                                $valuation = $unitCost * (float) $stock->quantity_on_hand;
                                            @endphp
                                            OMR {{ number_format($valuation, 3) }}
                                        </td>

                                        <!-- Actions -->
                                        <td class="py-3 px-4 text-center whitespace-nowrap">
                                            <div class="flex items-center justify-center space-x-1.5">
                                                <!-- View Details Button -->
                                                <button wire:click="openViewModal({{ $stock->stock_id }})" class="p-1.5 text-slate-500 hover:text-sky-600 hover:bg-sky-50 rounded-lg transition cursor-pointer" title="View Stock Ledger & Batch Details">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                </button>

                                                <!-- Reserve Button -->
                                                <button wire:click="openActionModal({{ $stock->stock_id }}, 'reserve')" class="px-2.5 py-1 text-[10px] font-bold bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 rounded-lg transition cursor-pointer" title="Reserve Stock">
                                                    Reserve
                                                </button>

                                                <!-- Release Button -->
                                                @if((float)$stock->quantity_reserved > 0)
                                                    <button wire:click="openActionModal({{ $stock->stock_id }}, 'release')" class="px-2.5 py-1 text-[10px] font-bold bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 rounded-lg transition cursor-pointer" title="Release Reservation">
                                                        Release
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="py-10 text-center text-slate-400">
                                            <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-2 text-slate-400">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                                            </div>
                                            <p class="text-sm font-medium text-slate-600">No stock ledger records found</p>
                                            <p class="text-xs text-slate-400 mt-0.5">Try adjusting your search filters or warehouse selections.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                @if($stocks->hasPages())
                    <div>
                        {{ $stocks->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- VIEW DETAILS MODAL -->
    <div x-cloak x-show="openView" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div class="bg-white rounded-xl shadow-2xl border border-slate-200 w-full max-w-2xl mx-auto overflow-hidden flex flex-col max-h-[90vh]" style="max-width: 672px; max-height: 90vh;" @click.away="openView = false; $wire.closeViewModal()">
            <!-- Modal Header -->
            <div class="px-6 py-4 bg-slate-50/50 border-b border-slate-100 flex items-center justify-between shrink-0">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 rounded-lg bg-rmsc-red/10 text-rmsc-red flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <div>
                        <h3 class="font-heading font-bold text-slate-900 text-base">Batch &amp; Stock Ledger Details</h3>
                        <p class="text-xs text-slate-500 font-medium">Inventory breakdown and source lineage</p>
                    </div>
                </div>
                <button type="button" @click="openView = false; $wire.closeViewModal()" class="text-slate-400 hover:text-slate-600 transition cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Modal Content Body -->
            <div class="p-6 space-y-6 overflow-y-auto flex-1">
                @if($selectedStockDetails)
                    <!-- Product & Warehouse Overview -->
                    <div class="p-4 bg-slate-50/50 rounded-xl border border-slate-200 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Product Details</span>
                            <h4 class="font-bold text-slate-900 text-sm mt-1">{{ $selectedStockDetails->product?->product_name }}</h4>
                            <p class="text-xs text-slate-500 font-mono mt-0.5">SKU: {{ $selectedStockDetails->product?->sku_code ?? '-' }}</p>
                            <p class="text-xs text-slate-500 font-medium mt-1">Category: {{ $selectedStockDetails->product?->category?->category_name ?? 'General' }}</p>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Warehouse Location</span>
                            <h4 class="font-bold text-slate-900 text-sm mt-1">{{ $selectedStockDetails->warehouse?->warehouse_name }}</h4>
                            <p class="text-xs text-slate-500 font-mono mt-0.5">Code: {{ $selectedStockDetails->warehouse?->warehouse_code }}</p>
                            <p class="text-xs text-slate-500 font-medium mt-1">Address: {{ $selectedStockDetails->warehouse?->location ?? 'Main Depot' }}</p>
                        </div>
                    </div>

                    <!-- Batch & Duty Information -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 divide-x divide-slate-200 border border-slate-200 rounded-xl overflow-hidden bg-slate-50/50">
                        <div class="p-3">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Batch Number</span>
                            <span class="font-mono font-bold text-rmsc-red text-sm mt-1 block">{{ $selectedStockDetails->batch?->batch_number ?? 'N/A' }}</span>
                        </div>

                        <div class="p-3">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Duty Status</span>
                            <div class="mt-1">
                                @if(($selectedStockDetails->batch?->duty_status ?? '') === 'DUTY_FREE_BONDED')
                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-sky-100 text-sky-800">Duty Free Bonded</span>
                                @else
                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-emerald-100 text-emerald-800">Duty Paid</span>
                                @endif
                            </div>
                        </div>

                        <div class="p-3">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Expiry Date</span>
                            <span class="font-mono font-bold text-slate-900 text-sm mt-1 block">
                                {{ $selectedStockDetails->batch?->expiry_date ? \Carbon\Carbon::parse($selectedStockDetails->batch->expiry_date)->format('d M Y') : 'No Expiry' }}
                            </span>
                        </div>
                    </div>

                    <!-- Stock Ledger Quantities -->
                    <div>
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-3">Ledger Stock Quantities</span>
                        <div class="grid grid-cols-3 divide-x divide-slate-200 border border-slate-200 rounded-xl overflow-hidden bg-slate-50/50 text-center">
                            <div class="p-3">
                                <span class="text-[10px] font-bold text-slate-400 uppercase block">On-Hand Qty</span>
                                <span class="font-heading font-extrabold text-slate-900 text-lg mt-1 block">{{ number_format((float)$selectedStockDetails->quantity_on_hand, 2) }}</span>
                            </div>
                            <div class="p-3">
                                <span class="text-[10px] font-bold text-slate-400 uppercase block">Reserved Qty</span>
                                <span class="font-heading font-extrabold text-amber-600 text-lg mt-1 block">{{ number_format((float)$selectedStockDetails->quantity_reserved, 2) }}</span>
                            </div>
                            <div class="p-3">
                                <span class="text-[10px] font-bold text-slate-400 uppercase block">Available Qty</span>
                                <span class="font-heading font-extrabold text-emerald-600 text-lg mt-1 block">{{ number_format((float)$selectedStockDetails->quantity_available, 2) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Valuation Breakdown -->
                    <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Financial Valuation</span>
                            <p class="text-xs text-slate-500 font-medium mt-0.5">Unit Cost: OMR {{ number_format((float)($selectedStockDetails->batch?->unit_cost ?? 0), 3) }}</p>
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Total Batch Value</span>
                            <span class="font-heading font-extrabold text-xl text-rmsc-red">
                                OMR {{ number_format(((float)($selectedStockDetails->batch?->unit_cost ?? 0)) * ((float)$selectedStockDetails->quantity_on_hand), 3) }}
                            </span>
                        </div>
                    </div>

                    <!-- Source GRN Information -->
                    @if($selectedStockDetails->batch?->sourceGrn)
                        <div class="p-4 bg-slate-50/50 border border-slate-200 rounded-xl">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-2">Source Intake Voucher</span>
                            <div class="flex items-center justify-between text-xs">
                                <div>
                                    <span class="font-bold font-mono text-slate-900">GRN: {{ $selectedStockDetails->batch->sourceGrn->grn_number }}</span>
                                    <span class="text-slate-500 font-medium block mt-0.5">Received on: {{ \Carbon\Carbon::parse($selectedStockDetails->batch->sourceGrn->received_date)->format('d M Y') }}</span>
                                </div>
                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-slate-200 text-slate-700">
                                    Status: {{ $selectedStockDetails->batch->sourceGrn->status ?? 'RECEIVED' }}
                                </span>
                            </div>
                        </div>
                    @endif
                @endif
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-3 bg-slate-50/50 border-t border-slate-100 flex justify-end shrink-0">
                <button type="button" @click="openView = false; $wire.closeViewModal()" class="px-4 py-1.5 bg-slate-800 hover:bg-slate-900 text-white text-xs font-semibold rounded-xl transition cursor-pointer">
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- RESERVE / RELEASE ACTION MODAL -->
    <div x-cloak 
         x-show="openAction" 
         x-transition:enter="transition ease-out duration-200" 
         x-transition:enter-start="opacity-0 scale-95" 
         x-transition:enter-end="opacity-100 scale-100" 
         x-transition:leave="transition ease-in duration-150" 
         x-transition:leave-start="opacity-100 scale-100" 
         x-transition:leave-end="opacity-0 scale-95" 
         class="fixed inset-0 z-50 overflow-hidden bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 sm:p-6"
         style="display: none;">
        <div @click.away="openAction = false" 
             class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-lg mx-auto overflow-hidden transform transition-all relative flex flex-col min-h-0"
             style="max-width: 512px; max-height: calc(100vh - 3rem); max-height: calc(100dvh - 3rem);">
            <!-- Modal Header -->
            <div class="px-6 py-4 bg-slate-50/60 border-b border-slate-100 flex items-center justify-between rounded-t-2xl shrink-0">
                <div class="flex items-center space-x-3">
                    <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 border border-amber-100 flex items-center justify-center shrink-0">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    </div>
                    <div>
                        <h3 class="font-heading font-bold text-slate-900 text-base">
                            {{ $action_type === 'reserve' ? 'Reserve Stock Hold' : 'Release Stock Reservation' }}
                        </h3>
                        <p class="text-xs text-slate-500 font-medium">Update warehouse stock availability</p>
                    </div>
                </div>
                <button type="button" @click="openAction = false" class="text-slate-400 hover:text-slate-600 transition cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Modal Form Body -->
            <form wire:submit.prevent="executeStockAction" class="flex flex-col flex-1 min-h-0 overflow-hidden">
                <div class="p-6 space-y-6 overflow-y-auto flex-1 min-h-0">
                    @if($actionStock)
                        <div class="p-4 bg-slate-50/70 border border-slate-200/80 rounded-xl space-y-2.5 text-xs">
                            <div class="flex items-start justify-between gap-3">
                                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider shrink-0">Product:</span>
                                <span class="font-bold text-slate-900 text-right">{{ $actionStock->product?->product_name }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-3 pt-1 border-t border-slate-200/50">
                                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider shrink-0">Warehouse:</span>
                                <span class="font-semibold text-slate-800 text-right">{{ $actionStock->warehouse?->warehouse_name }}</span>
                            </div>
                            <div class="grid grid-cols-2 gap-3 pt-2 border-t border-slate-200/50 text-center">
                                <div class="p-2 bg-white rounded-lg border border-slate-200/60 shadow-2xs">
                                    <span class="text-[10px] font-bold text-slate-400 uppercase block">Available Stock</span>
                                    <span class="font-mono font-bold text-emerald-600 text-sm mt-0.5 block">{{ number_format((float)$actionStock->quantity_available, 2) }}</span>
                                </div>
                                <div class="p-2 bg-white rounded-lg border border-slate-200/60 shadow-2xs">
                                    <span class="text-[10px] font-bold text-slate-400 uppercase block">Reserved Holds</span>
                                    <span class="font-mono font-bold text-amber-600 text-sm mt-0.5 block">{{ number_format((float)$actionStock->quantity_reserved, 2) }}</span>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Action Type Segment Tabs -->
                    <div class="space-y-2">
                        <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider">Action Type</label>
                        <div class="p-1 bg-slate-100/80 rounded-xl grid grid-cols-2 gap-1 border border-slate-200/60">
                            <button type="button" 
                                    wire:click="$set('action_type', 'reserve')" 
                                    class="py-2 px-3 text-xs font-bold rounded-lg transition-all cursor-pointer flex items-center justify-center space-x-1.5 {{ $action_type === 'reserve' ? 'bg-amber-500 text-white shadow-2xs' : 'text-slate-600 hover:text-slate-900' }}">
                                @if($action_type === 'reserve')
                                    <svg class="w-3.5 h-3.5 text-white shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                @endif
                                <span>Reserve Hold</span>
                            </button>
                            <button type="button" 
                                    wire:click="$set('action_type', 'release')" 
                                    class="py-2 px-3 text-xs font-bold rounded-lg transition-all cursor-pointer flex items-center justify-center space-x-1.5 {{ $action_type === 'release' ? 'bg-emerald-600 text-white shadow-2xs' : 'text-slate-600 hover:text-slate-900' }}">
                                @if($action_type === 'release')
                                    <svg class="w-3.5 h-3.5 text-white shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                @endif
                                <span>Release Hold</span>
                            </button>
                        </div>
                    </div>

                    <!-- Quantity Input -->
                    <div class="space-y-1.5">
                        <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider">Quantity</label>
                        <input type="number" 
                               step="0.01" 
                               min="0.01" 
                               wire:model="action_quantity" 
                               class="w-full h-10 px-3.5 text-xs bg-slate-50/60 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-rmsc-red/10 focus:border-rmsc-red text-slate-800 font-mono font-bold transition" />
                        @error('action_quantity') <span class="text-[11px] text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Reason / Reference -->
                    <div class="space-y-1.5">
                        <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider">Reason / Reference (Optional)</label>
                        <input type="text" 
                               wire:model="action_reason" 
                               placeholder="e.g. Sales Order SO-2026-09" 
                               class="w-full h-10 px-3.5 text-xs bg-slate-50/60 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-rmsc-red/10 focus:border-rmsc-red text-slate-800 font-medium transition" />
                    </div>
                </div>

                <!-- Modal Actions Footer -->
                <div class="px-6 py-4 bg-slate-50/60 border-t border-slate-100 flex items-center justify-end space-x-3 rounded-b-2xl shrink-0">
                    <button type="button" @click="openAction = false" class="px-4 py-2 border border-slate-200 text-slate-700 font-semibold text-xs rounded-xl hover:bg-slate-100 transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2 bg-rmsc-red hover:bg-rmsc-red-dark text-white font-semibold text-xs rounded-xl shadow-xs hover:shadow-md transition cursor-pointer">
                        Confirm {{ $action_type === 'reserve' ? 'Reservation' : 'Release' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>