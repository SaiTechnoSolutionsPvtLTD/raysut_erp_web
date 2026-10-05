<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Shift Operations & Reconciliation</h1>
                <p class="text-sm text-slate-500 mt-1">Sprint 3.1: Monitor cashier shifts, cash drawer floats, variance audits & approvals.</p>
            </div>
            <div>
                @if($currentShift)
                    <a href="{{ route('shifts.show-close', $currentShift->shift_id) }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-amber-600 hover:bg-amber-700 text-white text-sm font-bold rounded-xl shadow-xs transition">
                        🔒 Close Current Active Shift (#{{ $currentShift->shift_id }})
                    </a>
                @else
                    <a href="{{ route('shifts.open') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-rmsc-red hover:bg-rmsc-red-hover text-white text-sm font-bold rounded-xl shadow-xs transition">
                        🚀 Open New Shift
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Status Filter Bar -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <a href="{{ route('shifts.index') }}" class="px-3.5 py-1.5 rounded-lg text-xs font-semibold {{ !request('status') && !request('variance_status') ? 'bg-rmsc-navy text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">All Shifts</a>
                <a href="{{ route('shifts.index', ['status' => 'OPEN']) }}" class="px-3.5 py-1.5 rounded-lg text-xs font-semibold {{ request('status') == 'OPEN' ? 'bg-emerald-600 text-white' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }}">Active Open Shifts</a>
                <a href="{{ route('shifts.index', ['variance_status' => 'FLAGGED']) }}" class="px-3.5 py-1.5 rounded-lg text-xs font-semibold {{ request('variance_status') == 'FLAGGED' ? 'bg-red-600 text-white' : 'bg-red-50 text-red-700 hover:bg-red-100' }}">⚠️ Flagged Variances</a>
            </div>
            <span class="text-xs font-semibold text-slate-500">Total Shift Records: {{ $shifts->total() }}</span>
        </div>

        <!-- Shift Records Table -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-50 text-xs font-bold uppercase text-slate-500 tracking-wider border-b border-slate-100">
                        <tr>
                            <th class="py-3.5 px-5">Shift ID</th>
                            <th class="py-3.5 px-5">Cashier</th>
                            <th class="py-3.5 px-5">Branch / Counter</th>
                            <th class="py-3.5 px-5">Opened</th>
                            <th class="py-3.5 px-5">Float</th>
                            <th class="py-3.5 px-5">Expected / Counted</th>
                            <th class="py-3.5 px-5">Variance</th>
                            <th class="py-3.5 px-5">Status</th>
                            <th class="py-3.5 px-5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($shifts as $shift)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3.5 px-5 font-mono font-bold text-rmsc-navy">#{{ $shift->shift_id }}</td>
                                <td class="py-3.5 px-5 font-semibold text-slate-900">{{ $shift->user->full_name }}</td>
                                <td class="py-3.5 px-5">
                                    <div class="text-xs font-semibold text-slate-800">{{ $shift->branch->branch_code }}</div>
                                    <div class="text-2xs text-slate-400 font-mono">{{ $shift->counter->counter_code }}</div>
                                </td>
                                <td class="py-3.5 px-5 text-xs text-slate-500">
                                    {{ $shift->opened_at->format('M d, H:i') }}
                                    @if($shift->closed_at)
                                        <br><span class="text-2xs text-slate-400">Closed {{ $shift->closed_at->format('H:i') }}</span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-5 font-mono text-xs font-semibold">OMR {{ number_format($shift->opening_float, 3) }}</td>
                                <td class="py-3.5 px-5 font-mono text-xs">
                                    @if($shift->status === 'OPEN')
                                        <span class="text-slate-400 italic">In Progress</span>
                                    @else
                                        <span class="text-slate-500">Exp: {{ number_format($shift->expected_cash, 3) }}</span><br>
                                        <span class="font-bold text-slate-900">Cnt: {{ number_format($shift->counted_cash, 3) }}</span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-5 font-mono text-xs">
                                    @if($shift->status === 'OPEN')
                                        -
                                    @else
                                        <span class="font-bold {{ $shift->variance == 0 ? 'text-emerald-600' : ($shift->variance > 0 ? 'text-blue-600' : 'text-red-600') }}">
                                            {{ $shift->variance > 0 ? '+' : '' }}{{ number_format($shift->variance, 3) }}
                                        </span>
                                        <br>
                                        <span class="inline-flex px-2 py-0.5 rounded text-3xs font-extrabold uppercase {{ $shift->variance_status === 'FLAGGED' ? 'bg-red-100 text-red-800 border border-red-200' : ($shift->variance_status === 'APPROVED' ? 'bg-blue-100 text-blue-800' : 'bg-slate-100 text-slate-600') }}">
                                            {{ $shift->variance_status }}
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-5">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold uppercase {{ $shift->status === 'OPEN' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : ($shift->status === 'CLOSED' ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-slate-100 text-slate-700') }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $shift->status === 'OPEN' ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                        {{ $shift->status }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-5 text-right space-x-2">
                                    @if($shift->status === 'OPEN' && auth()->id() === $shift->user_id)
                                        <a href="{{ route('shifts.show-close', $shift->shift_id) }}" class="px-2.5 py-1 bg-amber-50 text-amber-700 hover:bg-amber-100 rounded-lg text-xs font-semibold transition">Close</a>
                                    @endif

                                    @if($shift->variance_status === 'FLAGGED')
                                        @can('approve', $shift)
                                            <form method="POST" action="{{ route('shifts.approve', $shift->shift_id) }}" class="inline">
                                                @csrf
                                                <button type="submit" onclick="return confirm('Approve shift variance override?')" class="px-2.5 py-1 bg-red-600 hover:bg-red-700 text-white rounded-lg text-xs font-semibold transition">Approve Variance</button>
                                            </form>
                                        @endcan
                                    @endif

                                    <a href="{{ route('shifts.print', $shift->shift_id) }}" target="_blank" class="p-1.5 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-lg inline-block transition" title="Print Summary">
                                        🖨️
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="py-8 text-center text-slate-400">No shift records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-slate-100">
                {{ $shifts->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
