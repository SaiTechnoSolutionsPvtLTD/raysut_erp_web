<div x-data="{
        open: @entangle('showModal'),

        // Confirmation dialog
        confirmDialog: { show: false, type: '', id: null, docNo: '', busy: false },
        confirmConfigs: {
            cancel: {
                method: 'confirmCancelOrder',
                title: 'Cancel Sales Order?',
                button: 'Yes, Cancel Order',
                tone: 'amber',
                hint: 'Any allocated duty-paid stock reservations will be released immediately.'
            }
        },
        askConfirm(type, id, docNo) {
            this.confirmDialog = { show: true, type: type, id: id, docNo: docNo, busy: false };
        },
        async runConfirmed() {
            if (this.confirmDialog.busy) return;
            const cfg = this.confirmConfigs[this.confirmDialog.type];
            if (!cfg) return;
            this.confirmDialog.busy = true;
            try {
                await $wire[cfg.method](this.confirmDialog.id);
            } finally {
                this.confirmDialog.show = false;
                this.confirmDialog.busy = false;
            }
        }
    }"
    @keydown.escape.window="if ($wire.showCancelModal) $wire.set('showCancelModal', false); if ($wire.showItemDeleteModal) $wire.set('showItemDeleteModal', false); if ($wire.showConfirmModal) $wire.set('showConfirmModal', false); confirmDialog.show = false"
    class="space-y-6">
    <!-- Single Row Header Banner (Title, Search, Filters & Action Button) -->
    <div class="flex flex-wrap items-center justify-between gap-3 pb-4 border-b border-slate-200/80">
        <!-- Title & Subtitle -->
        <div>
            <h1 class="font-heading font-bold text-xl text-slate-900 leading-tight">Sales Orders</h1>
            <p class="text-[11px] text-slate-500 mt-0.5">Wholesale orders, stock allocations, credit checks, and delivery tracking</p>
        </div>

        <!-- Controls Group -->
        <div class="flex items-center gap-2 shrink-0 flex-wrap">
            <!-- Search Bar -->
            <div class="relative w-52">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 z-10">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search SO no, customer..." style="padding-left: 2.5rem !important;" class="w-52 h-9 !pl-10 pr-8 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white focus:bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-medium text-slate-800 transition">
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
                <option value="CONFIRMED">Confirmed</option>
                <option value="PARTIALLY_DISPATCHED">Partially Dispatched</option>
                <option value="DISPATCHED">Dispatched</option>
                <option value="INVOICED">Invoiced</option>
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

            <!-- Add Sales Order Button -->
            @if(auth()->user()?->hasPermission('Retail & Wholesale Sales', 'CREATE') || auth()->user()?->hasRole('Super Admin') || auth()->user()?->hasRole('HQ Admin') || auth()->user()?->hasRole('Branch Admin'))
            <button wire:click="openCreateModal" @click="open = true" type="button" class="h-9 px-3 bg-rmsc-red hover:bg-rmsc-red-dark text-white font-semibold text-xs rounded-xl shadow-xs transition flex items-center space-x-1 shrink-0 cursor-pointer">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Add Sales Order</span>
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
                        <th class="px-6 py-3.5">SO No</th>
                        <th class="px-6 py-3.5">Customer</th>
                        <th class="px-6 py-3.5">Branch & Warehouse</th>
                        <th class="px-6 py-3.5 text-center">Items</th>
                        <th class="px-6 py-3.5 text-right">Subtotal</th>
                        <th class="px-6 py-3.5 text-right">Grand Total</th>
                        <th class="px-6 py-3.5">Credit Status</th>
                        <th class="px-6 py-3.5">Order Status</th>
                        <th class="px-6 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse($orders as $o)
                        <tr wire:key="sales-order-{{ $o->order_id }}" class="hover:bg-slate-50/60 transition group">
                            <td class="px-6 py-4">
                                <a href="{{ route('wholesale.orders.show', $o) }}" class="font-mono font-bold text-xs text-rmsc-red hover:underline block">
                                    {{ $o->so_no }}
                                </a>
                                <span class="text-[10px] text-slate-400 font-mono">{{ $o->created_at->format('d M Y') }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-semibold text-slate-900 text-xs">{{ $o->customer?->customer_name ?? '-' }}</div>
                                <div class="text-[11px] font-mono text-slate-400">{{ $o->customer?->customer_code }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-xs text-slate-800 font-medium">{{ $o->branch?->branch_name ?? '-' }}</span>
                                <span class="text-[11px] font-mono text-slate-400 block">{{ $o->warehouse?->warehouse_name ?? '-' }}</span>
                            </td>
                            <td class="px-6 py-4 text-center font-mono text-xs text-slate-600">
                                <span class="px-2 py-0.5 rounded-md bg-slate-100 font-semibold">{{ $o->items->count() }}</span>
                            </td>
                            <td class="px-6 py-4 font-mono text-xs text-slate-700 text-right">
                                {{ number_format($o->subtotal, 3) }}
                            </td>
                            <td class="px-6 py-4 font-mono text-xs font-bold text-slate-900 text-right">
                                {{ number_format($o->grand_total, 3) }} <span class="text-[10px] font-sans font-normal text-slate-400">OMR</span>
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $creditBadge = match($o->credit_check_status) {
                                        'PASSED' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        'OVERRIDDEN' => 'bg-amber-50 text-amber-700 border-amber-200',
                                        'BLOCKED' => 'bg-rose-50 text-rose-700 border-rose-200',
                                        default => 'bg-slate-100 text-slate-600 border-slate-200',
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-mono font-semibold border {{ $creditBadge }}">
                                    {{ $o->credit_check_status }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $statusPills = [
                                        'DRAFT' => 'bg-slate-100 text-slate-600 border-slate-200',
                                        'CONFIRMED' => 'bg-blue-50 text-blue-700 border-blue-200',
                                        'PARTIALLY_DISPATCHED' => 'bg-amber-50 text-amber-700 border-amber-200',
                                        'DISPATCHED' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        'INVOICED' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        'CANCELLED' => 'bg-rose-50 text-rose-700 border-rose-200',
                                    ];
                                    $pillClass = $statusPills[$o->status] ?? 'bg-slate-100 text-slate-600 border-slate-200';
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold border {{ $pillClass }}">
                                    {{ $o->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end space-x-1">
                                    <!-- View Link -->
                                    <a href="{{ route('wholesale.orders.show', $o) }}" class="p-1.5 text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded-lg transition" title="View Details">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </a>

                                    <!-- Status Actions -->
                                    @can('confirm', $o)
                                        @if($o->status === 'DRAFT')
                                            <!-- Confirm Button -->
                                            <button type="button"
                                                wire:click="openConfirmModal({{ $o->order_id }})"
                                                wire:loading.attr="disabled"
                                                wire:target="openConfirmModal({{ $o->order_id }})"
                                                class="p-1.5 text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded-lg transition disabled:opacity-50"
                                                title="Confirm Order & Reserve Stock">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            </button>
                                        @endif
                                    @endcan

                                    @can('cancel', $o)
                                        @if(in_array($o->status, ['DRAFT', 'CONFIRMED']))
                                            <!-- Cancel Button -->
                                            <button type="button"
                                                wire:click="confirmCancelOrder({{ $o->order_id }})"
                                                wire:loading.attr="disabled"
                                                wire:target="confirmCancelOrder({{ $o->order_id }})"
                                                class="p-1.5 text-amber-600 hover:text-amber-800 hover:bg-amber-50 rounded-lg transition disabled:opacity-50 cursor-pointer"
                                                title="Cancel Sales Order">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                            </button>
                                        @endif
                                    @endcan

                                    <!-- Delete Button -->
                                    @can('delete', $o)
                                        @if(in_array($o->status, ['DRAFT', 'CANCELLED']))
                                            <button type="button"
                                                wire:click="confirmDeleteItem({{ $o->order_id }}, '{{ $o->so_no }}')"
                                                wire:loading.attr="disabled"
                                                wire:target="confirmDeleteItem({{ $o->order_id }}, '{{ $o->so_no }}')"
                                                class="p-1.5 text-slate-400 hover:text-rmsc-red hover:bg-red-50 rounded-lg transition disabled:opacity-50"
                                                title="Delete Order">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-12 text-center text-slate-500">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-10 h-10 text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    <p class="text-sm font-semibold text-slate-700">No sales orders found.</p>
                                    <p class="text-xs text-slate-400 mt-1">Try adjusting your search criteria or create a new sales order.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-200/80">
            {{ $orders->links() }}
        </div>
    </div>

    <!-- Wide Modal: Create Sales Order (max-w-5xl, scrollable body, sticky footer) -->
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
            <div @click.away="if (!$wire.showItemDeleteModal && !$wire.showConfirmModal && !$wire.showCancelModal) { open = false; $wire.closeModal(); }"
                 class="bg-white rounded-2xl max-w-5xl w-full mx-auto shadow-2xl border border-slate-200 flex flex-col max-h-[90vh]"
                 style="max-width: 1024px; max-height: 90vh;">
                
                <!-- Modal Header -->
                <div class="px-6 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between rounded-t-2xl shrink-0">
                    <div>
                        <h3 class="font-bold text-slate-900 text-base">Create Wholesale Sales Order</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Direct sales order creation, warehouse fulfillment, and stock reservation</p>
                    </div>
                    <button type="button" @click="open = false; $wire.closeModal()" class="text-slate-400 hover:text-slate-600 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Modal Body (Scrollable) -->
                <form wire:submit.prevent="saveSalesOrder" id="salesOrderForm" class="p-6 space-y-6 overflow-y-auto flex-1">
                    <!-- Session Banners inside form -->
                    @if(session()->has('message'))
                        <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-xs font-semibold text-emerald-800">{{ session('message') }}</div>
                    @endif
                    @if(session()->has('error'))
                        <div class="p-3 bg-red-50 border border-red-200 rounded-xl text-xs font-semibold text-rmsc-red">{{ session('error') }}</div>
                    @endif

                    <!-- Header Details Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <!-- Customer Select -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Customer *</label>
                            <select wire:model.live="customer_id" class="w-full h-9 rounded-xl border border-slate-200 bg-white px-3 text-xs font-medium text-slate-800 focus:border-rmsc-red focus:ring-rmsc-red">
                                <option value="">Select Wholesale Customer</option>
                                @foreach($customers as $c)
                                    <option value="{{ $c->customer_id }}">{{ $c->customer_name }} ({{ $c->customer_code }})</option>
                                @endforeach
                            </select>
                            @error('customer_id') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Origin Branch Select -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Branch *</label>
                            @if(auth()->user()?->hasRole('Super Admin') || auth()->user()?->hasRole('HQ Admin'))
                                <select wire:model.live="branch_id" class="w-full h-9 rounded-xl border border-slate-200 bg-white px-3 text-xs font-medium text-slate-800 focus:border-rmsc-red focus:ring-rmsc-red">
                                    @foreach($branches as $b)
                                        <option value="{{ $b->branch_id }}">{{ $b->branch_name }} ({{ $b->branch_code }})</option>
                                    @endforeach
                                </select>
                            @else
                                <input type="text" value="{{ auth()->user()?->defaultBranch?->branch_name ?? 'My Branch' }}" readonly class="w-full h-9 rounded-xl border border-slate-200 bg-slate-100 text-xs font-medium text-slate-600 cursor-not-allowed">
                            @endif
                            @error('branch_id') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Fulfilling Warehouse (Non-bonded only) -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Fulfillment Warehouse *</label>
                            <select wire:model="warehouse_id" class="w-full h-9 rounded-xl border border-slate-200 bg-white px-3 text-xs font-medium text-slate-800 focus:border-rmsc-red focus:ring-rmsc-red">
                                <option value="">Select Warehouse</option>
                                @foreach($warehouses as $wh)
                                    <option value="{{ $wh->warehouse_id }}">{{ $wh->warehouse_name }} ({{ $wh->warehouse_code }})</option>
                                @endforeach
                            </select>
                            @error('warehouse_id') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Payment Terms Days -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Payment Terms (Days)</label>
                            <input type="number" min="0" wire:model="payment_terms_days" placeholder="30" class="w-full h-9 rounded-xl border border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-xs font-mono text-slate-800">
                            @error('payment_terms_days') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Notes / Special Instructions (Span 2) -->
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Notes / Shipping Instructions</label>
                            <input type="text" wire:model="notes" placeholder="Delivery instructions, reference PO number..." class="w-full h-9 rounded-xl border border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-xs text-slate-800">
                            @error('notes') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Line Items Section -->
                    <div class="border-t border-slate-200/80 pt-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center gap-2">
                                <span>Sales Order Line Items</span>
                                <span class="font-mono text-[10px] px-2 py-0.5 bg-slate-100 rounded-md text-slate-600">{{ count($items) }} Rows</span>
                            </h4>
                            <button type="button" wire:click="addItem" class="px-2.5 py-1 text-xs font-semibold text-rmsc-red hover:bg-red-50 border border-rmsc-red/30 rounded-lg transition flex items-center space-x-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                <span>Add Product</span>
                            </button>
                        </div>
                        @error('items') <span class="text-xs text-rmsc-red block">{{ $message }}</span> @enderror

                        <div class="overflow-x-auto rounded-xl border border-slate-200/80">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-slate-50 uppercase text-[11px] font-semibold text-slate-500 border-b border-slate-200">
                                    <tr>
                                        <th class="px-3 py-2.5 w-5/12">Product *</th>
                                        <th class="px-3 py-2.5 w-1/12">UOM</th>
                                        <th class="px-3 py-2.5 w-2/12">Qty *</th>
                                        <th class="px-3 py-2.5 w-2/12">Unit Price *</th>
                                        <th class="px-3 py-2.5 w-1/12">VAT %</th>
                                        <th class="px-3 py-2.5 w-2/12 text-right">Line Total</th>
                                        <th class="px-2 py-2.5 w-10 text-center"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach($items as $idx => $row)
                                        <tr wire:key="line-item-{{ $idx }}" class="hover:bg-slate-50/50">
                                            <td class="px-3 py-2">
                                                <select wire:model.live="items.{{ $idx }}.product_id" class="w-full h-8 rounded-lg border border-slate-200 text-xs font-medium focus:border-rmsc-red focus:ring-rmsc-red">
                                                    <option value="">Select Product...</option>
                                                    @foreach($products as $p)
                                                        <option value="{{ $p->product_id }}">{{ $p->sku_code }} - {{ $p->product_name }}</option>
                                                    @endforeach
                                                </select>
                                                @error("items.{$idx}.product_id") <span class="text-[10px] text-rmsc-red block mt-0.5">{{ $message }}</span> @enderror
                                            </td>
                                            <td class="px-3 py-2 font-mono text-slate-600">
                                                {{ $row['uom_code'] ?: '-' }}
                                            </td>
                                            <td class="px-3 py-2">
                                                <input type="number" step="0.001" min="0.001" wire:model.live.debounce.300ms="items.{{ $idx }}.quantity" class="w-full h-8 rounded-lg border border-slate-200 text-xs font-mono focus:border-rmsc-red focus:ring-rmsc-red">
                                                @error("items.{$idx}.quantity") <span class="text-[10px] text-rmsc-red block mt-0.5">{{ $message }}</span> @enderror
                                            </td>
                                            <td class="px-3 py-2">
                                                <input type="number" step="0.001" min="0" wire:model.live.debounce.300ms="items.{{ $idx }}.unit_price" class="w-full h-8 rounded-lg border border-slate-200 text-xs font-mono focus:border-rmsc-red focus:ring-rmsc-red">
                                                @error("items.{$idx}.unit_price") <span class="text-[10px] text-rmsc-red block mt-0.5">{{ $message }}</span> @enderror
                                            </td>
                                            <td class="px-3 py-2 font-mono text-slate-600">
                                                {{ number_format($row['vat_rate'], 1) }}%
                                            </td>
                                            <td class="px-3 py-2 text-right font-mono font-semibold text-slate-900">
                                                {{ number_format($row['line_total'], 3) }}
                                            </td>
                                            <td class="px-2 py-2 text-center">
                                                @if(count($items) > 1)
                                                    <button type="button" wire:click="removeItem({{ $idx }})" class="text-slate-400 hover:text-rmsc-red p-1 rounded transition" title="Remove line">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                    </button>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Live Totals Summary Card -->
                        <div class="flex justify-end pt-2">
                            <div class="w-72 bg-slate-50 p-4 rounded-xl border border-slate-200/80 space-y-2 text-xs">
                                <div class="flex justify-between text-slate-600">
                                    <span>Subtotal:</span>
                                    <span class="font-mono font-medium">{{ number_format($subtotal, 3) }} OMR</span>
                                </div>
                                <div class="flex justify-between text-slate-600">
                                    <span>VAT Total:</span>
                                    <span class="font-mono font-medium">{{ number_format($vat_total, 3) }} OMR</span>
                                </div>
                                <div class="border-t border-slate-200 pt-2 flex justify-between font-bold text-slate-900 text-sm">
                                    <span>Grand Total:</span>
                                    <span class="font-mono text-rmsc-red">{{ number_format($grand_total, 3) }} OMR</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>

                <!-- Modal Sticky Footer -->
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end space-x-3 rounded-b-2xl shrink-0">
                    <button type="button" @click="open = false; $wire.closeModal()" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-800 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" form="salesOrderForm" wire:loading.attr="disabled" wire:target="saveSalesOrder" class="px-5 py-2 text-xs font-semibold text-white bg-rmsc-red hover:bg-rmsc-red-dark rounded-xl shadow-xs transition cursor-pointer disabled:opacity-50">
                        Create Sales Order
                    </button>
                </div>
            </div>
        </div>
    </template>
    @endif

    <!-- Confirm / Supervisor Override Modal -->
    <template x-teleport="body">
        <div x-show="$wire.showConfirmModal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
             style="display: none;">
            <div @click.away="$wire.set('showConfirmModal', false)"
                 class="bg-white rounded-2xl max-w-lg w-full mx-auto shadow-2xl border border-slate-200 p-6 space-y-4"
                 style="max-width: 512px;">
                <div class="flex items-center space-x-3">
                    <div class="p-2.5 {{ $requiresOverride ? 'bg-amber-50 text-amber-600' : 'bg-blue-50 text-blue-600' }} rounded-xl">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-900 text-sm">Confirm Sales Order & Reserve Stock</h3>
                        <p class="text-xs text-slate-500">
                            {{ $requiresOverride ? 'Credit check warning - supervisor override required' : 'Credit check passed - order ready for confirmation' }}
                        </p>
                    </div>
                </div>

                <!-- Credit Check Result Box -->
                @if($creditCheckResult)
                    <div class="p-3 rounded-xl border text-xs {{ $creditCheckResult['allowed'] ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-amber-50 border-amber-200 text-amber-800' }}">
                        <div class="font-semibold mb-1 flex items-center justify-between">
                            <span>Credit Check: {{ $creditCheckResult['allowed'] ? 'PASSED' : 'ACTION REQUIRED' }}</span>
                            <span class="font-mono text-[11px]">{{ number_format($confirmOrder?->grand_total ?? 0, 3) }} OMR</span>
                        </div>
                        <p class="text-[11px]">{{ $creditCheckResult['reason'] }}</p>
                    </div>
                @endif

                @if($requiresOverride)
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Supervisor Override Reason *</label>
                        <textarea wire:model="overrideReason" rows="3" placeholder="State reason for approving credit limit override (e.g. Bank guarantee provided, HQ exception granted)..." class="w-full rounded-xl border border-slate-200 text-xs focus:border-rmsc-red focus:ring-rmsc-red"></textarea>
                        @error('overrideReason') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                    </div>
                @endif

                <div class="flex items-center justify-end space-x-2 pt-2">
                    <button type="button" @click="$wire.set('showConfirmModal', false)" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-800 bg-white border border-slate-200 rounded-xl transition">
                        Cancel
                    </button>
                    <button type="button" wire:click="executeConfirmOrder" wire:loading.attr="disabled" wire:target="executeConfirmOrder" class="px-4 py-2 text-xs font-semibold text-white {{ $requiresOverride ? 'bg-amber-600 hover:bg-amber-700' : 'bg-blue-600 hover:bg-blue-700' }} rounded-xl transition disabled:opacity-50">
                        {{ $requiresOverride ? 'Authorize & Confirm' : 'Confirm & Reserve Stock' }}
                    </button>
                </div>
            </div>
        </div>
    </template>

    <!-- Cancel Order Confirmation Modal (400px centered) -->
    <template x-teleport="body">
        <div x-show="$wire.showCancelModal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
             style="display: none;">
            <div @click.away="$wire.set('showCancelModal', false)"
                 class="bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden transform transition-all w-full mx-auto"
                 style="max-width: 400px;">
                <div class="p-6 text-center">
                    <div class="w-12 h-12 rounded-full bg-amber-100 text-amber-600 mx-auto flex items-center justify-center mb-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 mb-1">Cancel Sales Order?</h3>
                    <p class="text-xs text-slate-500 mb-6">
                        Are you sure you want to cancel this sales order? Any allocated duty-paid stock reservations will be released immediately back into warehouse available stock.
                    </p>
                    <div class="flex items-center justify-center space-x-3">
                        <button type="button" @click="$wire.set('showCancelModal', false)" class="px-4 py-2 text-xs font-semibold text-slate-700 hover:text-slate-900 bg-slate-100 hover:bg-slate-200/80 rounded-xl transition cursor-pointer">
                            Keep Order
                        </button>
                        <button type="button" wire:click="executeCancelOrder" wire:loading.attr="disabled" wire:target="executeCancelOrder" class="px-4 py-2 text-xs font-semibold text-white bg-amber-600 hover:bg-amber-700 rounded-xl shadow-xs transition cursor-pointer disabled:opacity-50 flex items-center gap-1.5">
                            <svg wire:loading wire:target="executeCancelOrder" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle style="opacity:.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path style="opacity:.85;" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                            <span wire:loading.remove wire:target="executeCancelOrder">Yes, Cancel Order</span>
                            <span wire:loading wire:target="executeCancelOrder">Cancelling...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>

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
                    <h3 class="text-base font-bold text-slate-900 mb-1">Delete Sales Order?</h3>
                    <p class="text-xs text-slate-500 mb-6">
                        Are you sure you want to delete sales order <span class="font-bold text-slate-800">{{ $itemToDeleteName }}</span>? This action cannot be undone.
                    </p>
                    <div class="flex items-center justify-center space-x-3">
                        <button type="button" @click="$wire.set('showItemDeleteModal', false)" class="px-4 py-2 text-xs font-semibold text-slate-700 hover:text-slate-900 bg-slate-100 hover:bg-slate-200/80 rounded-xl transition cursor-pointer">
                            Cancel
                        </button>
                        <button type="button" wire:click="executeDeleteItem" wire:loading.attr="disabled" wire:target="executeDeleteItem" class="px-4 py-2 text-xs font-semibold text-white bg-rmsc-red hover:bg-rmsc-red-dark rounded-xl shadow-xs transition cursor-pointer disabled:opacity-50">
                            Yes, Delete Order
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
