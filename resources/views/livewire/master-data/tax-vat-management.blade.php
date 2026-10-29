<div>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Tax & VAT Setup</h1>
                <p class="text-sm text-slate-500 mt-1">Phase 1 Master Data: Manage Royal Oman Police Bayan Tax Rates, Oman VAT (5%), Excise Tax (100%), and Customs Duty.</p>
            </div>
            <div>
                <button wire:click="openCreateModal" class="inline-flex items-center gap-2 px-4 py-2.5 bg-rmsc-red hover:bg-rmsc-red-hover text-white text-sm font-semibold rounded-xl shadow-xs transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    + Add Tax / VAT Rate
                </button>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Search & Filter Bar -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="relative flex-1 max-w-md">
                <svg class="w-5 h-5 absolute left-3.5 top-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search tax rates..." class="w-full pl-10 pr-4 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-rmsc-red">
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs font-semibold px-3 py-1.5 bg-amber-50 text-amber-700 border border-amber-200 rounded-lg">
                    ROP Customs Duty Default: 100%
                </span>
                <span class="text-xs font-semibold px-3 py-1.5 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-lg">
                    Oman VAT: 5.000%
                </span>
            </div>
        </div>

        <!-- Tax Table -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                <h3 class="font-bold text-slate-800 text-base">Tax Rates & Statutory Levies</h3>
                <span class="text-xs font-semibold px-2.5 py-1 bg-slate-100 text-slate-600 rounded-full">{{ $taxRates->total() }} Records</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-50 text-xs font-bold uppercase text-slate-500 tracking-wider border-b border-slate-100">
                        <tr>
                            <th class="py-3.5 px-5">Tax Name</th>
                            <th class="py-3.5 px-5">Category</th>
                            <th class="py-3.5 px-5">Rate (%)</th>
                            <th class="py-3.5 px-5">Effective From</th>
                            <th class="py-3.5 px-5">Effective To</th>
                            <th class="py-3.5 px-5">Status</th>
                            <th class="py-3.5 px-5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($taxRates as $tax)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3.5 px-5 font-semibold text-slate-900">{{ $tax->tax_name }}</td>
                                <td class="py-3.5 px-5">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold
                                        {{ $tax->tax_type === 'VAT' ? 'bg-emerald-100 text-emerald-800' : '' }}
                                        {{ $tax->tax_type === 'EXCISE' ? 'bg-amber-100 text-amber-800' : '' }}
                                        {{ $tax->tax_type === 'CUSTOMS_DUTY' ? 'bg-purple-100 text-purple-800' : '' }}
                                        {{ $tax->tax_type === 'OTHER' ? 'bg-slate-100 text-slate-700' : '' }}
                                    ">
                                        {{ $tax->tax_type }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-5 font-mono font-bold text-slate-900">{{ number_format($tax->rate_percent, 2) }}%</td>
                                <td class="py-3.5 px-5 text-xs text-slate-500">{{ $tax->effective_from }}</td>
                                <td class="py-3.5 px-5 text-xs text-slate-500">{{ $tax->effective_to ?? 'Indefinite' }}</td>
                                <td class="py-3.5 px-5">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $tax->is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-500' }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $tax->is_active ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                        {{ $tax->is_active ? 'ACTIVE' : 'INACTIVE' }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-5 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button wire:click="editTax({{ $tax->tax_rate_id }})" class="p-1.5 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-lg transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>
                                        <button wire:click="confirmDeleteTax({{ $tax->tax_rate_id }})" class="p-1.5 text-rose-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-slate-400">No tax rates defined.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-slate-100">
                {{ $taxRates->links() }}
            </div>
        </div>
    </div>

    <!-- Create/Edit Modal -->
    @if ($showCreateModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-md w-full mx-auto p-6 shadow-2xl border border-slate-200 relative animate-in fade-in zoom-in duration-200" style="max-width: 448px;">
                <h3 class="text-lg font-bold text-slate-900 mb-4">{{ $tax_rate_id ? 'Edit Tax Rate' : 'Add Tax / VAT Rate' }}</h3>
                <form wire:submit.prevent="saveTax" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Tax Description / Name</label>
                        <input type="text" wire:model="tax_name" placeholder="e.g. Standard VAT 5%, Excise 100%" class="w-full text-sm bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 focus:bg-white focus:ring-2 focus:ring-rmsc-red">
                        @error('tax_name') <span class="text-xs text-rose-500 font-semibold mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Category Type</label>
                            <select wire:model="tax_type" class="w-full text-sm bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 focus:bg-white focus:ring-2 focus:ring-rmsc-red">
                                <option value="VAT">VAT</option>
                                <option value="EXCISE">EXCISE</option>
                                <option value="CUSTOMS_DUTY">CUSTOMS DUTY</option>
                                <option value="OTHER">OTHER</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Rate (%)</label>
                            <input type="number" step="0.001" wire:model="rate_percent" class="w-full text-sm font-mono bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 focus:bg-white focus:ring-2 focus:ring-rmsc-red">
                            @error('rate_percent') <span class="text-xs text-rose-500 font-semibold mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Effective From</label>
                            <input type="date" wire:model="effective_from" class="w-full text-sm bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5">
                            @error('effective_from') <span class="text-xs text-rose-500 font-semibold mt-1">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Effective To (Optional)</label>
                            <input type="date" wire:model="effective_to" class="w-full text-sm bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5">
                            @error('effective_to') <span class="text-xs text-rose-500 font-semibold mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="flex items-center gap-2 pt-2">
                        <input type="checkbox" id="is_active" wire:model="is_active" class="w-4 h-4 text-rmsc-red rounded border-slate-300 focus:ring-rmsc-red">
                        <label for="is_active" class="text-xs font-semibold text-slate-700">Active Tax Rule</label>
                    </div>
                    <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100">
                        <button type="button" wire:click="$set('showCreateModal', false)" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100 rounded-xl">Cancel</button>
                        <button type="submit" class="px-5 py-2 text-sm font-semibold text-white bg-rmsc-red hover:bg-rmsc-red-hover rounded-xl shadow-xs">Save Tax Rate</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Delete Modal -->
    @if ($showDeleteModal && $taxToDelete)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-sm w-full mx-auto p-6 shadow-2xl border border-slate-200 text-center animate-in fade-in zoom-in duration-200" style="max-width: 384px;">
                <div class="w-12 h-12 bg-rose-100 text-rose-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <h3 class="text-lg font-bold text-slate-900 mb-2">Delete Tax Rate?</h3>
                <p class="text-sm text-slate-500 mb-6">Are you sure you want to delete <span class="font-bold text-slate-800">{{ $taxToDelete->tax_name }}</span>?</p>
                <div class="flex items-center justify-center gap-3">
                    <button wire:click="$set('showDeleteModal', false)" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100 rounded-xl">Cancel</button>
                    <button wire:click="deleteTax" class="px-5 py-2 text-sm font-semibold text-white bg-rose-600 hover:bg-rose-700 rounded-xl shadow-xs">Yes, Delete</button>
                </div>
            </div>
        </div>
    @endif
</div>
