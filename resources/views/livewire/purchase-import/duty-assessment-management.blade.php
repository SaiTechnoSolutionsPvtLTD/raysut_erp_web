<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Customs Duty Assessments & Landed Cost</h1>
            <p class="text-sm text-slate-500">ROP Customs duty assessments, CIF valuations, excise calculations, and port handling charges.</p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 font-semibold border-b border-slate-200">
                        <th class="py-3 px-4">Assessment ID</th>
                        <th class="py-3 px-4">Shipment Ref</th>
                        <th class="py-3 px-4 text-right">CIF Value (OMR)</th>
                        <th class="py-3 px-4 text-right">Duty Rate</th>
                        <th class="py-3 px-4 text-right">Duty Amount (OMR)</th>
                        <th class="py-3 px-4 text-right">Other Charges (OMR)</th>
                        <th class="py-3 px-4 text-right">Total Landed Cost (OMR)</th>
                        <th class="py-3 px-4">Assessed Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($assessments as $a)
                        <tr class="hover:bg-slate-50/80">
                            <td class="py-3 px-4 font-mono font-bold text-slate-900">#ASSESS-{{ str_pad($a->assessment_id, 4, '0', STR_PAD_LEFT) }}</td>
                            <td class="py-3 px-4 font-mono text-xs text-indigo-600 font-bold">{{ $a->shipment?->shipment_ref }}</td>
                            <td class="py-3 px-4 text-right font-mono">{{ number_format($a->cif_value, 3) }}</td>
                            <td class="py-3 px-4 text-right font-mono text-xs font-bold text-amber-600">{{ number_format($a->duty_rate_percent, 1) }}%</td>
                            <td class="py-3 px-4 text-right font-mono font-bold text-slate-900">{{ number_format($a->duty_amount, 3) }}</td>
                            <td class="py-3 px-4 text-right font-mono text-slate-500">{{ number_format($a->other_charges, 3) }}</td>
                            <td class="py-3 px-4 text-right font-mono font-bold text-emerald-600 text-base">
                                {{ number_format($a->total_landed_cost, 3) }}
                            </td>
                            <td class="py-3 px-4 font-mono text-xs">{{ $a->assessed_date?->format('Y-m-d') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-6 text-center text-slate-400">No duty assessments found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-200">
            {{ $assessments->links() }}
        </div>
    </div>
</div>
