<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Open Cashier Shift</h1>
                <p class="text-sm text-slate-500 mt-1">Select your assigned POS counter and enter initial drawer cash float.</p>
            </div>
            <a href="{{ route('dashboard') }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold rounded-xl transition">
                &larr; Dashboard
            </a>
        </div>
    </x-slot>

    <div class="max-w-xl mx-auto bg-white rounded-2xl border border-slate-200/80 shadow-xs p-8">
        <div class="flex items-center gap-4 pb-6 mb-6 border-b border-slate-100">
            <div class="w-12 h-12 rounded-2xl bg-rmsc-navy/10 text-rmsc-navy flex items-center justify-center font-bold text-xl">
                💳
            </div>
            <div>
                <h2 class="text-lg font-bold text-slate-900">Cashier: {{ auth()->user()->full_name }}</h2>
                <p class="text-xs font-semibold text-slate-500">Branch: {{ auth()->user()->defaultBranch?->branch_name ?? 'RMSC HQ' }}</p>
            </div>
        </div>

        @if(session('warning'))
            <div class="mb-6 p-4 bg-amber-50 border border-amber-200 rounded-xl text-amber-800 text-xs font-semibold flex items-center gap-2">
                <svg class="w-5 h-5 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <span>{{ session('warning') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl text-red-800 text-xs font-semibold flex items-center gap-2">
                <svg class="w-5 h-5 text-red-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <form method="POST" action="{{ route('shifts.open.store') }}" class="space-y-6">
            @csrf

            <div>
                <label for="counter_id" class="block text-sm font-semibold text-slate-700 mb-2">POS Counter Terminal <span class="text-red-500">*</span></label>
                <select name="counter_id" id="counter_id" required class="w-full px-4 py-3 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-rmsc-red font-semibold">
                    <option value="">-- Select Active Counter --</option>
                    @foreach($counters as $counter)
                        <option value="{{ $counter->counter_id }}" {{ old('counter_id') == $counter->counter_id ? 'selected' : '' }}>
                            {{ $counter->counter_code }} - {{ $counter->counter_name }} ({{ $counter->branch->branch_name ?? 'N/A' }})
                        </option>
                    @endforeach
                </select>
                @error('counter_id') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label for="opening_float" class="block text-sm font-semibold text-slate-700 mb-2">Opening Cash Float (OMR) <span class="text-red-500">*</span></label>
                <div class="relative">
                    <span class="absolute left-4 top-3 text-sm font-bold text-slate-400">OMR</span>
                    <input type="number" step="0.001" min="0" name="opening_float" id="opening_float" value="{{ old('opening_float', '50.000') }}" required class="w-full pl-16 pr-4 py-3 text-lg font-bold bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-rmsc-red text-slate-900">
                </div>
                <p class="text-2xs text-slate-500 mt-1.5">Count and verify the starting cash in your register drawer before confirming.</p>
                @error('opening_float') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label for="notes" class="block text-sm font-semibold text-slate-700 mb-1">Shift Opening Notes (Optional)</label>
                <textarea name="notes" id="notes" rows="2" placeholder="e.g. Received float bundle from supervisor..." class="w-full px-4 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-rmsc-red">{{ old('notes') }}</textarea>
                @error('notes') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
                <a href="{{ route('dashboard') }}" class="px-5 py-2.5 bg-slate-100 text-slate-700 text-sm font-semibold rounded-xl hover:bg-slate-200 transition">Cancel</a>
                <button type="submit" class="px-6 py-3 bg-rmsc-red text-white text-sm font-bold rounded-xl hover:bg-rmsc-red-hover transition shadow-sm">
                    🚀 Confirm & Open Shift
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
