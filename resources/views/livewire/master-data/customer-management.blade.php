<div x-data="{ open: @entangle('showModal') }" class="space-y-6">
    <!-- Single Row Header Banner (Title, Search, Filters & Action Button) -->
    <div class="flex flex-wrap items-center justify-between gap-3 pb-4 border-b border-slate-200/80">
        <!-- Title & Subtitle -->
        <div>
            <h1 class="font-heading font-bold text-xl text-slate-900 leading-tight">Customer & Account Directory</h1>
            <p class="text-[11px] text-slate-500 mt-0.5">HORECA hotel accounts, Oman Duty Free, retail permit holders, and credit terms</p>
        </div>

        <!-- Controls Group (Search, Filters, Button) -->
        <div class="flex items-center gap-2 shrink-0">
            <!-- Search Bar -->
            <div class="relative w-52">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 z-10">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search Customer..." style="padding-left: 2.5rem !important;" class="w-52 h-9 !pl-10 pr-8 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white focus:bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-medium text-slate-800 transition">
                @if($search)
                    <button wire:click="$set('search', '')" type="button" class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600 transition cursor-pointer" title="Clear search">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                @endif
            </div>

            <!-- Segment Filter -->
            <select wire:model.live="segment_filter" class="h-9 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white focus:bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-medium text-slate-800 transition pl-3 pr-8 cursor-pointer">
                <option value="">All Segments</option>
                @foreach($allSegments as $seg)
                    <option value="{{ $seg }}">{{ $seg }}</option>
                @endforeach
            </select>

            <!-- Status Filter -->
            <select wire:model.live="status_filter" class="h-9 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white focus:bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-medium text-slate-800 transition pl-3 pr-8 cursor-pointer">
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>

            <!-- Clear Filters Button -->
            @if($search || $segment_filter || $status_filter !== '')
                <button wire:click="clearFilters" type="button" class="h-9 px-2.5 bg-slate-100 hover:bg-slate-200/80 text-slate-600 hover:text-slate-900 text-xs font-medium rounded-xl transition flex items-center space-x-1 cursor-pointer shrink-0" title="Clear filters">
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    <span>Clear</span>
                </button>
            @endif

            <!-- Add Customer Button -->
            @if(auth()->user()?->hasPermission('Master Data', 'CREATE'))
            <button wire:click="openCreateModal" @click="open = true" type="button" class="h-9 px-3 bg-rmsc-red hover:bg-rmsc-red-dark text-white font-semibold text-xs rounded-xl shadow-xs transition flex items-center space-x-1 shrink-0 cursor-pointer">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Add Customer</span>
            </button>
            @endif
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                    <tr>
                        <th class="px-6 py-4">Customer Code</th>
                        <th class="px-6 py-4">Customer Name</th>
                        <th class="px-6 py-4">Segment</th>
                        <th class="px-6 py-4">Contact & Oman Card</th>
                        <th class="px-6 py-4">Payment Terms</th>
                        <th class="px-6 py-4 text-right">Credit Limit (OMR)</th>
                        <th class="px-6 py-4 text-right">Outstanding (OMR)</th>
                        <th class="px-6 py-4">Status</th>
                        @if(auth()->user()?->hasPermission('Master Data', 'EDIT') || auth()->user()?->hasPermission('Master Data', 'DELETE'))
                        <th class="px-6 py-4 text-right">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($customers as $c)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="px-6 py-4 font-mono font-bold text-slate-800 text-xs">{{ $c->customer_code }}</td>
                            <td class="px-6 py-4 font-semibold text-slate-900">
                                <div>{{ $c->customer_name }}</div>
                                @if($c->email)
                                    <div class="text-[11px] font-normal text-slate-400">{{ $c->email }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-slate-100 text-slate-700 border border-slate-200 uppercase">
                                    {{ $c->customer_segment }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-slate-800 text-xs font-medium">{{ $c->phone ?? '-' }}</div>
                                <div class="text-[11px] text-slate-400 font-mono">{{ $c->oman_card_no ?? '-' }}</div>
                            </td>
                            <td class="px-6 py-4 font-mono text-xs text-slate-600">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 font-semibold text-[11px]">
                                    {{ $c->creditTerms?->payment_terms_days ?? 30 }} Days
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right font-mono font-bold text-slate-900 text-xs">
                                {{ number_format($c->creditTerms?->credit_limit ?? 0, 3) }}
                            </td>
                            <td class="px-6 py-4 text-right font-mono text-amber-600 font-bold text-xs">
                                {{ number_format($c->creditTerms?->outstanding_balance ?? 0, 3) }}
                            </td>
                            <td class="px-6 py-4">
                                @if($c->is_active)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                        Inactive
                                    </span>
                                @endif
                            </td>
                            @if(auth()->user()?->hasPermission('Master Data', 'EDIT') || auth()->user()?->hasPermission('Master Data', 'DELETE'))
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end space-x-1">
                                    @if(auth()->user()?->hasPermission('Master Data', 'EDIT'))
                                    <button wire:click="editCustomer({{ $c->customer_id }})" @click="open = true" type="button" class="p-1.5 text-slate-500 hover:text-rmsc-red hover:bg-red-50 rounded-lg transition cursor-pointer" title="Edit Customer Account">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                    @endif
                                    @if(auth()->user()?->hasPermission('Master Data', 'DELETE'))
                                    <button type="button" wire:click="confirmDeleteCustomer({{ $c->customer_id }}, '{{ addslashes($c->customer_name) }}')" class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition cursor-pointer" title="Delete Customer Account">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                    @endif
                                </div>
                            </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ (auth()->user()?->hasPermission('Master Data', 'EDIT') || auth()->user()?->hasPermission('Master Data', 'DELETE')) ? 9 : 8 }}" class="px-6 py-8 text-center text-slate-400">No customers found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-200/80">
            {{ $customers->links() }}
        </div>
    </div>

    <!-- Create / Edit Customer Modal -->
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
            <div @click.away="if (!$wire.showItemDeleteModal) { open = false; $wire.set('showModal', false); }" 
                 class="bg-white rounded-2xl max-w-2xl w-full shadow-2xl border border-slate-200 overflow-hidden transform transition-all relative">
                <div class="px-6 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between rounded-t-2xl">
                    <h3 class="font-bold text-slate-900 text-base">{{ $isEdit ? 'Edit Customer Account' : 'Add New Customer Account' }}</h3>
                    <button type="button" @click="open = false; $wire.set('showModal', false)" class="text-slate-400 hover:text-slate-600 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form wire:submit.prevent="saveCustomer" class="p-6 space-y-4">
                    <!-- Code & Name -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Customer Code *</label>
                            <input type="text" wire:model="customer_code" readonly class="w-full rounded-xl border-slate-200 bg-slate-100/90 text-slate-600 font-mono text-sm cursor-not-allowed shadow-2xs focus:ring-0 focus:border-slate-200" placeholder="Auto-generated (e.g. CUST-0001)">
                            <p class="text-[11px] text-slate-400 mt-1 flex items-center">
                                <svg class="w-3.5 h-3.5 mr-1 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                <span>System Auto-Generated Code (Read-Only)</span>
                            </p>
                            @error('customer_code') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Customer Name *</label>
                            <input type="text" wire:model.blur="customer_name" class="w-full rounded-xl border border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-sm font-semibold text-slate-900" placeholder="e.g. Grand Hyatt Muscat">
                            @error('customer_name') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Segment & Oman Card -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Customer Segment *</label>
                            <div x-data="{ open: false }" class="relative">
                                <button type="button" @click="open = !open" class="w-full flex items-center justify-between rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-left font-medium text-slate-800 focus:outline-none focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 transition shadow-2xs">
                                    <span class="truncate">
                                        @if($customer_segment === 'ADD_NEW')
                                            <span class="text-amber-700 font-semibold">+ Add Custom Segment...</span>
                                        @elseif($customer_segment)
                                            {{ $customer_segment }}
                                        @else
                                            <span class="text-slate-400">Select Segment</span>
                                        @endif
                                    </span>
                                    <svg class="w-4 h-4 text-slate-400 shrink-0 ml-1 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </button>

                                <div x-show="open" @click.outside="open = false" x-transition class="absolute z-50 mt-1 w-full rounded-xl bg-white shadow-xl border border-slate-200 py-1 max-h-48 overflow-y-auto overscroll-contain shadow-2xl [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden" style="display: none;">
                                    <div @click="$wire.set('customer_segment', ''); open = false" class="px-3 py-2 text-xs font-medium text-slate-400 hover:bg-slate-50 cursor-pointer border-b border-slate-100">
                                        Select Segment
                                    </div>

                                    @foreach($allSegments as $seg)
                                        <div @click="$wire.set('customer_segment', '{{ addslashes($seg) }}'); open = false" class="flex items-center justify-between px-3 py-2 hover:bg-slate-50 transition cursor-pointer text-slate-700 text-xs {{ $customer_segment == $seg ? 'bg-red-50/50 font-semibold text-rmsc-red' : '' }}">
                                            <span class="flex-1 truncate py-0.5 text-xs">
                                                {{ $seg }}
                                            </span>
                                        </div>
                                    @endforeach

                                    <div @click="$wire.set('customer_segment', 'ADD_NEW'); open = false" class="px-3 py-2 text-xs font-semibold text-amber-700 hover:bg-amber-50 cursor-pointer border-t border-slate-100 flex items-center">
                                        <span>+ Add Custom Segment...</span>
                                    </div>
                                </div>
                            </div>
                            @error('customer_segment') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror

                            @if($isCustomSegment || $customer_segment === 'ADD_NEW')
                                <div class="mt-2.5 p-3 bg-amber-50/70 border border-amber-200/90 rounded-xl space-y-1.5">
                                    <label class="block text-[11px] font-bold text-amber-900 uppercase tracking-wider">New Segment Name *</label>
                                    <input type="text" wire:model.blur="custom_segment_name" class="w-full rounded-lg border-amber-200 bg-white focus:border-rmsc-red focus:ring-rmsc-red text-sm font-semibold text-slate-800 uppercase" placeholder="e.g. EMBASSY, CORPORATE">
                                    @error('custom_segment_name') <span class="text-xs text-rmsc-red block font-medium">{{ $message }}</span> @enderror
                                </div>
                            @endif
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Oman Card / Permit No</label>
                            <input type="text" wire:model.blur="oman_card_no" class="w-full rounded-xl border border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-sm font-mono" placeholder="e.g. PRM-2026-MCT">
                            @error('oman_card_no') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Phone & Email -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Phone Number</label>
                            <input type="text" wire:model.blur="phone" class="w-full rounded-xl border border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-sm font-mono" placeholder="e.g. +968 2464 1234">
                            @error('phone') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Email Address</label>
                            <input type="email" wire:model.blur="email" class="w-full rounded-xl border border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-sm" placeholder="e.g. accounts@hyattmuscat.com">
                            @error('email') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Credit Limit, Payment Terms & Outstanding Balance -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Credit Limit (OMR) *</label>
                            <input type="number" step="0.001" min="0" wire:model.blur="credit_limit" class="w-full rounded-xl border border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-sm font-mono font-bold text-slate-900" placeholder="5000.000">
                            @error('credit_limit') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Payment Terms (Days) *</label>
                            <input type="number" min="0" max="365" wire:model.blur="payment_terms_days" class="w-full rounded-xl border border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-sm font-mono font-semibold" placeholder="30">
                            @error('payment_terms_days') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Outstanding Balance (OMR) *</label>
                            <input type="number" step="0.001" min="0" wire:model.blur="outstanding_balance" class="w-full rounded-xl border border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-sm font-mono font-bold text-amber-700" placeholder="0.000">
                            @error('outstanding_balance') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="pt-2">
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="checkbox" wire:model="is_active" class="rounded border-slate-300 text-rmsc-red focus:ring-rmsc-red">
                            <span class="ml-2 text-xs font-semibold text-slate-700">Active Status</span>
                        </label>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex justify-end space-x-3">
                        <button type="button" @click="open = false; $wire.set('showModal', false)" class="px-4 py-2 border border-slate-200 text-slate-700 font-semibold text-xs rounded-xl hover:bg-slate-50 transition">Cancel</button>
                        <button type="submit" class="px-5 py-2 bg-rmsc-red hover:bg-rmsc-red-dark text-white font-semibold text-xs rounded-xl shadow-md transition">Save Customer Account</button>
                    </div>
                </form>
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
             class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
             style="display: none;">
            <div @click.away="$wire.set('showItemDeleteModal', false)" 
                 class="bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden transform transition-all w-full mx-auto"
                 style="max-width: 400px;">
                <div class="p-6 text-center">
                    <div class="w-12 h-12 rounded-full bg-red-100 text-red-600 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 mb-1">Delete {{ $itemToDeleteType }}?</h3>
                    <p class="text-xs text-slate-500 mb-6">Are you sure you want to delete <span class="font-semibold text-slate-800">"{{ $itemToDeleteName }}"</span>? This action cannot be undone.</p>

                    <div class="flex items-center justify-center space-x-3">
                        <button type="button" @click="$wire.set('showItemDeleteModal', false)" class="px-4 py-2 border border-slate-200 text-slate-700 font-semibold text-xs rounded-xl hover:bg-slate-50 transition">Cancel</button>
                        <button type="button" wire:click="executeDeleteItem" class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold text-xs rounded-xl shadow-md transition">Delete {{ $itemToDeleteType }}</button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
