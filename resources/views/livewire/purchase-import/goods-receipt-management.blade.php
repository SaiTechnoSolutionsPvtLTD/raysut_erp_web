<div x-data="{ openModal: @entangle('showModal'), openView: @entangle('showViewModal') }" x-effect="document.body.style.overflow = (openModal || openView) ? 'hidden' : ''" class="space-y-6">

    <!-- Page Header & Action Bar -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2.5">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center shadow-xs">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold font-heading text-slate-900 tracking-tight">Goods Receipt Notes (GRN)</h1>
                    <p class="text-xs text-slate-500 font-medium mt-0.5">Warehouse physical intake logs, batch registrations, and stock ledger updates</p>
                </div>
            </div>
        </div>

        <div class="flex items-center space-x-3">
            <button type="button" 
                    @click="openModal = true; $wire.openCreateModal()" 
                    class="px-4 py-2.5 bg-rmsc-red hover:bg-rmsc-red-dark text-white font-semibold text-xs rounded-xl shadow-md hover:shadow-lg transition-all flex items-center space-x-2 cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>New Goods Receipt (GRN)</span>
            </button>
        </div>
    </div>

    <!-- Alert Notifications -->
    @if(session()->has('message'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-xs font-semibold text-emerald-800 flex items-center justify-between shadow-xs">
            <div class="flex items-center space-x-2">
                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>{{ session('message') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-800 cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    @endif

    <!-- Metric Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Metric 1: Total GRNs -->
        <div class="p-5 bg-white rounded-xl border border-slate-200/80 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Total GRNs Posted</span>
                <span class="font-heading font-extrabold text-2xl text-slate-900 mt-1 block">{{ number_format($metrics['total_grns']) }}</span>
                <span class="text-[11px] text-slate-500 font-medium mt-0.5 block">Physical intake vouchers</span>
            </div>
            <div class="w-11 h-11 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
        </div>

        <!-- Metric 2: Clean Receipts -->
        <div class="p-5 bg-white rounded-xl border border-slate-200/80 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Clean Receipts</span>
                <span class="font-heading font-extrabold text-2xl text-emerald-600 mt-1 block">{{ number_format($metrics['clean_receipts']) }}</span>
                <span class="text-[11px] text-slate-500 font-medium mt-0.5 block">100% matched case count</span>
            </div>
            <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </div>
        </div>

        <!-- Metric 3: Discrepancy Receipts -->
        <div class="p-5 bg-white rounded-xl border border-slate-200/80 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Discrepancy Flagged</span>
                <span class="font-heading font-extrabold text-2xl text-amber-600 mt-1 block">{{ number_format($metrics['discrepancies']) }}</span>
                <span class="text-[11px] text-slate-500 font-medium mt-0.5 block">Shortages or overages</span>
            </div>
            <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
        </div>

        <!-- Metric 4: Total Intake Units -->
        <div class="p-5 bg-white rounded-xl border border-slate-200/80 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Total Quantity Intake</span>
                <span class="font-heading font-extrabold text-2xl text-slate-900 mt-1 block">{{ number_format($metrics['total_intake'], 0) }}</span>
                <span class="text-[11px] text-slate-500 font-medium mt-0.5 block">Units in warehouse ledger</span>
            </div>
            <div class="w-11 h-11 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            </div>
        </div>
    </div>

    <!-- Search & Filter Card -->
    <div class="p-4 bg-white rounded-xl border border-slate-200/80 shadow-xs space-y-3">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <!-- Search Input -->
            <div class="lg:col-span-2 relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <input type="text" 
                       wire:model.live.debounce.300ms="search" 
                       placeholder="Search GRN Number, PO, Supplier or Warehouse..." 
                       class="w-full pl-9 pr-4 h-9 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white text-xs text-slate-800 placeholder-slate-400 focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 transition">
            </div>

            <!-- Warehouse Filter -->
            <div>
                <select wire:model.live="warehouse_filter" class="w-full h-9 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white text-xs font-semibold text-slate-800 focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10">
                    <option value="">All Warehouses</option>
                    @foreach($warehouses as $w)
                        <option value="{{ $w->warehouse_id }}">{{ $w->warehouse_name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Discrepancy Status Filter -->
            <div>
                <select wire:model.live="discrepancy_filter" class="w-full h-9 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white text-xs font-semibold text-slate-800 focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10">
                    <option value="">All Receipt Statuses</option>
                    <option value="clean">Clean Receipts</option>
                    <option value="discrepancy">Discrepancy Flagged</option>
                </select>
            </div>

            <!-- Reset Filters -->
            <div class="flex items-center justify-end">
                @if($search || $warehouse_filter || $discrepancy_filter || $date_from || $date_to)
                    <button type="button" 
                            wire:click="$set('search', ''); $set('warehouse_filter', ''); $set('discrepancy_filter', ''); $set('date_from', ''); $set('date_to', '')" 
                            class="px-3 py-1.5 text-xs font-bold text-slate-500 hover:text-rmsc-red transition flex items-center space-x-1 cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        <span>Clear Filters</span>
                    </button>
                @endif
            </div>
        </div>
    </div>

    <!-- Main Data Table -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50/90 border-b border-slate-200 text-slate-500 uppercase text-[11px] tracking-wider font-semibold">
                        <th class="py-3 px-4">GRN Ref</th>
                        <th class="py-3 px-4">Purchase Order &amp; Supplier</th>
                        <th class="py-3 px-4">Receiving Warehouse</th>
                        <th class="py-3 px-4">Received Date</th>
                        <th class="py-3 px-4">Received By</th>
                        <th class="py-3 px-4 text-center">Receipt Status</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white text-slate-700">
                    @forelse($grns as $g)
                        <tr class="hover:bg-slate-50/80 transition">
                            <!-- GRN Ref -->
                            <td class="py-3 px-4">
                                <div class="font-mono font-bold text-slate-900 text-xs">{{ $g->grn_number }}</div>
                                @if($g->shipment)
                                    <span class="font-mono text-[10px] text-slate-400 block mt-0.5">Shipment: {{ $g->shipment->shipment_ref }}</span>
                                @endif
                            </td>

                            <!-- Purchase Order & Supplier -->
                            <td class="py-3 px-4">
                                <div class="font-mono font-bold text-indigo-600">{{ $g->purchaseOrder?->po_number }}</div>
                                <div class="text-[11px] text-slate-500 font-medium">{{ $g->purchaseOrder?->supplier?->supplier_name }}</div>
                            </td>

                            <!-- Warehouse -->
                            <td class="py-3 px-4 font-medium text-slate-900">
                                <div>{{ $g->warehouse?->warehouse_name }}</div>
                                <div class="font-mono text-[10px] text-slate-400">{{ $g->warehouse?->warehouse_code }}</div>
                            </td>

                            <!-- Received Date -->
                            <td class="py-3 px-4 font-mono font-medium text-slate-800">
                                {{ $g->received_date ? $g->received_date->format('d M Y') : '-' }}
                            </td>

                            <!-- Received By -->
                            <td class="py-3 px-4 text-slate-700 font-medium">
                                {{ $g->receiver?->full_name ?? $g->receiver?->name ?? 'System Admin' }}
                            </td>

                            <!-- Receipt Status -->
                            <td class="py-3 px-4 text-center">
                                @if($g->has_discrepancy)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">
                                        <svg class="w-3 h-3 mr-1 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                        Discrepancy
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                        <svg class="w-3 h-3 mr-1 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Clean Receipt
                                    </span>
                                @endif
                            </td>

                            <!-- Actions -->
                            <td class="py-3 px-4 text-right">
                                <div class="flex items-center justify-end space-x-1.5">
                                    <!-- View Details -->
                                    <button type="button" 
                                            @click="openView = true; $wire.openViewModal({{ $g->grn_id }})" 
                                            class="p-1.5 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-lg transition cursor-pointer" 
                                            title="View GRN Manifest &amp; Batches">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </button>

                                    <!-- Edit -->
                                    <button type="button" 
                                            @click="openModal = true; $wire.openEditModal({{ $g->grn_id }})" 
                                            class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition cursor-pointer" 
                                            title="Edit GRN Entry">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>

                                    <!-- Delete -->
                                    <button type="button" 
                                            wire:click="confirmDelete({{ $g->grn_id }})" 
                                            class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition cursor-pointer" 
                                            title="Delete / Void GRN">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-400">
                                <div class="flex flex-col items-center justify-center space-y-2">
                                    <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <p class="text-sm font-medium text-slate-600">No Goods Receipt Notes found</p>
                                    <p class="text-xs text-slate-400 mt-0.5">Try adjusting your search criteria or register a new physical receipt voucher.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($grns->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $grns->links() }}
            </div>
        @endif
    </div>

    <!-- Create / Edit GRN Modal -->
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
             {{-- Fixed-height card: header + footer stay pinned, only the body scrolls (scrollbar on the right) --}}
             <div @click.away="if (!$wire.showItemDeleteModal && !$wire.showViewModal) { openModal = false; $wire.set('showModal', false); }" 
                  class="bg-white rounded-2xl max-w-2xl w-full mx-auto shadow-2xl border border-slate-200 overflow-hidden transform transition-all relative flex flex-col min-h-0"
                  style="max-width: 672px; max-height: calc(100vh - 3rem); max-height: calc(100dvh - 3rem);">
                
                <!-- Modal Header -->
                <div class="px-6 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between rounded-t-2xl shrink-0">
                    <div>
                        <h3 class="font-bold text-slate-900 text-base">{{ $isEdit ? 'Edit Goods Receipt Note' : 'Add New Goods Receipt (GRN)' }}</h3>
                        <p class="text-xs text-slate-500 font-medium">Record physical warehouse intake, case counts, and batch registrations</p>
                    </div>
                    <button type="button" @click="openModal = false; $wire.set('showModal', false)" class="text-slate-400 hover:text-slate-600 transition cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Form Content -->
                <form wire:submit.prevent="saveGrn" class="flex flex-col flex-1 min-h-0 overflow-hidden">
                    <div class="p-6 overflow-y-auto overscroll-contain space-y-4 flex-1 min-h-0">
                        @if(session()->has('error'))
                            <div class="p-3 bg-red-50 border border-red-200 rounded-xl text-xs font-semibold text-rmsc-red flex items-center justify-between">
                                <span>{{ session('error') }}</span>
                            </div>
                        @endif

                        <!-- Section 1: GRN Reference & Associated Purchase Order -->
                        <div>
                            <span class="text-xs font-semibold text-slate-700 uppercase tracking-wider block mb-2">GRN Reference &amp; Purchase Order</span>
                            <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 space-y-4">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">GRN Ref Number *</label>
                                        <input type="text" wire:model="grn_number" readonly class="w-full h-9 rounded-xl border border-slate-200 bg-slate-100/90 text-slate-600 font-mono text-xs cursor-not-allowed shadow-2xs" placeholder="e.g. GRN-2026-0001">
                                        <p class="text-[10px] text-slate-400 mt-1 flex items-center">
                                            <svg class="w-3.5 h-3.5 mr-1 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                            <span>Auto-generated System Voucher Code</span>
                                        </p>
                                        @error('grn_number') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Associated Purchase Order *</label>
                                        <select wire:model.live="po_id" class="w-full h-9 rounded-xl border border-slate-200 bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-semibold text-slate-800">
                                            <option value="">-- Select Purchase Order --</option>
                                            @foreach($purchaseOrders as $p)
                                                <option value="{{ $p->po_id }}">
                                                    {{ $p->po_number }} - {{ $p->supplier?->supplier_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('po_id') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <!-- PO Info Preview Banner -->
                                @if($selected_po_info)
                                    <div class="p-3 bg-indigo-50/80 border border-indigo-200 rounded-xl text-xs text-indigo-900 flex items-center space-x-2">
                                        <svg class="w-4 h-4 text-indigo-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <span class="font-medium">{{ $selected_po_info }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Section 2: Warehouse, Date & Receiver -->
                        <div>
                            <span class="text-xs font-semibold text-slate-700 uppercase tracking-wider block mb-2">Warehouse Intake &amp; Receiving Officer</span>
                            <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 space-y-4">
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Receiving Warehouse *</label>
                                        <select wire:model="warehouse_id" class="w-full h-9 rounded-xl border border-slate-200 bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-semibold text-slate-800">
                                            <option value="">-- Select Warehouse --</option>
                                            @foreach($warehouses as $w)
                                                <option value="{{ $w->warehouse_id }}">{{ $w->warehouse_name }}</option>
                                            @endforeach
                                        </select>
                                        @error('warehouse_id') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Received Date *</label>
                                        <input type="date" wire:model="received_date" class="w-full h-9 rounded-xl border border-slate-200 bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-medium text-slate-800">
                                        @error('received_date') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Receiving Officer *</label>
                                        <select wire:model="received_by" class="w-full h-9 rounded-xl border border-slate-200 bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-semibold text-slate-800">
                                            @foreach($users as $u)
                                                <option value="{{ $u->user_id }}">{{ $u->full_name ?? $u->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('received_by') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section 3: Physical Item Receipt & Batch Builder -->
                        <div>
                            <span class="text-xs font-semibold text-slate-700 uppercase tracking-wider block mb-2">Inventory Item Intake &amp; Batch Registration</span>
                            
                            @if(count($items) > 0)
                                {{-- No inner max-height: the modal body is the single vertical scroller --}}
                                <div class="border border-slate-200 rounded-xl overflow-x-auto shadow-2xs">
                                    <table class="w-full text-left border-collapse text-xs min-w-[650px]">
                                        <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase text-[11px] tracking-wider">
                                            <tr>
                                                <th class="py-2.5 px-3 min-w-[180px]">SKU &amp; Product</th>
                                                <th class="py-2.5 px-3 text-right w-24">Ordered</th>
                                                <th class="py-2.5 px-3 text-right w-28">Qty Received *</th>
                                                <th class="py-2.5 px-3 w-36">Batch Number</th>
                                                <th class="py-2.5 px-3 w-36">Expiry Date</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 bg-white">
                                            @foreach($items as $index => $item)
                                                <tr class="hover:bg-slate-50/80 transition">
                                                    <td class="py-2.5 px-3">
                                                        <div class="font-bold text-slate-900">{{ $item['product_name'] }}</div>
                                                        <div class="font-mono text-[10px] text-slate-400">{{ $item['sku_code'] }}</div>
                                                    </td>

                                                    <td class="py-2.5 px-3 text-right font-mono font-medium text-slate-700">
                                                        {{ number_format($item['quantity_ordered'], 2) }}
                                                    </td>

                                                    <td class="py-2.5 px-3 text-right">
                                                        <input type="number" 
                                                               step="0.01" 
                                                               min="0" 
                                                               wire:model.live.debounce.300ms="items.{{ $index }}.quantity_received" 
                                                               class="w-full text-right font-mono font-bold text-slate-900 rounded-xl border border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-xs py-1.5">
                                                        @error("items.{$index}.quantity_received") <span class="text-[10px] text-rmsc-red block mt-0.5">{{ $message }}</span> @enderror
                                                    </td>

                                                    <td class="py-2.5 px-3">
                                                        <input type="text" 
                                                               wire:model="items.{{ $index }}.batch_number" 
                                                               class="w-full font-mono text-xs rounded-xl border border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red py-1.5" 
                                                               placeholder="BATCH-REF">
                                                    </td>

                                                    <td class="py-2.5 px-3">
                                                        <input type="date" 
                                                               wire:model="items.{{ $index }}.expiry_date" 
                                                               class="w-full text-xs rounded-xl border border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red py-1.5">
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="p-6 bg-slate-50/60 border border-dashed border-slate-200 rounded-xl text-center text-xs text-slate-500">
                                    Please select an Associated Purchase Order above to populate item rows for receipt.
                                </div>
                            @endif
                            @error('items') <span class="text-xs text-rmsc-red block mt-2">{{ $message }}</span> @enderror
                        </div>

                        <!-- Section 4: Manual Discrepancy Flag (Alpine-driven, instant toggle; syncs to Livewire on submit) -->
                        <div x-data="{ flagged: @entangle('has_discrepancy') }"
                             @click="flagged = !flagged"
                             class="p-3.5 rounded-xl border flex items-center justify-between cursor-pointer select-none transition-colors duration-200"
                             :class="flagged ? 'bg-amber-50/80 border-amber-200' : 'bg-slate-50/80 border-slate-200 hover:bg-slate-100/60'">
                            <div>
                                <div class="flex items-center space-x-2">
                                    <span class="text-xs font-semibold text-slate-800 block">Flag Discrepancy Note</span>
                                    <span x-show="flagged" style="display: none;" class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-amber-100 text-amber-800 border border-amber-200">Flagged</span>
                                    <span x-show="!flagged" class="px-2 py-0.5 text-[10px] font-semibold rounded-full bg-slate-200 text-slate-600">Clean</span>
                                </div>
                                <span class="text-[11px] text-slate-500 block mt-0.5">Check if physical intake contains damaged goods or box count mismatches</span>
                            </div>
                            <button type="button"
                                    role="switch"
                                    :aria-checked="flagged ? 'true' : 'false'"
                                    @click.stop="flagged = !flagged"
                                    class="relative inline-flex items-center shrink-0 ml-4 rounded-full transition-colors duration-200 ease-in-out focus:outline-none focus-visible:ring-2 focus-visible:ring-rmsc-red/40 cursor-pointer"
                                    style="width: 44px; height: 24px;"
                                    :class="flagged ? 'bg-rmsc-red' : ''"
                                    :style="{ backgroundColor: flagged ? '' : '#cbd5e1' }">
                                <span class="block rounded-full bg-white shadow-md transition-transform duration-200 ease-in-out"
                                      :style="{ width: '20px', height: '20px', backgroundColor: '#ffffff', transform: flagged ? 'translateX(22px)' : 'translateX(2px)' }"></span>
                            </button>
                        </div>
                    </div>

                    <!-- Modal Actions (Sticky Footer) -->
                    <div class="px-6 pt-4 pb-5 bg-slate-50 border-t border-slate-100 flex justify-end space-x-3 rounded-b-2xl shrink-0">
                        <button type="button" @click="openModal = false; $wire.set('showModal', false)" class="px-4 py-2 border border-slate-200 text-slate-700 font-semibold text-xs rounded-xl hover:bg-slate-50 transition cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2 bg-rmsc-red hover:bg-rmsc-red-dark text-white font-semibold text-xs rounded-xl shadow-md transition cursor-pointer">
                            {{ $isEdit ? 'Save Changes' : 'Post GRN Receipt' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <!-- View GRN Details Modal -->
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
                  class="bg-white rounded-2xl max-w-2xl w-full mx-auto shadow-2xl border border-slate-200 overflow-hidden transform transition-all relative flex flex-col min-h-0"
                  style="max-width: 672px; max-height: calc(100vh - 3rem); max-height: calc(100dvh - 3rem);">
                
                @if($selectedGrnDetails)
                    <!-- Details Header -->
                    <div class="px-6 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between rounded-t-2xl shrink-0">
                        <div class="flex items-center space-x-2">
                            <h3 class="font-bold text-slate-900 text-base">Goods Receipt Note Details</h3>
                            <span class="font-mono text-xs font-bold px-2 py-0.5 rounded-lg border border-slate-200 bg-white text-rmsc-red">{{ $selectedGrnDetails->grn_number }}</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button type="button" onclick="window.print()" class="p-2 text-slate-500 hover:text-slate-800 hover:bg-slate-200/60 rounded-xl transition cursor-pointer" title="Print GRN Voucher">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                            </button>
                            <button type="button" @click="openView = false; $wire.closeViewModal()" class="text-slate-400 hover:text-slate-600 transition cursor-pointer">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>

                    <!-- Details Body (Scrollable) -->
                    <div class="p-6 overflow-y-auto overscroll-contain space-y-4 flex-1 min-h-0">
                        <!-- Key Information Grid -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 divide-x divide-slate-200 border border-slate-200 rounded-xl overflow-hidden bg-slate-50/50">
                            <div class="p-3">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Purchase Order</span>
                                <span class="font-bold text-indigo-700 text-xs mt-0.5 block">{{ $selectedGrnDetails->purchaseOrder?->po_number }}</span>
                                <span class="text-[11px] text-slate-500 font-medium leading-tight block">{{ $selectedGrnDetails->purchaseOrder?->supplier?->supplier_name }}</span>
                            </div>
                            <div class="p-3">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Receiving Warehouse</span>
                                <span class="font-bold text-slate-900 text-xs mt-0.5 block">{{ $selectedGrnDetails->warehouse?->warehouse_name }}</span>
                                <span class="text-[11px] text-slate-500 font-mono">{{ $selectedGrnDetails->warehouse?->warehouse_code }}</span>
                            </div>
                            <div class="p-3">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Received Date</span>
                                <span class="font-mono text-xs font-semibold text-slate-800 mt-0.5 block">
                                    {{ $selectedGrnDetails->received_date ? $selectedGrnDetails->received_date->format('d M Y') : '-' }}
                                </span>
                            </div>
                            <div class="p-3">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Receiving Officer</span>
                                <span class="font-medium text-slate-900 text-xs mt-0.5 block">
                                    {{ $selectedGrnDetails->receiver?->full_name ?? $selectedGrnDetails->receiver?->name ?? 'System Admin' }}
                                </span>
                            </div>
                        </div>

                        <!-- Discrepancy Status Banner -->
                        <div class="p-3 sm:p-3.5 rounded-xl border {{ $selectedGrnDetails->has_discrepancy ? 'bg-amber-50 border-amber-200 text-amber-900' : 'bg-emerald-50 border-emerald-200 text-emerald-900' }} flex items-center space-x-3">
                            @if($selectedGrnDetails->has_discrepancy)
                                <svg class="w-5 h-5 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                <div>
                                    <span class="font-bold text-xs block">Discrepancy Flagged on Manifest</span>
                                    <span class="text-[11px] opacity-90 block">Physical intake quantities differ from the purchase order allocation.</span>
                                </div>
                            @else
                                <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <div>
                                    <span class="font-bold text-xs block">100% Clean Receipt Verification</span>
                                    <span class="text-[11px] opacity-90 block">All inventory case counts match physical arrival logs cleanly.</span>
                                </div>
                            @endif
                        </div>

                        <!-- Received Items & Batches Table -->
                        <div>
                            <span class="text-xs font-semibold text-slate-700 uppercase tracking-wider block mb-2">Intake Line Items &amp; Registered Batches</span>
                            <div class="border border-slate-200 rounded-xl overflow-x-auto shadow-2xs">
                                <table class="w-full text-left border-collapse text-xs min-w-[650px]">
                                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase text-[11px] tracking-wider">
                                        <tr>
                                            <th class="py-2.5 px-4 min-w-[180px]">SKU &amp; Product Name</th>
                                            <th class="py-2.5 px-4 min-w-[140px]">Registered Batch</th>
                                            <th class="py-2.5 px-4 min-w-[120px]">Expiry Date</th>
                                            <th class="py-2.5 px-4 text-right min-w-[100px]">Qty Ordered</th>
                                            <th class="py-2.5 px-4 text-right min-w-[100px]">Qty Received</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 bg-white text-slate-700">
                                        @foreach($selectedGrnDetails->items as $item)
                                            <tr class="hover:bg-slate-50/80 transition">
                                                <td class="py-2.5 px-4">
                                                    <div class="font-bold text-slate-900">{{ $item->product?->product_name }}</div>
                                                    <div class="font-mono text-[10px] text-slate-400">{{ $item->product?->sku_code }}</div>
                                                </td>
                                                <td class="py-2.5 px-4 font-mono font-bold text-indigo-700">
                                                    {{ $item->batch?->batch_number ?? '-' }}
                                                </td>
                                                <td class="py-2.5 px-4 font-mono text-slate-600">
                                                    {{ $item->batch?->expiry_date ? $item->batch->expiry_date->format('d M Y') : '-' }}
                                                </td>
                                                <td class="py-2.5 px-4 text-right font-mono font-medium">
                                                    {{ number_format($item->quantity_ordered, 2) }}
                                                </td>
                                                <td class="py-2.5 px-4 text-right font-mono font-bold text-emerald-600">
                                                    {{ number_format($item->quantity_received, 2) }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Details Footer -->
                    <div class="px-6 pt-4 pb-5 bg-slate-50 border-t border-slate-100 flex items-center justify-between rounded-b-2xl shrink-0">
                        <div class="text-xs text-slate-500 font-mono">
                            Voucher: {{ $selectedGrnDetails->grn_number }}
                        </div>
                        <button type="button" @click="openView = false; $wire.closeViewModal()" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white font-semibold text-xs rounded-xl transition cursor-pointer">
                            Close
                        </button>
                    </div>
                @else
                    <!-- Skeleton Fallback -->
                    <div class="px-6 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between shrink-0">
                        <h3 class="font-bold text-slate-900 text-base">Goods Receipt Note Details</h3>
                        <button type="button" @click="openView = false; $wire.closeViewModal()" class="text-slate-400 hover:text-slate-600 transition cursor-pointer">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div class="p-8 space-y-4 my-auto text-center">
                        <div class="w-8 h-8 border-3 border-slate-200 border-t-rmsc-red rounded-full animate-spin mx-auto"></div>
                        <span class="text-xs font-semibold text-slate-500">Loading GRN manifest details...</span>
                    </div>
                @endif
            </div>
        </div>
    </template>

    <!-- GRN Delete Confirmation Modal -->
    <template x-teleport="body">
        <div x-show="$wire.showItemDeleteModal" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="fixed inset-0 z-50 overflow-y-auto overscroll-contain bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
             style="display: none;">
            <div @click.away="$wire.set('showItemDeleteModal', false)" 
                 class="bg-white rounded-xl shadow-2xl border border-slate-200 overflow-hidden transform transition-all w-full mx-auto"
                 style="max-width: 400px;">
                <div class="p-6 text-center">
                    <div class="w-12 h-12 rounded-full bg-red-100 text-red-600 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </div>
                    <h3 class="font-heading text-base font-bold text-slate-900 mb-1">Delete Goods Receipt Note?</h3>
                    <p class="text-xs text-slate-500 mb-6">Are you sure you want to delete GRN voucher <span class="font-mono font-bold text-slate-800">"{{ $itemToDeleteNumber }}"</span>? This action cannot be undone.</p>

                    <div class="flex items-center justify-center space-x-3">
                        <button type="button" @click="$wire.set('showItemDeleteModal', false)" class="px-4 py-2 border border-slate-200 text-slate-700 font-semibold text-xs rounded-xl hover:bg-slate-50 transition cursor-pointer">Cancel</button>
                        <button type="button" wire:click="executeDeleteItem" class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold text-xs rounded-xl shadow-md transition cursor-pointer">Delete GRN</button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>