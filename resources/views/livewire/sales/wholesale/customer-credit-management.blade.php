<div x-data="{
        open: @entangle('showModal'),

        // Confirmation dialog (Hold / Release Hold)
        confirmDialog: { show: false, type: '', id: null, customerName: '', busy: false },
        confirmConfigs: {
            hold: {
                title: 'Place Customer on Credit Hold?',
                button: 'Yes, Place on Hold',
                tone: 'rose',
                hint: 'All new wholesale orders for this customer will be blocked until released.'
            },
            release: {
                title: 'Release Customer from Credit Hold?',
                button: 'Yes, Release Hold',
                tone: 'emerald',
                hint: 'The customer will be active and allowed to place wholesale orders within their credit limit.'
            }
        },
        askConfirm(type, id, customerName) {
            this.confirmDialog = { show: true, type: type, id: id, customerName: customerName, busy: false };
        },
        async runConfirmed() {
            if (this.confirmDialog.busy) return;
            this.confirmDialog.busy = true;
            try {
                await $wire.toggleCreditHold(this.confirmDialog.id);
            } finally {
                this.confirmDialog.show = false;
                this.confirmDialog.busy = false;
            }
        }
    }"
    @keydown.escape.window="if (!confirmDialog.busy) confirmDialog.show = false"
    class="space-y-6">
    <!-- Single Row Header Banner (Title, Search, Filters) -->
    <div class="flex flex-wrap items-center justify-between gap-3 pb-4 border-b border-slate-200/80">
        <!-- Title & Subtitle -->
        <div>
            <h1 class="font-heading font-bold text-xl text-slate-900 leading-tight">Customer Credit Limits</h1>
            <p class="text-[11px] text-slate-500 mt-0.5">Wholesale customer credit limits, outstanding balances, terms management, and receivables</p>
        </div>

        <!-- Controls Group -->
        <div class="flex items-center gap-2 shrink-0 flex-wrap">
            <!-- Search Bar -->
            <div class="relative w-52">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 z-10">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search customer, code..." style="padding-left: 2.5rem !important;" class="w-52 h-9 !pl-10 pr-8 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white focus:bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-medium text-slate-800 transition">
                @if($search)
                    <button wire:click="$set('search', '')" type="button" class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600 transition cursor-pointer" title="Clear search">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                @endif
            </div>

            <!-- Credit Status Filter -->
            <select wire:model.live="credit_status_filter" class="h-9 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white focus:bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-medium text-slate-800 transition pl-3 pr-8 cursor-pointer">
                <option value="">All Credit Status</option>
                <option value="ACTIVE">Active (Allowed)</option>
                <option value="ON_HOLD">On Hold (Blocked)</option>
            </select>

            <!-- Customer Type Filter -->
            <select wire:model.live="customer_type_filter" class="h-9 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white focus:bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-medium text-slate-800 transition pl-3 pr-8 cursor-pointer">
                <option value="">All Types</option>
                <option value="WHOLESALE">Wholesale</option>
                <option value="RETAIL">Retail</option>
            </select>

            <!-- Clear Filters Button -->
            @if($search || $credit_status_filter !== '' || $customer_type_filter !== '')
                <button wire:click="clearFilters" type="button" class="h-9 px-2.5 bg-slate-100 hover:bg-slate-200/80 text-slate-600 hover:text-slate-900 text-xs font-medium rounded-xl transition flex items-center space-x-1 cursor-pointer shrink-0" title="Clear filters">
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    <span>Clear</span>
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
                        <th class="px-6 py-3.5">Customer</th>
                        <th class="px-6 py-3.5">Type</th>
                        <th class="px-6 py-3.5 text-right">Credit Limit</th>
                        <th class="px-6 py-3.5 text-right">Outstanding</th>
                        <th class="px-6 py-3.5 text-right">Available</th>
                        <th class="px-6 py-3.5 text-center">Terms</th>
                        <th class="px-6 py-3.5 text-center">Overdue?</th>
                        <th class="px-6 py-3.5">Status</th>
                        <th class="px-6 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse($customers as $c)
                        <tr wire:key="customer-credit-{{ $c->customer_id }}" class="hover:bg-slate-50/60 transition group">
                            <td class="px-6 py-4">
                                <a href="{{ route('wholesale.credit.show', $c) }}" class="font-semibold text-xs text-slate-900 hover:text-rmsc-red hover:underline block">
                                    {{ $c->customer_name }}
                                </a>
                                <span class="text-[11px] font-mono text-slate-400">{{ $c->customer_code }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-mono font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                    {{ $c->customer_type ?? 'WHOLESALE' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 font-mono text-xs text-slate-800 text-right">
                                {{ number_format($c->computed_limit, 3) }}
                            </td>
                            <td class="px-6 py-4 font-mono text-xs text-right">
                                <span class="{{ $c->computed_outstanding > 0 ? 'text-slate-900 font-semibold' : 'text-slate-400' }}">
                                    {{ number_format($c->computed_outstanding, 3) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 font-mono text-xs font-bold text-right">
                                <span class="{{ $c->computed_available <= 0 ? 'text-rmsc-red' : 'text-emerald-700' }}">
                                    {{ number_format($c->computed_available, 3) }}
                                </span> <span class="text-[10px] font-sans font-normal text-slate-400">OMR</span>
                            </td>
                            <td class="px-6 py-4 text-center font-mono text-xs text-slate-600">
                                {{ $c->payment_terms_days ?? 30 }}d
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($c->has_overdue)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                        YES (Overdue)
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-500">
                                        None
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($c->credit_status === 'ACTIVE')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                        On Hold
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end space-x-1">
                                    <!-- View Ledger Link -->
                                    <a href="{{ route('wholesale.credit.show', $c) }}" class="p-1.5 text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded-lg transition" title="View Credit Ledger">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </a>

                                    @if(auth()->user()?->hasRole('Super Admin') || auth()->user()?->hasRole('HQ Admin') || auth()->user()?->hasRole('Branch Admin') || auth()->user()?->hasPermission('Retail & Wholesale Sales', 'APPROVE'))
                                        <!-- Edit Credit Limit Button -->
                                        <button type="button" wire:click="editCreditTerms({{ $c->customer_id }})" wire:loading.attr="disabled" wire:target="editCreditTerms({{ $c->customer_id }})" class="p-1.5 text-slate-500 hover:text-rmsc-red hover:bg-red-50 rounded-lg transition cursor-pointer" title="Edit Credit Limit & Terms">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>

                                        <!-- Toggle Hold Button -->
                                        <button type="button"
                                                @click="askConfirm('{{ $c->credit_status === 'ACTIVE' ? 'hold' : 'release' }}', {{ $c->customer_id }}, '{{ addslashes($c->customer_name) }}')"
                                                :disabled="confirmDialog.busy"
                                                class="p-1.5 {{ $c->credit_status === 'ACTIVE' ? 'text-rose-600 hover:bg-rose-50' : 'text-emerald-600 hover:bg-emerald-50' }} rounded-lg transition cursor-pointer disabled:opacity-50"
                                                title="{{ $c->credit_status === 'ACTIVE' ? 'Place on Credit Hold' : 'Release from Hold' }}">
                                            @if($c->credit_status === 'ACTIVE')
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                            @else
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                                            @endif
                                        </button>
                                    @endif

                                    <!-- Record Payment Button -->
                                    @if($c->computed_outstanding > 0)
                                        <button type="button" wire:click="openPaymentModal({{ $c->customer_id }})" wire:loading.attr="disabled" wire:target="openPaymentModal({{ $c->customer_id }})" class="p-1.5 text-emerald-600 hover:text-emerald-800 hover:bg-emerald-50 rounded-lg transition cursor-pointer" title="Record Customer Payment">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-12 text-center text-slate-500">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-10 h-10 text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    <p class="text-sm font-semibold text-slate-700">No customer credit profiles found.</p>
                                    <p class="text-xs text-slate-400 mt-1">Try adjusting your search criteria.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-200/80">
            {{ $customers->links() }}
        </div>
    </div>

    <!-- Confirmation Dialog: Hold / Release Hold (400px centered) -->
    <template x-teleport="body">
        <div x-show="confirmDialog.show"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
             style="display: none;">
            <div @click.away="if (!confirmDialog.busy) confirmDialog.show = false"
                 class="bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden transform transition-all w-full mx-auto"
                 style="max-width: 400px;">
                <div class="p-6 text-center">
                    <!-- Icon -->
                    <div class="w-12 h-12 rounded-full mx-auto flex items-center justify-center mb-4"
                         :class="confirmConfigs[confirmDialog.type]?.tone === 'emerald' ? 'bg-emerald-100 text-emerald-600' : 'bg-rose-100 text-rose-600'">
                        <svg x-show="confirmDialog.type === 'hold'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        <svg x-show="confirmDialog.type === 'release'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                    </div>

                    <h3 class="text-base font-bold text-slate-900 mb-1" x-text="confirmConfigs[confirmDialog.type]?.title"></h3>
                    <p class="text-xs text-slate-500">
                        <template x-if="confirmDialog.type === 'hold'">
                            <span>Are you sure you want to place <span class="font-bold text-slate-800" x-text="confirmDialog.customerName"></span> on credit hold?</span>
                        </template>
                        <template x-if="confirmDialog.type === 'release'">
                            <span>Are you sure you want to release <span class="font-bold text-slate-800" x-text="confirmDialog.customerName"></span> from credit hold?</span>
                        </template>
                    </p>
                    <p class="text-[11px] text-slate-400 mt-1 mb-6" x-text="confirmConfigs[confirmDialog.type]?.hint"></p>

                    <div class="flex items-center justify-center space-x-3">
                        <button type="button"
                                @click="confirmDialog.show = false"
                                :disabled="confirmDialog.busy"
                                class="px-4 py-2 text-xs font-semibold text-slate-700 hover:text-slate-900 bg-slate-100 hover:bg-slate-200/80 rounded-xl transition cursor-pointer disabled:opacity-50">
                            Cancel
                        </button>
                        <button type="button"
                                @click="runConfirmed()"
                                :disabled="confirmDialog.busy"
                                class="px-4 py-2 text-xs font-semibold text-white rounded-xl shadow-xs transition cursor-pointer disabled:opacity-60 flex items-center gap-1.5"
                                :class="confirmConfigs[confirmDialog.type]?.tone === 'emerald' ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-rose-600 hover:bg-rose-700'">
                            <svg x-show="confirmDialog.busy" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle style="opacity:.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path style="opacity:.85;" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                            <span x-text="confirmDialog.busy ? 'Processing...' : confirmConfigs[confirmDialog.type]?.button"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <!-- Modal: Edit Credit Limit & Terms (max-w-xl) -->
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
                 class="bg-white rounded-2xl max-w-xl w-full mx-auto shadow-2xl border border-slate-200 p-6 space-y-4"
                 style="max-width: 576px;">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="font-bold text-slate-900 text-base">Edit Credit Limit & Terms</h3>
                        <p class="text-xs text-slate-500 font-mono">{{ $customerName }} ({{ $customerCode }})</p>
                    </div>
                    <button type="button" @click="open = false; $wire.closeModal()" class="text-slate-400 hover:text-slate-600 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Credit Limit (OMR) *</label>
                            <input type="number" step="0.001" min="0" wire:model="creditLimit" class="w-full h-9 rounded-xl border border-slate-200 text-xs font-mono font-semibold focus:border-rmsc-red focus:ring-rmsc-red">
                            @error('creditLimit') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Payment Terms (Days) *</label>
                            <input type="number" min="0" wire:model="paymentTermsDays" class="w-full h-9 rounded-xl border border-slate-200 text-xs font-mono focus:border-rmsc-red focus:ring-rmsc-red">
                            @error('paymentTermsDays') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Credit Status *</label>
                        <select wire:model="creditStatus" class="w-full h-9 rounded-xl border border-slate-200 text-xs font-medium focus:border-rmsc-red focus:ring-rmsc-red">
                            <option value="ACTIVE">ACTIVE - Orders Allowed Within Limit</option>
                            <option value="ON_HOLD">ON HOLD - All Wholesale Orders Blocked</option>
                        </select>
                        @error('creditStatus') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="open = false; $wire.closeModal()" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-800 bg-white border border-slate-200 rounded-xl transition">
                        Cancel
                    </button>
                    <button type="button" wire:click="saveCreditTerms" wire:loading.attr="disabled" wire:target="saveCreditTerms" class="px-5 py-2 text-xs font-semibold text-white bg-rmsc-red hover:bg-rmsc-red-dark rounded-xl shadow-xs transition">
                        Save Credit Terms
                    </button>
                </div>
            </div>
        </div>
    </template>

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
                        <p class="text-xs text-slate-500 font-mono">{{ $paymentCustomer?->customer_name }}</p>
                    </div>
                </div>

                <div class="space-y-3">
                    @if(empty($unpaidInvoices))
                        <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-800">
                            No open or partially paid invoices found for this customer.
                        </div>
                    @endif

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Select Open Invoice *</label>
                        <select wire:model.live="paymentInvoiceId" class="w-full h-9 rounded-xl border border-slate-200 text-xs font-medium focus:border-rmsc-red focus:ring-rmsc-red">
                            <option value="">Select Invoice...</option>
                            @foreach($unpaidInvoices as $inv)
                                <option value="{{ $inv['invoice_id'] }}">
                                    {{ $inv['invoice_no'] }} - Balance Due: {{ number_format($inv['balance_due'] ?? ((float)($inv['grand_total'] ?? 0) - (float)($inv['amount_paid'] ?? 0)), 3) }} OMR (Due: {{ $inv['due_date'] ?? 'N/A' }})
                                </option>
                            @endforeach
                        </select>
                        @error('paymentInvoiceId') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Amount (OMR) *</label>
                            <input type="number" step="0.001" min="0.001" wire:model="paymentAmount" class="w-full h-9 rounded-xl border border-slate-200 text-xs font-mono font-semibold focus:border-rmsc-red focus:ring-rmsc-red">
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
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Payment Reference / Cheque #</label>
                        <input type="text" wire:model="paymentReference" placeholder="e.g. Bank ref #, cheque #..." class="w-full h-9 rounded-xl border border-slate-200 text-xs font-mono focus:border-rmsc-red focus:ring-rmsc-red">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Notes</label>
                        <input type="text" wire:model="paymentNotes" placeholder="Optional reconciliation notes..." class="w-full h-9 rounded-xl border border-slate-200 text-xs focus:border-rmsc-red focus:ring-rmsc-red">
                    </div>
                </div>

                <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="$wire.set('showPaymentModal', false)" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-800 bg-white border border-slate-200 rounded-xl transition">
                        Cancel
                    </button>
                    <button type="button" wire:click="saveCustomerPayment" wire:loading.attr="disabled" wire:target="saveCustomerPayment" @if(empty($unpaidInvoices)) disabled @endif class="px-5 py-2 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed rounded-xl transition">
                        Record Payment
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>
