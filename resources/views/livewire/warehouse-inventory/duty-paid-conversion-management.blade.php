<div>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Duty-Paid Conversions</h1>
                <p class="text-sm text-slate-500 mt-1">Phase 2 Scope: Convert stock from 3 Duty-Free Bonded Warehouses to Duty-Paid status & transfer to Main Distribution Center.</p>
            </div>
            <div>
                <button wire:click="openCreateModal" class="inline-flex items-center gap-2 px-4 py-2.5 bg-rmsc-red hover:bg-rmsc-red-hover text-white text-sm font-semibold rounded-xl shadow-xs transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    + Execute Duty-Paid Conversion
                </button>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Visual Banner Flow -->
        <div class="bg-gradient-to-r from-rmsc-navy via-slate-800 to-slate-900 rounded-2xl p-6 text-white shadow-md relative overflow-hidden">
            <div class="flex flex-col md:flex-row items-center justify-between gap-6 relative z-10">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-rmsc-red/20 border border-rmsc-red/40 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6 text-rmsc-red" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold">Bonded ➔ Duty-Paid Conversion Flow</h3>
                        <p class="text-xs text-slate-300">Customs Clearance via Royal Oman Police Bayan Portal | Duty Assessment & Stock Status Upgrade</p>
                    </div>
                </div>

                <div class="flex items-center gap-3 text-xs font-mono">
                    <div class="px-3 py-1.5 bg-white/10 rounded-lg border border-white/20 text-center">
                        <span class="block text-slate-400 text-[10px]">DUTY-FREE BONDED</span>
                        <span class="font-bold text-amber-300">3 Warehouses</span>
                    </div>
                    <span class="text-slate-400 font-bold">➔</span>
                    <div class="px-3 py-1.5 bg-emerald-500/20 rounded-lg border border-emerald-400/30 text-center">
                        <span class="block text-emerald-300 text-[10px]">BAYAN CLEARED</span>
                        <span class="font-bold text-emerald-200">100% Duty Paid</span>
                    </div>
                    <span class="text-slate-400 font-bold">➔</span>
                    <div class="px-3 py-1.5 bg-white/10 rounded-lg border border-white/20 text-center">
                        <span class="block text-slate-400 text-[10px]">MAIN WAREHOUSE</span>
                        <span class="font-bold text-sky-300">Duty-Paid Stock</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Conversions Table -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                <div class="relative max-w-sm w-full">
                    <svg class="w-5 h-5 absolute left-3.5 top-2.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search conversion ref or Bayan #..." class="w-full pl-10 pr-4 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-rmsc-red">
                </div>
                <span class="text-xs font-semibold px-3 py-1.5 bg-slate-100 text-slate-700 rounded-lg border border-slate-200">
                    {{ $conversions->total() }} Conversions Processed
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-50 text-xs font-bold uppercase text-slate-500 tracking-wider border-b border-slate-100">
                        <tr>
                            <th class="py-3.5 px-5">Ref #</th>
                            <th class="py-3.5 px-5">Source Bonded Warehouse</th>
                            <th class="py-3.5 px-5">Destination Main Warehouse</th>
                            <th class="py-3.5 px-5">Bayan Declaration #</th>
                            <th class="py-3.5 px-5 text-right">Total Duty Paid</th>
                            <th class="py-3.5 px-5">Converted Date</th>
                            <th class="py-3.5 px-5">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($conversions as $conv)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3.5 px-5 font-mono font-bold text-rmsc-navy">{{ $conv->conversion_number }}</td>
                                <td class="py-3.5 px-5">
                                    <span class="font-semibold text-slate-800">{{ $conv->items->first()?->batch?->product?->product_name ?? 'Bonded Product' }}</span>
                                    <span class="block text-[11px] font-mono text-amber-600">Batch: {{ $conv->items->first()?->batch?->batch_number }}</span>
                                </td>
                                <td class="py-3.5 px-5">
                                    <span class="font-semibold text-slate-800">{{ $conv->warehouse?->warehouse_name }}</span>
                                    <span class="block text-[11px] font-mono text-sky-600">{{ $conv->warehouse?->warehouse_code }}</span>
                                </td>
                                <td class="py-3.5 px-5 font-mono text-xs font-bold text-slate-700">BAYAN-2026-DP</td>
                                <td class="py-3.5 px-5 text-right font-mono font-bold text-emerald-600">OMR {{ number_format($conv->items->sum('duty_paid_unit_cost'), 3) }}</td>
                                <td class="py-3.5 px-5 text-xs text-slate-500">{{ $conv->conversion_date ? $conv->conversion_date->format('d-M-Y') : 'N/A' }}</td>
                                <td class="py-3.5 px-5">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        {{ $conv->status }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-slate-400">No duty-paid conversions recorded yet. Click above to execute a conversion.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-slate-100">
                {{ $conversions->links() }}
            </div>
        </div>
    </div>

    <!-- Create Duty-Paid Conversion Modal -->
    @if ($showCreateModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 relative animate-in fade-in zoom-in duration-200">
                <h3 class="text-lg font-bold text-slate-900 mb-1">Execute Duty-Paid Conversion</h3>
                <p class="text-xs text-slate-500 mb-5">Convert stock batch from Duty-Free Bonded to Duty-Paid & transfer into Main Warehouse.</p>

                <form wire:submit.prevent="executeConversion" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Source Duty-Free Bonded Warehouse</label>
                        <select wire:model.live="bonded_warehouse_id" class="w-full text-sm bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 focus:bg-white focus:ring-2 focus:ring-rmsc-red">
                            <option value="">Select Duty-Free Warehouse...</option>
                            @foreach ($bondedWarehouses as $wh)
                                <option value="{{ $wh->warehouse_id }}">{{ $wh->warehouse_code }} — {{ $wh->warehouse_name }}</option>
                            @endforeach
                        </select>
                        @error('bonded_warehouse_id') <span class="text-xs text-rose-500 font-semibold mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Bonded Stock Batch to Convert</label>
                        <select wire:model.live="bonded_batch_id" class="w-full text-sm font-mono bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 focus:bg-white focus:ring-2 focus:ring-rmsc-red">
                            <option value="">Select Bonded Batch...</option>
                            @foreach ($availableBatches as $stock)
                                <option value="{{ $stock->batch_id }}">
                                    {{ $stock->batch?->batch_number }} | {{ $stock->product?->product_name }} (Avail: {{ (float)$stock->quantity_available }})
                                </option>
                            @endforeach
                        </select>
                        @error('bonded_batch_id') <span class="text-xs text-rose-500 font-semibold mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Quantity to Convert</label>
                            <input type="number" step="0.001" wire:model="quantity_converted" class="w-full text-sm font-mono bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5">
                            @error('quantity_converted') <span class="text-xs text-rose-500 font-semibold mt-1">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Duty Amount Per Unit (OMR)</label>
                            <input type="number" step="0.001" wire:model="duty_amount_per_unit" class="w-full text-sm font-mono bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5">
                            @error('duty_amount_per_unit') <span class="text-xs text-rose-500 font-semibold mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Destination Main Warehouse</label>
                            <select wire:model="destination_warehouse_id" class="w-full text-sm bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5">
                                <option value="">Select Main Warehouse...</option>
                                @foreach ($mainWarehouses as $wh)
                                    <option value="{{ $wh->warehouse_id }}">{{ $wh->warehouse_code }} — {{ $wh->warehouse_name }}</option>
                                @endforeach
                            </select>
                            @error('destination_warehouse_id') <span class="text-xs text-rose-500 font-semibold mt-1">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">ROP Bayan Customs Declaration #</label>
                            <input type="text" wire:model="bayan_declaration_no" class="w-full text-sm font-mono bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5">
                            @error('bayan_declaration_no') <span class="text-xs text-rose-500 font-semibold mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="p-3 bg-amber-50 border border-amber-200/80 rounded-xl text-xs text-amber-800 flex items-center justify-between">
                        <span>Total Duty Assessment Payable:</span>
                        <span class="font-mono font-bold text-sm text-amber-950">OMR {{ number_format((float)$quantity_converted * (float)$duty_amount_per_unit, 3) }}</span>
                    </div>

                    <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100">
                        <button type="button" wire:click="$set('showCreateModal', false)" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100 rounded-xl">Cancel</button>
                        <button type="submit" class="px-5 py-2 text-sm font-semibold text-white bg-rmsc-red hover:bg-rmsc-red-hover rounded-xl shadow-xs">Execute Duty Conversion</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
