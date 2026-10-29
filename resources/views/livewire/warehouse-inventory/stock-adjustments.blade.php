<div x-data="{ openModal: @entangle('showModal'), openView: @entangle('showViewModal') }" x-effect="document.body.style.overflow = (openModal || openView) ? 'hidden' : ''">
    <!-- Page Header Slot -->
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="font-heading font-bold text-2xl text-slate-900 tracking-tight">Physical Counts &amp; Stock Adjustments</h1>
                <p class="text-xs text-slate-500 mt-1 font-medium">Record physical stock count audits, write-offs, damage variances, and reconcile warehouse stock ledgers</p>
            </div>
            <div class="flex items-center space-x-3">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs text-slate-600 font-medium shadow-2xs">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Live Audit Ledger Sync</span>
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
            <!-- Stat 1: Total Physical Audits -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-2xs flex flex-col justify-between hover:border-slate-300 transition">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Audits</span>
                    <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="font-heading font-extrabold text-2xl text-slate-900">{{ number_format($metrics['total_adjustments']) }}</div>
                    <p class="text-[11px] text-slate-500 font-medium mt-1">Audit vouchers recorded</p>
                </div>
            </div>

            <!-- Stat 2: Surplus Audits -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-2xs flex flex-col justify-between hover:border-slate-300 transition">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Surplus Reconciliations</span>
                    <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 4v16m8-8H4"/></svg>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="font-heading font-extrabold text-2xl text-emerald-600">{{ number_format($metrics['surplus_count']) }}</div>
                    <p class="text-[11px] text-emerald-700 font-medium mt-1">Physical stock > System stock</p>
                </div>
            </div>

            <!-- Stat 3: Deficit / Damage Audits -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-2xs flex flex-col justify-between hover:border-slate-300 transition">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Deficit / Breakage</span>
                    <div class="w-8 h-8 rounded-lg bg-rose-50 text-rmsc-red flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M20 12H4"/></svg>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="font-heading font-extrabold text-2xl {{ $metrics['deficit_count'] > 0 ? 'text-rmsc-red' : 'text-slate-900' }}">{{ number_format($metrics['deficit_count']) }}</div>
                    <p class="text-[11px] text-rose-700 font-medium mt-1">Physical count write-offs</p>
                </div>
            </div>

            <!-- Stat 4: Net Variance Units -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-2xs flex flex-col justify-between hover:border-slate-300 transition">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Net Variance Units</span>
                    <div class="w-8 h-8 rounded-lg bg-sky-50 text-sky-600 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="font-heading font-extrabold text-2xl {{ $metrics['total_variance_units'] >= 0 ? 'text-slate-900' : 'text-rmsc-red' }}">
                        {{ $metrics['total_variance_units'] > 0 ? '+' : '' }}{{ number_format($metrics['total_variance_units'], 2) }}
                    </div>
                    <p class="text-[11px] text-sky-700 font-medium mt-1">Total unit discrepancy</p>
                </div>
            </div>
        </div>

        <!-- Stock Adjustments Register Panel -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
            <!-- Panel Header -->
            <div class="px-6 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3 bg-slate-50/50">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 rounded-lg bg-rmsc-red/10 text-rmsc-red flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                    </div>
                    <div>
                        <div class="flex items-center space-x-2">
                            <h3 class="font-heading font-bold text-slate-900 text-base">Physical Count Adjustments Register</h3>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-200 text-slate-700 font-mono">{{ $adjustments->total() }}</span>
                        </div>
                        <p class="text-xs text-slate-500 font-medium">Log stock audits, record variances, and update physical on-hand ledgers</p>
                    </div>
                </div>

                <button type="button" wire:click="openCreateModal" style="height: 2.25rem; padding: 0 1.25rem; gap: 0.5rem; white-space: nowrap;" class="bg-rmsc-red hover:bg-rmsc-red-dark text-white font-semibold text-xs rounded-xl shadow-xs transition inline-flex items-center justify-center shrink-0 cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>Register Physical Count</span>
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
                        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search adjustment #, warehouse, batch..." style="padding-left: 2.5rem !important;" class="w-full h-9 !pl-10 pr-8 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white focus:bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-medium text-slate-800 transition">
                        @if($search)
                            <button wire:click="$set('search', '')" type="button" class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600 transition cursor-pointer" title="Clear search">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        @endif
                    </div>

                    <!-- Warehouse Select Filter -->
                    <select wire:model.live="warehouse_id_filter" class="h-9 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white focus:bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-medium text-slate-800 transition pl-3 pr-8 cursor-pointer max-w-[200px]">
                        <option value="">All Warehouses</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->warehouse_id }}">{{ $wh->warehouse_name }}</option>
                        @endforeach
                    </select>

                    <!-- Reason Code Select Filter -->
                    <select wire:model.live="reason_code_filter" class="h-9 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white focus:bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-medium text-slate-800 transition pl-3 pr-8 cursor-pointer">
                        <option value="">All Reason Codes</option>
                        <option value="RECOUNT">Routine Physical Count / Recount</option>
                        <option value="BREAKAGE">Damage / Breakage Loss</option>
                        <option value="THEFT">Theft / Unexplained Loss</option>
                        <option value="EXPIRY_WRITE_OFF">Expiry Write-Off</option>
                        <option value="OTHER">System Stock Correction / Other</option>
                    </select>

                    <!-- Variance Type Filter -->
                    <select wire:model.live="variance_type_filter" class="h-9 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white focus:bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-medium text-slate-800 transition pl-3 pr-8 cursor-pointer">
                        <option value="">All Variances</option>
                        <option value="surplus">Surplus (+)</option>
                        <option value="deficit">Deficit (-)</option>
                        <option value="exact">Exact Match (0)</option>
                    </select>

                    <!-- Clear Filters Button -->
                    @if($search !== '' || $warehouse_id_filter !== '' || $reason_code_filter !== '' || $variance_type_filter !== '')
                        <button type="button" wire:click="clearFilters" class="h-9 px-2.5 bg-slate-100 hover:bg-slate-200/80 text-slate-600 hover:text-slate-900 text-xs font-medium rounded-xl transition flex items-center space-x-1 cursor-pointer shrink-0" title="Clear all filters">
                            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            <span>Clear</span>
                        </button>
                    @endif
                </div>

                <!-- Adjustments Data Table -->
                <div class="border border-slate-200 rounded-xl overflow-hidden shadow-2xs">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-slate-50/80 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                                    <th class="py-3 px-4">Audit Voucher</th>
                                    <th class="py-3 px-4">Warehouse Depot</th>
                                    <th class="py-3 px-4">Audit Reason</th>
                                    <th class="py-3 px-4">Count Date</th>
                                    <th class="py-3 px-4 text-center">Items &amp; Net Variance</th>
                                    <th class="py-3 px-4 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 bg-white">
                                @forelse($adjustments as $adj)
                                    @php
                                        $totalVariance = (float) $adj->items->sum('variance_quantity');
                                    @endphp
                                    <tr class="hover:bg-slate-50/80 transition">
                                        <!-- Voucher Number -->
                                        <td class="py-3 px-4 whitespace-nowrap">
                                            <div class="font-mono font-bold text-slate-900">
                                                {{ $adj->adjustment_number }}
                                            </div>
                                            <div class="text-[11px] text-slate-500 mt-0.5 font-medium">
                                                Auditor: <span class="text-slate-700 font-semibold">{{ $adj->approver?->name ?? 'System' }}</span>
                                            </div>
                                        </td>

                                        <!-- Warehouse -->
                                        <td class="py-3 px-4 whitespace-nowrap">
                                            <span class="px-2.5 py-1 rounded-lg bg-slate-100 border border-slate-200 text-xs font-semibold text-slate-800">
                                                {{ $adj->warehouse?->warehouse_name ?? 'N/A' }}
                                            </span>
                                            <div class="text-[10px] text-slate-400 font-mono mt-0.5">({{ $adj->warehouse?->warehouse_code }})</div>
                                        </td>

                                        <!-- Reason Code Badge -->
                                        <td class="py-3 px-4 whitespace-nowrap">
                                            @if($adj->reason_code === 'RECOUNT')
                                                <span class="px-2.5 py-1 text-[10px] font-bold rounded-full bg-sky-100 text-sky-800">Routine Recount</span>
                                            @elseif($adj->reason_code === 'BREAKAGE')
                                                <span class="px-2.5 py-1 text-[10px] font-bold rounded-full bg-rose-100 text-rmsc-red">Damage / Breakage</span>
                                            @elseif($adj->reason_code === 'THEFT')
                                                <span class="px-2.5 py-1 text-[10px] font-bold rounded-full bg-purple-100 text-purple-800">Theft / Loss</span>
                                            @elseif($adj->reason_code === 'EXPIRY_WRITE_OFF')
                                                <span class="px-2.5 py-1 text-[10px] font-bold rounded-full bg-amber-100 text-amber-800">Expiry Write-Off</span>
                                            @else
                                                <span class="px-2.5 py-1 text-[10px] font-bold rounded-full bg-slate-100 text-slate-700">Stock Correction</span>
                                            @endif
                                        </td>

                                        <!-- Count Date -->
                                        <td class="py-3 px-4 whitespace-nowrap font-mono text-slate-700">
                                            {{ $adj->count_date ? $adj->count_date->format('d M Y') : 'N/A' }}
                                        </td>

                                        <!-- Items & Variance -->
                                        <td class="py-3 px-4 text-center whitespace-nowrap">
                                            <div class="flex items-center justify-center space-x-2">
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700">
                                                    {{ $adj->items->count() }} line(s)
                                                </span>
                                                @if($totalVariance > 0)
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 font-mono">+{{ number_format($totalVariance, 2) }}</span>
                                                @elseif($totalVariance < 0)
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rmsc-red font-mono">{{ number_format($totalVariance, 2) }}</span>
                                                @else
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 font-mono">0.00</span>
                                                @endif
                                            </div>
                                        </td>

                                        <!-- Actions -->
                                        <td class="py-3 px-4 text-center whitespace-nowrap">
                                            <button wire:click="openViewModal({{ $adj->adjustment_id }})" wire:loading.attr="disabled" class="p-1.5 text-slate-500 hover:text-sky-600 hover:bg-sky-50 rounded-lg transition cursor-pointer relative" title="View Audit Details">
                                                <svg wire:loading.remove wire:target="openViewModal({{ $adj->adjustment_id }})" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                <svg wire:loading wire:target="openViewModal({{ $adj->adjustment_id }})" class="w-4 h-4 text-sky-600 animate-spin" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="py-10 text-center text-slate-400">
                                            <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-2 text-slate-400">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                                            </div>
                                            <p class="text-sm font-medium text-slate-600">No physical count adjustments found</p>
                                            <p class="text-xs text-slate-400 mt-0.5">Try adjusting your search filters or record a new audit voucher.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                @if($adjustments->hasPages())
                    <div>
                        {{ $adjustments->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- CREATE PHYSICAL COUNT MODAL -->
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
             class="bg-white rounded-xl shadow-2xl border border-slate-200 w-full max-w-lg mx-auto overflow-hidden transform transition-all relative flex flex-col min-h-0"
             style="max-width: 512px; max-height: calc(100vh - 3rem); max-height: calc(100dvh - 3rem);">
            <!-- Modal Header -->
            <div class="px-6 py-4 bg-slate-50/50 border-b border-slate-100 flex items-center justify-between rounded-t-xl shrink-0">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 rounded-lg bg-rmsc-red/10 text-rmsc-red flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                    </div>
                    <div>
                        <h3 class="font-heading font-bold text-slate-900 text-base">Register Physical Stock Count</h3>
                        <p class="text-xs text-slate-500 font-medium">Record physical count units and reconcile inventory ledgers</p>
                    </div>
                </div>
                <button type="button" @click="openModal = false; $wire.closeModal()" class="text-slate-400 hover:text-slate-600 transition cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Modal Form Body -->
            <form wire:submit.prevent="saveAdjustment" class="flex flex-col flex-1 min-h-0 overflow-hidden">
                <div class="p-6 overflow-y-auto flex-1 min-h-0 [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden" style="scrollbar-width: none; -ms-overflow-style: none;">
                    <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 space-y-4">
                        <!-- Voucher Number -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Voucher Number (Auto-Generated)</label>
                            <input type="text" wire:model="adjustment_number" readonly class="w-full h-9 px-3 text-xs bg-slate-100/80 border border-slate-200 rounded-xl text-slate-700 font-mono font-bold cursor-not-allowed select-none" />
                            @error('adjustment_number') <span class="text-[11px] text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Count Date -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Audit Count Date *</label>
                            <input type="date" wire:model="count_date" class="w-full h-9 px-3 text-xs bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-rmsc-red/10 focus:border-rmsc-red text-slate-800 font-mono transition" />
                            @error('count_date') <span class="text-[11px] text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Target Warehouse -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Target Warehouse Depot *</label>
                            <select wire:model.live="warehouse_id" class="w-full h-9 px-3 text-xs bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-rmsc-red/10 focus:border-rmsc-red text-slate-800 font-medium transition cursor-pointer">
                                <option value="">Select Warehouse...</option>
                                @foreach($warehouses as $wh)
                                    <option value="{{ $wh->warehouse_id }}">{{ $wh->warehouse_name }} ({{ $wh->warehouse_code }})</option>
                                @endforeach
                            </select>
                            @error('warehouse_id') <span class="text-[11px] text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Reason Code -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Audit Reason Code *</label>
                            <select wire:model="reason_code" class="w-full h-9 px-3 text-xs bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-rmsc-red/10 focus:border-rmsc-red text-slate-800 font-medium transition cursor-pointer">
                                <option value="RECOUNT">Routine Physical Count / Recount</option>
                                <option value="BREAKAGE">Damage / Breakage Loss</option>
                                <option value="THEFT">Theft / Unexplained Loss</option>
                                <option value="EXPIRY_WRITE_OFF">Expiry Shelf-Life Write-Off</option>
                                <option value="OTHER">System Stock Correction / Other</option>
                            </select>
                            @error('reason_code') <span class="text-[11px] text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Line Items Section -->
                        <div class="pt-3 border-t border-slate-200 space-y-3">
                            <div class="flex items-center justify-between">
                                <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center space-x-1.5">
                                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                    <span>Physical Count Lines</span>
                                </h4>
                                <button type="button" wire:click="addItem" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition flex items-center space-x-1 cursor-pointer">
                                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    <span>Add Line</span>
                                </button>
                            </div>

                            @error('items') <span class="text-[11px] text-rmsc-red block">{{ $message }}</span> @enderror

                            <div class="space-y-3">
                                @foreach($items as $index => $item)
                                    @php
                                        $variance = (float)($item['variance_quantity'] ?? 0);
                                    @endphp
                                    <div class="p-3 bg-white border border-slate-200 rounded-xl space-y-2.5">
                                        <!-- Batch Selection -->
                                        <div>
                                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Stock Batch *</label>
                                            <select wire:model.live="items.{{ $index }}.batch_id" class="w-full h-9 px-2.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-rmsc-red/10 focus:border-rmsc-red text-slate-800 font-medium transition cursor-pointer">
                                                <option value="">-- Select Batch to Count --</option>
                                                @foreach($availableBatches as $batch)
                                                    <option value="{{ $batch->batch_id }}">
                                                        {{ $batch->batch_number }} | {{ $batch->product?->product_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error("items.{$index}.batch_id") <span class="text-[10px] text-rmsc-red block mt-0.5">{{ $message }}</span> @enderror
                                        </div>

                                        <!-- System vs Physical Count Grid -->
                                        <div class="grid grid-cols-2 gap-3 pt-1">
                                            <div>
                                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">System Stock</label>
                                                <input type="text" readonly value="{{ number_format((float)($item['system_quantity'] ?? 0), 2) }}" class="w-full h-8 px-2.5 text-xs bg-slate-100 border border-slate-200 rounded-lg text-slate-600 font-mono font-semibold cursor-not-allowed select-none" />
                                            </div>

                                            <div>
                                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Physical Count *</label>
                                                <input type="number" step="0.01" min="0" wire:model.live="items.{{ $index }}.counted_quantity" placeholder="0.00" class="w-full h-8 px-2.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-rmsc-red/10 focus:border-rmsc-red text-slate-800 font-mono transition" />
                                                @error("items.{$index}.counted_quantity") <span class="text-[10px] text-rmsc-red block mt-0.5">{{ $message }}</span> @enderror
                                            </div>
                                        </div>

                                        <!-- Variance Badge & Trash Action Row -->
                                        <div class="flex items-center justify-between pt-1 border-t border-slate-100">
                                            <div class="flex items-center space-x-1.5">
                                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Variance:</span>
                                                @if($variance > 0)
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 font-mono">+{{ number_format($variance, 2) }} (Surplus)</span>
                                                @elseif($variance < 0)
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rmsc-red font-mono">{{ number_format($variance, 2) }} (Deficit)</span>
                                                @else
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 font-mono">0.00 (Exact)</span>
                                                @endif
                                            </div>

                                            @if(count($items) > 1)
                                                <button type="button" wire:click="removeItem({{ $index }})" class="p-1 text-slate-400 hover:text-rmsc-red hover:bg-rose-50 rounded-lg transition cursor-pointer" title="Remove line">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
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
                        <span>Post Stock Adjustment</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- VIEW AUDIT DETAILS MODAL -->
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
             class="bg-white rounded-xl shadow-2xl border border-slate-200 w-full max-w-xl mx-auto overflow-hidden transform transition-all relative flex flex-col min-h-0"
             style="max-width: 576px; max-height: calc(100vh - 3rem); max-height: calc(100dvh - 3rem);">
            
            @if($selectedAdjustmentDetails)
                <!-- Modal Header -->
                <div class="px-6 py-4 bg-slate-50/50 border-b border-slate-100 flex items-center justify-between rounded-t-xl shrink-0">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 00-2 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                        </div>
                        <div>
                            <h3 class="font-heading font-bold text-slate-900 text-base">Physical Count Reconciliation</h3>
                            <p class="text-xs text-indigo-600 font-mono font-bold">{{ $selectedAdjustmentDetails->adjustment_number }}</p>
                        </div>
                    </div>
                    <button type="button" @click="openView = false; $wire.closeViewModal()" class="text-slate-400 hover:text-slate-600 transition cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Modal Content -->
                <div class="p-6 overflow-y-auto flex-1 min-h-0 space-y-5 [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden" style="scrollbar-width: none; -ms-overflow-style: none;">
                    <!-- Warehouse Summary Card -->
                    <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200 flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Audited Warehouse</span>
                            <span class="text-sm font-bold text-slate-900">{{ $selectedAdjustmentDetails->warehouse?->warehouse_name }}</span>
                            <span class="text-xs text-slate-500 block font-mono">({{ $selectedAdjustmentDetails->warehouse?->warehouse_code }})</span>
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Audit Reason</span>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-indigo-100 text-indigo-800">
                                {{ str_replace('_', ' ', $selectedAdjustmentDetails->reason_code) }}
                            </span>
                        </div>
                    </div>

                    <!-- Details Grid -->
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 p-3.5 bg-slate-50/50 rounded-xl border border-slate-200 text-xs">
                        <div>
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Count Date</span>
                            <span class="font-bold text-slate-800 font-mono">{{ $selectedAdjustmentDetails->count_date ? $selectedAdjustmentDetails->count_date->format('d M Y') : 'N/A' }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Auditor / Approver</span>
                            <span class="font-bold text-slate-800">{{ $selectedAdjustmentDetails->approver?->name ?? 'System User' }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Total Audited Lines</span>
                            <span class="font-bold text-slate-800">{{ $selectedAdjustmentDetails->items->count() }} line(s)</span>
                        </div>
                    </div>

                    <!-- Manifest Breakdown Table -->
                    <div>
                        <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-2">Reconciliation Breakdown</h4>
                        <div class="border border-slate-200 rounded-xl overflow-hidden shadow-2xs">
                            <table class="w-full text-left text-xs">
                                <thead>
                                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold uppercase text-[10px]">
                                        <th class="py-2.5 px-3">Batch Number</th>
                                        <th class="py-2.5 px-3">Product Name</th>
                                        <th class="py-2.5 px-3 text-right">System Qty</th>
                                        <th class="py-2.5 px-3 text-right">Physical Count</th>
                                        <th class="py-2.5 px-3 text-right">Variance</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 bg-white text-slate-800">
                                    @foreach($selectedAdjustmentDetails->items as $item)
                                        @php
                                            $var = (float) $item->variance_quantity;
                                        @endphp
                                        <tr>
                                            <td class="py-2.5 px-3 font-mono font-bold text-indigo-600">{{ $item->batch?->batch_number }}</td>
                                            <td class="py-2.5 px-3 font-medium text-slate-900">{{ $item->batch?->product?->product_name }}</td>
                                            <td class="py-2.5 px-3 text-right font-mono text-slate-600">{{ number_format($item->system_quantity, 2) }}</td>
                                            <td class="py-2.5 px-3 text-right font-mono font-bold text-slate-900">{{ number_format($item->counted_quantity, 2) }}</td>
                                            <td class="py-2.5 px-3 text-right font-mono">
                                                @if($var > 0)
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">+{{ number_format($var, 2) }}</span>
                                                @elseif($var < 0)
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rmsc-red">{{ number_format($var, 2) }}</span>
                                                @else
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600">0.00</span>
                                                @endif
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
