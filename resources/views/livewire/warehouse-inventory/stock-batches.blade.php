<div x-data="{ openModal: @entangle('showModal'), openView: @entangle('showViewModal') }" x-effect="document.body.style.overflow = (openModal || openView) ? 'hidden' : ''">
    <!-- Page Header Slot -->
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="font-heading font-bold text-2xl text-slate-900 tracking-tight">Batch &amp; Expiry Registry</h1>
                <p class="text-xs text-slate-500 mt-1 font-medium">Track liquor product lot numbers, bottling/manufacture dates, shelf-life expiry alerts, and duty status ledger</p>
            </div>
            <div class="flex items-center space-x-3">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs text-slate-600 font-medium shadow-2xs">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Live as of {{ now()->format('d M Y · H:i') }}</span>
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
            <!-- Stat 1: Total Registered Batches -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-2xs flex flex-col justify-between hover:border-slate-300 transition">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Registered Batches</span>
                    <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="font-heading font-extrabold text-2xl text-slate-900">{{ number_format($metrics['total_batches']) }}</div>
                    <p class="text-[11px] text-slate-500 font-medium mt-1">Lot numbers in database</p>
                </div>
            </div>

            <!-- Stat 2: Active Batches In Stock -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-2xs flex flex-col justify-between hover:border-slate-300 transition">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Active Batches In Stock</span>
                    <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="font-heading font-extrabold text-2xl text-slate-900">{{ number_format($metrics['active_batches']) }}</div>
                    <p class="text-[11px] text-emerald-700 font-medium mt-1">Batches with physical units</p>
                </div>
            </div>

            <!-- Stat 3: Expiring Soon (30 Days) -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-2xs flex flex-col justify-between hover:border-slate-300 transition">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Expiring Soon (30 Days)</span>
                    <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="font-heading font-extrabold text-2xl {{ $metrics['expiring_soon'] > 0 ? 'text-amber-600' : 'text-slate-900' }}">{{ number_format($metrics['expiring_soon']) }}</div>
                    <p class="text-[11px] text-amber-700 font-medium mt-1">Shelf-life alert window</p>
                </div>
            </div>

            <!-- Stat 4: Duty-Free Bonded Batches -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-2xs flex flex-col justify-between hover:border-slate-300 transition">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Duty-Free Bonded Batches</span>
                    <div class="w-8 h-8 rounded-lg bg-sky-50 text-sky-600 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="font-heading font-extrabold text-2xl text-slate-900">{{ number_format($metrics['duty_free_bonded']) }}</div>
                    <p class="text-[11px] text-sky-700 font-medium mt-1">Under customs clearance hold</p>
                </div>
            </div>
        </div>

        <!-- Stock Batch Register Panel -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
            <!-- Panel Header -->
            <div class="px-6 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3 bg-slate-50/50">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 rounded-lg bg-rmsc-red/10 text-rmsc-red flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    </div>
                    <div>
                        <div class="flex items-center space-x-2">
                            <h3 class="font-heading font-bold text-slate-900 text-base">Batch &amp; Expiry Register</h3>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-200 text-slate-700 font-mono">{{ $batches->total() }}</span>
                        </div>
                        <p class="text-xs text-slate-500 font-medium">Manage lot numbers, shelf life dates, and customs duty classifications</p>
                    </div>
                </div>

                <button type="button" wire:click="openCreateModal" @click="openModal = true" style="height: 2.25rem; padding: 0 1.25rem; gap: 0.5rem; white-space: nowrap;" class="bg-rmsc-red hover:bg-rmsc-red-dark text-white font-semibold text-xs rounded-xl shadow-xs transition inline-flex items-center justify-center shrink-0 cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>Register New Batch</span>
                </button>
            </div>

            <div class="p-6" style="display: flex; flex-direction: column; gap: 1.25rem;">
                <!-- Filters Toolbar -->
                <div class="flex flex-wrap items-center gap-2">
                    <!-- Search Input -->
                    <div class="relative" style="width: 20rem;">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 z-10">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search batch #, SKU, product name..." style="padding-left: 2.5rem !important;" class="w-full h-9 !pl-10 pr-8 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white focus:bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-medium text-slate-800 transition">
                        @if($search)
                            <button wire:click="$set('search', '')" type="button" class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600 transition cursor-pointer" title="Clear search">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        @endif
                    </div>

                    <!-- Product Select Filter -->
                    <select wire:model.live="product_id_filter" class="h-9 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white focus:bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-medium text-slate-800 transition pl-3 pr-8 cursor-pointer max-w-[200px]">
                        <option value="">All Products</option>
                        @foreach($products as $p)
                            <option value="{{ $p->product_id }}">{{ $p->product_name }}</option>
                        @endforeach
                    </select>

                    <!-- Duty Status Select -->
                    <select wire:model.live="duty_status_filter" class="h-9 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white focus:bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-medium text-slate-800 transition pl-3 pr-8 cursor-pointer">
                        <option value="">All Duty Statuses</option>
                        <option value="DUTY_FREE_BONDED">Duty Free Bonded</option>
                        <option value="DUTY_PAID">Duty Paid</option>
                    </select>

                    <!-- Expiry Status Select -->
                    <select wire:model.live="expiry_filter" class="h-9 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white focus:bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-medium text-slate-800 transition pl-3 pr-8 cursor-pointer">
                        <option value="">All Expiry Statuses</option>
                        <option value="expiring_soon">Expiring Soon (30 Days)</option>
                        <option value="expired">Expired Batches</option>
                        <option value="valid">Valid / Good Shelf Life</option>
                    </select>

                    <!-- Clear Filters Button -->
                    @if($search !== '' || $duty_status_filter !== '' || $expiry_filter !== '' || $product_id_filter !== '')
                        <button type="button" wire:click="clearFilters" class="h-9 px-2.5 bg-slate-100 hover:bg-slate-200/80 text-slate-600 hover:text-slate-900 text-xs font-medium rounded-xl transition flex items-center space-x-1 cursor-pointer shrink-0" title="Clear all filters">
                            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            <span>Clear</span>
                        </button>
                    @endif
                </div>

                <!-- Batch Table -->
                <div class="border border-slate-200 rounded-xl overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead class="bg-slate-50/80 border-b border-slate-200 text-slate-500 font-semibold">
                                <tr>
                                    <th class="py-2.5 px-4">Batch Number</th>
                                    <th class="py-2.5 px-4">Product / SKU</th>
                                    <th class="py-2.5 px-4">Source GRN</th>
                                    <th class="py-2.5 px-4">Manufacture Date</th>
                                    <th class="py-2.5 px-4">Expiry Date &amp; Status</th>
                                    <th class="py-2.5 px-4">Duty Status</th>
                                    <th class="py-2.5 px-4 text-right">Physical On-Hand</th>
                                    <th class="py-2.5 px-4 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700">
                                @forelse($batches as $b)
                                    @php
                                        $now = \Carbon\Carbon::now();
                                        $isExpired = $b->expiry_date && $b->expiry_date->isPast();
                                        $isExpiringSoon = $b->expiry_date && !$isExpired && $b->expiry_date->diffInDays($now) <= 30;
                                        $totalOnHand = $b->stocks->sum('quantity_on_hand');
                                    @endphp
                                    <tr class="hover:bg-slate-50/80 transition">
                                        <!-- Batch Number -->
                                        <td class="py-3 px-4 whitespace-nowrap">
                                            <span class="font-mono text-rmsc-red font-bold block">{{ $b->batch_number }}</span>
                                            <span class="text-[10px] text-slate-400 font-mono block mt-0.5">ID: #{{ $b->batch_id }}</span>
                                        </td>

                                        <!-- Product / SKU -->
                                        <td class="py-3 px-4 whitespace-nowrap">
                                            <span class="font-bold text-slate-900 block">{{ $b->product?->product_name ?? 'Unassigned' }}</span>
                                            <span class="text-[10px] text-slate-500 font-mono block mt-0.5">SKU: {{ $b->product?->sku_code ?? '-' }}</span>
                                        </td>

                                        <!-- Source GRN -->
                                        <td class="py-3 px-4 whitespace-nowrap">
                                            @if($b->sourceGrn)
                                                <span class="font-mono font-bold text-slate-800 block">{{ $b->sourceGrn->grn_number }}</span>
                                                <span class="text-[10px] text-slate-400 font-mono block mt-0.5">Rec: {{ \Carbon\Carbon::parse($b->sourceGrn->received_date)->format('d M Y') }}</span>
                                            @else
                                                <span class="text-slate-400 font-mono">Manual Entry</span>
                                            @endif
                                        </td>

                                        <!-- Manufacture Date -->
                                        <td class="py-3 px-4 whitespace-nowrap font-mono text-slate-700">
                                            {{ $b->manufacture_date ? $b->manufacture_date->format('d M Y') : 'N/A' }}
                                        </td>

                                        <!-- Expiry Date & Alert -->
                                        <td class="py-3 px-4 whitespace-nowrap">
                                            <div class="font-mono font-bold text-slate-900">
                                                {{ $b->expiry_date ? $b->expiry_date->format('d M Y') : 'No Expiry' }}
                                            </div>
                                            <div class="mt-1">
                                                @if(!$b->expiry_date)
                                                    <span class="text-[10px] text-slate-400 font-medium">Non-Perishable</span>
                                                @elseif($isExpired)
                                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-rose-100 text-rmsc-red">Expired</span>
                                                @elseif($isExpiringSoon)
                                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-amber-100 text-amber-800">Expiring Soon ({{ (int)$now->diffInDays($b->expiry_date) }}d)</span>
                                                @else
                                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-emerald-100 text-emerald-800">Valid</span>
                                                @endif
                                            </div>
                                        </td>

                                        <!-- Duty Status -->
                                        <td class="py-3 px-4 whitespace-nowrap">
                                            @if($b->duty_status === 'DUTY_FREE_BONDED')
                                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-sky-100 text-sky-800">Duty Free Bonded</span>
                                            @else
                                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-emerald-100 text-emerald-800">Duty Paid</span>
                                            @endif
                                        </td>

                                        <!-- Physical On-Hand -->
                                        <td class="py-3 px-4 text-right font-mono font-bold text-slate-900 whitespace-nowrap">
                                            {{ number_format((float)$totalOnHand, 2) }}
                                            <span class="text-[10px] text-slate-400 font-normal ml-0.5">{{ $b->product?->uom?->uom_code ?? '' }}</span>
                                        </td>

                                        <!-- Actions -->
                                        <td class="py-3 px-4 text-center whitespace-nowrap">
                                            <div class="flex items-center justify-center space-x-1">
                                                <!-- View Details Button -->
                                                <button wire:click="openViewModal({{ $b->batch_id }})" class="p-1.5 text-slate-500 hover:text-sky-600 hover:bg-sky-50 rounded-lg transition cursor-pointer" title="View Batch Lineage & Stock Breakdown">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                </button>

                                                <!-- Edit Button -->
                                                <button wire:click="openEditModal({{ $b->batch_id }})" class="p-1.5 text-slate-500 hover:text-rmsc-red hover:bg-red-50 rounded-lg transition cursor-pointer" title="Edit Batch Dates & Duty Status">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="py-10 text-center text-slate-400">
                                            <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-2 text-slate-400">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                            </div>
                                            <p class="text-sm font-medium text-slate-600">No stock batches found</p>
                                            <p class="text-xs text-slate-400 mt-0.5">Try adjusting your search filters or product selections.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                @if($batches->hasPages())
                    <div>
                        {{ $batches->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- CREATE / EDIT BATCH MODAL -->
    <div x-cloak 
         x-show="openModal" 
         x-transition:enter="transition ease-out duration-200" 
         x-transition:enter-start="opacity-0 scale-95" 
         x-transition:enter-end="opacity-100 scale-100" 
         x-transition:leave="transition ease-in duration-150" 
         x-transition:leave-start="opacity-100 scale-100" 
         x-transition:leave-end="opacity-0 scale-95" 
         class="fixed inset-0 z-50 overflow-hidden bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 sm:p-6"
         style="display: none;">
        <div @click.away="openModal = false; $wire.closeModal()" 
             class="bg-white rounded-xl shadow-2xl border border-slate-200 w-full max-w-lg overflow-hidden transform transition-all relative flex flex-col min-h-0"
             style="max-height: calc(100vh - 3rem); max-height: calc(100dvh - 3rem);">
            <!-- Modal Header -->
            <div class="px-6 py-4 bg-slate-50/50 border-b border-slate-100 flex items-center justify-between rounded-t-xl shrink-0">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 rounded-lg bg-rmsc-red/10 text-rmsc-red flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    </div>
                    <div>
                        <h3 class="font-heading font-bold text-slate-900 text-base">
                            {{ $isEdit ? 'Edit Batch Details' : 'Register New Stock Batch' }}
                        </h3>
                        <p class="text-xs text-slate-500 font-medium">Record serial numbers, manufacturing &amp; expiry dates</p>
                    </div>
                </div>
                <button type="button" @click="openModal = false; $wire.closeModal()" class="text-slate-400 hover:text-slate-600 transition cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Modal Form Body -->
            <form wire:submit.prevent="saveBatch" class="flex flex-col flex-1 min-h-0 overflow-hidden">
                <div class="p-6 overflow-y-auto flex-1 min-h-0">
                    <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50" style="display: flex; flex-direction: column; gap: 1rem;">
                        <!-- Batch Serial Number -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Batch Serial Number (Auto-Generated)</label>
                            <input type="text" wire:model="batch_number" readonly placeholder="e.g. BATCH-SKU-HEIN-202609" class="w-full h-9 px-3 text-xs bg-slate-100/80 border border-slate-200 rounded-xl text-slate-700 font-mono font-bold cursor-not-allowed select-none" />
                            @error('batch_number') <span class="text-[11px] text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Associated Product -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Associated Product *</label>
                            <select wire:model="product_id" class="w-full h-9 px-3 text-xs bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-rmsc-red/10 focus:border-rmsc-red text-slate-800 font-medium transition cursor-pointer">
                                <option value="">Select Product...</option>
                                @foreach($products as $p)
                                    <option value="{{ $p->product_id }}">{{ $p->product_name }} (SKU: {{ $p->sku_code }})</option>
                                @endforeach
                            </select>
                            @error('product_id') <span class="text-[11px] text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Date Inputs Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Manufacture Date -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Manufacture Date</label>
                                <input type="date" wire:model="manufacture_date" class="w-full h-9 px-3 text-xs bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-rmsc-red/10 focus:border-rmsc-red text-slate-800 font-mono transition" />
                                @error('manufacture_date') <span class="text-[11px] text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- Expiry Date -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Expiry Date</label>
                                <input type="date" wire:model="expiry_date" class="w-full h-9 px-3 text-xs bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-rmsc-red/10 focus:border-rmsc-red text-slate-800 font-mono transition" />
                                @error('expiry_date') <span class="text-[11px] text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <!-- Duty Status Selector -->
                        <div class="py-1">
                            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2">Duty Status *</label>
                            <div class="grid grid-cols-2 gap-3">
                                <button type="button" 
                                        wire:click="$set('duty_status', 'DUTY_FREE_BONDED')" 
                                        class="py-2.5 px-4 text-xs rounded-xl border flex items-center justify-center space-x-2 transition cursor-pointer {{ $duty_status === 'DUTY_FREE_BONDED' ? 'bg-sky-100 text-sky-900 border-2 border-sky-400 font-extrabold shadow-2xs' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50 font-medium' }}">
                                    @if($duty_status === 'DUTY_FREE_BONDED')
                                        <svg class="w-4 h-4 text-sky-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                    @endif
                                    <span>Duty Free Bonded</span>
                                </button>
                                <button type="button" 
                                        wire:click="$set('duty_status', 'DUTY_PAID')" 
                                        class="py-2.5 px-4 text-xs rounded-xl border flex items-center justify-center space-x-2 transition cursor-pointer {{ $duty_status === 'DUTY_PAID' ? 'bg-emerald-100 text-emerald-900 border-2 border-emerald-400 font-extrabold shadow-2xs' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50 font-medium' }}">
                                    @if($duty_status === 'DUTY_PAID')
                                        <svg class="w-4 h-4 text-emerald-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                    @endif
                                    <span>Duty Paid</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Actions Footer -->
                <div class="px-6 py-4 bg-slate-50/50 border-t border-slate-100 flex items-center justify-end space-x-3 rounded-b-xl shrink-0">
                    <button type="button" @click="openModal = false; $wire.closeModal()" class="px-4 py-2 border border-slate-200 text-slate-700 font-semibold text-xs rounded-xl hover:bg-slate-50 transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2 bg-rmsc-red hover:bg-rmsc-red-dark text-white font-semibold text-xs rounded-xl shadow-md transition cursor-pointer">
                        {{ $isEdit ? 'Update Batch' : 'Register Batch' }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- VIEW BATCH DETAILS MODAL -->
    <div x-cloak 
         x-show="openView" 
         x-transition:enter="transition ease-out duration-200" 
         x-transition:enter-start="opacity-0 scale-95" 
         x-transition:enter-end="opacity-100 scale-100" 
         x-transition:leave="transition ease-in duration-150" 
         x-transition:leave-start="opacity-100 scale-100" 
         x-transition:leave-end="opacity-0 scale-95" 
         class="fixed inset-0 z-50 overflow-hidden bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 sm:p-6"
         style="display: none;">
        <div @click.away="openView = false; $wire.closeViewModal()" 
             class="bg-white rounded-xl shadow-2xl border border-slate-200 w-full max-w-2xl overflow-hidden transform transition-all relative flex flex-col min-h-0"
             style="max-height: calc(100vh - 3rem); max-height: calc(100dvh - 3rem);">
            <!-- Modal Header -->
            <div class="px-6 py-4 bg-slate-50/50 border-b border-slate-100 flex items-center justify-between rounded-t-xl shrink-0">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 rounded-lg bg-rmsc-red/10 text-rmsc-red flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    </div>
                    <div>
                        <h3 class="font-heading font-bold text-slate-900 text-base">Stock Batch Profile &amp; Lineage</h3>
                        <p class="text-xs text-slate-500 font-medium">Batch serial specification and warehouse inventory holds</p>
                    </div>
                </div>
                <button type="button" @click="openView = false; $wire.closeViewModal()" class="text-slate-400 hover:text-slate-600 transition cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Modal Content Body -->
            <div class="p-6 space-y-6 overflow-y-auto flex-1 min-h-0">
                @if($selectedBatchDetails)
                    <!-- Product & Batch Header Overview -->
                    <div class="p-4 bg-slate-50/50 rounded-xl border border-slate-200 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Product Specification</span>
                            <h4 class="font-bold text-slate-900 text-sm mt-1">{{ $selectedBatchDetails->product?->product_name }}</h4>
                            <p class="text-xs text-slate-500 font-mono mt-0.5">SKU: {{ $selectedBatchDetails->product?->sku_code ?? '-' }}</p>
                            <p class="text-xs text-slate-500 font-medium mt-1">Category: {{ $selectedBatchDetails->product?->category?->category_name ?? 'General' }}</p>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Batch Serial #</span>
                            <h4 class="font-mono font-extrabold text-rmsc-red text-sm mt-1">{{ $selectedBatchDetails->batch_number }}</h4>
                            <div class="mt-1.5">
                                @if($selectedBatchDetails->duty_status === 'DUTY_FREE_BONDED')
                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-sky-100 text-sky-800">Duty Free Bonded</span>
                                @else
                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-emerald-100 text-emerald-800">Duty Paid</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Shelf Life & Dates Breakdown -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 divide-y sm:divide-y-0 sm:divide-x divide-slate-200 border border-slate-200 rounded-xl overflow-hidden bg-slate-50/50">
                        <div class="p-3">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Manufacture Date</span>
                            <span class="font-mono font-bold text-slate-900 text-sm mt-1 block">
                                {{ $selectedBatchDetails->manufacture_date ? $selectedBatchDetails->manufacture_date->format('d M Y') : 'N/A' }}
                            </span>
                        </div>
                        <div class="p-3">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Expiry Date</span>
                            <div class="flex items-center justify-between mt-1">
                                <span class="font-mono font-bold text-slate-900 text-sm">
                                    {{ $selectedBatchDetails->expiry_date ? $selectedBatchDetails->expiry_date->format('d M Y') : 'No Expiry' }}
                                </span>
                                @if($selectedBatchDetails->expiry_date)
                                    @php
                                        $now = \Carbon\Carbon::now();
                                        $isExp = $selectedBatchDetails->expiry_date->isPast();
                                        $daysLeft = (int)$now->diffInDays($selectedBatchDetails->expiry_date, false);
                                    @endphp
                                    @if($isExp)
                                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-rose-100 text-rmsc-red">Expired</span>
                                    @elseif($daysLeft <= 30)
                                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-amber-100 text-amber-800">{{ $daysLeft }} days remaining</span>
                                    @else
                                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-emerald-100 text-emerald-800">{{ $daysLeft }} days remaining</span>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Source GRN Voucher Details -->
                    @if($selectedBatchDetails->sourceGrn)
                        <div class="p-4 bg-slate-50/50 rounded-xl border border-slate-200">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-2">Source Intake Voucher</span>
                            <div class="flex items-center justify-between text-xs">
                                <div>
                                    <span class="font-bold font-mono text-slate-900 text-sm block">GRN: {{ $selectedBatchDetails->sourceGrn->grn_number }}</span>
                                    <span class="text-slate-500 font-medium block mt-0.5">Supplier: {{ $selectedBatchDetails->sourceGrn->purchaseOrder?->supplier?->supplier_name ?? 'N/A' }}</span>
                                </div>
                                <div class="text-right">
                                    <span class="text-slate-500 font-medium block">Received: {{ \Carbon\Carbon::parse($selectedBatchDetails->sourceGrn->received_date)->format('d M Y') }}</span>
                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-slate-200 text-slate-700 inline-block mt-1">
                                        {{ $selectedBatchDetails->sourceGrn->has_discrepancy ? 'DISCREPANCY' : 'RECEIVED' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Warehouse Stock Allocation Breakdown -->
                    <div>
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-3">Warehouse Physical Stock Distribution</span>
                        <div class="border border-slate-200 rounded-xl overflow-hidden">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead class="bg-slate-50/80 border-b border-slate-200 text-slate-500 font-semibold">
                                    <tr>
                                        <th class="py-2.5 px-4">Warehouse</th>
                                        <th class="py-2.5 px-4 text-right">On-Hand</th>
                                        <th class="py-2.5 px-4 text-right">Reserved</th>
                                        <th class="py-2.5 px-4 text-right">Available</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white text-slate-700">
                                    @forelse($selectedBatchDetails->stocks as $st)
                                        <tr class="hover:bg-slate-50/80 transition">
                                            <td class="py-2.5 px-4">
                                                <div class="font-semibold text-slate-800">{{ $st->warehouse?->warehouse_name }}</div>
                                                <span class="text-[10px] text-slate-400 font-mono block mt-0.5">{{ $st->warehouse?->warehouse_code }}</span>
                                            </td>
                                            <td class="py-2.5 px-4 text-right font-mono font-bold text-slate-900">{{ number_format((float)$st->quantity_on_hand, 2) }}</td>
                                            <td class="py-2.5 px-4 text-right font-mono font-bold text-amber-600">{{ number_format((float)$st->quantity_reserved, 2) }}</td>
                                            <td class="py-2.5 px-4 text-right font-mono font-bold text-emerald-600">{{ number_format((float)$st->quantity_available, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="py-4 text-center text-slate-400 font-medium">No active warehouse stock allocations for this batch.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-3 bg-slate-50/50 border-t border-slate-100 flex justify-end shrink-0 rounded-b-xl">
                <button type="button" @click="openView = false; $wire.closeViewModal()" class="px-4 py-1.5 bg-slate-800 hover:bg-slate-900 text-white text-xs font-semibold rounded-xl transition cursor-pointer">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>