<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Stock On Hand & Ledger</h1>
            <p class="text-sm text-slate-500">Real-time inventory levels across Muscat Central Bonded Warehouse, Salalah Depot, and Sohar Terminal.</p>
        </div>
    </div>

    <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 flex flex-col md:flex-row gap-4 items-center justify-between">
        <div class="w-full md:w-1/3">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search Product SKU, Name or Batch..." class="w-full text-sm rounded-lg border-slate-300 focus:ring-rmsc-red focus:border-rmsc-red">
        </div>
        <div class="w-full md:w-auto">
            <select wire:model.live="warehouse_id" class="text-sm rounded-lg border-slate-300">
                <option value="">All Warehouses & Depots</option>
                @foreach($warehouses as $w)
                    <option value="{{ $w->warehouse_id }}">{{ $w->warehouse_name }} ({{ $w->warehouse_code }})</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 font-semibold border-b border-slate-200">
                        <th class="py-3 px-4">Warehouse</th>
                        <th class="py-3 px-4">SKU / Product Name</th>
                        <th class="py-3 px-4">Batch Number & Duty Status</th>
                        <th class="py-3 px-4 text-right">On-Hand</th>
                        <th class="py-3 px-4 text-right">Reserved</th>
                        <th class="py-3 px-4 text-right">Available</th>
                        <th class="py-3 px-4">Last Updated</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($stocks as $stk)
                        <tr class="hover:bg-slate-50/80">
                            <td class="py-3 px-4">
                                <div class="font-bold text-slate-900">{{ $stk->warehouse?->warehouse_name }}</div>
                                <div class="text-xs text-slate-400 font-mono">{{ $stk->warehouse?->warehouse_code }}</div>
                            </td>
                            <td class="py-3 px-4">
                                <div class="font-bold text-slate-900">{{ $stk->product?->product_name }}</div>
                                <div class="text-xs font-mono text-slate-400">{{ $stk->product?->sku_code }}</div>
                            </td>
                            <td class="py-3 px-4">
                                <div class="font-mono text-xs font-bold text-indigo-600">{{ $stk->batch?->batch_number }}</div>
                                @if($stk->batch?->duty_status === 'DUTY_FREE_BONDED')
                                    <span class="inline-block px-1.5 py-0.5 text-[10px] bg-sky-100 text-sky-700 rounded font-semibold">Duty Free Bonded</span>
                                @else
                                    <span class="inline-block px-1.5 py-0.5 text-[10px] bg-emerald-100 text-emerald-700 rounded font-semibold">Duty Paid</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-right font-mono font-bold text-slate-900 text-base">
                                {{ number_format($stk->quantity_on_hand, 2) }}
                            </td>
                            <td class="py-3 px-4 text-right font-mono text-amber-600">
                                {{ number_format($stk->quantity_reserved, 2) }}
                            </td>
                            <td class="py-3 px-4 text-right font-mono font-bold text-emerald-600 text-base">
                                {{ number_format($stk->quantity_available, 2) }}
                            </td>
                            <td class="py-3 px-4 font-mono text-xs text-slate-400">
                                {{ $stk->last_updated_at?->format('Y-m-d H:i') ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-6 text-center text-slate-400">No warehouse stock records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-200">
            {{ $stocks->links() }}
        </div>
    </div>
</div>
