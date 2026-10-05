<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Edit Branch: {{ $branch->branch_name }}</h1>
                <p class="text-sm text-slate-500 mt-1">Update branch configuration and associated POS counters.</p>
            </div>
            <a href="{{ route('system.branches.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold rounded-xl transition">
                &larr; Back to Branches
            </a>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 max-w-6xl mx-auto">
        <!-- Branch Edit Form -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6">
            <h2 class="text-lg font-bold text-slate-800 mb-4 pb-2 border-b border-slate-100">Branch Details</h2>
            <form method="POST" action="{{ route('system.branches.update', $branch->branch_id) }}" class="space-y-5">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label for="branch_code" class="block text-sm font-semibold text-slate-700 mb-1">Branch Code <span class="text-red-500">*</span></label>
                        <input type="text" name="branch_code" id="branch_code" value="{{ old('branch_code', $branch->branch_code) }}" required class="w-full px-4 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-rmsc-red font-mono">
                        @error('branch_code') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="branch_name" class="block text-sm font-semibold text-slate-700 mb-1">Branch Name <span class="text-red-500">*</span></label>
                        <input type="text" name="branch_name" id="branch_name" value="{{ old('branch_name', $branch->branch_name) }}" required class="w-full px-4 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-rmsc-red">
                        @error('branch_name') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="branch_type" class="block text-sm font-semibold text-slate-700 mb-1">Facility Type <span class="text-red-500">*</span></label>
                        <select name="branch_type" id="branch_type" required class="w-full px-4 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-rmsc-red">
                            <option value="RETAIL" {{ old('branch_type', $branch->branch_type) == 'RETAIL' ? 'selected' : '' }}>Retail Branch</option>
                            <option value="WHOLESALE" {{ old('branch_type', $branch->branch_type) == 'WHOLESALE' ? 'selected' : '' }}>Wholesale Facility</option>
                            <option value="HARBOR" {{ old('branch_type', $branch->branch_type) == 'HARBOR' ? 'selected' : '' }}>Harbor Terminal</option>
                            <option value="GROCERY" {{ old('branch_type', $branch->branch_type) == 'GROCERY' ? 'selected' : '' }}>Grocery / Depot</option>
                            <option value="MIXED" {{ old('branch_type', $branch->branch_type) == 'MIXED' ? 'selected' : '' }}>Mixed Facility</option>
                        </select>
                        @error('branch_type') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="city" class="block text-sm font-semibold text-slate-700 mb-1">City / Region</label>
                        <input type="text" name="city" id="city" value="{{ old('city', $branch->city) }}" class="w-full px-4 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-rmsc-red">
                        @error('city') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div>
                    <label for="address" class="block text-sm font-semibold text-slate-700 mb-1">Full Address / Location Details</label>
                    <textarea name="address" id="address" rows="3" class="w-full px-4 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-rmsc-red">{{ old('address', $branch->address) }}</textarea>
                    @error('address') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="flex items-center gap-2 pt-2">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $branch->is_active) ? 'checked' : '' }} class="w-4 h-4 text-rmsc-red rounded border-slate-300 focus:ring-rmsc-red">
                    <label for="is_active" class="text-sm font-medium text-slate-700">Operational status Active</label>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                    <a href="{{ route('system.branches.index') }}" class="px-5 py-2.5 bg-slate-100 text-slate-700 text-sm font-semibold rounded-xl hover:bg-slate-200 transition">Cancel</a>
                    <button type="submit" class="px-5 py-2.5 bg-rmsc-red text-white text-sm font-semibold rounded-xl hover:bg-rmsc-red-hover transition shadow-xs">Update Branch</button>
                </div>
            </form>
        </div>

        <!-- Associated POS Counters Section -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h2 class="text-lg font-bold text-slate-800">POS Counters</h2>
                <span class="text-xs font-mono font-semibold px-2 py-1 bg-slate-100 text-slate-600 rounded-md">{{ $branch->counters->count() }} Registered</span>
            </div>

            <!-- List existing counters -->
            <div class="space-y-2.5 max-h-60 overflow-y-auto pr-1">
                @forelse($branch->counters as $cnt)
                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/60 flex items-center justify-between text-xs">
                        <div>
                            <span class="font-mono font-bold text-slate-800">{{ $cnt->counter_code }}</span>
                            <p class="text-slate-500 font-medium">{{ $cnt->counter_name }}</p>
                        </div>
                        <form method="POST" action="{{ route('system.counters.toggle-status', $cnt->counter_id) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="px-2 py-1 rounded text-2xs font-bold uppercase tracking-wider {{ $cnt->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600' }}">
                                {{ $cnt->is_active ? 'Active' : 'Disabled' }}
                            </button>
                        </form>
                    </div>
                @empty
                    <p class="text-xs text-slate-400 italic">No POS counters configured for this branch.</p>
                @endforelse
            </div>

            <!-- Quick Add Counter Form -->
            <div class="pt-4 border-t border-slate-100">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3">Add New POS Counter</h3>
                <form method="POST" action="{{ route('system.counters.store') }}" class="space-y-3">
                    @csrf
                    <input type="hidden" name="branch_id" value="{{ $branch->branch_id }}">

                    <div>
                        <input type="text" name="counter_code" placeholder="Counter Code (e.g. CNT-01)" required class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:ring-1 focus:ring-rmsc-red font-mono">
                    </div>
                    <div>
                        <input type="text" name="counter_name" placeholder="Counter Name (e.g. Checkout 1)" required class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:ring-1 focus:ring-rmsc-red">
                    </div>
                    <div>
                        <input type="text" name="terminal_id" placeholder="Terminal ID / IP (Optional)" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:ring-1 focus:ring-rmsc-red font-mono">
                    </div>
                    <button type="submit" class="w-full py-2 bg-slate-800 hover:bg-slate-900 text-white text-xs font-semibold rounded-lg transition">+ Add Counter</button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
