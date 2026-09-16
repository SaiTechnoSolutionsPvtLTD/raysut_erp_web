<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Import Shipments & Bayan Customs</h1>
            <p class="text-sm text-slate-500">Track sea/air import containers, Bayan customs declarations, and port stages.</p>
        </div>
    </div>

    <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search Shipment Ref, Bayan No, or Container..." class="w-full md:w-1/3 text-sm rounded-lg border-slate-300 focus:ring-rmsc-red focus:border-rmsc-red">
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 font-semibold border-b border-slate-200">
                        <th class="py-3 px-4">Shipment Ref</th>
                        <th class="py-3 px-4">PO Ref</th>
                        <th class="py-3 px-4">Bayan Declaration No</th>
                        <th class="py-3 px-4">B/L Container No</th>
                        <th class="py-3 px-4">Port of Entry</th>
                        <th class="py-3 px-4">Arrival Date</th>
                        <th class="py-3 px-4">Stage</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($shipments as $s)
                        <tr class="hover:bg-slate-50/80">
                            <td class="py-3 px-4 font-mono font-bold text-slate-900">{{ $s->shipment_ref }}</td>
                            <td class="py-3 px-4 font-mono text-xs text-indigo-600">{{ $s->purchaseOrder?->po_number }}</td>
                            <td class="py-3 px-4 font-mono text-xs font-semibold text-slate-900">{{ $s->bayan_declaration_no ?? '-' }}</td>
                            <td class="py-3 px-4 font-mono text-xs text-slate-500">{{ $s->bl_container_no ?? '-' }}</td>
                            <td class="py-3 px-4">{{ $s->port_of_entry ?? '-' }}</td>
                            <td class="py-3 px-4 font-mono text-xs">{{ $s->actual_arrival_date?->format('Y-m-d') ?? $s->eta_date?->format('Y-m-d') }}</td>
                            <td class="py-3 px-4">
                                <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-amber-100 text-amber-800">
                                    {{ $s->stage }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-6 text-center text-slate-400">No import shipments found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-200">
            {{ $shipments->links() }}
        </div>
    </div>
</div>
