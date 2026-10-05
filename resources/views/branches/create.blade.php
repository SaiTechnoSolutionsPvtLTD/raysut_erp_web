<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Add New Branch</h1>
                <p class="text-sm text-slate-500 mt-1">Create a new facility or operational branch location.</p>
            </div>
            <a href="{{ route('system.branches.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold rounded-xl transition">
                &larr; Back to Branches
            </a>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6">
        <form method="POST" action="{{ route('system.branches.store') }}" class="space-y-5">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label for="branch_code" class="block text-sm font-semibold text-slate-700 mb-1">Branch Code <span class="text-red-500">*</span></label>
                    <input type="text" name="branch_code" id="branch_code" value="{{ old('branch_code', $suggestedCode) }}" required class="w-full px-4 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-rmsc-red font-mono">
                    @error('branch_code') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label for="branch_name" class="block text-sm font-semibold text-slate-700 mb-1">Branch Name <span class="text-red-500">*</span></label>
                    <input type="text" name="branch_name" id="branch_name" value="{{ old('branch_name') }}" required placeholder="e.g. Muscat HQ, Salalah Depot" class="w-full px-4 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-rmsc-red">
                    @error('branch_name') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label for="branch_type" class="block text-sm font-semibold text-slate-700 mb-1">Facility Type <span class="text-red-500">*</span></label>
                    <select name="branch_type" id="branch_type" required class="w-full px-4 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-rmsc-red">
                        <option value="RETAIL" {{ old('branch_type') == 'RETAIL' ? 'selected' : '' }}>Retail Branch</option>
                        <option value="WHOLESALE" {{ old('branch_type') == 'WHOLESALE' ? 'selected' : '' }}>Wholesale Facility</option>
                        <option value="HARBOR" {{ old('branch_type') == 'HARBOR' ? 'selected' : '' }}>Harbor Terminal</option>
                        <option value="GROCERY" {{ old('branch_type') == 'GROCERY' ? 'selected' : '' }}>Grocery / Depot</option>
                        <option value="MIXED" {{ old('branch_type') == 'MIXED' ? 'selected' : '' }}>Mixed Facility</option>
                    </select>
                    @error('branch_type') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label for="city" class="block text-sm font-semibold text-slate-700 mb-1">City / Region</label>
                    <input type="text" name="city" id="city" value="{{ old('city') }}" placeholder="e.g. Muscat, Salalah, Sohar" class="w-full px-4 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-rmsc-red">
                    @error('city') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>

            <div>
                <label for="address" class="block text-sm font-semibold text-slate-700 mb-1">Full Address / Location Details</label>
                <textarea name="address" id="address" rows="3" placeholder="Enter physical street address or GPS coordinates..." class="w-full px-4 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-rmsc-red">{{ old('address') }}</textarea>
                @error('address') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="flex items-center gap-2 pt-2">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', '1') == '1' ? 'checked' : '' }} class="w-4 h-4 text-rmsc-red rounded border-slate-300 focus:ring-rmsc-red">
                <label for="is_active" class="text-sm font-medium text-slate-700">Set branch operational status to Active</label>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                <a href="{{ route('system.branches.index') }}" class="px-5 py-2.5 bg-slate-100 text-slate-700 text-sm font-semibold rounded-xl hover:bg-slate-200 transition">Cancel</a>
                <button type="submit" class="px-5 py-2.5 bg-rmsc-red text-white text-sm font-semibold rounded-xl hover:bg-rmsc-red-hover transition shadow-xs">Save Branch</button>
            </div>
        </form>
    </div>
</x-app-layout>
