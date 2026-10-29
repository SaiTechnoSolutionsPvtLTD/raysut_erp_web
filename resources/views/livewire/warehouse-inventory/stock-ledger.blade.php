<div class="space-y-5 min-w-0 max-w-full">
    <!-- Header -->
    <div class="pb-4 border-b border-slate-200/80">
        <h1 class="font-heading font-bold text-xl text-slate-900 leading-tight">Stock Ledger</h1>
        <p class="text-[11px] text-slate-500 mt-0.5">Real-time immutable stock movement audit trail, signed batch deductions, and running balances</p>
    </div>

    <!-- Filters (responsive grid, never wider than the container) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-6 gap-2 min-w-0">
        <!-- Search Bar -->
        <div class="relative min-w-0">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 z-10">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search product, SKU, batch..." style="padding-left: 2.5rem !important;" class="w-full min-w-0 h-9 pr-8 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white focus:bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-medium text-slate-800 transition">
            @if($search)
                <button wire:click="$set('search', '')" type="button" class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600 transition cursor-pointer" title="Clear search">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            @endif
        </div>

        <!-- Warehouse Filter -->
        <select wire:model.live="warehouse_filter" class="w-full min-w-0 h-9 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white focus:bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-medium text-slate-800 transition pl-3 pr-8 cursor-pointer truncate">
            <option value="">All Warehouses</option>
            @foreach($warehouses as $wh)
                <option value="{{ $wh->warehouse_id }}">{{ $wh->warehouse_code }} ({{ $wh->warehouse_name }})</option>
            @endforeach
        </select>

        <!-- Branch Filter (HQ Only) -->
        @if($isHq)
            <select wire:model.live="branch_filter" class="w-full min-w-0 h-9 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white focus:bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-medium text-slate-800 transition pl-3 pr-8 cursor-pointer truncate">
                <option value="">All Branches</option>
                @foreach($branches as $b)
                    <option value="{{ $b->branch_id }}">{{ $b->branch_code }} - {{ $b->branch_name }}</option>
                @endforeach
            </select>
        @endif

        <!-- Movement Type Filter -->
        <select wire:model.live="movement_type_filter" class="w-full min-w-0 h-9 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white focus:bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-medium text-slate-800 transition pl-3 pr-8 cursor-pointer truncate">
            <option value="">All Movements</option>
            <option value="SALES_RETAIL">Retail Sales (Deduction)</option>
            <option value="SALES_RETAIL_VOID">Retail Void (Reversal)</option>
            <option value="SALES_WHOLESALE">Wholesale Invoice (Deduction)</option>
            <option value="SALES_WHOLESALE_VOID">Wholesale Void (Reversal)</option>
            <option value="OPENING_BALANCE">Opening Balance</option>
            <option value="INTAKE_GRN">GRN Intake</option>
            <option value="ADJUSTMENT">Stock Adjustment</option>
            <option value="TRANSFER_IN">Transfer In</option>
            <option value="TRANSFER_OUT">Transfer Out</option>
        </select>

        <!-- Duty Status Filter -->
        <select wire:model.live="duty_status_filter" class="w-full min-w-0 h-9 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white focus:bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-medium text-slate-800 transition pl-3 pr-8 cursor-pointer truncate">
            <option value="">All Duty Status</option>
            <option value="DUTY_PAID">Duty-Paid</option>
            <option value="DUTY_FREE_BONDED">Duty-Free / Bonded</option>
        </select>

        <!-- Actions -->
        <div class="flex items-center gap-2 min-w-0">
            @if($search || $warehouse_filter || $branch_filter || $product_filter || $movement_type_filter || $duty_status_filter || $date_from || $date_to)
                <button wire:click="clearFilters" type="button" class="h-9 px-2.5 bg-slate-100 hover:bg-slate-200/80 text-slate-600 hover:text-slate-900 text-xs font-medium rounded-xl transition flex items-center gap-1 cursor-pointer shrink-0" title="Clear filters">
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    <span>Clear</span>
                </button>
            @endif

            <button wire:click="exportCsv" type="button" class="h-9 px-3 flex-1 sm:flex-none bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-xl shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                <svg class="w-3.5 h-3.5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span>Export CSV</span>
            </button>
        </div>
    </div>

    <!-- Summary Strip (On-Hand, Reserved, Available) -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 min-w-0">
        <div class="bg-white rounded-xl border border-slate-200/80 p-3.5 shadow-xs flex items-center justify-between min-w-0">
            <div class="min-w-0">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Total On-Hand</span>
                <p class="text-base font-bold text-slate-900 font-mono mt-0.5 truncate">{{ number_format($summary['on_hand'], 2) }}</p>
            </div>
            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200/80 p-3.5 shadow-xs flex items-center justify-between min-w-0">
            <div class="min-w-0">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Reserved (Orders)</span>
                <p class="text-base font-bold text-amber-700 font-mono mt-0.5 truncate">{{ number_format($summary['reserved'], 2) }}</p>
            </div>
            <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200/80 p-3.5 shadow-xs flex items-center justify-between min-w-0">
            <div class="min-w-0">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Available to Sell</span>
                <p class="text-base font-bold text-emerald-700 font-mono mt-0.5 truncate">{{ number_format($summary['available'], 2) }}</p>
            </div>
            <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>
    </div>

    <!-- Table Card -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden min-w-0 max-w-full">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50/50 text-slate-500 font-bold uppercase tracking-wider border-b border-slate-200/80 text-[11px]">
                    <tr>
                        <th class="py-3 px-4">Date / User</th>
                        <th class="py-3 px-3">Location</th>
                        <th class="py-3 px-3">Product / Batch</th>
                        <th class="py-3 px-3">Movement</th>
                        <th class="py-3 px-3 text-right">Qty</th>
                        <th class="py-3 px-3 text-right">Balance</th>
                        <th class="py-3 px-4">Reference</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @php
                        $typeColors = [
                            'SALES_RETAIL' => 'bg-amber-50 text-amber-800 border-amber-200',
                            'SALES_RETAIL_VOID' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                            'SALES_WHOLESALE' => 'bg-blue-50 text-blue-800 border-blue-200',
                            'SALES_WHOLESALE_VOID' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                            'OPENING_BALANCE' => 'bg-slate-100 text-slate-700 border-slate-200',
                            'INTAKE_GRN' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                            'ADJUSTMENT' => 'bg-violet-50 text-violet-700 border-violet-200',
                            'TRANSFER_IN' => 'bg-teal-50 text-teal-700 border-teal-200',
                            'TRANSFER_OUT' => 'bg-orange-50 text-orange-700 border-orange-200',
                        ];
                    @endphp
                    @forelse($entries as $entry)
                        <tr class="hover:bg-slate-50/60 transition align-top">
                            {{-- Date / User --}}
                            <td class="py-3 px-4">
                                <div class="font-mono text-[11px] text-slate-600 whitespace-nowrap">{{ $entry->created_at?->format('d M Y H:i') }}</div>
                                <div class="text-[10px] text-slate-400 mt-0.5 truncate max-w-[130px]" title="{{ $entry->user?->name ?? 'System' }}">{{ $entry->user?->name ?? 'System' }}</div>
                            </td>

                            {{-- Location: Warehouse + Branch --}}
                            <td class="py-3 px-3">
                                <div class="font-semibold text-slate-800 break-words">{{ $entry->warehouse?->warehouse_code ?? "WH-{$entry->warehouse_id}" }}</div>
                                <span class="inline-block mt-1 px-1.5 py-0.5 rounded bg-slate-100 text-[10px] font-mono font-semibold text-slate-600">{{ $entry->branch?->branch_code ?? 'HQ' }}</span>
                            </td>

                            {{-- Product / SKU / Batch / Duty --}}
                            <td class="py-3 px-3 max-w-[260px] xl:max-w-[340px]">
                                <div class="font-semibold text-slate-900 truncate" title="{{ $entry->product?->product_name }}">{{ $entry->product?->product_name ?? 'N/A' }}</div>
                                <div class="text-[10px] font-mono text-slate-400 truncate">{{ $entry->product?->product_code }}</div>
                                <div class="flex flex-wrap items-center gap-1.5 mt-1">
                                    <span class="font-mono text-[10px] text-slate-600 break-all">{{ $entry->batch?->batch_number ?? "B-{$entry->batch_id}" }}</span>
                                    @if(($entry->batch?->duty_status ?? '') === 'DUTY_PAID')
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[9px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 whitespace-nowrap">Duty-Paid</span>
                                    @else
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[9px] font-bold bg-purple-50 text-purple-700 border border-purple-200 whitespace-nowrap">Bonded</span>
                                    @endif
                                </div>
                            </td>

                            {{-- Movement --}}
                            <td class="py-3 px-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border leading-tight {{ $typeColors[$entry->movement_type] ?? 'bg-slate-100 text-slate-700 border-slate-200' }}">
                                    {{ str_replace('_', ' ', $entry->movement_type) }}
                                </span>
                            </td>

                            {{-- Qty --}}
                            <td class="py-3 px-3 text-right font-mono font-bold whitespace-nowrap {{ (float)$entry->quantity < 0 ? 'text-amber-800' : 'text-emerald-700' }}">
                                {{ (float)$entry->quantity > 0 ? '+' : '' }}{{ number_format((float)$entry->quantity, 2) }}
                            </td>

                            {{-- Balance --}}
                            <td class="py-3 px-3 text-right font-mono font-semibold text-slate-800 whitespace-nowrap">
                                {{ number_format((float)$entry->balance_after, 2) }}
                            </td>

                            {{-- Reference --}}
                            <td class="py-3 px-4 max-w-[180px]">
                                @if($entry->reference_type === 'retail_sale' && Route::has('retail.sales.show') && $entry->reference_id)
                                    <a href="{{ route('retail.sales.show', $entry->reference_id) }}" class="font-mono text-rmsc-navy font-semibold hover:underline break-all">
                                        {{ $entry->reference_no ?? ('Receipt #' . $entry->reference_id) }}
                                    </a>
                                @elseif($entry->reference_type === 'sales_invoice' && Route::has('wholesale.invoices.show') && $entry->reference_id)
                                    <a href="{{ route('wholesale.invoices.show', $entry->reference_id) }}" class="font-mono text-rmsc-navy font-semibold hover:underline break-all">
                                        {{ $entry->reference_no ?? ('Invoice #' . $entry->reference_id) }}
                                    </a>
                                @elseif($entry->reference_type === 'sales_order' && Route::has('wholesale.orders.show') && $entry->reference_id)
                                    <a href="{{ route('wholesale.orders.show', $entry->reference_id) }}" class="font-mono text-rmsc-navy font-semibold hover:underline break-all">
                                        {{ $entry->reference_no ?? ('Order #' . $entry->reference_id) }}
                                    </a>
                                @elseif($entry->reference_type === 'delivery_note' && Route::has('wholesale.delivery-notes.show') && $entry->reference_id)
                                    <a href="{{ route('wholesale.delivery-notes.show', $entry->reference_id) }}" class="font-mono text-rmsc-navy font-semibold hover:underline break-all">
                                        {{ $entry->reference_no ?? ('DN #' . $entry->reference_id) }}
                                    </a>
                                @else
                                    <span class="font-mono text-slate-600 break-all">{{ $entry->reference_no ?? ($entry->reference_type . ' #' . $entry->reference_id) }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-500">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-10 h-10 text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    <p class="text-sm font-semibold text-slate-700">No stock ledger entries found.</p>
                                    <p class="text-xs text-slate-400 mt-1">Try adjusting your filters or date range.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-200/80">
            {{ $entries->links() }}
        </div>
    </div>
</div>