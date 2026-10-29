<div x-data="{ openModal: @entangle('showModal'), openView: @entangle('showViewModal') }" x-effect="document.body.style.overflow = (openModal || openView) ? 'hidden' : ''">
    <!-- Page Header Slot -->
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="font-heading font-bold text-2xl text-slate-900 tracking-tight">Duty &amp; Landed Cost Management</h1>
                <p class="text-xs text-slate-500 mt-1 font-medium">ROP Customs duty assessments, CIF valuations, excise calculations, and port handling allocations</p>
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
            <!-- Stat 1: Registered Assessments -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-2xs flex flex-col justify-between hover:border-slate-300 transition">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Registered Assessments</span>
                    <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="font-heading font-extrabold text-2xl text-slate-900">{{ number_format($metrics['total_assessments']) }}</div>
                    <p class="text-[11px] text-slate-500 font-medium mt-1">Customs duty records</p>
                </div>
            </div>

            <!-- Stat 2: Total CIF Valuation -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-2xs flex flex-col justify-between hover:border-slate-300 transition">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total CIF Valuation</span>
                    <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="font-heading font-extrabold text-xl text-slate-900 truncate" title="OMR {{ number_format($metrics['total_cif'], 3) }}">OMR {{ number_format($metrics['total_cif'], 3) }}</div>
                    <p class="text-[11px] text-emerald-700 font-medium mt-1">Cost, Insurance &amp; Freight</p>
                </div>
            </div>

            <!-- Stat 3: Total Customs Duty -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-2xs flex flex-col justify-between hover:border-slate-300 transition">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Customs Duty</span>
                    <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="font-heading font-extrabold text-xl text-slate-900 truncate" title="OMR {{ number_format($metrics['total_duty'], 3) }}">OMR {{ number_format($metrics['total_duty'], 3) }}</div>
                    <p class="text-[11px] text-amber-700 font-medium mt-1">ROP duty collected</p>
                </div>
            </div>

            <!-- Stat 4: Total Landed Costs -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-2xs flex flex-col justify-between hover:border-slate-300 transition">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Landed Costs</span>
                    <div class="w-8 h-8 rounded-lg bg-rose-50 text-rmsc-red flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="font-heading font-extrabold text-xl text-rmsc-red truncate" title="OMR {{ number_format($metrics['total_landed'], 3) }}">OMR {{ number_format($metrics['total_landed'], 3) }}</div>
                    <p class="text-[11px] text-slate-500 font-medium mt-1">CIF + Duty + Port charges</p>
                </div>
            </div>
        </div>

        <!-- Duty Assessment Register Panel -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
            <!-- Panel Header -->
            <div class="px-6 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3 bg-slate-50/50">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    </div>
                    <div>
                        <div class="flex items-center space-x-2">
                            <h3 class="font-heading font-bold text-slate-900 text-base">Duty Assessment Register</h3>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-200 text-slate-700 font-mono">{{ $assessments->total() }}</span>
                        </div>
                        <p class="text-xs text-slate-500 font-medium">CIF valuations, customs duty rates, port charges and total landed cost per shipment</p>
                    </div>
                </div>

                <button type="button" wire:click="openCreateModal" @click="openModal = true" style="height: 2.25rem; padding: 0 1.25rem; gap: 0.5rem; white-space: nowrap;" class="bg-rmsc-red hover:bg-rmsc-red-dark text-white font-semibold text-xs rounded-xl shadow-xs transition inline-flex items-center justify-center shrink-0 cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>New Duty Assessment</span>
                </button>
            </div>

            <div class="p-6" style="display: flex; flex-direction: column; gap: 1.25rem;">
                <!-- Filters Toolbar -->
                <div class="flex flex-wrap items-center gap-2">
                    <!-- Search Bar -->
                    <div class="relative" style="width: 18rem;">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 z-10">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search Ref, Bayan No, PO #..." style="padding-left: 2.5rem !important;" class="w-full h-9 !pl-10 pr-8 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white focus:bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-medium text-slate-800 transition">
                        @if($search)
                            <button wire:click="$set('search', '')" type="button" class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600 transition cursor-pointer" title="Clear search">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        @endif
                    </div>

                    <!-- Date Range Filters -->
                    <div class="flex items-center gap-1.5">
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">From</span>
                        <input type="date" wire:model.live="date_from" style="width: 8.5rem;" class="h-9 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white focus:bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-medium text-slate-800 transition px-2">
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider" style="margin-left: 0.25rem;">To</span>
                        <input type="date" wire:model.live="date_to" style="width: 8.5rem;" class="h-9 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white focus:bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-medium text-slate-800 transition px-2">
                    </div>

                    <!-- Clear Filters Button -->
                    @if($search || $date_from || $date_to)
                        <button type="button" wire:click="clearFilters" class="h-9 px-2.5 bg-slate-100 hover:bg-slate-200/80 text-slate-600 hover:text-slate-900 text-xs font-medium rounded-xl transition flex items-center space-x-1 cursor-pointer shrink-0" title="Clear all filters">
                            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            <span>Clear</span>
                        </button>
                    @endif
                </div>

                <!-- Data Table -->
                <div class="border border-slate-200 rounded-xl overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead class="bg-slate-50/80 border-b border-slate-200 text-slate-500 font-semibold">
                                <tr>
                                    <th class="py-2.5 px-4">Assessment ID</th>
                                    <th class="py-2.5 px-4">Shipment Ref &amp; Bayan</th>
                                    <th class="py-2.5 px-4 text-right">CIF Value (OMR)</th>
                                    <th class="py-2.5 px-4 text-center">Duty Rate</th>
                                    <th class="py-2.5 px-4 text-right">Customs Duty</th>
                                    <th class="py-2.5 px-4 text-right">Other Charges</th>
                                    <th class="py-2.5 px-4 text-right">Total Landed Cost</th>
                                    <th class="py-2.5 px-4">Assessed Date</th>
                                    <th class="py-2.5 px-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700">
                                @forelse($assessments as $a)
                                    <tr class="hover:bg-slate-50/80 transition">
                                        <!-- Assessment ID -->
                                        <td class="py-3 px-4 font-mono font-bold text-rmsc-red" style="white-space: nowrap;">
                                            #ASSESS-{{ str_pad((string)$a->assessment_id, 4, '0', STR_PAD_LEFT) }}
                                        </td>

                                        <!-- Shipment Ref & Bayan -->
                                        <td class="py-3 px-4">
                                            <div class="font-bold text-indigo-700 font-mono">{{ $a->shipment?->shipment_ref }}</div>
                                            <div class="text-[11px] text-slate-500 font-medium mt-0.5">
                                                {{ $a->shipment?->bayan_declaration_no ? 'Bayan: ' . $a->shipment->bayan_declaration_no : ($a->shipment?->purchaseOrder?->supplier?->supplier_name ?? 'Import Shipment') }}
                                            </div>
                                        </td>

                                        <!-- CIF Value -->
                                        <td class="py-3 px-4 text-right font-mono font-medium text-slate-800">
                                            {{ number_format($a->cif_value, 3) }}
                                        </td>

                                        <!-- Duty Rate -->
                                        <td class="py-3 px-4 text-center">
                                            <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-amber-100 text-amber-800 font-mono">
                                                {{ number_format($a->duty_rate_percent, 1) }}%
                                            </span>
                                        </td>

                                        <!-- Duty Amount -->
                                        <td class="py-3 px-4 text-right font-mono font-bold text-slate-900">
                                            {{ number_format($a->duty_amount, 3) }}
                                        </td>

                                        <!-- Other Charges -->
                                        <td class="py-3 px-4 text-right font-mono text-slate-500">
                                            {{ number_format($a->other_charges, 3) }}
                                        </td>

                                        <!-- Total Landed Cost -->
                                        <td class="py-3 px-4 text-right font-mono font-bold text-emerald-600" style="white-space: nowrap;">
                                            OMR {{ number_format($a->total_landed_cost, 3) }}
                                        </td>

                                        <!-- Assessed Date & Officer -->
                                        <td class="py-3 px-4 font-mono" style="white-space: nowrap;">
                                            <div class="font-semibold text-slate-800">{{ $a->assessed_date ? $a->assessed_date->format('d M Y') : '-' }}</div>
                                            <div class="text-[10px] text-slate-400 mt-0.5">{{ $a->assessor?->full_name ?? $a->assessor?->name ?? 'Customs Admin' }}</div>
                                        </td>

                                        <!-- Actions -->
                                        <td class="py-3 px-4 text-right">
                                            <div class="flex items-center justify-end space-x-1">
                                                <!-- View Details -->
                                                <button type="button" wire:click="openViewModal({{ $a->assessment_id }})" @click="openView = true" class="p-1.5 text-slate-500 hover:text-sky-600 hover:bg-sky-50 rounded-lg transition cursor-pointer" title="View Assessment Breakdown">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                </button>

                                                <!-- Edit Assessment -->
                                                <button type="button" wire:click="openEditModal({{ $a->assessment_id }})" @click="openModal = true" class="p-1.5 text-slate-500 hover:text-rmsc-red hover:bg-red-50 rounded-lg transition cursor-pointer" title="Edit Assessment">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                </button>

                                                <!-- Delete Assessment -->
                                                <button type="button" wire:click="confirmDelete({{ $a->assessment_id }})" class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition cursor-pointer" title="Delete Record">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="py-10 text-center text-slate-400">
                                            <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-2 text-slate-400">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            </div>
                                            <p class="text-sm font-medium text-slate-600">No duty assessments recorded</p>
                                            <p class="text-xs text-slate-400 mt-0.5">Click "New Duty Assessment" to record customs duty and landed cost components.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                <div>
                    {{ $assessments->links() }}
                </div>
            </div>
        </div>
    </div>

    <!-- Create / Edit Duty Assessment Modal -->
    <template x-teleport="body">
        <div x-show="openModal" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="fixed inset-0 z-50 overflow-hidden bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 sm:p-6"
             style="display: none;">
            <div @click.away="if (!$wire.showItemDeleteModal && !$wire.showViewModal) { openModal = false; $wire.set('showModal', false); }" 
                 class="bg-white rounded-xl max-w-2xl w-full mx-auto shadow-2xl border border-slate-200 overflow-hidden transform transition-all relative flex flex-col min-h-0"
                 style="max-width: 672px; max-height: calc(100vh - 3rem); max-height: calc(100dvh - 3rem);">
                
                <!-- Modal Header -->
                <div class="px-6 py-4 bg-slate-50/50 border-b border-slate-100 flex items-center justify-between rounded-t-xl shrink-0">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 rounded-lg bg-rmsc-red/10 text-rmsc-red flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        </div>
                        <div>
                            <h3 class="font-heading font-bold text-slate-900 text-base">{{ $isEdit ? 'Edit Customs Duty Assessment' : 'Register New Duty Assessment' }}</h3>
                            <p class="text-xs text-slate-500 font-medium">Record ROP duty, CIF valuation, and port handling landed costs</p>
                        </div>
                    </div>
                    <button type="button" @click="openModal = false; $wire.set('showModal', false)" class="text-slate-400 hover:text-slate-600 transition cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Form Content -->
                <form wire:submit.prevent="saveAssessment" class="flex flex-col flex-1 min-h-0 overflow-hidden">
                    <div class="p-6 overflow-y-auto overscroll-contain space-y-6 flex-1 min-h-0">
                        @if(session()->has('error'))
                            <div class="p-3 bg-red-50 border border-red-200 rounded-xl text-xs font-semibold text-rmsc-red flex items-center justify-between">
                                <span>{{ session('error') }}</span>
                            </div>
                        @endif

                        <!-- Section 1: Associated Import Shipment -->
                        <div>
                            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-3">Import Shipment &amp; Customs Declaration</span>
                            <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50" style="display: flex; flex-direction: column; gap: 1rem;">
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Select Import Shipment *</label>
                                    <select wire:model.live="shipment_id" class="w-full h-9 rounded-xl border border-slate-200 bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-semibold text-slate-800">
                                        <option value="">-- Choose Import Shipment --</option>
                                        @foreach($shipments as $s)
                                            <option value="{{ $s->shipment_id }}">
                                                {{ $s->shipment_ref }} — {{ $s->purchaseOrder?->supplier?->supplier_name }} ({{ $s->bayan_declaration_no ?: $s->port_of_entry }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('shipment_id') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                                </div>

                                <!-- Selected Shipment Info Banner -->
                                @if($selected_shipment_info)
                                    <div class="p-3 bg-indigo-50/80 border border-indigo-200 rounded-xl text-xs text-indigo-900 flex items-center space-x-2">
                                        <svg class="w-4 h-4 text-indigo-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <span class="font-medium">{{ $selected_shipment_info }}</span>
                                    </div>
                                @endif

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Assessment Date *</label>
                                        <input type="date" wire:model="assessed_date" class="w-full h-9 rounded-xl border border-slate-200 bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-medium text-slate-800">
                                        @error('assessed_date') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Assessor / Customs Officer *</label>
                                        <select wire:model="assessed_by" class="w-full h-9 rounded-xl border border-slate-200 bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-semibold text-slate-800">
                                            @foreach($users as $u)
                                                <option value="{{ $u->user_id }}">{{ $u->full_name ?? $u->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('assessed_by') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section 2: CIF Valuation & Duty Calculation -->
                        <div>
                            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-3">CIF Valuation &amp; ROP Duty Rate</span>
                            <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50">
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">CIF Value (OMR) *</label>
                                        <input type="number" step="0.001" min="0" wire:model.live.debounce.300ms="cif_value" class="w-full h-9 text-right font-mono font-bold text-slate-900 rounded-xl border border-slate-200 bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs" placeholder="0.000">
                                        <p class="text-[10px] text-slate-400 mt-1">Cost, Insurance &amp; Freight Value</p>
                                        @error('cif_value') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Duty Rate (%) *</label>
                                        <input type="number" step="0.1" min="0" max="1000" wire:model.live.debounce.300ms="duty_rate_percent" class="w-full h-9 text-center font-mono font-bold text-amber-800 bg-amber-50/60 rounded-xl border border-amber-200 focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs" placeholder="100.0">
                                        <p class="text-[10px] text-slate-400 mt-1">100.0% Excise / 5.0% Standard</p>
                                        @error('duty_rate_percent') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Calculated Duty (OMR)</label>
                                        <input type="text" wire:model="duty_amount" readonly class="w-full h-9 text-right font-mono font-extrabold text-slate-900 bg-slate-100 rounded-xl border border-slate-200 text-xs cursor-not-allowed shadow-2xs">
                                        <p class="text-[10px] text-slate-400 mt-1">CIF × Duty Rate %</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section 3: Landed Cost Breakdown Components -->
                        <div>
                            <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Landed Cost Components &amp; Port Fees</span>
                                <div class="flex items-center gap-1.5">
                                    <button type="button" wire:click="addCostComponent('Bayan Port Handling & Demurrage')" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] font-semibold rounded-lg transition cursor-pointer">
                                        + Port Fee
                                    </button>
                                    <button type="button" wire:click="addCostComponent('Customs Clearance & Brokerage Fee')" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] font-semibold rounded-lg transition cursor-pointer">
                                        + Clearance
                                    </button>
                                    <button type="button" wire:click="addCostComponent()" class="px-2.5 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-[10px] font-bold rounded-lg transition cursor-pointer">
                                        + Add Row
                                    </button>
                                </div>
                            </div>

                            @if(count($cost_components) > 0)
                                <div class="border border-slate-200 rounded-xl overflow-x-auto">
                                    <table class="w-full text-left border-collapse text-xs min-w-[500px]">
                                        <thead class="bg-slate-50/80 border-b border-slate-200 text-slate-500 font-semibold">
                                            <tr>
                                                <th class="py-2.5 px-4 min-w-[240px]">Charge Type / Description *</th>
                                                <th class="py-2.5 px-4 text-right w-36">Amount (OMR) *</th>
                                                <th class="py-2.5 px-2 text-center w-12"></th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 bg-white text-slate-700">
                                            @foreach($cost_components as $index => $comp)
                                                <tr class="hover:bg-slate-50/80 transition">
                                                    <td class="py-2 px-4">
                                                        <input type="text" wire:model="cost_components.{{ $index }}.charge_type" list="charge-types-list" class="w-full font-medium text-xs rounded-xl border border-slate-200 focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 py-1.5" placeholder="e.g. Bayan Port Handling & Demurrage">
                                                        @error("cost_components.{$index}.charge_type") <span class="text-[10px] text-rmsc-red block mt-0.5">{{ $message }}</span> @enderror
                                                    </td>

                                                    <td class="py-2 px-4 text-right">
                                                        <input type="number" step="0.001" min="0" wire:model.live.debounce.300ms="cost_components.{{ $index }}.amount" class="w-full text-right font-mono font-bold text-slate-900 rounded-xl border border-slate-200 focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs py-1.5" placeholder="0.000">
                                                        @error("cost_components.{$index}.amount") <span class="text-[10px] text-rmsc-red block mt-0.5">{{ $message }}</span> @enderror
                                                    </td>

                                                    <td class="py-2 px-2 text-center">
                                                        <button type="button" wire:click="removeCostComponent({{ $index }})" class="p-1 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition cursor-pointer" title="Remove Row">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <datalist id="charge-types-list">
                                    @foreach($standardChargeTypes as $preset)
                                        <option value="{{ $preset }}">
                                    @endforeach
                                </datalist>
                            @else
                                <div class="p-4 bg-slate-50/60 border border-dashed border-slate-200 rounded-xl text-center text-xs text-slate-500 font-medium">
                                    No additional landed cost components added yet. Click "+ Add Row" above.
                                </div>
                            @endif
                        </div>

                        <!-- Live Total Landed Cost Summary Card -->
                        <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 flex items-center justify-between">
                            <div>
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Calculated Total Landed Cost</span>
                                <span class="text-[11px] text-slate-500 font-medium block mt-0.5">CIF Valuation + ROP Duty Amount + Other Port Charges</span>
                            </div>
                            <div class="text-right">
                                <span class="font-heading font-extrabold text-xl text-rmsc-red block">OMR {{ number_format((float)$total_landed_cost, 3) }}</span>
                                <span class="text-[10px] text-slate-400 font-mono block mt-0.5">Duty: OMR {{ number_format((float)$duty_amount, 3) }} • Other: OMR {{ number_format((float)$other_charges, 3) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Actions (Sticky Footer) -->
                    <div class="px-6 py-4 bg-slate-50/50 border-t border-slate-100 flex justify-end space-x-3 rounded-b-xl shrink-0">
                        <button type="button" @click="openModal = false; $wire.set('showModal', false)" class="px-4 py-2 border border-slate-200 text-slate-700 font-semibold text-xs rounded-xl hover:bg-slate-50 transition cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2 bg-rmsc-red hover:bg-rmsc-red-dark text-white font-semibold text-xs rounded-xl shadow-md transition cursor-pointer">
                            {{ $isEdit ? 'Save Changes' : 'Save Duty Assessment' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <!-- View Duty Assessment Details Modal -->
    <template x-teleport="body">
        <div x-show="openView" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="fixed inset-0 z-50 overflow-hidden bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 sm:p-6"
             style="display: none;">
            <div @click.away="openView = false; $wire.closeViewModal()" 
                 class="bg-white rounded-xl max-w-2xl w-full mx-auto shadow-2xl border border-slate-200 overflow-hidden transform transition-all relative flex flex-col min-h-0"
                 style="max-width: 672px; max-height: calc(100vh - 3rem); max-height: calc(100dvh - 3rem);">
                
                @if($selectedAssessmentDetails)
                    <!-- Details Header -->
                    <div class="px-6 py-4 bg-slate-50/50 border-b border-slate-100 flex items-center justify-between shrink-0">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            </div>
                            <div class="flex items-center space-x-2">
                                <h3 class="font-heading font-bold text-slate-900 text-base">Duty Assessment Details</h3>
                                <span class="font-mono text-[10px] font-bold px-2 py-0.5 rounded border border-slate-200 bg-white text-rmsc-red">#ASSESS-{{ str_pad((string)$selectedAssessmentDetails->assessment_id, 4, '0', STR_PAD_LEFT) }}</span>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button type="button" onclick="window.print()" class="p-2 text-slate-500 hover:text-slate-800 hover:bg-slate-200/60 rounded-xl transition cursor-pointer" title="Print Assessment Voucher">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                            </button>
                            <button type="button" @click="openView = false; $wire.closeViewModal()" class="text-slate-400 hover:text-slate-600 transition cursor-pointer">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>

                    <!-- Details Body (Scrollable) -->
                    <div class="p-6 overflow-y-auto overscroll-contain space-y-6 flex-1 min-h-0">
                        <!-- Key Cost Indicators Grid -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 divide-x divide-slate-200 border border-slate-200 rounded-xl overflow-hidden bg-slate-50/50">
                            <div class="p-3">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">CIF Valuation</span>
                                <span class="font-mono font-bold text-slate-900 text-xs mt-1 block">OMR {{ number_format($selectedAssessmentDetails->cif_value, 3) }}</span>
                            </div>
                            <div class="p-3">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Customs Duty</span>
                                <span class="font-mono font-bold text-amber-700 text-xs mt-1 block">OMR {{ number_format($selectedAssessmentDetails->duty_amount, 3) }}</span>
                                <span class="text-[10px] text-amber-800 font-bold font-mono">({{ number_format($selectedAssessmentDetails->duty_rate_percent, 1) }}%)</span>
                            </div>
                            <div class="p-3">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Other Charges</span>
                                <span class="font-mono font-bold text-slate-700 text-xs mt-1 block">OMR {{ number_format($selectedAssessmentDetails->other_charges, 3) }}</span>
                            </div>
                            <div class="p-3">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Total Landed Cost</span>
                                <span class="font-mono font-extrabold text-rmsc-red text-xs mt-1 block">OMR {{ number_format($selectedAssessmentDetails->total_landed_cost, 3) }}</span>
                            </div>
                        </div>

                        <!-- Import Shipment & Customs Declaration -->
                        <div>
                            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-3">Linked Import Shipment Details</span>
                            <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50">
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                                    <div>
                                        <span class="text-[10px] text-slate-400 font-bold block uppercase">Shipment Reference</span>
                                        <span class="font-mono font-bold text-indigo-700">{{ $selectedAssessmentDetails->shipment?->shipment_ref }}</span>
                                    </div>
                                    <div>
                                        <span class="text-[10px] text-slate-400 font-bold block uppercase">Bayan Declaration No</span>
                                        <span class="font-mono font-bold text-slate-800">{{ $selectedAssessmentDetails->shipment?->bayan_declaration_no ?: 'N/A' }}</span>
                                    </div>
                                    <div>
                                        <span class="text-[10px] text-slate-400 font-bold block uppercase">Port of Entry</span>
                                        <span class="font-medium text-slate-700">{{ $selectedAssessmentDetails->shipment?->port_of_entry }}</span>
                                    </div>
                                    <div>
                                        <span class="text-[10px] text-slate-400 font-bold block uppercase">Purchase Order</span>
                                        <span class="font-mono font-bold text-slate-800">{{ $selectedAssessmentDetails->shipment?->purchaseOrder?->po_number }}</span>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <span class="text-[10px] text-slate-400 font-bold block uppercase">Supplier &amp; Warehouse</span>
                                        <span class="font-medium text-slate-700">{{ $selectedAssessmentDetails->shipment?->purchaseOrder?->supplier?->supplier_name }} → {{ $selectedAssessmentDetails->shipment?->purchaseOrder?->warehouse?->warehouse_name }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Landed Cost Component Breakdown Table -->
                        <div>
                            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-3">Itemized Landed Cost Breakdown</span>
                            <div class="border border-slate-200 rounded-xl overflow-x-auto">
                                <table class="w-full text-left border-collapse text-xs min-w-[500px]">
                                    <thead class="bg-slate-50/80 border-b border-slate-200 text-slate-500 font-semibold">
                                        <tr>
                                            <th class="py-2.5 px-4">Cost Component / Charge Description</th>
                                            <th class="py-2.5 px-4 text-right">Amount (OMR)</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 bg-white text-slate-700">
                                        <!-- Customs Duty Component (Primary) -->
                                        <tr class="bg-amber-50/30">
                                            <td class="py-2.5 px-4">
                                                <div class="flex items-center space-x-2">
                                                    <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                                    <span class="font-bold text-slate-900">ROP Customs Duty ({{ number_format($selectedAssessmentDetails->duty_rate_percent, 1) }}%)</span>
                                                </div>
                                            </td>
                                            <td class="py-2.5 px-4 text-right font-mono font-bold text-amber-800">
                                                {{ number_format($selectedAssessmentDetails->duty_amount, 3) }}
                                            </td>
                                        </tr>

                                        @foreach($selectedAssessmentDetails->landedCostComponents as $comp)
                                            <tr class="hover:bg-slate-50/80 transition">
                                                <td class="py-2.5 px-4 font-medium text-slate-800">
                                                    {{ $comp->charge_type }}
                                                </td>
                                                <td class="py-2.5 px-4 text-right font-mono font-semibold text-slate-900">
                                                    {{ number_format($comp->amount, 3) }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="bg-slate-50/80 border-t border-slate-200 text-slate-900">
                                        <tr>
                                            <td class="py-3 px-4 font-bold uppercase tracking-wider text-[11px] text-slate-500">Accumulated Total Landed Charges</td>
                                            <td class="py-3 px-4 text-right font-mono text-rmsc-red text-sm font-extrabold">
                                                OMR {{ number_format($selectedAssessmentDetails->total_landed_cost, 3) }}
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Details Footer -->
                    <div class="px-6 py-3 bg-slate-50/50 border-t border-slate-100 flex items-center justify-between shrink-0">
                        <div class="text-[11px] text-slate-400 font-mono">
                            Assessed: {{ $selectedAssessmentDetails->assessed_date ? $selectedAssessmentDetails->assessed_date->format('d M Y') : '-' }} • By: {{ $selectedAssessmentDetails->assessor?->full_name ?? 'Admin' }}
                        </div>
                        <button type="button" @click="openView = false; $wire.closeViewModal()" class="px-4 py-1.5 bg-slate-800 hover:bg-slate-900 text-white font-semibold text-xs rounded-xl transition cursor-pointer">
                            Close
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </template>

    <!-- Delete Confirmation Modal -->
    <template x-teleport="body">
        <div x-show="$wire.showItemDeleteModal" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="fixed inset-0 z-50 overflow-hidden bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
             style="display: none;">
            <div @click.away="$wire.set('showItemDeleteModal', false)" class="bg-white rounded-xl max-w-sm w-full mx-auto shadow-2xl border border-slate-200 p-6 text-center transform transition-all" style="max-width: 384px;">
                <div class="w-12 h-12 rounded-full bg-red-100 text-red-600 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </div>
                <h3 class="font-heading font-bold text-slate-900 text-base mb-1">Delete Duty Assessment?</h3>
                <p class="text-xs text-slate-500 mb-6">Are you sure you want to delete <span class="font-mono font-bold text-slate-800">{{ $itemToDeleteNumber }}</span>? This will permanently remove its landed cost components.</p>
                
                <div class="flex items-center justify-center space-x-3">
                    <button type="button" @click="$wire.set('showItemDeleteModal', false)" class="px-4 py-2 border border-slate-200 text-slate-700 font-semibold text-xs rounded-xl hover:bg-slate-50 transition cursor-pointer">Cancel</button>
                    <button type="button" wire:click="executeDeleteItem" class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold text-xs rounded-xl shadow-md transition cursor-pointer">Delete Record</button>
                </div>
            </div>
        </div>
    </template>
</div>