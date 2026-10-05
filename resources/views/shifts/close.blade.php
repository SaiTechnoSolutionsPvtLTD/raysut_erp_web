<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Close & Reconcile Shift #{{ $shift->shift_id }}</h1>
                <p class="text-sm text-slate-500 mt-1">Opened on {{ $shift->opened_at->format('M d, Y H:i A') }} at {{ $shift->counter->counter_name }}</p>
            </div>
            <a href="{{ route('shifts.index') }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold rounded-xl transition">
                &larr; Shifts Overview
            </a>
        </div>
    </x-slot>

    <div class="max-w-2xl mx-auto space-y-6">
        <!-- Shift Info Header Card -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 grid grid-cols-3 gap-4 text-center divide-x divide-slate-100">
            <div>
                <span class="text-2xs font-bold text-slate-400 uppercase tracking-wider">Starting Float</span>
                <p class="text-lg font-extrabold text-slate-900 mt-1">OMR {{ number_format($shift->opening_float, 3) }}</p>
            </div>
            <div>
                <span class="text-2xs font-bold text-slate-400 uppercase tracking-wider">Counter Terminal</span>
                <p class="text-sm font-bold text-rmsc-navy mt-1.5">{{ $shift->counter->counter_code }}</p>
            </div>
            <div>
                <span class="text-2xs font-bold text-slate-400 uppercase tracking-wider">Branch</span>
                <p class="text-sm font-bold text-slate-700 mt-1.5">{{ $shift->branch->branch_name }}</p>
            </div>
        </div>

        <!-- Shift Closing Form -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-8">
            <form method="POST" action="{{ route('shifts.close.store', $shift->shift_id) }}" class="space-y-6">
                @csrf

                <div class="space-y-4">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-slate-800 border-b border-slate-100 pb-2">Physical Cash & Slip Counts</h3>

                    <div>
                        <label for="counted_cash" class="block text-sm font-semibold text-slate-700 mb-1">Physical Cash Count in Drawer (OMR) <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <span class="absolute left-4 top-3 text-sm font-bold text-slate-400">OMR</span>
                            <input type="number" step="0.001" min="0" name="counted_cash" id="counted_cash" value="{{ old('counted_cash', number_format($shift->opening_float, 3, '.', '')) }}" required class="w-full pl-16 pr-4 py-3 text-lg font-bold bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-rmsc-red text-slate-900">
                        </div>
                        <p class="text-2xs text-slate-500 mt-1">Total physical cash including opening float + cash collected during shift.</p>
                        @error('counted_cash') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="card_total" class="block text-sm font-semibold text-slate-700 mb-1">Total Card Payment Slips / Terminal Total (OMR) <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <span class="absolute left-4 top-3 text-sm font-bold text-slate-400">OMR</span>
                            <input type="number" step="0.001" min="0" name="card_total" id="card_total" value="{{ old('card_total', '0.000') }}" required class="w-full pl-16 pr-4 py-3 text-lg font-bold bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-rmsc-red text-slate-900">
                        </div>
                        @error('card_total') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="notes" class="block text-sm font-semibold text-slate-700 mb-1">Shift Closing Remarks / Reason for Discrepancy</label>
                        <textarea name="notes" id="notes" rows="3" placeholder="Notes on any cash variance, notes breakdown, or card machine batch print..." class="w-full px-4 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-rmsc-red">{{ old('notes') }}</textarea>
                        @error('notes') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
                    <a href="{{ route('shifts.index') }}" class="px-5 py-2.5 bg-slate-100 text-slate-700 text-sm font-semibold rounded-xl hover:bg-slate-200 transition">Cancel</a>
                    <button type="submit" class="px-6 py-3 bg-rmsc-navy text-white text-sm font-bold rounded-xl hover:bg-slate-800 transition shadow-xs">
                        🔒 Close & Perform Reconciliation
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
