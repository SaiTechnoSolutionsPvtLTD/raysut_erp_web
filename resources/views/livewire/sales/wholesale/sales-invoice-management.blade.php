<div x-data="{ open: @entangle('showModal') }" class="space-y-6">
    <!-- Single Row Header Banner (Title, Search, Filters & Action Button) -->
    <div class="flex flex-wrap items-center justify-between gap-3 pb-4 border-b border-slate-200/80">
        <!-- Title & Subtitle -->
        <div>
            <h1 class="font-heading font-bold text-xl text-slate-900 leading-tight">Sales Invoices</h1>
            <p class="text-[11px] text-slate-500 mt-0.5">Wholesale tax invoices, credit management, payment collections, and accounting ledgers</p>
        </div>

        <!-- Controls Group -->
        <div class="flex items-center gap-2 shrink-0 flex-wrap">
            <!-- Search Bar -->
            <div class="relative w-52">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 z-10">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search invoice no, customer..." style="padding-left: 2.5rem !important;" class="w-52 h-9 !pl-10 pr-8 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white focus:bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-medium text-slate-800 transition">
                @if($search)
                    <button wire:click="$set('search', '')" type="button" class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600 transition cursor-pointer" title="Clear search">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                @endif
            </div>

            <!-- Status Filter -->
            <select wire:model.live="status_filter" class="h-9 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white focus:bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-medium text-slate-800 transition pl-3 pr-8 cursor-pointer">
                <option value="">All Statuses</option>
                <option value="POSTED">Posted</option>
                <option value="PAID">Paid</option>
                <option value="PARTIALLY_PAID">Partially Paid</option>
                <option value="VOIDED">Voided</option>
                <option value="DRAFT">Draft</option>
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

            <!-- Add Invoice Button -->
            @if(auth()->user()?->hasPermission('Retail & Wholesale Sales', 'CREATE') || auth()->user()?->hasRole('Super Admin') || auth()->user()?->hasRole('HQ Admin') || auth()->user()?->hasRole('Branch Admin'))
            <button wire:click="openCreateModal" wire:loading.attr="disabled" wire:target="openCreateModal" type="button" class="h-9 px-3 bg-rmsc-red hover:bg-rmsc-red-dark text-white font-semibold text-xs rounded-xl shadow-xs transition flex items-center space-x-1 shrink-0 cursor-pointer">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Post Invoice</span>
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
                        <th class="px-6 py-3.5">Invoice No</th>
                        <th class="px-6 py-3.5">Customer</th>
                        <th class="px-6 py-3.5">Branch</th>
                        <th class="px-6 py-3.5">Order / DN Ref</th>
                        <th class="px-6 py-3.5">Due Date</th>
                        <th class="px-6 py-3.5 text-right">Grand Total</th>
                        <th class="px-6 py-3.5 text-right">Balance Due</th>
                        <th class="px-6 py-3.5">Status</th>
                        <th class="px-6 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse($invoices as $inv)
                        <tr wire:key="sales-invoice-{{ $inv->invoice_id }}" class="hover:bg-slate-50/60 transition group">
                            <td class="px-6 py-4">
                                <a href="{{ route('wholesale.invoices.show', $inv) }}" class="font-mono font-bold text-xs text-rmsc-red hover:underline block">
                                    {{ $inv->invoice_no }}
                                </a>
                                <span class="text-[10px] text-slate-400 font-mono">{{ $inv->invoice_date->format('d M Y') }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-semibold text-slate-900 text-xs">{{ $inv->customer?->customer_name ?? '-' }}</div>
                                <div class="text-[11px] font-mono text-slate-400">{{ $inv->customer?->customer_code }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-xs text-slate-800 font-medium">{{ $inv->branch?->branch_name ?? '-' }}</span>
                                <span class="text-[11px] font-mono text-slate-400 block">{{ $inv->branch?->branch_code }}</span>
                            </td>
                            <td class="px-6 py-4 text-xs font-mono text-slate-700">
                                @if($inv->deliveryNote)
                                    <div>DN: <a href="{{ route('wholesale.delivery-notes.show', $inv->deliveryNote) }}" class="text-rmsc-red hover:underline">{{ $inv->deliveryNote->dn_no }}</a></div>
                                @endif
                                @if($inv->salesOrder)
                                    <div class="text-slate-500">SO: <a href="{{ route('wholesale.orders.show', $inv->salesOrder) }}" class="hover:underline">{{ $inv->salesOrder->so_no }}</a></div>
                                @endif
                            </td>
                            <td class="px-6 py-4 font-mono text-xs text-slate-600">
                                {{ $inv->due_date ? $inv->due_date->format('d M Y') : '-' }}
                            </td>
                            <td class="px-6 py-4 font-mono text-xs font-semibold text-slate-900 text-right">
                                {{ number_format($inv->grand_total, 3) }}
                            </td>
                            <td class="px-6 py-4 font-mono text-xs text-right">
                                <span class="{{ (float)$inv->balance_due > 0 ? 'text-amber-700 font-bold' : 'text-emerald-700 font-semibold' }}">
                                    {{ number_format($inv->balance_due, 3) }}
                                </span> <span class="text-[10px] font-sans text-slate-400">OMR</span>
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $statusPills = [
                                        'DRAFT' => 'bg-slate-100 text-slate-600 border-slate-200',
                                        'POSTED' => 'bg-blue-50 text-blue-700 border-blue-200',
                                        'PAID' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        'PARTIALLY_PAID' => 'bg-amber-50 text-amber-700 border-amber-200',
                                        'VOIDED' => 'bg-rose-50 text-rose-700 border-rose-200',
                                    ];
                                    $pillClass = $statusPills[$inv->status] ?? 'bg-slate-100 text-slate-600 border-slate-200';
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold border {{ $pillClass }}">
                                    {{ $inv->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end space-x-1">
                                    <!-- View Link -->
                                    <a href="{{ route('wholesale.invoices.show', $inv) }}" class="p-1.5 text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded-lg transition" title="View Details">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </a>

                                    <!-- Print Link -->
                                    <a href="{{ route('wholesale.invoices.print', $inv) }}" target="_blank" class="p-1.5 text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded-lg transition" title="Print Invoice">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                    </a>

                                    <!-- Record Payment Button -->
                                    @can('recordPayment', $inv)
                                        @if(in_array($inv->status, ['POSTED', 'PARTIALLY_PAID']))
                                            <button type="button" wire:click="openPaymentModal({{ $inv->invoice_id }})" wire:loading.attr="disabled" wire:target="openPaymentModal({{ $inv->invoice_id }})" class="p-1.5 text-emerald-600 hover:text-emerald-800 hover:bg-emerald-50 rounded-lg transition cursor-pointer" title="Record Payment">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                            </button>
                                        @endif
                                    @endcan

                                    <!-- Void Button -->
                                    @can('void', $inv)
                                        @if(in_array($inv->status, ['POSTED', 'PARTIALLY_PAID']))
                                            <button type="button" wire:click="openVoidModal({{ $inv->invoice_id }})" wire:loading.attr="disabled" wire:target="openVoidModal({{ $inv->invoice_id }})" class="p-1.5 text-rose-600 hover:text-rose-800 hover:bg-rose-50 rounded-lg transition cursor-pointer" title="Void Invoice">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
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
                                    <p class="text-sm font-semibold text-slate-700">No sales invoices found.</p>
                                    <p class="text-xs text-slate-400 mt-1">Post a new invoice from an active delivery note or sales order.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-200/80">
            {{ $invoices->links() }}
        </div>
    </div>

    <!-- Modal: Post Invoice from DN or SO (max-w-2xl) -->
    @if(auth()->user()?->hasPermission('Retail & Wholesale Sales', 'CREATE') || auth()->user()?->hasRole('Super Admin') || auth()->user()?->hasRole('HQ Admin') || auth()->user()?->hasRole('Branch Admin'))
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
            <div @click.away="open = false; $wire.closeModal()"
                 class="bg-white rounded-2xl max-w-2xl w-full mx-auto shadow-2xl border border-slate-200 p-6 space-y-4"
                 style="max-width: 672px;">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="font-bold text-slate-900 text-base">Generate Wholesale Tax Invoice</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Post invoice from dispatched delivery note or confirmed sales order</p>
                    </div>
                    <button type="button" @click="open = false; $wire.closeModal()" class="text-slate-400 hover:text-slate-600 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Source Switcher Tabs -->
                <div class="flex items-center space-x-2 bg-slate-100 p-1 rounded-xl text-xs font-semibold">
                    <button type="button" wire:click="$set('sourceType', 'DELIVERY_NOTE')" class="flex-1 py-1.5 rounded-lg transition {{ $sourceType === 'DELIVERY_NOTE' ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-600 hover:text-slate-900' }}">
                        From Dispatched Delivery Note (Standard)
                    </button>
                    <button type="button" wire:click="$set('sourceType', 'SALES_ORDER')" class="flex-1 py-1.5 rounded-lg transition {{ $sourceType === 'SALES_ORDER' ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-600 hover:text-slate-900' }}">
                        From Confirmed Sales Order (Direct)
                    </button>
                </div>

                @if($sourceType === 'DELIVERY_NOTE')
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Select Dispatched Delivery Note *</label>
                        <select wire:model="selectedSourceId" class="w-full h-9 rounded-xl border border-slate-200 text-xs font-medium focus:border-rmsc-red focus:ring-rmsc-red">
                            <option value="">Select Delivery Note...</option>
                            @foreach($unInvoicedDns as $dn)
                                <option value="{{ $dn->delivery_note_id }}">{{ $dn->dn_no }} - {{ $dn->salesOrder?->customer?->customer_name }} (SO: {{ $dn->salesOrder?->so_no }})</option>
                            @endforeach
                        </select>
                        @error('selectedSourceId') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                    </div>
                @else
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Select Confirmed Sales Order *</label>
                        <select wire:model="selectedSourceId" class="w-full h-9 rounded-xl border border-slate-200 text-xs font-medium focus:border-rmsc-red focus:ring-rmsc-red">
                            <option value="">Select Sales Order...</option>
                            @foreach($unInvoicedOrders as $so)
                                <option value="{{ $so->order_id }}">{{ $so->so_no }} - {{ $so->customer?->customer_name }} ({{ number_format($so->grand_total, 3) }} OMR)</option>
                            @endforeach
                        </select>
                        @error('selectedSourceId') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                    </div>
                @endif

                <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="open = false; $wire.closeModal()" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-800 bg-white border border-slate-200 rounded-xl transition">
                        Cancel
                    </button>
                    <button type="button" wire:click="postInvoice" wire:loading.attr="disabled" wire:target="postInvoice" class="px-5 py-2 text-xs font-semibold text-white bg-rmsc-red hover:bg-rmsc-red-dark rounded-xl shadow-xs transition">
                        Generate & Post Invoice
                    </button>
                </div>
            </div>
        </div>
    </template>
    @endif

    <!-- Record Payment Modal -->
    <template x-teleport="body">
        <div x-show="$wire.showPaymentModal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
             style="display: none;">
            <div @click.away="$wire.set('showPaymentModal', false)"
                 class="bg-white rounded-2xl max-w-lg w-full mx-auto shadow-2xl border border-slate-200 p-6 space-y-4"
                 style="max-width: 512px;">
                <div class="flex items-center space-x-3 pb-3 border-b border-slate-100">
                    <div class="p-2.5 bg-emerald-50 rounded-xl text-emerald-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-900 text-sm">Record Customer Payment</h3>
                        <p class="text-xs text-slate-500 font-mono">{{ $paymentInvoice?->invoice_no }} (Balance: {{ number_format($paymentInvoice?->balance_due ?? 0, 3) }} OMR)</p>
                    </div>
                </div>

                <div class="space-y-3">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Amount (OMR) *</label>
                            <input type="number" step="0.001" min="0.001" max="{{ $paymentInvoice?->balance_due }}" wire:model="paymentAmount" class="w-full h-9 rounded-xl border border-slate-200 text-xs font-mono font-semibold focus:border-rmsc-red focus:ring-rmsc-red">
                            @error('paymentAmount') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Payment Method *</label>
                            <select wire:model="paymentMethod" class="w-full h-9 rounded-xl border border-slate-200 text-xs font-medium focus:border-rmsc-red focus:ring-rmsc-red">
                                <option value="BANK_TRANSFER">Bank Transfer</option>
                                <option value="CHEQUE">Cheque</option>
                                <option value="CARD">Card</option>
                                <option value="CASH">Cash</option>
                            </select>
                            @error('paymentMethod') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Payment Reference / Txn ID</label>
                        <input type="text" wire:model="paymentReference" placeholder="e.g. Bank slip #, cheque #..." class="w-full h-9 rounded-xl border border-slate-200 text-xs font-mono focus:border-rmsc-red focus:ring-rmsc-red">
                        @error('paymentReference') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Notes</label>
                        <input type="text" wire:model="paymentNotes" placeholder="Additional notes or reconciliation details..." class="w-full h-9 rounded-xl border border-slate-200 text-xs focus:border-rmsc-red focus:ring-rmsc-red">
                    </div>
                </div>

                <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="$wire.set('showPaymentModal', false)" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-800 bg-white border border-slate-200 rounded-xl transition">
                        Cancel
                    </button>
                    <button type="button" wire:click="recordPayment" wire:loading.attr="disabled" wire:target="recordPayment" class="px-5 py-2 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl transition">
                        Save Payment
                    </button>
                </div>
            </div>
        </div>
    </template>

    <!-- Void Invoice Modal -->
    <template x-teleport="body">
        <div x-show="$wire.showVoidModal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
             style="display: none;">
            <div @click.away="$wire.set('showVoidModal', false)"
                 class="bg-white rounded-2xl max-w-md w-full mx-auto shadow-2xl border border-slate-200 p-6 space-y-4"
                 style="max-width: 448px;">
                <div class="flex items-center space-x-3 pb-2">
                    <div class="p-2.5 bg-rose-50 rounded-xl text-rose-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-900 text-sm">Void Tax Invoice</h3>
                        <p class="text-xs text-slate-500 font-mono">{{ $voidInvoice?->invoice_no }}</p>
                    </div>
                </div>

                @if($voidInvoice && $voidInvoice->payments->count() > 0)
                    <div class="p-3 bg-red-50 border border-red-200 rounded-xl text-xs text-rmsc-red font-semibold">
                        This invoice has recorded customer payments. Voiding is blocked until payments are reversed.
                    </div>
                @else
                    <p class="text-xs text-slate-500">
                        Voiding will reverse inventory stock deductions and cancel receivables. This action is permanently audited.
                    </p>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Reason for Voiding *</label>
                        <textarea wire:model="voidReason" rows="3" placeholder="Provide mandatory reason for tax audit log..." class="w-full rounded-xl border border-slate-200 text-xs focus:border-rmsc-red focus:ring-rmsc-red"></textarea>
                        @error('voidReason') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                    </div>
                @endif

                <div class="flex items-center justify-end space-x-2 pt-2">
                    <button type="button" @click="$wire.set('showVoidModal', false)" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-800 bg-white border border-slate-200 rounded-xl transition">
                        Cancel
                    </button>
                    @if(!($voidInvoice && $voidInvoice->payments->count() > 0))
                        <button type="button" wire:click="executeVoidInvoice" wire:loading.attr="disabled" wire:target="executeVoidInvoice" class="px-4 py-2 text-xs font-semibold text-white bg-rose-600 hover:bg-rose-700 rounded-xl transition">
                            Confirm Void
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </template>
</div>
