<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Physical Counts & Stock Adjustments</h1>
            <p class="text-sm text-slate-500">Record physical audit variances, bottle breakages, expiry write-offs, and theft adjustments.</p>
        </div>
    </div>

    <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search Adjustment Number..." class="w-full md:w-1/3 text-sm rounded-lg border-slate-300 focus:ring-rmsc-red focus:border-rmsc-red">
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 font-semibold border-b border-slate-200">
                        <th class="py-3 px-4">Adjustment Ref</th>
                        <th class="py-3 px-4">Warehouse</th>
                        <th class="py-3 px-4">Count Date</th>
                        <th class="py-3 px-4">Reason Code</th>
                        <th class="py-3 px-4">Approved By</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($adjustments as $adj)
                        <tr class="hover:bg-slate-50/80">
                            <td class="py-3 px-4 font-mono font-bold text-slate-900">{{ $adj->adjustment_number }}</td>
                            <td class="py-3 px-4 font-medium text-slate-900">{{ $adj->warehouse?->warehouse_name }}</td>
                            <td class="py-3 px-4 font-mono text-xs">{{ $adj->count_date?->format('Y-m-d') }}</td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-0.5 text-xs font-semibold bg-amber-100 text-amber-800 rounded">
                                    {{ $adj->reason_code }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-xs">{{ $adj->approver?->full_name ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-6 text-center text-slate-400">No stock adjustments found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-200">
            {{ $adjustments->links() }}
        </div>
    </div>
</div>
