<div>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Units of Measure (UOM) & Conversions</h1>
                <p class="text-sm text-slate-500 mt-1">Phase 1 Foundation: Define packaging units (Bottle, Case, Keg) and conversion multipliers.</p>
            </div>
            <div class="flex items-center gap-3">
                <button wire:click="openConversionModal" class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-800 text-white hover:bg-slate-700 text-sm font-semibold rounded-xl shadow-xs transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                    Add Conversion Factor
                </button>
                <button wire:click="openCreateModal" class="inline-flex items-center gap-2 px-4 py-2.5 bg-rmsc-red hover:bg-rmsc-red-hover text-white text-sm font-semibold rounded-xl shadow-xs transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    + Add New UOM
                </button>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Search & Info Banner -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="relative flex-1 max-w-md">
                <svg class="w-5 h-5 absolute left-3.5 top-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search by code or name..." class="w-full pl-10 pr-4 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-rmsc-red focus:border-rmsc-red transition">
            </div>
            <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 bg-slate-100 px-3 py-1.5 rounded-lg border border-slate-200">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>Base Currency: OMR (Omani Rial)</span>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left 2 Cols: UOM Master Table -->
            <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="font-bold text-slate-800 text-base">Standard Measurement Units</h3>
                    <span class="text-xs font-semibold px-2.5 py-1 bg-slate-100 text-slate-600 rounded-full">{{ $uoms->total() }} Units</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600">
                        <thead class="bg-slate-50 text-xs font-bold uppercase text-slate-500 tracking-wider border-b border-slate-100">
                            <tr>
                                <th class="py-3.5 px-5">Code</th>
                                <th class="py-3.5 px-5">Unit Name</th>
                                <th class="py-3.5 px-5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($uoms as $uom)
                                <tr class="hover:bg-slate-50/80 transition">
                                    <td class="py-3.5 px-5 font-mono font-bold text-rmsc-navy">{{ $uom->uom_code }}</td>
                                    <td class="py-3.5 px-5 font-medium text-slate-800">{{ $uom->uom_name }}</td>
                                    <td class="py-3.5 px-5 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <button wire:click="editUom({{ $uom->uom_id }})" class="p-1.5 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-lg transition" title="Edit">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            </button>
                                            <button wire:click="confirmDeleteUom({{ $uom->uom_id }})" class="p-1.5 text-rose-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition" title="Delete">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="py-8 text-center text-slate-400">No UOM units found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-4 border-t border-slate-100">
                    {{ $uoms->links() }}
                </div>
            </div>

            <!-- Right Col: UOM Conversions Card -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-bold text-slate-800 text-base">Packaging Conversions</h3>
                    <span class="text-xs text-slate-400 font-mono">{{ count($conversions) }} configured</span>
                </div>
                <div class="space-y-3">
                    @forelse ($conversions as $conv)
                        <div class="p-3.5 bg-slate-50 border border-slate-200/60 rounded-xl flex flex-col gap-1">
                            <span class="text-xs font-semibold text-slate-500">{{ $conv->product?->product_name ?? 'Global Default' }}</span>
                            <div class="flex items-center justify-between">
                                <span class="font-mono text-sm font-bold text-slate-800">1 {{ $conv->fromUom?->uom_code }}</span>
                                <span class="text-xs text-rmsc-red font-bold">➔ {{ (float)$conv->conversion_factor }}</span>
                                <span class="font-mono text-sm font-bold text-slate-800">{{ $conv->toUom?->uom_code }}</span>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 text-center py-6">No packaging conversion rules specified yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Create/Edit UOM Modal -->
    @if ($showCreateModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200 relative animate-in fade-in zoom-in duration-200">
                <h3 class="text-lg font-bold text-slate-900 mb-4">{{ $uom_id ? 'Edit Unit of Measure' : 'Create Unit of Measure' }}</h3>
                <form wire:submit.prevent="saveUom" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">UOM Code (e.g. BTL, CSE, LTR)</label>
                        <input type="text" wire:model="uom_code" class="w-full text-sm font-mono uppercase bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 focus:bg-white focus:ring-2 focus:ring-rmsc-red">
                        @error('uom_code') <span class="text-xs text-rose-500 font-semibold mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Unit Name</label>
                        <input type="text" wire:model="uom_name" placeholder="e.g. Bottle, Case, Litre" class="w-full text-sm bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 focus:bg-white focus:ring-2 focus:ring-rmsc-red">
                        @error('uom_name') <span class="text-xs text-rose-500 font-semibold mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100">
                        <button type="button" wire:click="$set('showCreateModal', false)" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100 rounded-xl">Cancel</button>
                        <button type="submit" class="px-5 py-2 text-sm font-semibold text-white bg-rmsc-red hover:bg-rmsc-red-hover rounded-xl shadow-xs">Save UOM</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Conversion Modal -->
    @if ($showConversionModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200 relative animate-in fade-in zoom-in duration-200">
                <h3 class="text-lg font-bold text-slate-900 mb-4">Add UOM Conversion Rule</h3>
                <form wire:submit.prevent="saveConversion" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Target Product</label>
                        <select wire:model="product_id" class="w-full text-sm bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 focus:bg-white focus:ring-2 focus:ring-rmsc-red">
                            <option value="">Select Product...</option>
                            @foreach ($products as $prod)
                                <option value="{{ $prod->product_id }}">{{ $prod->sku_code }} — {{ $prod->product_name }}</option>
                            @endforeach
                        </select>
                        @error('product_id') <span class="text-xs text-rose-500 font-semibold mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">From UOM</label>
                            <select wire:model="from_uom_id" class="w-full text-sm bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5">
                                <option value="">From...</option>
                                @foreach ($uoms as $u)
                                    <option value="{{ $u->uom_id }}">{{ $u->uom_code }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">To UOM</label>
                            <select wire:model="to_uom_id" class="w-full text-sm bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5">
                                <option value="">To...</option>
                                @foreach ($uoms as $u)
                                    <option value="{{ $u->uom_id }}">{{ $u->uom_code }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Conversion Factor (Multiplier)</label>
                        <input type="number" step="0.0001" wire:model="conversion_factor" placeholder="e.g. 12" class="w-full text-sm font-mono bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5">
                        @error('conversion_factor') <span class="text-xs text-rose-500 font-semibold mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100">
                        <button type="button" wire:click="$set('showConversionModal', false)" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100 rounded-xl">Cancel</button>
                        <button type="submit" class="px-5 py-2 text-sm font-semibold text-white bg-rmsc-red hover:bg-rmsc-red-hover rounded-xl shadow-xs">Save Conversion</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Delete Modal -->
    @if ($showDeleteModal && $uomToDelete)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-sm w-full p-6 shadow-2xl border border-slate-200 text-center animate-in fade-in zoom-in duration-200">
                <div class="w-12 h-12 bg-rose-100 text-rose-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <h3 class="text-lg font-bold text-slate-900 mb-2">Delete UOM?</h3>
                <p class="text-sm text-slate-500 mb-6">Are you sure you want to delete <span class="font-bold text-slate-800">{{ $uomToDelete->uom_name }}</span>?</p>
                <div class="flex items-center justify-center gap-3">
                    <button wire:click="$set('showDeleteModal', false)" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100 rounded-xl">Cancel</button>
                    <button wire:click="deleteUom" class="px-5 py-2 text-sm font-semibold text-white bg-rose-600 hover:bg-rose-700 rounded-xl shadow-xs">Yes, Delete UOM</button>
                </div>
            </div>
        </div>
    @endif
</div>
