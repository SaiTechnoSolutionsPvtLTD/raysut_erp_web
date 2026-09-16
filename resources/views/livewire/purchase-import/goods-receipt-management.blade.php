<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Goods Receipt Notes (GRN)</h1>
            <p class="text-sm text-slate-500">Warehouse intake logs, physical case counts, and batch registrations.</p>
        </div>
    </div>

    <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search GRN Number..." class="w-full md:w-1/3 text-sm rounded-lg border-slate-300 focus:ring-rmsc-red focus:border-rmsc-red">
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 font-semibold border-b border-slate-200">
                        <th class="py-3 px-4">GRN Number</th>
                        <th class="py-3 px-4">PO & Shipment</th>
                        <th class="py-3 px-4">Receiving Warehouse</th>
                        <th class="py-3 px-4">Received Date</th>
                        <th class="py-3 px-4">Received By</th>
                        <th class="py-3 px-4">Discrepancy</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($grns as $g)
                        <tr class="hover:bg-slate-50/80">
                            <td class="py-3 px-4 font-mono font-bold text-slate-900">{{ $g->grn_number }}</td>
                            <td class="py-3 px-4">
                                <div class="font-mono text-xs text-indigo-600">{{ $g->purchaseOrder?->po_number }}</div>
                                <div class="text-xs text-slate-400 font-mono">{{ $g->shipment?->shipment_ref ?? '-' }}</div>
                            </td>
                            <td class="py-3 px-4 font-medium text-slate-900">{{ $g->warehouse?->warehouse_name }}</td>
                            <td class="py-3 px-4 font-mono text-xs">{{ $g->received_date?->format('Y-m-d') }}</td>
                            <td class="py-3 px-4 text-xs">{{ $g->receiver?->full_name ?? '-' }}</td>
                            <td class="py-3 px-4">
                                @if($g->has_discrepancy)
                                    <span class="px-2 py-0.5 text-xs font-semibold bg-rose-100 text-rose-800 rounded">Discrepancy</span>
                                @else
                                    <span class="px-2 py-0.5 text-xs font-semibold bg-emerald-100 text-emerald-800 rounded">Clean Receipt</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-6 text-center text-slate-400">No GRNs found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-200">
            {{ $grns->links() }}
        </div>
    </div>
</div>
