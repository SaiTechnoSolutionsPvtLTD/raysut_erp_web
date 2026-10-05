<div x-data="{ openModal: @entangle('showModal'), openView: @entangle('showViewModal') }" x-effect="document.body.style.overflow = (openModal || openView) ? 'hidden' : ''">
    <!-- Page Header Slot -->
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="font-heading font-bold text-2xl text-slate-900 tracking-tight">Inter-Warehouse Stock Transfers</h1>
                <p class="text-xs text-slate-500 mt-1 font-medium">Manage stock movements between warehouses, transit dispatches, and warehouse stock receipts</p>
            </div>
            <div class="flex items-center space-x-3">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs text-slate-600 font-medium shadow-2xs">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Live Inventory Routing</span>
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
            <!-- Stat 1: Total Transfers -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-2xs flex flex-col justify-between hover:border-slate-300 transition">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Transfers</span>
                    <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="font-heading font-extrabold text-2xl text-slate-900">{{ number_format($metrics['total_transfers']) }}</div>
                    <p class="text-[11px] text-slate-500 font-medium mt-1">All recorded transfers</p>
                </div>
            </div>

            <!-- Stat 2: In-Transit -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-2xs flex flex-col justify-between hover:border-slate-300 transition">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">In-Transit</span>
                    <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="font-heading font-extrabold text-2xl {{ $metrics['in_transit'] > 0 ? 'text-amber-600' : 'text-slate-900' }}">{{ number_format($metrics['in_transit']) }}</div>
                    <p class="text-[11px] text-amber-700 font-medium mt-1">On the move between depots</p>
                </div>
            </div>

            <!-- Stat 3: Completed Transfers -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-2xs flex flex-col justify-between hover:border-slate-300 transition">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Completed</span>
                    <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="font-heading font-extrabold text-2xl text-slate-900">{{ number_format($metrics['completed']) }}</div>
                    <p class="text-[11px] text-emerald-700 font-medium mt-1">Stock received &amp; ledgers updated</p>
                </div>
            </div>

            <!-- Stat 4: Draft Requests -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-2xs flex flex-col justify-between hover:border-slate-300 transition">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Draft Requests</span>
                    <div class="w-8 h-8 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="font-heading font-extrabold text-2xl text-slate-900">{{ number_format($metrics['draft']) }}</div>
                    <p class="text-[11px] text-slate-500 font-medium mt-1">Pending warehouse dispatch</p>
                </div>
            </div>
        </div>

        <!-- Stock Transfer Register Panel -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
            <!-- Panel Header -->
            <div class="px-6 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3 bg-slate-50/50">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 rounded-lg bg-rmsc-red/10 text-rmsc-red flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                    </div>
                    <div>
                        <div class="flex items-center space-x-2">
                            <h3 class="font-heading font-bold text-slate-900 text-base">Inter-Warehouse Transfer Vouchers</h3>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-200 text-slate-700 font-mono">{{ $transfers->total() }}</span>
                        </div>
                        <p class="text-xs text-slate-500 font-medium">Manage stock relocation dispatches, in-transit manifests, and warehouse receipts</p>
                    </div>
                </div>

                <button type="button" wire:click="openCreateModal" style="height: 2.25rem; padding: 0 1.25rem; gap: 0.5rem; white-space: nowrap;" class="bg-rmsc-red hover:bg-rmsc-red-dark text-white font-semibold text-xs rounded-xl shadow-xs transition inline-flex items-center justify-center shrink-0 cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>New Stock Transfer</span>
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
                        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search transfer #, warehouse, batch..." style="padding-left: 2.5rem !important;" class="w-full h-9 !pl-10 pr-8 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white focus:bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-medium text-slate-800 transition">
                        @if($search)
                            <button wire:click="$set('search', '')" type="button" class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600 transition cursor-pointer" title="Clear search">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        @endif
                    </div>

                    <!-- Source Warehouse Select Filter -->
                    <select wire:model.live="source_warehouse_filter" class="h-9 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white focus:bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-medium text-slate-800 transition pl-3 pr-8 cursor-pointer max-w-[200px]">
                        <option value="">All Source Warehouses</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->warehouse_id }}">{{ $wh->warehouse_name }}</option>
                        @endforeach
                    </select>

                    <!-- Destination Warehouse Select Filter -->
                    <select wire:model.live="dest_warehouse_filter" class="h-9 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white focus:bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-medium text-slate-800 transition pl-3 pr-8 cursor-pointer max-w-[200px]">
                        <option value="">All Destination Warehouses</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->warehouse_id }}">{{ $wh->warehouse_name }}</option>
                        @endforeach
                    </select>

                    <!-- Status Select -->
                    <select wire:model.live="status_filter" class="h-9 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white focus:bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-medium text-slate-800 transition pl-3 pr-8 cursor-pointer">
                        <option value="">All Statuses</option>
                        <option value="DRAFT">Draft</option>
                        <option value="IN_TRANSIT">In-Transit</option>
                        <option value="RECEIVED">Received</option>
                        <option value="CANCELLED">Cancelled</option>
                    </select>

                    <!-- Clear Filters Button -->
                    @if($search !== '' || $source_warehouse_filter !== '' || $dest_warehouse_filter !== '' || $status_filter !== '')
                        <button type="button" wire:click="clearFilters" class="h-9 px-2.5 bg-slate-100 hover:bg-slate-200/80 text-slate-600 hover:text-slate-900 text-xs font-medium rounded-xl transition flex items-center space-x-1 cursor-pointer shrink-0" title="Clear all filters">
                            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            <span>Clear</span>
                        </button>
                    @endif
                </div>

                <!-- Transfers Data Table -->
                <div class="border border-slate-200 rounded-xl overflow-hidden shadow-2xs">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-slate-50/80 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                                    <th class="py-3 px-4">Transfer Voucher</th>
                                    <th class="py-3 px-4">Routing Pathway</th>
                                    <th class="py-3 px-4 text-center">Items Count</th>
                                    <th class="py-3 px-4">Transfer Date</th>
                                    <th class="py-3 px-4 text-center">Status</th>
                                    <th class="py-3 px-4 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 bg-white">
                                @forelse($transfers as $transfer)
                                    <tr class="hover:bg-slate-50/80 transition">
                                        <!-- Transfer Voucher -->
                                        <td class="py-3 px-4 whitespace-nowrap">
                                            <div class="font-mono font-bold text-slate-900">
                                                {{ $transfer->transfer_number }}
                                            </div>
                                            <div class="text-[11px] text-slate-500 mt-0.5 font-medium">
                                                Req by: <span class="text-slate-700 font-semibold">{{ $transfer->requester?->name ?? 'System' }}</span>
                                            </div>
                                        </td>

                                        <!-- Routing Pathway -->
                                        <td class="py-3 px-4 whitespace-nowrap">
                                            <div class="flex items-center space-x-2">
                                                <span class="px-2.5 py-1 rounded-lg bg-slate-100 border border-slate-200 text-xs font-semibold text-slate-800">
                                                    {{ $transfer->sourceWarehouse?->warehouse_name ?? 'N/A' }}
                                                </span>
                                                <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                                <span class="px-2.5 py-1 rounded-lg bg-indigo-50 border border-indigo-200 text-xs font-semibold text-indigo-900">
                                                    {{ $transfer->destinationWarehouse?->warehouse_name ?? 'N/A' }}
                                                </span>
                                            </div>
                                        </td>

                                        <!-- Items Count -->
                                        <td class="py-3 px-4 text-center whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-700">
                                                {{ $transfer->items->count() }} item(s)
                                            </span>
                                        </td>

                                        <!-- Transfer Date -->
                                        <td class="py-3 px-4 whitespace-nowrap font-mono text-slate-700">
                                            {{ $transfer->transfer_date ? \Carbon\Carbon::parse($transfer->transfer_date)->format('d M Y') : 'N/A' }}
                                        </td>

                                        <!-- Status Badge -->
                                        <td class="py-3 px-4 text-center whitespace-nowrap">
                                            @if($transfer->status === 'DRAFT')
                                                <span class="px-2.5 py-1 text-[10px] font-bold rounded-full bg-slate-100 text-slate-700">Draft</span>
                                            @elseif($transfer->status === 'IN_TRANSIT')
                                                <span class="px-2.5 py-1 text-[10px] font-bold rounded-full bg-amber-100 text-amber-800 flex items-center justify-center gap-1 w-max mx-auto">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span> In-Transit
                                                </span>
                                            @elseif($transfer->status === 'RECEIVED')
                                                <span class="px-2.5 py-1 text-[10px] font-bold rounded-full bg-emerald-100 text-emerald-800">Received</span>
                                            @elseif($transfer->status === 'CANCELLED')
                                                <span class="px-2.5 py-1 text-[10px] font-bold rounded-full bg-rose-100 text-rmsc-red">Cancelled</span>
                                            @endif
                                        </td>

                                        <!-- Actions -->
                                        <td class="py-3 px-4 text-center whitespace-nowrap">
                                            <div class="flex items-center justify-center space-x-1">
                                                <!-- View Details Button -->
                                                <button wire:click="openViewModal({{ $transfer->transfer_id }})" wire:loading.attr="disabled" class="p-1.5 text-slate-500 hover:text-sky-600 hover:bg-sky-50 rounded-lg transition cursor-pointer relative" title="View Voucher Manifest">
                                                    <svg wire:loading.remove wire:target="openViewModal({{ $transfer->transfer_id }})" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                    <svg wire:loading wire:target="openViewModal({{ $transfer->transfer_id }})" class="w-4 h-4 text-sky-600 animate-spin" fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                    </svg>
                                                </button>

                                                @if($transfer->status === 'DRAFT')
                                                    <!-- Edit Button -->
                                                    <button wire:click="openEditModal({{ $transfer->transfer_id }})" class="p-1.5 text-slate-500 hover:text-rmsc-red hover:bg-red-50 rounded-lg transition cursor-pointer" title="Edit Transfer Request">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                    </button>

                                                    <!-- Dispatch Button -->
                                                    <button wire:click="dispatchTransfer({{ $transfer->transfer_id }})" class="px-2 py-1 text-[10px] font-bold rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-800 transition border border-amber-200 flex items-center space-x-1 cursor-pointer" title="Mark as Dispatched">
                                                        <svg class="w-3 h-3 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                                        <span>Dispatch</span>
                                                    </button>
                                                @elseif($transfer->status === 'IN_TRANSIT')
                                                    <!-- Receive Intake Button -->
                                                    <button wire:click="receiveTransfer({{ $transfer->transfer_id }})" class="px-2 py-1 text-[10px] font-bold rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-800 transition border border-emerald-200 flex items-center space-x-1 cursor-pointer" title="Receive Intake & Update Stock Ledgers">
                                                        <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                        <span>Receive</span>
                                                    </button>
                                                @endif

                                                @if($transfer->status !== 'RECEIVED' && $transfer->status !== 'CANCELLED')
                                                    <!-- Cancel Button -->
                                                    <button wire:click="cancelTransfer({{ $transfer->transfer_id }})" class="p-1.5 text-slate-400 hover:text-rmsc-red hover:bg-rose-50 rounded-lg transition cursor-pointer" title="Cancel Transfer">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="py-10 text-center text-slate-400">
                                            <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-2 text-slate-400">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                            </div>
                                            <p class="text-sm font-medium text-slate-600">No stock transfer vouchers found</p>
                                            <p class="text-xs text-slate-400 mt-0.5">Try adjusting your search criteria or create a new inter-warehouse transfer request.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                @if($transfers->hasPages())
                    <div>
                        {{ $transfers->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- CREATE / EDIT TRANSFER MODAL -->
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
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                    </div>
                    <div>
                        <h3 class="font-heading font-bold text-slate-900 text-base">
                            {{ $isEdit ? 'Edit Stock Transfer Voucher' : 'Register New Stock Transfer' }}
                        </h3>
                        <p class="text-xs text-slate-500 font-medium">Select source &amp; destination depots and batch quantities to transfer</p>
                    </div>
                </div>
                <button type="button" @click="openModal = false; $wire.closeModal()" class="text-slate-400 hover:text-slate-600 transition cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Modal Form Body -->
            <form wire:submit.prevent="saveTransfer" class="flex flex-col flex-1 min-h-0 overflow-hidden">
                <div class="p-6 overflow-y-auto flex-1 min-h-0 [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden" style="scrollbar-width: none; -ms-overflow-style: none;">
                    <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 space-y-4">
                        <!-- Voucher Number -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Voucher Number (Auto-Generated)</label>
                            <input type="text" wire:model="transfer_number" readonly class="w-full h-9 px-3 text-xs bg-slate-100/80 border border-slate-200 rounded-xl text-slate-700 font-mono font-bold cursor-not-allowed select-none" />
                            @error('transfer_number') <span class="text-[11px] text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Transfer Date -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Transfer Date *</label>
                            <input type="date" wire:model="transfer_date" class="w-full h-9 px-3 text-xs bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-rmsc-red/10 focus:border-rmsc-red text-slate-800 font-mono transition" />
                            @error('transfer_date') <span class="text-[11px] text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Source Warehouse -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Source Warehouse *</label>
                            <select wire:model="source_warehouse_id" class="w-full h-9 px-3 text-xs bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-rmsc-red/10 focus:border-rmsc-red text-slate-800 font-medium transition cursor-pointer">
                                <option value="">Select Source Depot...</option>
                                @foreach($warehouses as $wh)
                                    <option value="{{ $wh->warehouse_id }}">{{ $wh->warehouse_name }} ({{ $wh->warehouse_code }})</option>
                                @endforeach
                            </select>
                            @error('source_warehouse_id') <span class="text-[11px] text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Destination Warehouse -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Destination Warehouse *</label>
                            <select wire:model="destination_warehouse_id" class="w-full h-9 px-3 text-xs bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-rmsc-red/10 focus:border-rmsc-red text-slate-800 font-medium transition cursor-pointer">
                                <option value="">Select Destination Depot...</option>
                                @foreach($warehouses as $wh)
                                    <option value="{{ $wh->warehouse_id }}">{{ $wh->warehouse_name }} ({{ $wh->warehouse_code }})</option>
                                @endforeach
                            </select>
                            @error('destination_warehouse_id') <span class="text-[11px] text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Line Items Manifest -->
                        <div class="pt-3 border-t border-slate-200 space-y-3">
                            <div class="flex items-center justify-between">
                                <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center space-x-1.5">
                                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                    <span>Transfer Line Items</span>
                                </h4>
                                <button type="button" wire:click="addItem" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition flex items-center space-x-1 cursor-pointer">
                                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    <span>Add Item</span>
                                </button>
                            </div>

                            @error('items') <span class="text-[11px] text-rmsc-red block">{{ $message }}</span> @enderror

                            <div class="space-y-3">
                                @foreach($items as $index => $item)
                                    <div class="p-3 bg-white border border-slate-200 rounded-xl space-y-2.5">
                                        <div>
                                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Stock Batch *</label>
                                            <select wire:model="items.{{ $index }}.batch_id" class="w-full h-9 px-2.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-rmsc-red/10 focus:border-rmsc-red text-slate-800 font-medium transition cursor-pointer">
                                                <option value="">-- Select Batch to Transfer --</option>
                                                @foreach($availableBatches as $batch)
                                                    <option value="{{ $batch->batch_id }}">
                                                        {{ $batch->batch_number }} | {{ $batch->product?->product_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error("items.{$index}.batch_id") <span class="text-[10px] text-rmsc-red block mt-0.5">{{ $message }}</span> @enderror
                                        </div>

                                        <div class="flex items-center justify-between gap-3">
                                            <div class="flex-1">
                                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Transfer Qty *</label>
                                                <input type="number" step="0.01" min="0.01" wire:model="items.{{ $index }}.quantity" placeholder="0.00" class="w-full h-8 px-2.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-rmsc-red/10 focus:border-rmsc-red text-slate-800 font-mono transition" />
                                                @error("items.{$index}.quantity") <span class="text-[10px] text-rmsc-red block mt-0.5">{{ $message }}</span> @enderror
                                            </div>

                                            @if(count($items) > 1)
                                                <div class="pt-4">
                                                    <button type="button" wire:click="removeItem({{ $index }})" class="p-1.5 text-slate-400 hover:text-rmsc-red hover:bg-rose-50 rounded-lg transition cursor-pointer" title="Remove line">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                    </button>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 bg-slate-50/50 border-t border-slate-100 flex items-center justify-end space-x-3 rounded-b-xl shrink-0">
                    <button type="button" @click="openModal = false; $wire.closeModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-xl transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" class="bg-rmsc-red hover:bg-rmsc-red-dark text-white font-semibold text-xs rounded-xl shadow-xs px-4 py-2 transition inline-flex items-center space-x-1.5 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>{{ $isEdit ? 'Save Changes' : 'Create Transfer Voucher' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    <!-- VIEW TRANSFER DETAILS MODAL -->
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
            
            @if($selectedTransferDetails)
                <!-- Modal Header -->
                <div class="px-6 py-4 bg-slate-50/50 border-b border-slate-100 flex items-center justify-between rounded-t-xl shrink-0">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 01-2-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <div>
                            <h3 class="font-heading font-bold text-slate-900 text-base">Transfer Voucher Manifest</h3>
                            <p class="text-xs text-indigo-600 font-mono font-bold">{{ $selectedTransferDetails->transfer_number }}</p>
                        </div>
                    </div>
                    <button type="button" @click="openView = false; $wire.closeViewModal()" class="text-slate-400 hover:text-slate-600 transition cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Modal Content -->
                <div class="p-6 overflow-y-auto flex-1 min-h-0 space-y-5 [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden" style="scrollbar-width: none; -ms-overflow-style: none;">
                    <!-- Route Pathway Card -->
                    <div class="p-4 bg-slate-50/80 rounded-xl border border-slate-200">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 relative">
                            <!-- Source Warehouse Box -->
                            <div class="p-3.5 bg-white rounded-xl border border-slate-200/90 shadow-2xs flex flex-col justify-between">
                                <div>
                                    <div class="flex items-center justify-between mb-1.5">
                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Source Origin</span>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 font-mono">FROM</span>
                                    </div>
                                    <div class="text-sm font-bold text-slate-900 leading-snug">{{ $selectedTransferDetails->sourceWarehouse?->warehouse_name }}</div>
                                </div>
                                <div class="text-xs text-slate-500 font-mono mt-2 pt-2 border-t border-slate-100">
                                    Code: <span class="font-semibold text-slate-700">{{ $selectedTransferDetails->sourceWarehouse?->warehouse_code }}</span>
                                </div>
                            </div>

                            <!-- Destination Warehouse Box -->
                            <div class="p-3.5 bg-indigo-50/40 rounded-xl border border-indigo-100/90 shadow-2xs flex flex-col justify-between">
                                <div>
                                    <div class="flex items-center justify-between mb-1.5">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-100 text-indigo-700 font-mono">TO</span>
                                        <span class="text-[10px] font-bold text-indigo-400 uppercase tracking-wider">Destination Target</span>
                                    </div>
                                    <div class="text-sm font-bold text-indigo-950 leading-snug">{{ $selectedTransferDetails->destinationWarehouse?->warehouse_name }}</div>
                                </div>
                                <div class="text-xs text-indigo-600 font-mono mt-2 pt-2 border-t border-indigo-100/80">
                                    Code: <span class="font-semibold text-indigo-900">{{ $selectedTransferDetails->destinationWarehouse?->warehouse_code }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Overview Summary Grid -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 p-3.5 bg-slate-50/50 rounded-xl border border-slate-200 text-xs">
                        <div>
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Transfer Date</span>
                            <span class="font-bold text-slate-800 font-mono">{{ $selectedTransferDetails->transfer_date ? \Carbon\Carbon::parse($selectedTransferDetails->transfer_date)->format('d M Y') : 'N/A' }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Requested By</span>
                            <span class="font-bold text-slate-800">{{ $selectedTransferDetails->requester?->name ?? 'System User' }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Status</span>
                            <span class="font-bold text-indigo-600">{{ $selectedTransferDetails->status }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Line Items</span>
                            <span class="font-bold text-slate-800">{{ $selectedTransferDetails->items->count() }} item(s)</span>
                        </div>
                    </div>

                    <!-- Line Items Table -->
                    <div>
                        <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-2">Manifest Breakdown</h4>
                        <div class="border border-slate-200 rounded-xl overflow-hidden shadow-2xs">
                            <table class="w-full text-left text-xs">
                                <thead>
                                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold uppercase text-[10px]">
                                        <th class="py-2.5 px-3">Batch Number</th>
                                        <th class="py-2.5 px-3">Product Name</th>
                                        <th class="py-2.5 px-3">SKU</th>
                                        <th class="py-2.5 px-3 text-right">Transfer Qty</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 bg-white text-slate-800">
                                    @foreach($selectedTransferDetails->items as $item)
                                        <tr>
                                            <td class="py-2.5 px-3 font-mono font-bold text-indigo-600">{{ $item->batch?->batch_number }}</td>
                                            <td class="py-2.5 px-3 font-medium text-slate-900">{{ $item->batch?->product?->product_name }}</td>
                                            <td class="py-2.5 px-3 text-slate-500 font-mono">{{ $item->batch?->product?->sku }}</td>
                                            <td class="py-2.5 px-3 text-right font-mono font-bold text-slate-900">
                                                {{ number_format($item->quantity, 2) }} {{ $item->batch?->product?->uom?->uom_code }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 bg-slate-50/50 border-t border-slate-100 flex items-center justify-end rounded-b-xl shrink-0">
                    <button type="button" @click="openView = false; $wire.closeViewModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-xl transition cursor-pointer">
                        Close
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>
