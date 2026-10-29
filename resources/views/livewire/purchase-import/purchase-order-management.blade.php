<div x-data="{ openModal: @entangle('showModal'), openView: @entangle('showViewModal'), deleteModal: @entangle('showItemDeleteModal') }"
     x-effect="document.body.classList.toggle('overflow-hidden', Boolean(openModal || openView || deleteModal))">
    <!-- Page Header -->
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="font-heading font-bold text-2xl text-slate-900 tracking-tight">Purchase Orders</h1>
                <p class="text-xs text-slate-500 mt-1 font-medium">Overseas liquor import authorizations, supplier billing currency &amp; fulfillment tracking</p>
            </div>
            <div class="flex items-center space-x-3">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs text-slate-600 font-medium shadow-2xs">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Live as of {{ now()->format('d M Y · H:i') }}</span>
                </div>
                <button wire:click="$refresh" class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold rounded-lg transition flex items-center space-x-1.5 border border-slate-200 shadow-2xs">
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    <span>Refresh</span>
                </button>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Top KPI Row (5 Stat Cards) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">

            <!-- Stat 1: Total POs -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-2xs flex flex-col justify-between hover:border-slate-300 transition">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total POs</span>
                    <div class="w-8 h-8 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="font-heading font-extrabold text-2xl text-slate-900">{{ $metrics['total_pos'] }}</div>
                    <p class="text-[11px] text-slate-500 font-medium mt-1">All purchase orders</p>
                </div>
            </div>

            <!-- Stat 2: Approved -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-2xs flex flex-col justify-between hover:border-slate-300 transition">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Approved</span>
                    <div class="w-8 h-8 rounded-lg bg-sky-50 text-sky-600 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="font-heading font-extrabold text-2xl text-slate-900">{{ $metrics['approved_pos'] }}</div>
                    <p class="text-[11px] text-sky-700 font-medium mt-1">Awaiting shipment</p>
                </div>
            </div>

            <!-- Stat 3: In Transit -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-2xs flex flex-col justify-between hover:border-slate-300 transition">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">In Transit</span>
                    <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="font-heading font-extrabold text-2xl text-slate-900">{{ $metrics['shipped_pos'] }}</div>
                    <p class="text-[11px] text-slate-500 font-medium mt-1">Overseas shipments moving</p>
                </div>
            </div>

            <!-- Stat 4: Drafts -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-2xs flex flex-col justify-between hover:border-slate-300 transition">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Drafts</span>
                    <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="font-heading font-extrabold text-2xl text-slate-900">{{ $metrics['draft_pos'] }}</div>
                    <p class="text-[11px] text-amber-700 font-medium mt-1">Pending approval</p>
                </div>
            </div>

            <!-- Stat 5: Completed -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-2xs flex flex-col justify-between hover:border-slate-300 transition">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Completed</span>
                    <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M5 13l4 4L19 7"/></svg>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="font-heading font-extrabold text-2xl text-slate-900">{{ $metrics['completed_pos'] }}</div>
                    <p class="text-[11px] text-slate-500 font-medium mt-1">Fully received orders</p>
                </div>
            </div>

        </div>

        <!-- Purchase Order Register Panel -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
            <!-- Panel Header -->
            <div class="px-6 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3 bg-slate-50/50">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 rounded-lg bg-sky-50 text-sky-600 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/></svg>
                    </div>
                    <div>
                        <h3 class="font-heading font-bold text-slate-900 text-base">Purchase Order Register</h3>
                        <p class="text-xs text-slate-500 font-medium">Supplier contracts, destination warehouses, order values and fulfillment status</p>
                    </div>
                </div>

                @if(auth()->user()?->hasPermission('Purchase & Import', 'CREATE'))
                <button wire:click="openCreateModal" @click="openModal = true" type="button" style="height: 2.25rem; padding: 0 1.25rem; gap: 0.5rem; white-space: nowrap;" class="bg-rmsc-red hover:bg-rmsc-red-dark text-white font-semibold text-xs rounded-xl shadow-xs transition inline-flex items-center justify-center shrink-0 cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>New Purchase Order</span>
                </button>
                @endif
            </div>

            <div class="p-6" style="display: flex; flex-direction: column; gap: 1.25rem;">
                <!-- Filters Toolbar -->
                <div class="flex flex-wrap items-center gap-2">
                    <!-- Search Bar -->
                    <div class="relative w-56">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 z-10">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search PO or Supplier..." style="padding-left: 2.5rem !important;" class="w-56 h-9 !pl-10 pr-8 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white focus:bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-medium text-slate-800 transition">
                        @if($search)
                            <button wire:click="$set('search', '')" type="button" class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600 transition cursor-pointer" title="Clear search">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        @endif
                    </div>

                    <!-- Supplier Filter -->
                    <select wire:model.live="supplier_filter" class="h-9 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white focus:bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-medium text-slate-800 transition pl-3 pr-8 cursor-pointer max-w-[170px]">
                        <option value="">All Suppliers</option>
                        @foreach($suppliers as $sup)
                            <option value="{{ $sup->supplier_id }}">{{ $sup->supplier_name }}</option>
                        @endforeach
                    </select>

                    <!-- Warehouse Filter -->
                    <select wire:model.live="warehouse_filter" class="h-9 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white focus:bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-medium text-slate-800 transition pl-3 pr-8 cursor-pointer max-w-[170px]">
                        <option value="">All Warehouses</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->warehouse_id }}">{{ $wh->warehouse_name }}</option>
                        @endforeach
                    </select>

                    <!-- Status Filter -->
                    <select wire:model.live="status_filter" class="h-9 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white focus:bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-medium text-slate-800 transition pl-3 pr-8 cursor-pointer">
                        <option value="">All Statuses</option>
                        <option value="DRAFT">Draft</option>
                        <option value="APPROVED">Approved</option>
                        <option value="SHIPPED">Shipped</option>
                        <option value="PARTIALLY_RECEIVED">Partially Received</option>
                        <option value="COMPLETED">Completed</option>
                        <option value="CANCELLED">Cancelled</option>
                    </select>

                    <!-- Clear Filters Button -->
                    @if($search || $supplier_filter || $warehouse_filter || $status_filter || $date_from || $date_to)
                        <button wire:click="clearFilters" type="button" class="h-9 px-2.5 bg-slate-100 hover:bg-slate-200/80 text-slate-600 hover:text-slate-900 text-xs font-medium rounded-xl transition flex items-center space-x-1 cursor-pointer shrink-0" title="Clear all filters">
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
                                    <th class="py-2.5 px-4">PO Number</th>
                                    <th class="py-2.5 px-4">Supplier</th>
                                    <th class="py-2.5 px-4">Destination Warehouse</th>
                                    <th class="py-2.5 px-4">Order Dates</th>
                                    <th class="py-2.5 px-4 text-center">Items &amp; Units</th>
                                    <th class="py-2.5 px-4 text-right">Order Value</th>
                                    <th class="py-2.5 px-4 text-center">Status</th>
                                    <th class="py-2.5 px-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700">
                                @forelse($purchaseOrders as $po)
                                    @php
                                        $totalVal = $po->items->sum(fn($it) => $it->quantity_ordered * $it->unit_cost);
                                        $totalQty = $po->items->sum('quantity_ordered');
                                    @endphp
                                    <tr class="hover:bg-slate-50/80 transition">
                                        <!-- PO Number -->
                                        <td class="py-3 px-4">
                                            <button wire:click="viewPurchaseOrder({{ $po->po_id }})" @click="openView = true" type="button" class="font-mono font-bold text-rmsc-red hover:underline transition cursor-pointer">
                                                {{ $po->po_number }}
                                            </button>
                                            <div class="text-[10px] text-slate-400 font-mono mt-0.5">By {{ $po->creator?->full_name ?? 'System' }}</div>
                                        </td>

                                        <!-- Supplier -->
                                        <td class="py-3 px-4">
                                            <div class="font-semibold text-slate-900">{{ $po->supplier?->supplier_name ?? '-' }}</div>
                                            <div class="flex items-center space-x-1.5 mt-0.5">
                                                <span class="text-[10px] text-slate-500 font-medium">{{ $po->supplier?->country ?? 'Overseas' }}</span>
                                                <span class="inline-block w-1 h-1 rounded-full bg-slate-300"></span>
                                                <span class="font-mono text-[10px] font-bold text-slate-500">{{ $po->currency_code }}</span>
                                            </div>
                                        </td>

                                        <!-- Warehouse -->
                                        <td class="py-3 px-4">
                                            <div class="font-medium text-slate-800">{{ $po->warehouse?->warehouse_name ?? '-' }}</div>
                                            <span class="font-mono text-[10px] font-bold px-2 py-0.5 border border-slate-200 rounded text-slate-700 bg-white inline-block mt-0.5">
                                                {{ $po->warehouse?->warehouse_code ?? 'WH' }}
                                            </span>
                                        </td>

                                        <!-- Order Dates -->
                                        <td class="py-3 px-4">
                                            <div class="font-mono font-semibold text-slate-800">
                                                {{ $po->order_date ? $po->order_date->format('d M Y') : '-' }}
                                            </div>
                                            <div class="text-[10px] text-slate-400 font-mono mt-0.5" style="white-space: nowrap;">
                                                Exp: {{ $po->expected_delivery_date ? $po->expected_delivery_date->format('d M Y') : 'TBD' }}
                                            </div>
                                        </td>

                                        <!-- Items & Units -->
                                        <td class="py-3 px-4 text-center">
                                            <div class="font-mono font-bold text-slate-800">{{ $po->items->count() }} SKU(s)</div>
                                            <div class="text-[10px] text-slate-500 font-mono mt-0.5">{{ number_format($totalQty, 0) }} units</div>
                                        </td>

                                        <!-- Order Value -->
                                        <td class="py-3 px-4 text-right">
                                            <div class="font-mono font-bold text-slate-900">{{ number_format($totalVal, 3) }}</div>
                                            <span class="text-[10px] font-mono font-bold text-emerald-700 bg-emerald-50 px-1.5 rounded inline-block mt-0.5">{{ $po->currency_code }}</span>
                                        </td>

                                        <!-- Status -->
                                        <td class="py-3 px-4 text-center">
                                            @if($po->status === 'DRAFT')
                                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-slate-200 text-slate-700">Draft</span>
                                            @elseif($po->status === 'APPROVED')
                                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-sky-100 text-sky-800">Approved</span>
                                            @elseif($po->status === 'SHIPPED')
                                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-indigo-100 text-indigo-800">Shipped</span>
                                            @elseif($po->status === 'PARTIALLY_RECEIVED')
                                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-amber-100 text-amber-800">Partial Recv</span>
                                            @elseif($po->status === 'COMPLETED')
                                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-emerald-100 text-emerald-800">Completed</span>
                                            @elseif($po->status === 'CANCELLED')
                                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-rose-100 text-rmsc-red">Cancelled</span>
                                            @else
                                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-slate-200 text-slate-700">{{ $po->status }}</span>
                                            @endif

                                            @if($po->shipments && $po->shipments->count())
                                                <div class="mt-1">
                                                    <span class="text-[9px] font-mono font-bold px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-700 border border-indigo-200" title="Linked to Shipping Manifest">
                                                        {{ $po->shipments->first()->shipment_ref }}
                                                    </span>
                                                </div>
                                            @endif
                                        </td>

                                        <!-- Actions -->
                                        <td class="py-3 px-4 text-right">
                                            <div class="flex items-center justify-end space-x-1">
                                                <!-- View Details -->
                                                <button type="button" wire:click="viewPurchaseOrder({{ $po->po_id }})" @click="openView = true" class="p-1.5 text-slate-500 hover:text-sky-600 hover:bg-sky-50 rounded-lg transition" title="View PO Details">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                </button>

                                                <!-- Quick Approve (if draft) -->
                                                @if($po->status === 'DRAFT' && auth()->user()?->hasPermission('Purchase & Import', 'EDIT'))
                                                <button type="button" wire:click="quickApprovePO({{ $po->po_id }})" class="p-1.5 text-slate-500 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition" title="Approve Purchase Order">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                </button>
                                                @endif

                                                <!-- Quick Ship & Convert to Shipment (if approved) -->
                                                @if($po->status === 'APPROVED' && auth()->user()?->hasPermission('Purchase & Import', 'EDIT'))
                                                <button type="button" wire:click="quickShipPO({{ $po->po_id }})" class="p-1.5 text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition" title="Dispatch & Convert to Shipping Manifest">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                                                </button>
                                                @endif

                                                <!-- Edit -->
                                                @if(auth()->user()?->hasPermission('Purchase & Import', 'EDIT'))
                                                <button type="button" wire:click="editPurchaseOrder({{ $po->po_id }})" @click="openModal = true" class="p-1.5 text-slate-500 hover:text-rmsc-red hover:bg-red-50 rounded-lg transition" title="Edit Purchase Order">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                </button>
                                                @endif

                                                <!-- Delete -->
                                                @if(auth()->user()?->hasPermission('Purchase & Import', 'DELETE'))
                                                <button type="button" wire:click="confirmDeletePO({{ $po->po_id }}, '{{ addslashes($po->po_number) }}')" class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition" title="Delete Purchase Order">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="py-10 text-center text-slate-400">
                                            <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-2 text-slate-400">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            </div>
                                            <p class="text-sm font-medium text-slate-600">No purchase orders found</p>
                                            <p class="text-xs text-slate-400 mt-0.5">Try adjusting your search criteria or create a new purchase order.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                <div>
                    {{ $purchaseOrders->links() }}
                </div>
            </div>
        </div>
    </div>
    <!-- Create / Edit Purchase Order Modal -->
    <template x-teleport="body">
        <div x-show="openModal" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="fixed inset-0 z-50 overflow-y-auto overscroll-contain bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 pt-10 sm:pt-14 pb-8 sm:pb-12"
             style="display: none;">
            <div @click.away="if (!$wire.showItemDeleteModal && !$wire.showViewModal) { openModal = false; $wire.set('showModal', false); }" 
                 class="bg-white rounded-2xl max-w-2xl w-full mx-auto shadow-2xl border border-slate-200 transform transition-all relative max-h-[88vh] flex flex-col my-auto"
                 style="max-width: 672px; max-height: 88vh;">
                
                <!-- Modal Header -->
                <div class="px-6 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between rounded-t-2xl shrink-0">
                    <h3 class="font-bold text-slate-900 text-base">{{ $isEdit ? 'Edit Purchase Order' : 'Add New Purchase Order' }}</h3>
                    <button type="button" @click="openModal = false; $wire.set('showModal', false)" class="text-slate-400 hover:text-slate-600 transition cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Form Content (Scrollable) -->
                <form wire:submit.prevent="savePurchaseOrder" class="p-6 overflow-y-auto overscroll-contain space-y-4 flex-1">
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

                    <!-- Row 1: PO Number & Supplier -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">PO Number *</label>
                            <input type="text" wire:model="po_number" readonly class="w-full rounded-xl border-slate-200 bg-slate-100/90 text-slate-600 font-mono text-sm cursor-not-allowed shadow-2xs focus:ring-0 focus:border-slate-200" placeholder="Auto-generated (e.g. PO-2026-0001)">
                            <p class="text-[11px] text-slate-400 mt-1 flex items-center">
                                <svg class="w-3.5 h-3.5 mr-1 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                <span>System Auto-Generated Code (Read-Only)</span>
                            </p>
                            @error('po_number') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Supplier *</label>
                            <select wire:model.live="supplier_id" class="w-full rounded-xl border border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-sm font-semibold text-slate-900">
                                <option value="">-- Select Supplier --</option>
                                @foreach($suppliers as $sup)
                                    <option value="{{ $sup->supplier_id }}">
                                        {{ $sup->supplier_name }} ({{ $sup->country ?? 'Overseas' }} - {{ $sup->currency_code }})
                                    </option>
                                @endforeach
                            </select>
                            @error('supplier_id') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Row 2: Destination Warehouse & Billing Currency -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Destination Warehouse *</label>
                            <select wire:model="warehouse_id" class="w-full rounded-xl border border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-sm font-semibold text-slate-900">
                                <option value="">-- Select Warehouse --</option>
                                @foreach($warehouses as $wh)
                                    <option value="{{ $wh->warehouse_id }}">
                                        {{ $wh->warehouse_name }} ({{ $wh->warehouse_code }})
                                    </option>
                                @endforeach
                            </select>
                            @error('warehouse_id') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Billing Currency *</label>
                            <input type="text" wire:model="currency_code" readonly class="w-full rounded-xl border-slate-200 bg-slate-100/90 text-slate-700 font-mono font-bold text-sm cursor-not-allowed shadow-2xs focus:ring-0 focus:border-slate-200" placeholder="Auto-populated from Supplier">
                            <p class="text-[11px] text-slate-400 mt-1 flex items-center">
                                <svg class="w-3.5 h-3.5 mr-1 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                <span>Auto-derived from Supplier profile</span>
                            </p>
                            @error('currency_code') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Row 3: Order Date, Expected Delivery Date & Status -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Order Date *</label>
                            <input type="date" wire:model.live="order_date" class="w-full rounded-xl border border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-sm font-medium text-slate-900">
                            @error('order_date') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Expected Delivery Date</label>
                            <input type="date" wire:model.live="expected_delivery_date" @if($order_date) min="{{ $order_date }}" @endif class="w-full rounded-xl border border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-sm font-medium text-slate-900">
                            @error('expected_delivery_date') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Order Status *</label>
                            <select wire:model="status" class="w-full rounded-xl border border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-sm font-semibold text-slate-900">
                                <option value="DRAFT">Draft</option>
                                <option value="APPROVED">Approved</option>
                                <option value="SHIPPED">Shipped (In Transit)</option>
                                <option value="PARTIALLY_RECEIVED">Partially Received</option>
                                <option value="COMPLETED">Completed</option>
                                <option value="CANCELLED">Cancelled</option>
                            </select>
                            @error('status') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Line Items Builder -->
                    <div class="pt-2">
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider">Purchase Order Items *</label>
                            <button type="button" wire:click="addItem" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-xl transition flex items-center space-x-1 cursor-pointer">
                                <span>+ Add Item Row</span>
                            </button>
                        </div>

                        <div class="border border-slate-200 rounded-xl overflow-hidden shadow-2xs">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase text-[11px] tracking-wider">
                                    <tr>
                                        <th class="py-2.5 px-3">Product / SKU *</th>
                                        <th class="py-2.5 px-3 text-right w-28">Quantity *</th>
                                        <th class="py-2.5 px-3 text-right w-36">Unit Cost ({{ $currency_code ?: 'OMR' }}) *</th>
                                        <th class="py-2.5 px-3 text-right w-32">Line Total</th>
                                        <th class="py-2.5 px-2 text-center w-10"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white text-slate-700">
                                    @foreach($items as $index => $item)
                                        @php
                                            $lineTotal = (float)($item['quantity_ordered'] ?? 0) * (float)($item['unit_cost'] ?? 0);
                                        @endphp
                                        <tr class="hover:bg-slate-50/70 transition">
                                            <!-- Product Select -->
                                            <td class="py-2 px-3">
                                                <select wire:model.live="items.{{ $index }}.product_id" class="w-full rounded-xl border border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-xs py-1.5 font-medium text-slate-800">
                                                    <option value="">-- Choose Product / SKU --</option>
                                                    @foreach($products as $p)
                                                        <option value="{{ $p->product_id }}">
                                                            {{ $p->sku_code }} - {{ $p->product_name }} ({{ $p->pack_size ?? $p->uom?->uom_name }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error("items.{$index}.product_id") <span class="text-[11px] text-rmsc-red block mt-0.5">{{ $message }}</span> @enderror
                                            </td>

                                            <!-- Quantity Ordered -->
                                            <td class="py-2 px-3 text-right">
                                                <input type="number" step="1" min="1" wire:model.live.debounce.300ms="items.{{ $index }}.quantity_ordered" class="w-full text-right font-mono rounded-xl border border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-xs py-1.5 font-semibold text-slate-800" placeholder="0">
                                                @error("items.{$index}.quantity_ordered") <span class="text-[11px] text-rmsc-red block mt-0.5">{{ $message }}</span> @enderror
                                            </td>

                                            <!-- Unit Cost -->
                                            <td class="py-2 px-3 text-right">
                                                <input type="number" step="0.001" min="0" wire:model.live.debounce.300ms="items.{{ $index }}.unit_cost" class="w-full text-right font-mono rounded-xl border border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-xs py-1.5 font-semibold text-slate-800" placeholder="0.000">
                                                @error("items.{$index}.unit_cost") <span class="text-[11px] text-rmsc-red block mt-0.5">{{ $message }}</span> @enderror
                                            </td>

                                            <!-- Line Total -->
                                            <td class="py-2 px-3 text-right font-mono font-bold text-slate-900">
                                                {{ number_format($lineTotal, 3) }}
                                            </td>

                                            <!-- Remove Row -->
                                            <td class="py-2 px-2 text-center">
                                                @if(count($items) > 1)
                                                    <button type="button" wire:click="removeItem({{ $index }})" class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition cursor-pointer" title="Remove Item">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                    </button>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @error('items') <span class="text-xs text-rmsc-red block mt-2">{{ $message }}</span> @enderror

                        <!-- Dynamic Summary Bar -->
                        @php
                            $calcTotal = 0;
                            $calcUnits = 0;
                            foreach($items as $it) {
                                $q = (float)($it['quantity_ordered'] ?? 0);
                                $c = (float)($it['unit_cost'] ?? 0);
                                $calcTotal += ($q * $c);
                                $calcUnits += $q;
                            }
                        @endphp
                        <div class="mt-3 p-3.5 rounded-xl border border-slate-200 bg-slate-50/70 flex items-center justify-between">
                            <div class="flex items-center space-x-6">
                                <div>
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Line Items</span>
                                    <span class="font-mono font-bold text-sm text-slate-800">{{ count($items) }} SKUs</span>
                                </div>
                                <div class="w-px h-7 bg-slate-200"></div>
                                <div>
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Total Quantity</span>
                                    <span class="font-mono font-bold text-sm text-slate-800">{{ number_format($calcUnits, 0) }} Units</span>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Order Subtotal</span>
                                <div class="flex items-baseline justify-end space-x-1.5">
                                    <span class="font-bold text-lg text-rmsc-red">{{ number_format($calcTotal, 3) }}</span>
                                    <span class="text-xs font-mono font-bold text-slate-500">{{ $currency_code ?: 'OMR' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Actions -->
                    <div class="pt-4 border-t border-slate-100 flex justify-end space-x-3">
                        <button type="button" @click="openModal = false; $wire.set('showModal', false)" class="px-4 py-2 border border-slate-200 text-slate-700 font-semibold text-xs rounded-xl hover:bg-slate-50 transition cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2 bg-rmsc-red hover:bg-rmsc-red-dark text-white font-semibold text-xs rounded-xl shadow-md transition cursor-pointer">
                            {{ $isEdit ? 'Save Changes' : 'Save Purchase Order' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <!-- View Purchase Order Details Modal -->
    <template x-teleport="body">
        <div x-show="openView" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="fixed inset-0 z-50 overflow-y-auto overscroll-contain bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 pt-10 sm:pt-14 pb-8 sm:pb-12"
             style="display: none;">
            <div @click.away="openView = false; $wire.closeViewModal()" 
                 class="bg-white rounded-2xl max-w-2xl w-full mx-auto shadow-2xl border border-slate-200 overflow-hidden transform transition-all relative max-h-[88vh] flex flex-col my-auto"
                 style="max-width: 672px; max-height: 88vh;">
                
                @if($selectedPoDetails)
                    <!-- Details Header -->
                    <div class="px-6 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between rounded-t-2xl shrink-0">
                        <div class="flex items-center space-x-2">
                            <h3 class="font-bold text-slate-900 text-base">Purchase Order Details</h3>
                            <span class="font-mono text-xs font-bold px-2 py-0.5 rounded-lg border border-slate-200 bg-white text-rmsc-red">{{ $selectedPoDetails->po_number }}</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button type="button" onclick="window.print()" class="p-2 text-slate-500 hover:text-slate-800 hover:bg-slate-200/60 rounded-xl transition cursor-pointer" title="Print PO Slip">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                            </button>
                            <button type="button" @click="openView = false; $wire.closeViewModal()" class="text-slate-400 hover:text-slate-600 transition cursor-pointer">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>

                    <!-- Details Body (Scrollable) -->
                    <div class="p-6 overflow-y-auto overscroll-contain space-y-5 flex-1">
                        <!-- Key Information Grid -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 divide-x divide-slate-200 border border-slate-200 rounded-xl overflow-hidden bg-slate-50/50">
                            <div class="p-3">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Supplier</span>
                                <span class="font-bold text-slate-900 text-xs mt-1 block">{{ $selectedPoDetails->supplier?->supplier_name }}</span>
                                <span class="text-[11px] text-slate-500 font-medium">{{ $selectedPoDetails->supplier?->country }} ({{ $selectedPoDetails->currency_code }})</span>
                            </div>
                            <div class="p-3">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Destination Warehouse</span>
                                <span class="font-bold text-slate-900 text-xs mt-1 block">{{ $selectedPoDetails->warehouse?->warehouse_name }}</span>
                                <span class="text-[11px] text-slate-500 font-mono">{{ $selectedPoDetails->warehouse?->warehouse_code }}</span>
                            </div>
                            <div class="p-3">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Expected Delivery</span>
                                <span class="font-mono text-xs font-semibold text-slate-800 mt-1 block">
                                    {{ $selectedPoDetails->expected_delivery_date ? $selectedPoDetails->expected_delivery_date->format('d M Y') : 'Not Specified' }}
                                </span>
                            </div>
                            <div class="p-3">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Approval Status</span>
                                <span class="inline-block px-2 py-0.5 text-[10px] font-bold rounded-full bg-emerald-100 text-emerald-800 mt-1">
                                    {{ $selectedPoDetails->status }}
                                </span>
                            </div>
                        </div>

                        <!-- Ordered Items -->
                        <div>
                            <span class="text-xs font-semibold text-slate-700 uppercase tracking-wider block mb-2">Ordered Inventory Items</span>
                            <div class="border border-slate-200 rounded-xl overflow-hidden">
                                <table class="w-full text-left border-collapse text-xs">
                                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase text-[11px] tracking-wider">
                                        <tr>
                                            <th class="py-2.5 px-4">SKU &amp; Product Name</th>
                                            <th class="py-2.5 px-4 text-right">Ordered</th>
                                            <th class="py-2.5 px-4 text-right">Received</th>
                                            <th class="py-2.5 px-4 text-right">Unit Cost</th>
                                            <th class="py-2.5 px-4 text-right">Total ({{ $selectedPoDetails->currency_code }})</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 bg-white text-slate-700">
                                        @php $poGrandTotal = 0; @endphp
                                        @foreach($selectedPoDetails->items as $item)
                                            @php
                                                $lineTotal = $item->quantity_ordered * $item->unit_cost;
                                                $poGrandTotal += $lineTotal;
                                            @endphp
                                            <tr class="hover:bg-slate-50/80 transition">
                                                <td class="py-2.5 px-4">
                                                    <div class="font-bold text-slate-900">{{ $item->product?->product_name }}</div>
                                                    <div class="font-mono text-[10px] text-slate-400">{{ $item->product?->sku_code }} &bull; {{ $item->product?->pack_size ?? $item->product?->uom?->uom_name }}</div>
                                                </td>
                                                <td class="py-2.5 px-4 text-right font-mono font-medium">{{ number_format($item->quantity_ordered, 2) }}</td>
                                                <td class="py-2.5 px-4 text-right font-mono font-bold text-emerald-600">{{ number_format($item->quantity_received, 2) }}</td>
                                                <td class="py-2.5 px-4 text-right font-mono">{{ number_format($item->unit_cost, 3) }}</td>
                                                <td class="py-2.5 px-4 text-right font-mono font-bold text-slate-900">{{ number_format($lineTotal, 3) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="bg-slate-50 border-t border-slate-200 text-slate-900">
                                        <tr>
                                            <td colspan="4" class="py-3 px-4 text-right font-bold uppercase tracking-wider text-[11px] text-slate-500">Total Order Value</td>
                                            <td class="py-3 px-4 text-right font-mono text-sm text-rmsc-red font-extrabold">
                                                {{ number_format($poGrandTotal, 3) }} {{ $selectedPoDetails->currency_code }}
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <!-- Linked Operations Summary (Shipments & GRNs) -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Linked Shipments -->
                            <div class="p-4 bg-slate-50/50 rounded-xl border border-slate-200">
                                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2 flex items-center justify-between">
                                    <span>Linked Shipments (Bayan)</span>
                                    <span class="font-mono text-[10px] font-bold px-2 py-0.5 border border-slate-200 rounded text-slate-700 bg-white">{{ $selectedPoDetails->shipments->count() }}</span>
                                </span>
                                @forelse($selectedPoDetails->shipments as $sh)
                                    <div class="text-xs font-mono font-semibold text-slate-800 py-1.5 border-b border-slate-200 last:border-0 flex items-center justify-between">
                                        <span>{{ $sh->shipment_ref }}</span>
                                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-amber-100 text-amber-800">{{ str_replace('_', ' ', $sh->stage ?? 'In Transit') }}</span>
                                    </div>
                                @empty
                                    <p class="text-xs text-slate-400 font-medium">No shipments linked yet.</p>
                                @endforelse
                            </div>

                            <!-- Linked GRNs -->
                            <div class="p-4 bg-slate-50/50 rounded-xl border border-slate-200">
                                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2 flex items-center justify-between">
                                    <span>Goods Receipt Notes (GRN)</span>
                                    <span class="font-mono text-[10px] font-bold px-2 py-0.5 border border-slate-200 rounded text-slate-700 bg-white">{{ $selectedPoDetails->grns->count() }}</span>
                                </span>
                                @forelse($selectedPoDetails->grns as $grn)
                                    <div class="text-xs font-mono font-semibold text-slate-800 py-1.5 border-b border-slate-200 last:border-0 flex items-center justify-between">
                                        <span>{{ $grn->grn_number }}</span>
                                        <span class="text-[10px] font-medium text-emerald-600">{{ $grn->received_date?->format('d M Y') }}</span>
                                    </div>
                                @empty
                                    <p class="text-xs text-slate-400 font-medium">No GRN receipts posted yet.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <!-- Details Footer -->
                    <div class="px-6 py-3 bg-slate-50 border-t border-slate-100 flex items-center justify-between shrink-0">
                        <div class="text-[11px] text-slate-400 font-mono">
                            Created By: {{ $selectedPoDetails->creator?->full_name ?? 'System' }}
                        </div>
                        <button type="button" @click="openView = false; $wire.closeViewModal()" class="px-4 py-1.5 bg-slate-800 hover:bg-slate-900 text-white font-semibold text-xs rounded-xl transition cursor-pointer">
                            Close
                        </button>
                    </div>
                @else
                    <!-- Instant Loader Header & Skeleton State -->
                    <div class="px-6 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between rounded-t-2xl shrink-0">
                        <div class="flex items-center space-x-2">
                            <h3 class="font-bold text-slate-900 text-base">Purchase Order Details</h3>
                            <span class="w-20 h-5 bg-slate-200 animate-pulse rounded-lg inline-block"></span>
                        </div>
                        <button type="button" @click="openView = false; $wire.closeViewModal()" class="text-slate-400 hover:text-slate-600 transition cursor-pointer">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div class="p-8 space-y-4 my-auto">
                        <div class="flex flex-col items-center justify-center space-y-3 py-6">
                            <div class="w-8 h-8 border-3 border-slate-200 border-t-rmsc-red rounded-full animate-spin"></div>
                            <span class="text-xs font-semibold text-slate-500">Loading purchase order details...</span>
                        </div>
                        <div class="grid grid-cols-4 gap-3 animate-pulse">
                            <div class="h-14 bg-slate-100 rounded-xl"></div>
                            <div class="h-14 bg-slate-100 rounded-xl"></div>
                            <div class="h-14 bg-slate-100 rounded-xl"></div>
                            <div class="h-14 bg-slate-100 rounded-xl"></div>
                        </div>
                        <div class="h-28 bg-slate-100 rounded-xl animate-pulse"></div>
                    </div>
                @endif
            </div>
        </div>
    </template>

    <!-- PO Delete Confirmation Modal -->
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
                 class="bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden transform transition-all w-full mx-auto"
                 style="max-width: 400px;">
                <div class="p-6 text-center">
                    <div class="w-12 h-12 rounded-full bg-red-100 text-red-600 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 mb-1">Delete Purchase Order?</h3>
                    <p class="text-xs text-slate-500 mb-6">Are you sure you want to delete PO <span class="font-mono font-bold text-slate-800">"{{ $itemToDeleteNumber }}"</span>? This will remove all associated line items.</p>

                    <div class="flex items-center justify-center space-x-3">
                        <button type="button" @click="$wire.set('showItemDeleteModal', false)" class="px-4 py-2 border border-slate-200 text-slate-700 font-semibold text-xs rounded-xl hover:bg-slate-50 transition cursor-pointer">Cancel</button>
                        <button type="button" wire:click="executeDeleteItem" class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold text-xs rounded-xl shadow-md transition cursor-pointer">Delete PO</button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>