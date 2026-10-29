<div x-data="{ open: @entangle('showModal') }" class="space-y-6">
    <!-- Single Row Header Banner (Title, Search, Filters & Action Button) -->
    <div class="flex flex-wrap items-center justify-between gap-3 pb-4 border-b border-slate-200/80">
        <!-- Title & Subtitle -->
        <div>
            <h1 class="font-heading font-bold text-xl text-slate-900 leading-tight">Delivery Notes</h1>
            <p class="text-[11px] text-slate-500 mt-0.5">Warehouse dispatches, stock releases, and delivery driver manifests</p>
        </div>

        <!-- Controls Group -->
        <div class="flex items-center gap-2 shrink-0 flex-wrap">
            <!-- Search Bar -->
            <div class="relative w-52">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 z-10">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search DN no, SO no, customer..." style="padding-left: 2.5rem !important;" class="w-52 h-9 !pl-10 pr-8 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white focus:bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-medium text-slate-800 transition">
                @if($search)
                    <button wire:click="$set('search', '')" type="button" class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600 transition cursor-pointer" title="Clear search">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                @endif
            </div>

            <!-- Status Filter -->
            <select wire:model.live="status_filter" class="h-9 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white focus:bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-medium text-slate-800 transition pl-3 pr-8 cursor-pointer">
                <option value="">All Statuses</option>
                <option value="DRAFT">Draft</option>
                <option value="DISPATCHED">Dispatched</option>
                <option value="DELIVERED">Delivered</option>
                <option value="CANCELLED">Cancelled</option>
            </select>

            <!-- Branch Filter (HQ only) -->
            @if(auth()->user()?->hasRole('Super Admin') || auth()->user()?->hasRole('HQ Admin'))
            <select wire:model.live="branch_filter" class="h-9 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white focus:bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-medium text-slate-800 transition pl-3 pr-8 cursor-pointer">
                <option value="">All Branches</option>
                @foreach($branches as $b)
                    <option value="{{ $b->branch_id }}">{{ $b->branch_code }} - {{ $b->branch_name }}</option>
                @endforeach
            </select>
            @endif

            <!-- Clear Filters Button -->
            @if($search || $status_filter !== '' || $branch_filter !== '' || $date_from || $date_to)
                <button wire:click="clearFilters" type="button" class="h-9 px-2.5 bg-slate-100 hover:bg-slate-200/80 text-slate-600 hover:text-slate-900 text-xs font-medium rounded-xl transition flex items-center space-x-1 cursor-pointer shrink-0" title="Clear filters">
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    <span>Clear</span>
                </button>
            @endif

            <!-- Add Delivery Note Button -->
            @if(auth()->user()?->hasPermission('Retail & Wholesale Sales', 'CREATE') || auth()->user()?->hasRole('Super Admin') || auth()->user()?->hasRole('HQ Admin') || auth()->user()?->hasRole('Branch Admin'))
            <button wire:click="openCreateModal()" @click="open = true" type="button" class="h-9 px-3 bg-rmsc-red hover:bg-rmsc-red-dark text-white font-semibold text-xs rounded-xl shadow-xs transition flex items-center space-x-1 shrink-0 cursor-pointer">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Add Delivery Note</span>
            </button>
            @endif
        </div>
    </div>

    <!-- Page-level Flash Alerts -->
    @if(session()->has('message'))
        <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-xs font-semibold text-emerald-800 flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span>{{ session('message') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700">&times;</button>
        </div>
    @endif
    @if(session()->has('error'))
        <div class="p-3 bg-red-50 border border-red-200 rounded-xl text-xs font-semibold text-rmsc-red flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <svg class="w-4 h-4 text-rmsc-red shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <span>{{ session('error') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-red-400 hover:text-red-600">&times;</button>
        </div>
    @endif

    <!-- Table Card -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-slate-50 uppercase text-xs font-semibold text-slate-500 border-b border-slate-200/80">
                    <tr>
                        <th class="px-6 py-3.5">DN No</th>
                        <th class="px-6 py-3.5">Sales Order</th>
                        <th class="px-6 py-3.5">Customer</th>
                        <th class="px-6 py-3.5">Branch & Warehouse</th>
                        <th class="px-6 py-3.5">Driver & Vehicle</th>
                        <th class="px-6 py-3.5 text-center">Items</th>
                        <th class="px-6 py-3.5">Status</th>
                        <th class="px-6 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse($deliveryNotes as $dn)
                        <tr wire:key="delivery-note-{{ $dn->delivery_note_id }}" class="hover:bg-slate-50/60 transition group">
                            <td class="px-6 py-4">
                                <a href="{{ route('wholesale.delivery-notes.show', $dn) }}" class="font-mono font-bold text-xs text-rmsc-red hover:underline block">
                                    {{ $dn->dn_no }}
                                </a>
                                <span class="text-[10px] text-slate-400 font-mono">{{ $dn->created_at->format('d M Y') }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <a href="{{ route('wholesale.orders.show', $dn->salesOrder) }}" class="font-mono font-semibold text-xs text-slate-700 hover:underline">
                                    {{ $dn->salesOrder?->so_no ?? '-' }}
                                </a>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-semibold text-slate-900 text-xs">{{ $dn->salesOrder?->customer?->customer_name ?? '-' }}</div>
                                <div class="text-[11px] font-mono text-slate-400">{{ $dn->salesOrder?->customer?->customer_code }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-xs text-slate-800 font-medium">{{ $dn->branch?->branch_name ?? '-' }}</span>
                                <span class="text-[11px] font-mono text-slate-400 block">{{ $dn->warehouse?->warehouse_name ?? '-' }}</span>
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-700">
                                @if($dn->driver_name || $dn->vehicle_no)
                                    <div class="font-medium">{{ $dn->driver_name ?: 'Driver Not Specified' }}</div>
                                    <div class="text-[11px] font-mono text-slate-400">{{ $dn->vehicle_no ?: '-' }}</div>
                                @else
                                    <span class="text-slate-400 italic">Not Assigned</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center font-mono text-xs text-slate-600">
                                <span class="px-2 py-0.5 rounded-md bg-slate-100 font-semibold">{{ $dn->items->count() }}</span>
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $statusPills = [
                                        'DRAFT' => 'bg-slate-100 text-slate-600 border-slate-200',
                                        'DISPATCHED' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        'DELIVERED' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        'CANCELLED' => 'bg-rose-50 text-rose-700 border-rose-200',
                                    ];
                                    $pillClass = $statusPills[$dn->status] ?? 'bg-slate-100 text-slate-600 border-slate-200';
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold border {{ $pillClass }}">
                                    {{ $dn->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end space-x-1">
                                    <!-- View Link -->
                                    <a href="{{ route('wholesale.delivery-notes.show', $dn) }}" class="p-1.5 text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded-lg transition" title="View Details">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </a>

                                    <!-- Print Link -->
                                    <a href="{{ route('wholesale.delivery-notes.print', $dn) }}" target="_blank" class="p-1.5 text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded-lg transition" title="Print Delivery Note">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                    </a>

                                    <!-- Delete Button -->
                                    @can('delete', $dn)
                                        @if($dn->status !== 'DELIVERED')
                                            <button type="button"
                                                wire:click="confirmDeleteItem({{ $dn->delivery_note_id }}, '{{ $dn->dn_no }}')"
                                                wire:loading.attr="disabled"
                                                wire:target="confirmDeleteItem({{ $dn->delivery_note_id }}, '{{ $dn->dn_no }}')"
                                                class="p-1.5 text-slate-400 hover:text-rmsc-red hover:bg-red-50 rounded-lg transition disabled:opacity-50"
                                                title="Delete Delivery Note">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-slate-500">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-10 h-10 text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    <p class="text-sm font-semibold text-slate-700">No delivery notes found.</p>
                                    <p class="text-xs text-slate-400 mt-1">Select a confirmed sales order to create and dispatch a new delivery note.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-200/80">
            {{ $deliveryNotes->links() }}
        </div>
    </div>

    <!-- Wide Modal: Create Delivery Note & Dispatch (max-w-5xl, scrollable body, sticky footer) -->
    @if(auth()->user()?->hasPermission('Retail & Wholesale Sales', 'CREATE') || auth()->user()?->hasRole('Super Admin') || auth()->user()?->hasRole('HQ Admin') || auth()->user()?->hasRole('Branch Admin'))
    <template x-teleport="body">
        <div x-show="open"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 sm:p-6"
             style="display: none;">
            <div @click.away="if (!$wire.showItemDeleteModal) { open = false; $wire.closeModal(); }"
                 class="bg-white rounded-2xl max-w-5xl w-full mx-auto shadow-2xl border border-slate-200 flex flex-col max-h-[90vh]"
                 style="max-width: 1024px; max-height: 90vh;">
                
                <!-- Modal Header -->
                <div class="px-6 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between rounded-t-2xl shrink-0">
                    <div>
                        <h3 class="font-bold text-slate-900 text-base">Create Delivery Note & Dispatch</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Select a confirmed sales order, verify batch allocations, and dispatch delivery</p>
                    </div>
                    <button type="button" @click="open = false; $wire.closeModal()" class="text-slate-400 hover:text-slate-600 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Modal Body (Scrollable) -->
                <form wire:submit.prevent="saveDeliveryNote" id="dnForm" class="p-6 space-y-6 overflow-y-auto flex-1">
                    @if(session()->has('message'))
                        <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-xs font-semibold text-emerald-800">{{ session('message') }}</div>
                    @endif
                    @if(session()->has('error'))
                        <div class="p-3 bg-red-50 border border-red-200 rounded-xl text-xs font-semibold text-rmsc-red">{{ session('error') }}</div>
                    @endif

                    <!-- Header Details Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <!-- Sales Order Select -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Select Sales Order *</label>
                            <select wire:model.live="selected_sales_order_id" class="w-full h-9 rounded-xl border border-slate-200 bg-white px-3 text-xs font-medium text-slate-800 focus:border-rmsc-red focus:ring-rmsc-red">
                                <option value="">Choose Confirmed Order...</option>
                                @foreach($confirmedOrders as $ord)
                                    <option value="{{ $ord->order_id }}">{{ $ord->so_no }} - {{ $ord->customer?->customer_name }} ({{ $ord->branch?->branch_code }})</option>
                                @endforeach
                            </select>
                            @error('selected_sales_order_id') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Driver Name -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Driver Name</label>
                            <input type="text" wire:model="driver_name" placeholder="e.g. Ahmed Al-Balushi" class="w-full h-9 rounded-xl border border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-xs text-slate-800">
                            @error('driver_name') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Vehicle No -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Vehicle Registration / Plate No</label>
                            <input type="text" wire:model="vehicle_no" placeholder="e.g. 12345-A" class="w-full h-9 rounded-xl border border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-xs font-mono text-slate-800">
                            @error('vehicle_no') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Notes (Span 3) -->
                        <div class="sm:col-span-3">
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Dispatch Notes</label>
                            <input type="text" wire:model="notes" placeholder="Gate pass info, delivery instructions, receiving contact..." class="w-full h-9 rounded-xl border border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-xs text-slate-800">
                            @error('notes') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Selected Order Information Banner -->
                    @if($selectedOrder)
                        <div class="p-3 bg-slate-50 border border-slate-200/80 rounded-xl grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                            <div>
                                <span class="text-slate-400 block text-[10px] uppercase font-bold">Customer</span>
                                <span class="font-semibold text-slate-900">{{ $selectedOrder->customer?->customer_name }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[10px] uppercase font-bold">Branch</span>
                                <span class="font-semibold text-slate-900">{{ $selectedOrder->branch?->branch_name }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[10px] uppercase font-bold">Warehouse</span>
                                <span class="font-semibold text-slate-900">{{ $selectedOrder->warehouse?->warehouse_name }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[10px] uppercase font-bold">Order Value</span>
                                <span class="font-mono font-bold text-rmsc-red">{{ number_format($selectedOrder->grand_total, 3) }} OMR</span>
                            </div>
                        </div>

                        <!-- Dispatch Line Items Section -->
                        <div class="border-t border-slate-200/80 pt-4 space-y-3">
                            <div class="flex items-center justify-between">
                                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center gap-2">
                                    <span>Items To Dispatch</span>
                                    <span class="font-mono text-[10px] px-2 py-0.5 bg-slate-100 rounded-md text-slate-600">{{ count($items) }} Products</span>
                                </h4>
                            </div>
                            @error('items') <span class="text-xs text-rmsc-red block">{{ $message }}</span> @enderror

                            <div class="overflow-x-auto rounded-xl border border-slate-200/80">
                                <table class="w-full text-left text-xs">
                                    <thead class="bg-slate-50 uppercase text-[11px] font-semibold text-slate-500 border-b border-slate-200">
                                        <tr>
                                            <th class="px-4 py-2.5">Product</th>
                                            <th class="px-4 py-2.5">Allocated Batch</th>
                                            <th class="px-4 py-2.5 text-right">Ordered Qty</th>
                                            <th class="px-4 py-2.5 text-right">Dispatched Qty</th>
                                            <th class="px-4 py-2.5 text-right">Remaining Qty</th>
                                            <th class="px-4 py-2.5 w-36 text-right">Dispatch Qty *</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach($items as $idx => $row)
                                            <tr wire:key="dn-item-{{ $idx }}" class="hover:bg-slate-50/50">
                                                <td class="px-4 py-3">
                                                    <div class="font-semibold text-slate-900">{{ $row['product_name'] }}</div>
                                                    <div class="text-[10px] font-mono text-slate-400">{{ $row['sku_code'] }}</div>
                                                </td>
                                                <td class="px-4 py-3 font-mono text-slate-600">
                                                    {{ $row['batch_number'] }}
                                                </td>
                                                <td class="px-4 py-3 text-right font-mono text-slate-700">
                                                    {{ number_format($row['ordered_quantity'], 3) }} <span class="text-[10px] text-slate-400">{{ $row['uom_code'] }}</span>
                                                </td>
                                                <td class="px-4 py-3 text-right font-mono text-slate-500">
                                                    {{ number_format($row['dispatched_quantity'], 3) }}
                                                </td>
                                                <td class="px-4 py-3 text-right font-mono font-semibold text-slate-800">
                                                    {{ number_format($row['remaining_quantity'], 3) }}
                                                </td>
                                                <td class="px-4 py-3 text-right">
                                                    <input type="number" step="0.001" min="0" max="{{ $row['remaining_quantity'] }}" wire:model="items.{{ $idx }}.quantity" class="w-full h-8 rounded-lg border border-slate-200 text-xs font-mono text-right focus:border-rmsc-red focus:ring-rmsc-red">
                                                    @error("items.{$idx}.quantity") <span class="text-[10px] text-rmsc-red block mt-0.5">{{ $message }}</span> @enderror
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </form>

                <!-- Modal Sticky Footer -->
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end space-x-3 rounded-b-2xl shrink-0">
                    <button type="button" @click="open = false; $wire.closeModal()" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-800 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" form="dnForm" wire:loading.attr="disabled" wire:target="saveDeliveryNote" class="px-5 py-2 text-xs font-semibold text-white bg-rmsc-red hover:bg-rmsc-red-dark rounded-xl shadow-xs transition cursor-pointer disabled:opacity-50" {{ empty($items) ? 'disabled' : '' }}>
                        Create & Dispatch Delivery
                    </button>
                </div>
            </div>
        </div>
    </template>
    @endif

    <!-- Delete Confirmation Modal (400px centered) -->
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
                    <div class="w-12 h-12 rounded-full bg-red-100 text-rmsc-red mx-auto flex items-center justify-center mb-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 mb-1">Delete Delivery Note?</h3>
                    <p class="text-xs text-slate-500 mb-6">
                        Are you sure you want to delete delivery note <span class="font-bold text-slate-800">{{ $itemToDeleteName }}</span>? This action cannot be undone.
                    </p>
                    <div class="flex items-center justify-center space-x-3">
                        <button type="button" @click="$wire.set('showItemDeleteModal', false)" class="px-4 py-2 text-xs font-semibold text-slate-700 hover:text-slate-900 bg-slate-100 hover:bg-slate-200/80 rounded-xl transition cursor-pointer">
                            Cancel
                        </button>
                        <button type="button" wire:click="executeDeleteItem" wire:loading.attr="disabled" wire:target="executeDeleteItem" class="px-4 py-2 text-xs font-semibold text-white bg-rmsc-red hover:bg-rmsc-red-dark rounded-xl shadow-xs transition cursor-pointer disabled:opacity-50">
                            Yes, Delete Note
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
