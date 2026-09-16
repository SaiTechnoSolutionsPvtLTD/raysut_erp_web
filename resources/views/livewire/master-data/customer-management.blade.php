<div class="space-y-6">
    <!-- Single Row Header Banner (Title & Search) -->
    <div class="flex flex-wrap items-center justify-between gap-3 pb-4 border-b border-slate-200/80">
        <div>
            <h1 class="font-heading font-bold text-xl text-slate-900 leading-tight">Customer & Account Directory</h1>
            <p class="text-[11px] text-slate-500 mt-0.5">HORECA hotel accounts, Oman Duty Free, retail permit holders, and credit terms</p>
        </div>

        <div class="relative w-52 shrink-0">
            <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400 z-10">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search Customer..." style="padding-left: 2.25rem !important;" class="w-52 h-9 pr-3 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white focus:bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-medium text-slate-800 transition">
        </div>


    </div>




    <!-- Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                    <tr>
                        <th class="px-6 py-4">Customer Code</th>
                        <th class="px-6 py-4">Customer Name</th>
                        <th class="px-6 py-4">Segment</th>
                        <th class="px-6 py-4">Contact & Oman Card</th>
                        <th class="px-6 py-4 text-right">Credit Limit (OMR)</th>
                        <th class="px-6 py-4 text-right">Outstanding (OMR)</th>
                        <th class="px-6 py-4">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($customers as $c)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="px-6 py-4 font-mono font-bold text-slate-800 text-xs">{{ $c->customer_code }}</td>
                            <td class="px-6 py-4 font-semibold text-slate-900">{{ $c->customer_name }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                    {{ $c->customer_segment }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-slate-800 text-xs font-medium">{{ $c->phone ?? '-' }}</div>
                                <div class="text-[11px] text-slate-400 font-mono">{{ $c->oman_card_no ?? '-' }}</div>
                            </td>
                            <td class="px-6 py-4 text-right font-mono font-bold text-slate-900 text-xs">
                                {{ number_format($c->creditTerms?->credit_limit ?? 0, 3) }}
                            </td>
                            <td class="px-6 py-4 text-right font-mono text-amber-600 font-bold text-xs">
                                {{ number_format($c->creditTerms?->outstanding_balance ?? 0, 3) }}
                            </td>
                            <td class="px-6 py-4">
                                @if($c->is_active)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                        Inactive
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-slate-400">No customers found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-200/80">
            {{ $customers->links() }}
        </div>
    </div>
</div>

