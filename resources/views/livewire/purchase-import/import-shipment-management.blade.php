<div x-data="{ openModal: @entangle('showModal'), openView: @entangle('showViewModal'), deleteModal: @entangle('showItemDeleteModal') }"
     x-effect="document.body.classList.toggle('overflow-hidden', Boolean(openModal || openView || deleteModal))">
    <!-- Page Header Slot -->
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="font-heading font-bold text-2xl text-slate-900 tracking-tight">Import Shipments &amp; Bayan Customs</h1>
                <p class="text-xs text-slate-500 mt-1 font-medium">Sea &amp; air container authorizations, Bayan customs declarations, port clearance stages &amp; bonded freight tracking</p>
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
        <!-- Top KPI Metric Cards (5 Stat Cards) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <!-- Stat 1: Total Shipments -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-2xs flex flex-col justify-between hover:border-slate-300 transition">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Shipments</span>
                    <div class="w-8 h-8 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="font-heading font-extrabold text-2xl text-slate-900">{{ $metrics['total_shipments'] }}</div>
                    <p class="text-[11px] text-slate-500 font-medium mt-1">All container manifests</p>
                </div>
            </div>

            <!-- Stat 2: In Transit -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-2xs flex flex-col justify-between hover:border-slate-300 transition">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">In Transit</span>
                    <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="font-heading font-extrabold text-2xl text-slate-900">{{ $metrics['in_transit'] }}</div>
                    <p class="text-[11px] text-indigo-600 font-medium mt-1">Sea / Air freight in route</p>
                </div>
            </div>

            <!-- Stat 3: Customs Clearance -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-2xs flex flex-col justify-between hover:border-slate-300 transition">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Customs Clearance</span>
                    <div class="w-8 h-8 rounded-lg bg-sky-50 text-sky-600 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="font-heading font-extrabold text-2xl text-slate-900">{{ $metrics['customs_clearance'] }}</div>
                    <p class="text-[11px] text-sky-700 font-medium mt-1">At port customs station</p>
                </div>
            </div>

            <!-- Stat 4: Duty Assessment -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-2xs flex flex-col justify-between hover:border-slate-300 transition">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Duty Assessment</span>
                    <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="font-heading font-extrabold text-2xl text-slate-900">{{ $metrics['duty_assessment'] }}</div>
                    <p class="text-[11px] text-amber-700 font-medium mt-1">Bayan calculation pending</p>
                </div>
            </div>

            <!-- Stat 5: Received & Landed -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-2xs flex flex-col justify-between hover:border-slate-300 transition">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Received &amp; Landed</span>
                    <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M5 13l4 4L19 7"/></svg>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="font-heading font-extrabold text-2xl text-slate-900">{{ $metrics['received'] }}</div>
                    <p class="text-[11px] text-emerald-700 font-medium mt-1">Grounded into bonded WH</p>
                </div>
            </div>
        </div>

        <!-- Import Manifest Register Panel -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
            <!-- Panel Header -->
            <div class="px-6 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3 bg-slate-50/50">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </div>
                    <div>
                        <div class="flex items-center space-x-2">
                            <h3 class="font-heading font-bold text-slate-900 text-base">Import Manifest Register</h3>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-200 text-slate-700 font-mono">{{ $shipments->total() }}</span>
                        </div>
                        <p class="text-xs text-slate-500 font-medium">Container manifests, Bayan declarations, port of entry and customs clearance stages</p>
                    </div>
                </div>

                @if(auth()->user()?->hasPermission('Purchase & Import', 'CREATE'))
                <button type="button" wire:click="openCreateModal" @click="openModal = true" style="height: 2.25rem; padding: 0 1.25rem; gap: 0.5rem; white-space: nowrap;" class="bg-rmsc-red hover:bg-rmsc-red-dark text-white font-semibold text-xs rounded-xl shadow-xs transition inline-flex items-center justify-center shrink-0 cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>New Import Shipment</span>
                </button>
                @endif
            </div>

            <div class="p-6" style="display: flex; flex-direction: column; gap: 1.25rem;">
                <!-- Filters Toolbar -->
                <div class="flex flex-wrap items-center gap-2">
                    <!-- Search Bar -->
                    <div class="relative" style="width: 18rem;">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 z-10">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search Shipment, Bayan, Container, PO..." style="padding-left: 2.5rem !important;" class="w-full h-9 !pl-10 pr-8 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white focus:bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-medium text-slate-800 transition">
                        @if($search)
                            <button wire:click="$set('search', '')" type="button" class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600 transition cursor-pointer" title="Clear search">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        @endif
                    </div>

                    <!-- Stage Filter -->
                    <select wire:model.live="stage_filter" class="h-9 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white focus:bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-medium text-slate-800 transition pl-3 pr-8 cursor-pointer">
                        <option value="">All Customs Stages</option>
                        @foreach($stages as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>

                    <!-- Port of Entry Filter -->
                    <select wire:model.live="port_filter" class="h-9 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white focus:bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-medium text-slate-800 transition pl-3 pr-8 cursor-pointer max-w-[180px]">
                        <option value="">All Ports of Entry</option>
                        @foreach($portsList as $port)
                            @if($port !== 'Other (Custom Port)')
                                <option value="{{ $port }}">{{ $port }}</option>
                            @endif
                        @endforeach
                    </select>

                    <!-- Associated PO Filter -->
                    <select wire:model.live="po_filter" class="h-9 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white focus:bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-medium text-slate-800 transition pl-3 pr-8 cursor-pointer max-w-[200px]">
                        <option value="">All Purchase Orders</option>
                        @foreach($purchaseOrders as $p)
                            <option value="{{ $p->po_id }}">{{ $p->po_number }} ({{ $p->supplier?->supplier_name }})</option>
                        @endforeach
                    </select>

                    <!-- Date Range Filters -->
                    <div class="flex items-center gap-1.5">
                        <input type="date" wire:model.live="date_from" title="Arrival / ETA From" style="width: 8.5rem;" class="h-9 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white focus:bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-medium text-slate-800 transition px-2">
                        <span class="text-slate-400 text-xs">-</span>
                        <input type="date" wire:model.live="date_to" title="Arrival / ETA To" style="width: 8.5rem;" class="h-9 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white focus:bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-medium text-slate-800 transition px-2">
                    </div>

                    <!-- Clear Filters Button -->
                    @if($search || $stage_filter || $port_filter || $po_filter || $date_from || $date_to)
                        <button wire:click="clearFilters" type="button" class="h-9 px-2.5 bg-slate-100 hover:bg-slate-200/80 text-slate-600 hover:text-slate-900 text-xs font-medium rounded-xl transition flex items-center space-x-1 cursor-pointer shrink-0" title="Clear all filters">
                            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            <span>Clear</span>
                        </button>
                    @endif
                </div>

                <!-- Shipments Table -->
                <div class="border border-slate-200 rounded-xl overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead class="bg-slate-50/80 border-b border-slate-200 text-slate-500 font-semibold">
                                <tr>
                                    <th class="py-2.5 px-4">Shipment Ref &amp; Container</th>
                                    <th class="py-2.5 px-4">Purchase Order &amp; Supplier</th>
                                    <th class="py-2.5 px-4">Bayan Declaration</th>
                                    <th class="py-2.5 px-4">Port of Entry</th>
                                    <th class="py-2.5 px-4 text-center">ETA / Arrival</th>
                                    <th class="py-2.5 px-4 text-center">Customs Stage</th>
                                    <th class="py-2.5 px-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700">
                                @forelse($shipments as $s)
                                    <tr class="hover:bg-slate-50/80 transition">
                                        <!-- Shipment Ref & Container -->
                                        <td class="py-3 px-4">
                                            <span class="font-mono font-bold text-rmsc-red block">{{ $s->shipment_ref }}</span>
                                            <div class="flex items-center space-x-1 mt-0.5 text-slate-500 font-mono text-[11px]">
                                                <svg class="w-3 h-3 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                                <span>{{ $s->bl_container_no ?: 'No Container Assigned' }}</span>
                                            </div>
                                        </td>

                                        <!-- Purchase Order & Supplier -->
                                        <td class="py-3 px-4">
                                            <div class="font-mono font-bold text-indigo-700">{{ $s->purchaseOrder?->po_number }}</div>
                                            <div class="text-[11px] text-slate-700 font-semibold truncate max-w-xs">{{ $s->purchaseOrder?->supplier?->supplier_name }}</div>
                                            <div class="text-[10px] text-slate-400 font-medium">{{ $s->purchaseOrder?->supplier?->country }} &bull; {{ $s->purchaseOrder?->warehouse?->warehouse_code }}</div>
                                        </td>

                                        <!-- Bayan Declaration No -->
                                        <td class="py-3 px-4">
                                            @if($s->bayan_declaration_no)
                                                <span class="font-mono font-bold text-[11px] px-2 py-0.5 rounded-lg bg-sky-50 text-sky-800 border border-sky-200 inline-block" style="white-space: nowrap;">
                                                    {{ $s->bayan_declaration_no }}
                                                </span>
                                            @else
                                                <span class="text-[11px] font-mono text-slate-400 italic">Pending Filing</span>
                                            @endif
                                        </td>

                                        <!-- Port of Entry -->
                                        <td class="py-3 px-4">
                                            <div class="flex items-center space-x-1 text-slate-800 font-medium">
                                                <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                <span>{{ $s->port_of_entry ?: 'Not Specified' }}</span>
                                            </div>
                                        </td>

                                        <!-- ETA / Arrival Date -->
                                        <td class="py-3 px-4 text-center font-mono" style="white-space: nowrap;">
                                            @if($s->actual_arrival_date)
                                                <span class="font-bold text-slate-900 block">{{ $s->actual_arrival_date->format('d M Y') }}</span>
                                                <span class="text-[10px] text-emerald-600 font-bold uppercase">Arrived</span>
                                            @elseif($s->eta_date)
                                                <span class="font-semibold text-slate-700 block">{{ $s->eta_date->format('d M Y') }}</span>
                                                <span class="text-[10px] text-indigo-600 font-bold uppercase">ETA</span>
                                            @else
                                                <span class="text-slate-400">-</span>
                                            @endif
                                        </td>

                                        <!-- Stage -->
                                        <td class="py-3 px-4 text-center">
                                            @if($s->stage === 'ORDERED')
                                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-slate-200 text-slate-700" style="white-space: nowrap;">Ordered</span>
                                            @elseif($s->stage === 'IN_TRANSIT')
                                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-indigo-100 text-indigo-800" style="white-space: nowrap;">In Transit</span>
                                            @elseif($s->stage === 'CUSTOMS_CLEARANCE')
                                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-sky-100 text-sky-800" style="white-space: nowrap;">Customs Clearance</span>
                                            @elseif($s->stage === 'DUTY_ASSESSMENT')
                                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-amber-100 text-amber-800" style="white-space: nowrap;">Duty Assessment</span>
                                            @elseif($s->stage === 'RECEIVED')
                                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-emerald-100 text-emerald-800" style="white-space: nowrap;">Received &amp; Landed</span>
                                            @else
                                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-slate-200 text-slate-700">{{ $s->stage }}</span>
                                            @endif
                                        </td>

                                        <!-- Actions -->
                                        <td class="py-3 px-4 text-right">
                                            <div class="flex items-center justify-end space-x-1">
                                                <!-- View Details -->
                                                <button type="button" wire:click="viewShipment({{ $s->shipment_id }})" @click="openView = true" class="p-1.5 text-slate-500 hover:text-sky-600 hover:bg-sky-50 rounded-lg transition active:scale-95 cursor-pointer" title="View Shipment & Bayan Details">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                </button>

                                                <!-- Advance Stage Quick Action -->
                                                @if($s->stage !== 'RECEIVED' && auth()->user()?->hasPermission('Purchase & Import', 'EDIT'))
                                                <button type="button" wire:click="advanceStage({{ $s->shipment_id }})" class="p-1.5 text-slate-500 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition active:scale-95 cursor-pointer" title="Advance to Next Stage">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>
                                                </button>
                                                @endif

                                                <!-- Edit -->
                                                @if(auth()->user()?->hasPermission('Purchase & Import', 'EDIT'))
                                                <button type="button" wire:click="editShipment({{ $s->shipment_id }})" @click="openModal = true" class="p-1.5 text-slate-500 hover:text-rmsc-red hover:bg-red-50 rounded-lg transition active:scale-95 cursor-pointer" title="Edit Shipment">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                </button>
                                                @endif

                                                <!-- Delete -->
                                                @if(auth()->user()?->hasPermission('Purchase & Import', 'DELETE'))
                                                <button type="button" wire:click="confirmDeleteShipment({{ $s->shipment_id }}, '{{ addslashes($s->shipment_ref) }}')" class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition active:scale-95 cursor-pointer" title="Delete Shipment">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="py-10 text-center text-slate-400">
                                            <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-2 text-slate-400">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                            </div>
                                            <p class="text-sm font-medium text-slate-600">No import shipments found</p>
                                            <p class="text-xs text-slate-400 mt-0.5">Try adjusting your search criteria or register a new shipment manifest.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                @if($shipments->hasPages())
                    <div>
                        {{ $shipments->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Create / Edit Shipment Modal -->
    <template x-teleport="body">
        <div x-show="openModal" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="fixed inset-0 z-50 overflow-y-auto overscroll-contain bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 sm:p-6"
             style="display: none;">
            <div @click.away="if (!$wire.showItemDeleteModal && !$wire.showViewModal) { openModal = false; $wire.set('showModal', false); }" 
                 class="bg-white rounded-2xl max-w-2xl w-full mx-auto shadow-2xl border border-slate-200 overflow-hidden transform transition-all relative max-h-[calc(100vh-5rem)] flex flex-col my-auto"
                 style="max-width: 672px; max-height: calc(100vh - 5rem);">
                
                <!-- Modal Header -->
                <div class="px-6 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between rounded-t-2xl shrink-0">
                    <div>
                        <h3 class="font-bold text-slate-900 text-base">{{ $isEdit ? 'Edit Import Shipment' : 'Add New Import Shipment' }}</h3>
                        <p class="text-xs text-slate-500 font-medium">Register container manifest, Bayan declaration, port of entry and customs stage</p>
                    </div>
                    <button type="button" @click="openModal = false; $wire.set('showModal', false)" class="text-slate-400 hover:text-slate-600 transition cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Form Content -->
                <form wire:submit.prevent="saveShipment" class="flex flex-col flex-1 overflow-hidden">
                    <div class="p-6 overflow-y-auto overscroll-contain space-y-5 flex-1">
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

                        <!-- Section 1: Reference & Purchase Order -->
                        <div>
                            <span class="text-xs font-semibold text-slate-700 uppercase tracking-wider block mb-2">Shipment Reference</span>
                            <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 space-y-4">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Shipment Ref *</label>
                                        <input type="text" wire:model="shipment_ref" readonly class="w-full h-9 rounded-xl border border-slate-200 bg-slate-100/90 text-slate-600 font-mono text-xs cursor-not-allowed shadow-2xs focus:ring-0 focus:border-slate-200" placeholder="Auto-generated (e.g. SHIP-2026-0001)">
                                        <p class="text-[10px] text-slate-400 mt-1 flex items-center">
                                            <svg class="w-3.5 h-3.5 mr-1 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                            <span>System Auto-Generated Code (Read-Only)</span>
                                        </p>
                                        @error('shipment_ref') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Associated Purchase Order *</label>
                                        <select wire:model.live="po_id" class="w-full h-9 rounded-xl border border-slate-200 bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-semibold text-slate-800">
                                            <option value="">-- Select Purchase Order --</option>
                                            @foreach($purchaseOrders as $p)
                                                <option value="{{ $p->po_id }}">
                                                    {{ $p->po_number }} - {{ $p->supplier?->supplier_name }} ({{ $p->currency_code }})
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

                        <!-- Section 2: Customs & Freight -->
                        <div>
                            <span class="text-xs font-semibold text-slate-700 uppercase tracking-wider block mb-2">Customs &amp; Freight Details</span>
                            <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 space-y-4">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Bayan Customs Declaration No</label>
                                        <input type="text" wire:model="bayan_declaration_no" class="w-full h-9 rounded-xl border border-slate-200 bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-mono uppercase font-semibold text-slate-800" placeholder="e.g. BAYAN-2026-88910">
                                        <p class="text-[10px] text-slate-400 mt-1">Oman Customs Bayan Declaration System Reference</p>
                                        @error('bayan_declaration_no') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">B/L or Container Number</label>
                                        <input type="text" wire:model="bl_container_no" class="w-full h-9 rounded-xl border border-slate-200 bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-mono uppercase font-semibold text-slate-800" placeholder="e.g. MSCU-9812345 / 40FT HC">
                                        <p class="text-[10px] text-slate-400 mt-1">Bill of Lading or Sea / Air Cargo Container Ref</p>
                                        @error('bl_container_no') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <!-- Port of Entry -->
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Port of Entry *</label>
                                    <select wire:model.live="port_of_entry" class="w-full h-9 rounded-xl border border-slate-200 bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-semibold text-slate-800">
                                        @foreach($standardPorts as $port)
                                            <option value="{{ $port }}">{{ $port }}</option>
                                        @endforeach
                                    </select>
                                    @error('port_of_entry') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror

                                    @if($port_of_entry === 'Other (Custom Port)')
                                        <div class="mt-2">
                                            <input type="text" wire:model="custom_port" class="w-full h-9 rounded-xl border border-slate-200 bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs text-slate-800" placeholder="Enter custom border station or terminal name">
                                            @error('custom_port') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Section 3: Arrival & Stage -->
                        <div>
                            <span class="text-xs font-semibold text-slate-700 uppercase tracking-wider block mb-2">Arrival &amp; Clearance Stage</span>
                            <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50">
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Estimated Arrival (ETA)</label>
                                        <input type="date" wire:model.live="eta_date" class="w-full h-9 rounded-xl border border-slate-200 bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-medium text-slate-800">
                                        @error('eta_date') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Actual Arrival Date</label>
                                        <input type="date" wire:model.live="actual_arrival_date" @if($eta_date) min="{{ $eta_date }}" @endif class="w-full h-9 rounded-xl border border-slate-200 bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-medium text-slate-800">
                                        @error('actual_arrival_date') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Customs Stage *</label>
                                        <select wire:model="stage" class="w-full h-9 rounded-xl border border-slate-200 bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-semibold text-slate-800">
                                            @foreach($stages as $key => $label)
                                                <option value="{{ $key }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @error('stage') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Actions (Sticky Footer) -->
                    <div class="px-6 pt-4 pb-5 bg-slate-50 border-t border-slate-100 flex justify-end space-x-3 rounded-b-2xl shrink-0">
                        <button type="button" @click="openModal = false; $wire.set('showModal', false)" class="px-4 py-2 border border-slate-200 text-slate-700 font-semibold text-xs rounded-xl hover:bg-slate-50 transition cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2 bg-rmsc-red hover:bg-rmsc-red-dark text-white font-semibold text-xs rounded-xl shadow-md transition cursor-pointer">
                            {{ $isEdit ? 'Save Changes' : 'Register Shipment' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <!-- View Shipment & Bayan Details Modal -->
    <template x-teleport="body">
        <div x-show="openView" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="fixed inset-0 z-50 overflow-y-auto overscroll-contain bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 sm:p-6"
             style="display: none;">
            <div @click.away="openView = false; $wire.closeViewModal()" 
                 class="bg-white rounded-2xl max-w-2xl w-full mx-auto shadow-2xl border border-slate-200 overflow-hidden transform transition-all relative max-h-[calc(100vh-5rem)] flex flex-col my-auto"
                 style="max-width: 672px; max-height: calc(100vh - 5rem);">
                
                @if($selectedShipmentDetails)
                    <!-- Details Header -->
                    <div class="px-6 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between rounded-t-2xl shrink-0">
                        <div class="flex items-center space-x-2">
                            <h3 class="font-bold text-slate-900 text-base">Import Shipment Details</h3>
                            <span class="font-mono text-xs font-bold px-2 py-0.5 rounded-lg border border-slate-200 bg-white text-rmsc-red">{{ $selectedShipmentDetails->shipment_ref }}</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button type="button" onclick="window.print()" class="p-2 text-slate-500 hover:text-slate-800 hover:bg-slate-200/60 rounded-xl transition cursor-pointer" title="Print Shipment Manifest">
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
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Purchase Order</span>
                                <span class="font-bold text-indigo-700 text-xs mt-1 block">{{ $selectedShipmentDetails->purchaseOrder?->po_number }}</span>
                                <span class="text-[11px] text-slate-500 font-medium leading-tight block">{{ $selectedShipmentDetails->purchaseOrder?->supplier?->supplier_name }}</span>
                            </div>
                            <div class="p-3">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Port of Entry</span>
                                <span class="font-bold text-slate-900 text-xs mt-1 block">{{ $selectedShipmentDetails->port_of_entry }}</span>
                                <span class="text-[11px] text-slate-500 font-mono">{{ $selectedShipmentDetails->purchaseOrder?->warehouse?->warehouse_code }}</span>
                            </div>
                            <div class="p-3">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Container / B/L</span>
                                <span class="font-mono text-xs font-semibold text-slate-800 mt-1 block">
                                    {{ $selectedShipmentDetails->bl_container_no ?: 'None' }}
                                </span>
                            </div>
                            <div class="p-3">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Customs Stage</span>
                                <span class="inline-block px-2 py-0.5 text-[10px] font-bold rounded-full bg-amber-100 text-amber-800 mt-1">
                                    {{ str_replace('_', ' ', $selectedShipmentDetails->stage) }}
                                </span>
                            </div>
                        </div>

                        <!-- Customs & Bayan Declaration Card -->
                        <div>
                            <span class="text-xs font-semibold text-slate-700 uppercase tracking-wider block mb-2">Oman Customs Bayan Declaration</span>
                            <div class="p-4 bg-slate-50/50 border border-slate-200 rounded-xl">
                                <div class="flex items-center justify-between mb-3">
                                    <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Declaration No</span>
                                    @if($selectedShipmentDetails->bayan_declaration_no)
                                        <span class="font-mono text-xs font-bold px-2.5 py-0.5 bg-sky-100 text-sky-800 rounded-lg border border-sky-200">{{ $selectedShipmentDetails->bayan_declaration_no }}</span>
                                    @else
                                        <span class="text-xs font-mono text-slate-400 italic">Not Yet Filed</span>
                                    @endif
                                </div>
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-xs">
                                    <div>
                                        <span class="text-[10px] text-slate-400 font-bold uppercase block">Estimated Arrival (ETA)</span>
                                        <span class="font-mono font-medium text-slate-700">{{ $selectedShipmentDetails->eta_date ? $selectedShipmentDetails->eta_date->format('d M Y') : 'Not Set' }}</span>
                                    </div>
                                    <div>
                                        <span class="text-[10px] text-slate-400 font-bold uppercase block">Actual Port Arrival</span>
                                        <span class="font-mono font-bold text-slate-900">{{ $selectedShipmentDetails->actual_arrival_date ? $selectedShipmentDetails->actual_arrival_date->format('d M Y') : 'In Transit' }}</span>
                                    </div>
                                    <div>
                                        <span class="text-[10px] text-slate-400 font-bold uppercase block">Bonded Destination</span>
                                        <span class="font-medium text-slate-700">{{ $selectedShipmentDetails->purchaseOrder?->warehouse?->warehouse_name }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Associated PO Line Items -->
                        @if($selectedShipmentDetails->purchaseOrder && $selectedShipmentDetails->purchaseOrder->items->count())
                        <div>
                            <span class="text-xs font-semibold text-slate-700 uppercase tracking-wider block mb-2">Manifest Inventory Items (PO: {{ $selectedShipmentDetails->purchaseOrder->po_number }})</span>
                            <div class="border border-slate-200 rounded-xl overflow-hidden">
                                <table class="w-full text-left border-collapse text-xs">
                                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase text-[11px] tracking-wider">
                                        <tr>
                                            <th class="py-2.5 px-4">SKU &amp; Product Name</th>
                                            <th class="py-2.5 px-4 text-right">Qty Ordered</th>
                                            <th class="py-2.5 px-4 text-right">Qty Received</th>
                                            <th class="py-2.5 px-4 text-right">Unit Cost</th>
                                            <th class="py-2.5 px-4 text-right">Total ({{ $selectedShipmentDetails->purchaseOrder->currency_code }})</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 bg-white text-slate-700">
                                        @foreach($selectedShipmentDetails->purchaseOrder->items as $item)
                                            @php $lineTotal = $item->quantity_ordered * $item->unit_cost; @endphp
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
                                </table>
                            </div>
                        </div>
                        @endif

                        <!-- Linked Customs Duty Assessments & GRNs -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Linked Duty Assessments -->
                            <div class="p-4 bg-slate-50/50 rounded-xl border border-slate-200">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2 flex items-center justify-between">
                                    <span>Duty Assessments</span>
                                    <span class="font-mono text-[10px] font-bold px-2 py-0.5 border border-slate-200 rounded text-slate-700 bg-white">{{ $selectedShipmentDetails->dutyAssessments->count() }}</span>
                                </span>
                                @forelse($selectedShipmentDetails->dutyAssessments as $da)
                                    <div class="text-xs font-mono font-semibold text-slate-800 py-1.5 border-b border-slate-200 last:border-0 flex items-center justify-between">
                                        <span>CIF: {{ number_format($da->cif_value, 3) }} OMR</span>
                                        <span class="text-rmsc-red font-bold">Duty: {{ number_format($da->duty_amount, 3) }} OMR</span>
                                    </div>
                                @empty
                                    <p class="text-xs text-slate-400 font-medium">No duty assessment recorded.</p>
                                @endforelse
                            </div>

                            <!-- Linked GRNs -->
                            <div class="p-4 bg-slate-50/50 rounded-xl border border-slate-200">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2 flex items-center justify-between">
                                    <span>Goods Receipt Notes (GRN)</span>
                                    <span class="font-mono text-[10px] font-bold px-2 py-0.5 border border-slate-200 rounded text-slate-700 bg-white">{{ $selectedShipmentDetails->grns->count() }}</span>
                                </span>
                                @forelse($selectedShipmentDetails->grns as $grn)
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
                    <div class="px-6 pt-4 pb-5 bg-slate-50 border-t border-slate-100 flex items-center justify-between rounded-b-2xl shrink-0">
                        <div class="text-xs text-slate-500 font-mono">
                            Shipment: {{ $selectedShipmentDetails->shipment_ref }}
                        </div>
                        <button type="button" @click="openView = false; $wire.closeViewModal()" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white font-semibold text-xs rounded-xl transition cursor-pointer">
                            Close
                        </button>
                    </div>
                @else
                    <!-- Instant Skeleton Fallback while fetching -->
                    <div class="px-6 py-4 bg-slate-50/50 border-b border-slate-100 flex items-center justify-between shrink-0">
                        <div class="flex items-center space-x-2">
                            <h3 class="font-heading font-bold text-slate-900 text-base">Import Shipment Details</h3>
                            <span class="w-20 h-5 bg-slate-200 animate-pulse rounded-lg inline-block"></span>
                        </div>
                        <button type="button" @click="openView = false; $wire.closeViewModal()" class="text-slate-400 hover:text-slate-600 transition cursor-pointer">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div class="p-8 space-y-4 my-auto">
                        <div class="flex flex-col items-center justify-center space-y-3 py-6">
                            <div class="w-8 h-8 border-3 border-slate-200 border-t-rmsc-red rounded-full animate-spin"></div>
                            <span class="text-xs font-semibold text-slate-500">Loading shipment manifest details...</span>
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

    <!-- Shipment Delete Confirmation Modal -->
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
                    <h3 class="font-heading text-base font-bold text-slate-900 mb-1">Delete Import Shipment?</h3>
                    <p class="text-xs text-slate-500 mb-6">Are you sure you want to delete shipment <span class="font-mono font-bold text-slate-800">"{{ $itemToDeleteNumber }}"</span>? This action cannot be undone.</p>

                    <div class="flex items-center justify-center space-x-3">
                        <button type="button" @click="$wire.set('showItemDeleteModal', false)" class="px-4 py-2 border border-slate-200 text-slate-700 font-semibold text-xs rounded-xl hover:bg-slate-50 transition cursor-pointer">Cancel</button>
                        <button type="button" wire:click="executeDeleteItem" class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold text-xs rounded-xl shadow-md transition cursor-pointer">Delete Shipment</button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>