<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Batch & Expiry Registry</h1>
            <p class="text-sm text-slate-500">Track liquor product lot numbers, bottling/manufacture dates, shelf-life expiry, and duty status.</p>
        </div>
    </div>

    <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search Batch Number or Product..." class="w-full md:w-1/3 text-sm rounded-lg border-slate-300 focus:ring-rmsc-red focus:border-rmsc-red">
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 font-semibold border-b border-slate-200">
                        <th class="py-3 px-4">Batch Number</th>
                        <th class="py-3 px-4">Product</th>
                        <th class="py-3 px-4">Source GRN</th>
                        <th class="py-3 px-4">Manufacture Date</th>
                        <th class="py-3 px-4">Expiry Date</th>
                        <th class="py-3 px-4">Duty Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($batches as $b)
                        <tr class="hover:bg-slate-50/80">
                            <td class="py-3 px-4 font-mono font-bold text-indigo-600">{{ $b->batch_number }}</td>
                            <td class="py-3 px-4">
                                <div class="font-bold text-slate-900">{{ $b->product?->product_name }}</div>
                                <div class="text-xs font-mono text-slate-400">{{ $b->product?->sku_code }}</div>
                            </td>
                            <td class="py-3 px-4 font-mono text-xs">{{ $b->sourceGrn?->grn_number ?? '-' }}</td>
                            <td class="py-3 px-4 font-mono text-xs">{{ $b->manufacture_date?->format('Y-m-d') ?? '-' }}</td>
                            <td class="py-3 px-4 font-mono text-xs font-bold text-slate-800">{{ $b->expiry_date?->format('Y-m-d') ?? '-' }}</td>
                            <td class="py-3 px-4">
                                @if($b->duty_status === 'DUTY_FREE_BONDED')
                                    <span class="px-2 py-0.5 text-xs font-bold bg-sky-100 text-sky-800 rounded">DUTY FREE BONDED</span>
                                @else
                                    <span class="px-2 py-0.5 text-xs font-bold bg-emerald-100 text-emerald-800 rounded">DUTY PAID</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-6 text-center text-slate-400">No stock batches found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-200">
            {{ $batches->links() }}
        </div>
    </div>
</div>
