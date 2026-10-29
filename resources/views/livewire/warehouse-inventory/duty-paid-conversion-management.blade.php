<div class="space-y-5 min-w-0 max-w-full">
    <!-- Header -->
    <div class="flex flex-wrap items-center justify-between gap-3 pb-4 border-b border-slate-200/80">
        <div class="min-w-0">
            <h1 class="font-heading font-bold text-xl text-slate-900 leading-tight">Duty-Paid Conversions</h1>
            <p class="text-[11px] text-slate-500 mt-0.5">Convert stock from duty-free bonded warehouses to duty-paid status and move it into a main distribution warehouse</p>
        </div>

        <button wire:click="openCreateModal" type="button"
                class="h-9 px-4 bg-rmsc-red hover:bg-rmsc-red-hover text-white text-xs font-semibold rounded-xl shadow-xs transition flex items-center gap-1.5 shrink-0 cursor-pointer">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>New Conversion</span>
        </button>
    </div>

    <!-- Conversion Flow -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-4">
        <div class="flex flex-col md:flex-row md:items-stretch gap-3">
            <!-- Step 1 -->
            <div class="flex-1 min-w-0 flex items-center gap-3 rounded-xl border border-amber-200/80 bg-amber-50/60 p-3">
                <div class="w-9 h-9 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center shrink-0">
                    <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/></svg>
                </div>
                <div class="min-w-0">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-amber-700">1 · Duty-Free Bonded</p>
                    <p class="text-sm font-semibold text-slate-900 truncate">{{ count($bondedWarehouses ?? []) }} bonded {{ count($bondedWarehouses ?? []) === 1 ? 'warehouse' : 'warehouses' }}</p>
                </div>
            </div>

            <div class="flex items-center justify-center text-slate-300 shrink-0">
                <svg class="w-5 h-5 rotate-90 md:rotate-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
            </div>

            <!-- Step 2 -->
            <div class="flex-1 min-w-0 flex items-center gap-3 rounded-xl border border-blue-200/80 bg-blue-50/60 p-3">
                <div class="w-9 h-9 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center shrink-0">
                    <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                </div>
                <div class="min-w-0">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-blue-700">2 · Customs Clearance</p>
                    <p class="text-sm font-semibold text-slate-900 truncate">Bayan declaration &amp; duty</p>
                </div>
            </div>

            <div class="flex items-center justify-center text-slate-300 shrink-0">
                <svg class="w-5 h-5 rotate-90 md:rotate-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
            </div>

            <!-- Step 3 -->
            <div class="flex-1 min-w-0 flex items-center gap-3 rounded-xl border border-emerald-200/80 bg-emerald-50/60 p-3">
                <div class="w-9 h-9 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                    <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </div>
                <div class="min-w-0">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-700">3 · Main Warehouse</p>
                    <p class="text-sm font-semibold text-slate-900 truncate">Duty-paid stock</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Conversions Table Card -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden min-w-0 max-w-full">
        <!-- Toolbar -->
        <div class="p-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
            <div class="relative w-full sm:w-72">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 z-10">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search conversion ref or Bayan #..." style="padding-left: 2.5rem !important;"
                       class="w-full h-9 pr-8 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white focus:bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-medium text-slate-800 transition">
                @if($search)
                    <button wire:click="$set('search', '')" type="button" class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600 transition cursor-pointer" title="Clear search">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                @endif
            </div>

            <span class="text-[11px] font-semibold px-2.5 py-1.5 bg-slate-100 text-slate-600 rounded-lg border border-slate-200 whitespace-nowrap">
                {{ number_format($conversions->total()) }} {{ $conversions->total() === 1 ? 'conversion' : 'conversions' }}
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50/50 text-slate-500 font-bold uppercase tracking-wider border-b border-slate-200/80 text-[11px]">
                    <tr>
                        <th class="py-3 px-4">Ref # / Date</th>
                        <th class="py-3 px-3">Product / Batch</th>
                        <th class="py-3 px-3">Destination Warehouse</th>
                        <th class="py-3 px-3">Bayan Declaration #</th>
                        <th class="py-3 px-3 text-right">Total Duty</th>
                        <th class="py-3 px-4">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @php
                        $statusColors = [
                            'COMPLETED' => ['bg-emerald-50 text-emerald-700 border-emerald-200', 'bg-emerald-500'],
                            'CONVERTED' => ['bg-emerald-50 text-emerald-700 border-emerald-200', 'bg-emerald-500'],
                            'POSTED'    => ['bg-emerald-50 text-emerald-700 border-emerald-200', 'bg-emerald-500'],
                            'APPROVED'  => ['bg-emerald-50 text-emerald-700 border-emerald-200', 'bg-emerald-500'],
                            'DRAFT'     => ['bg-amber-50 text-amber-700 border-amber-200', 'bg-amber-500'],
                            'PENDING'   => ['bg-amber-50 text-amber-700 border-amber-200', 'bg-amber-500'],
                            'CANCELLED' => ['bg-rose-50 text-rose-700 border-rose-200', 'bg-rose-500'],
                            'VOID'      => ['bg-rose-50 text-rose-700 border-rose-200', 'bg-rose-500'],
                            'VOIDED'    => ['bg-rose-50 text-rose-700 border-rose-200', 'bg-rose-500'],
                            'REJECTED'  => ['bg-rose-50 text-rose-700 border-rose-200', 'bg-rose-500'],
                        ];
                    @endphp

                    @forelse ($conversions as $conv)
                        @php
                            $firstItem = $conv->items->first();
                            $extraItems = max($conv->items->count() - 1, 0);
                            $statusKey = strtoupper((string) $conv->status);
                            [$badgeClass, $dotClass] = $statusColors[$statusKey] ?? ['bg-slate-100 text-slate-700 border-slate-200', 'bg-slate-400'];
                        @endphp
                        <tr class="hover:bg-slate-50/60 transition align-top">
                            {{-- Ref / Date --}}
                            <td class="py-3 px-4 whitespace-nowrap">
                                <div class="font-mono font-bold text-rmsc-navy">{{ $conv->conversion_number }}</div>
                                <div class="font-mono text-[10px] text-slate-400 mt-0.5">{{ $conv->conversion_date ? $conv->conversion_date->format('d M Y') : 'N/A' }}</div>
                            </td>

                            {{-- Product / Batch --}}
                            <td class="py-3 px-3 max-w-[260px]">
                                <div class="font-semibold text-slate-900 truncate" title="{{ $firstItem?->batch?->product?->product_name }}">
                                    {{ $firstItem?->batch?->product?->product_name ?? 'Bonded Product' }}
                                </div>
                                <div class="flex flex-wrap items-center gap-1.5 mt-0.5">
                                    <span class="font-mono text-[10px] text-amber-700 break-all">{{ $firstItem?->batch?->batch_number ?? '—' }}</span>
                                    @if($extraItems > 0)
                                        <span class="px-1.5 py-0.5 rounded bg-slate-100 text-[10px] font-semibold text-slate-600 whitespace-nowrap">+{{ $extraItems }} more</span>
                                    @endif
                                </div>
                            </td>

                            {{-- Destination --}}
                            <td class="py-3 px-3">
                                <div class="font-semibold text-slate-800 break-words">{{ $conv->warehouse?->warehouse_name ?? '—' }}</div>
                                @if($conv->warehouse?->warehouse_code)
                                    <span class="inline-block mt-0.5 px-1.5 py-0.5 rounded bg-sky-50 text-[10px] font-mono font-semibold text-sky-700">{{ $conv->warehouse->warehouse_code }}</span>
                                @endif
                            </td>

                            {{-- Bayan # --}}
                            <td class="py-3 px-3 font-mono font-semibold text-slate-700 break-all max-w-[180px]">
                                {{ $conv->bayan_declaration_no ?? '—' }}
                            </td>

                            {{-- Total duty --}}
                            <td class="py-3 px-3 text-right font-mono font-bold text-emerald-700 whitespace-nowrap">
                                OMR {{ number_format($conv->items->sum(fn($item) => (float)$item->quantity_converted * (float)$item->duty_paid_unit_cost) ?: (float)$conv->items->sum('duty_paid_unit_cost'), 3) }}
                            </td>

                            {{-- Status --}}
                            <td class="py-3 px-4 whitespace-nowrap">
                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $badgeClass }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $dotClass }}"></span>
                                    {{ ucfirst(strtolower(str_replace('_', ' ', (string) $conv->status))) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-10 h-10 text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                    @if($search)
                                        <p class="text-sm font-semibold text-slate-700">No conversions match your search.</p>
                                        <p class="text-xs text-slate-400 mt-1">Try a different conversion ref or Bayan number.</p>
                                    @else
                                        <p class="text-sm font-semibold text-slate-700">No duty-paid conversions recorded yet.</p>
                                        <p class="text-xs text-slate-400 mt-1">Convert bonded stock to duty-paid to see it here.</p>
                                        <button wire:click="openCreateModal" type="button" class="mt-4 h-9 px-4 bg-rmsc-red hover:bg-rmsc-red-hover text-white text-xs font-semibold rounded-xl shadow-xs transition cursor-pointer">
                                            New Conversion
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-200/80">
            {{ $conversions->links() }}
        </div>
    </div>

    <!-- Create Duty-Paid Conversion Modal -->
    @if ($showCreateModal)
        @php
            $selectedStock = $bonded_batch_id ? collect($availableBatches)->firstWhere('batch_id', $bonded_batch_id) : null;
            $selectedAvail = $selectedStock ? (float) $selectedStock->quantity_available : null;
            $totalDuty = (float) $quantity_converted * (float) $duty_amount_per_unit;
            $overQty = $selectedAvail !== null && (float) $quantity_converted > $selectedAvail;
        @endphp

        <div class="fixed inset-0 z-50 overflow-y-auto"
             role="dialog" aria-modal="true" aria-labelledby="conversion-modal-title"
             x-data="{
                init() { document.body.classList.add('overflow-hidden'); },
                destroy() { document.body.classList.remove('overflow-hidden'); }
             }"
             @keydown.escape.window="$wire.set('showCreateModal', false)">

            <!-- Backdrop -->
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs" wire:click="$set('showCreateModal', false)"></div>

            <!-- Panel wrapper (scrolls on short screens) -->
            <div class="relative flex min-h-full items-center justify-center p-4 pointer-events-none">
                <div class="pointer-events-auto w-full max-w-xl bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden">
                    <!-- Modal header -->
                    <div class="px-6 py-4 border-b border-slate-100 flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <h3 id="conversion-modal-title" class="text-base font-bold text-slate-900">Execute Duty-Paid Conversion</h3>
                            <p class="text-[11px] text-slate-500 mt-0.5">Convert a bonded stock batch to duty-paid and move it into a main warehouse.</p>
                        </div>
                        <button type="button" wire:click="$set('showCreateModal', false)" aria-label="Close"
                                class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition shrink-0 cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <form wire:submit.prevent="executeConversion">
                        <div class="px-6 py-5 space-y-5">
                            <!-- Section: Source -->
                            <div class="space-y-3">
                                <p class="text-[11px] font-bold uppercase tracking-wider text-amber-700">Source (Bonded)</p>

                                <div>
                                    <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-1">Bonded Warehouse</label>
                                    <select wire:model.live="bonded_warehouse_id"
                                            class="w-full h-9 rounded-xl border bg-slate-50/60 hover:bg-white focus:bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-medium text-slate-800 transition pl-3 pr-8 cursor-pointer @error('bonded_warehouse_id') border-rose-300 @else border-slate-200 @enderror">
                                        <option value="">Select duty-free warehouse...</option>
                                        @foreach ($bondedWarehouses as $wh)
                                            <option value="{{ $wh->warehouse_id }}">{{ $wh->warehouse_code }} — {{ $wh->warehouse_name }}</option>
                                        @endforeach
                                    </select>
                                    @error('bonded_warehouse_id') <p class="mt-1 text-[11px] font-semibold text-rose-600">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-1">Bonded Stock Batch</label>
                                    <select wire:model.live="bonded_batch_id" @disabled(! $bonded_warehouse_id)
                                            class="w-full h-9 rounded-xl border bg-slate-50/60 hover:bg-white focus:bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-mono text-slate-800 transition pl-3 pr-8 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed @error('bonded_batch_id') border-rose-300 @else border-slate-200 @enderror">
                                        <option value="">{{ $bonded_warehouse_id ? 'Select bonded batch...' : 'Select a warehouse first' }}</option>
                                        @foreach ($availableBatches as $stock)
                                            <option value="{{ $stock->batch_id }}">
                                                {{ $stock->batch?->batch_number }} | {{ $stock->product?->product_name }} (Avail: {{ (float) $stock->quantity_available }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @if($bonded_warehouse_id && count($availableBatches) === 0)
                                        <p class="mt-1 text-[11px] text-amber-700">No bonded batches with available stock in this warehouse.</p>
                                    @endif
                                    @error('bonded_batch_id') <p class="mt-1 text-[11px] font-semibold text-rose-600">{{ $message }}</p> @enderror
                                </div>
                            </div>

                            <!-- Section: Quantity & Duty -->
                            <div class="space-y-3">
                                <p class="text-[11px] font-bold uppercase tracking-wider text-blue-700">Quantity &amp; Duty</p>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-1">Quantity to Convert</label>
                                        <input type="number" step="0.001" min="0" @if($selectedAvail !== null) max="{{ $selectedAvail }}" @endif wire:model.live.debounce.300ms="quantity_converted"
                                               class="w-full h-9 rounded-xl border bg-slate-50/60 hover:bg-white focus:bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 px-3 text-xs font-mono text-slate-800 transition @error('quantity_converted') border-rose-300 @else border-slate-200 @enderror">
                                        @if($selectedAvail !== null)
                                            <p class="mt-1 text-[11px] {{ $overQty ? 'font-semibold text-rose-600' : 'text-slate-400' }}">
                                                {{ $overQty ? 'Exceeds available stock:' : 'Available:' }} {{ rtrim(rtrim(number_format($selectedAvail, 3, '.', ''), '0'), '.') }}
                                            </p>
                                        @endif
                                        @error('quantity_converted') <p class="mt-1 text-[11px] font-semibold text-rose-600">{{ $message }}</p> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-1">Duty per Unit</label>
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-[11px] font-bold text-slate-400 pointer-events-none z-10">OMR</span>
                                            <input type="number" step="0.001" min="0" wire:model.live.debounce.300ms="duty_amount_per_unit" style="padding-left: 3rem !important;"
                                                   class="w-full h-9 pr-3 rounded-xl border bg-slate-50/60 hover:bg-white focus:bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-mono text-slate-800 transition @error('duty_amount_per_unit') border-rose-300 @else border-slate-200 @enderror">
                                        </div>
                                        @error('duty_amount_per_unit') <p class="mt-1 text-[11px] font-semibold text-rose-600">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Section: Destination & Customs -->
                            <div class="space-y-3">
                                <p class="text-[11px] font-bold uppercase tracking-wider text-emerald-700">Destination &amp; Customs</p>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-1">Main Warehouse</label>
                                        <select wire:model="destination_warehouse_id"
                                                class="w-full h-9 rounded-xl border bg-slate-50/60 hover:bg-white focus:bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-medium text-slate-800 transition pl-3 pr-8 cursor-pointer @error('destination_warehouse_id') border-rose-300 @else border-slate-200 @enderror">
                                            <option value="">Select main warehouse...</option>
                                            @foreach ($mainWarehouses as $wh)
                                                <option value="{{ $wh->warehouse_id }}">{{ $wh->warehouse_code }} — {{ $wh->warehouse_name }}</option>
                                            @endforeach
                                        </select>
                                        @error('destination_warehouse_id') <p class="mt-1 text-[11px] font-semibold text-rose-600">{{ $message }}</p> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-1">ROP Bayan Declaration #</label>
                                        <input type="text" wire:model="bayan_declaration_no" placeholder="e.g. BAYAN-2026-000123"
                                               class="w-full h-9 rounded-xl border bg-slate-50/60 hover:bg-white focus:bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 px-3 text-xs font-mono text-slate-800 placeholder:text-slate-400 transition @error('bayan_declaration_no') border-rose-300 @else border-slate-200 @enderror">
                                        @error('bayan_declaration_no') <p class="mt-1 text-[11px] font-semibold text-rose-600">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Total -->
                            <div class="p-3 bg-amber-50 border border-amber-200/80 rounded-xl flex items-center justify-between gap-3">
                                <span class="text-xs text-amber-800">Total duty assessment payable</span>
                                <span class="font-mono font-bold text-sm text-amber-950 whitespace-nowrap">OMR {{ number_format($totalDuty, 3) }}</span>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50 flex items-center justify-end gap-2">
                            <button type="button" wire:click="$set('showCreateModal', false)"
                                    class="h-9 px-4 text-xs font-semibold text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-xl transition cursor-pointer">
                                Cancel
                            </button>
                            <button type="submit" wire:loading.attr="disabled" wire:target="executeConversion"
                                    class="h-9 px-5 text-xs font-semibold text-white bg-rmsc-red hover:bg-rmsc-red-hover rounded-xl shadow-xs transition flex items-center gap-1.5 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed">
                                <svg wire:loading wire:target="executeConversion" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                                <span wire:loading.remove wire:target="executeConversion">Execute Conversion</span>
                                <span wire:loading wire:target="executeConversion">Processing…</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>