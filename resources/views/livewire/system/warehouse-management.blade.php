<div x-data="{ open: @entangle('isModalOpen'), deleteOpen: @entangle('isDeleteModalOpen') }" class="space-y-6">
    <!-- Header Banner -->
    <div class="flex items-center justify-between gap-4 pb-4 border-b border-slate-200/80">
        <div>
            <h1 class="font-heading font-bold text-2xl text-slate-900 leading-tight">Warehouses & Bonded Storage</h1>
            <p class="text-xs text-slate-500 mt-1">Bonded, duty-paid, freezone depots, and main central warehouses</p>
        </div>
        @if(auth()->user()?->hasPermission('Warehouse & Inventory', 'CREATE'))
        <button wire:click="create" @click="open = true" type="button" class="px-4 py-2 bg-rmsc-red hover:bg-rmsc-red-dark text-white font-semibold text-xs rounded-xl shadow-md shadow-rmsc-red/20 transition flex items-center space-x-2 shrink-0 cursor-pointer">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>Add Warehouse</span>
        </button>
        @endif
    </div>

    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                    <tr>
                        <th class="px-6 py-4">WH Code</th>
                        <th class="px-6 py-4">Warehouse Name</th>
                        <th class="px-6 py-4">Type</th>
                        <th class="px-6 py-4">Branch</th>
                        <th class="px-6 py-4">Customs Bond Ref</th>
                        @if(auth()->user()?->hasPermission('Warehouse & Inventory', 'EDIT') || auth()->user()?->hasPermission('Warehouse & Inventory', 'DELETE'))
                        <th class="px-6 py-4 text-right">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($warehouses as $wh)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="px-6 py-4 font-mono font-bold text-slate-800 text-xs">{{ $wh->warehouse_code }}</td>
                            <td class="px-6 py-4 font-semibold text-slate-900">{{ $wh->warehouse_name }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                    {{ $wh->warehouse_type }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-slate-600">{{ $wh->branch?->branch_name ?? 'N/A' }}</td>
                            <td class="px-6 py-4 font-mono text-xs text-slate-500">{{ $wh->customs_bond_ref ?? 'N/A' }}</td>
                            @if(auth()->user()?->hasPermission('Warehouse & Inventory', 'EDIT') || auth()->user()?->hasPermission('Warehouse & Inventory', 'DELETE'))
                            <td class="px-6 py-4 text-right space-x-2">
                                @if(auth()->user()?->hasPermission('Warehouse & Inventory', 'EDIT'))
                                <button wire:click="edit({{ $wh->warehouse_id }})" @click="open = true" class="p-1.5 text-slate-500 hover:text-rmsc-red hover:bg-red-50 rounded-lg transition" title="Edit Warehouse">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                @endif
                                @if(auth()->user()?->hasPermission('Warehouse & Inventory', 'DELETE'))
                                <button type="button" @click="deleteOpen = true" wire:click="confirmDelete({{ $wh->warehouse_id }})" class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition" title="Delete Warehouse">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                                @endif
                            </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-slate-400">No warehouses registered. Click "Add Warehouse" above.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Edit/Create Warehouse Modal -->
    <template x-teleport="body">
        <div x-show="open" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
             style="display: none;">
            <div @click.away="if (!$wire.showItemDeleteModal && !deleteOpen) { open = false; $wire.set('isModalOpen', false); }" 
                 class="bg-white rounded-2xl max-w-lg w-full mx-auto shadow-2xl border border-slate-200 transform transition-all relative"
                 style="max-width: 512px;">
                <div class="px-6 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between rounded-t-2xl">
                    <h3 class="font-bold text-slate-900 text-base">{{ $editingWarehouseId ? 'Edit Warehouse' : 'Add New Warehouse' }}</h3>
                    <button type="button" @click="open = false; $wire.set('isModalOpen', false)" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form wire:submit.prevent="save" class="p-6 space-y-4">
                    <!-- Warehouse Code (Auto-Generated Read-Only) -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Warehouse Code *</label>
                        <input type="text" wire:model="warehouse_code" readonly class="w-full rounded-xl border-slate-200 bg-slate-100/90 text-slate-600 font-mono text-sm cursor-not-allowed shadow-2xs focus:ring-0 focus:border-slate-200" placeholder="Auto-generated (e.g. WH-001)">
                        <p class="text-[11px] text-slate-400 mt-1 flex items-center">
                            <svg class="w-3.5 h-3.5 mr-1 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                            <span>System Auto-Generated Code (Read-Only)</span>
                        </p>
                        @error('warehouse_code') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Warehouse Name -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Warehouse Name *</label>
                        <input type="text" wire:model="warehouse_name" class="w-full rounded-xl border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-sm" placeholder="e.g. Muscat Central Bonded Warehouse">
                        @error('warehouse_name') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Dynamic Warehouse Type Select -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Warehouse Type *</label>
                        <div x-data="{ open: false }" class="relative">
                            <button type="button" @click="open = !open" class="w-full flex items-center justify-between rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-left font-medium text-slate-800 focus:outline-none focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 transition shadow-2xs">
                                <span class="truncate">
                                    @if($warehouse_type === 'ADD_NEW')
                                        <span class="text-amber-700 font-semibold">+ Add Custom Warehouse Type...</span>
                                    @elseif($warehouse_type)
                                        {{ $warehouse_type }}
                                    @else
                                        <span class="text-slate-400">Select Warehouse Type</span>
                                    @endif
                                </span>
                                <svg class="w-4 h-4 text-slate-400 shrink-0 ml-1 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>

                            <div x-show="open" @click.outside="open = false" x-transition class="absolute z-50 mt-1 w-full rounded-xl bg-white shadow-xl border border-slate-200 py-1 max-h-48 overflow-y-auto overscroll-contain shadow-2xl [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden" style="display: none;">
                                @foreach($warehouseTypes as $type)
                                    <div class="flex items-center justify-between px-3 py-2 hover:bg-slate-50 transition cursor-pointer group {{ $warehouse_type == $type ? 'bg-red-50/50 font-semibold text-rmsc-red' : 'text-slate-700 text-xs' }}">
                                        <span @click="$wire.set('warehouse_type', '{{ $type }}'); open = false" class="flex-1 truncate pr-2 py-0.5 text-xs">
                                            {{ $type }}
                                        </span>
                                        @if(!in_array($type, ['DUTY_FREE_BONDED', 'DUTY_PAID', 'MAIN', 'BRANCH_STORE', 'TRANSIT', 'COLD_STORAGE']))
                                            <button type="button" 
                                                    wire:click.stop="confirmDeleteWarehouseType('{{ addslashes($type) }}')" 
                                                    class="p-1 text-slate-400 hover:text-red-600 hover:bg-red-100/70 rounded-lg transition" 
                                                    title="Delete Custom Warehouse Type">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        @endif
                                    </div>
                                @endforeach

                                <div @click="$wire.set('warehouse_type', 'ADD_NEW'); open = false" class="px-3 py-2 text-xs font-semibold text-amber-700 hover:bg-amber-50 cursor-pointer border-t border-slate-100 flex items-center">
                                    <svg class="w-3.5 h-3.5 mr-1.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    <span>+ Add Custom Warehouse Type...</span>
                                </div>
                            </div>
                        </div>
                        @error('warehouse_type') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror

                        @if($isCustomType || $warehouse_type === 'ADD_NEW')
                            <div class="mt-2.5 p-3 bg-amber-50/70 border border-amber-200/90 rounded-xl space-y-1.5 animate-fadeIn">
                                <label class="block text-[11px] font-bold text-amber-900 uppercase tracking-wider">New Warehouse Type Name *</label>
                                <input type="text" wire:model="custom_warehouse_type" class="w-full rounded-lg border-amber-200 bg-white focus:border-rmsc-red focus:ring-rmsc-red text-sm uppercase font-semibold text-slate-800" placeholder="e.g. FREEZONE_DEPOT, COLD_CHAIN, HUB">
                                @error('custom_warehouse_type') <span class="text-xs text-rmsc-red block font-medium">{{ $message }}</span> @enderror
                            </div>
                        @endif
                    </div>

                    <!-- Creative Assigned Branch Select -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Assigned Branch</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m3 0h1m-1-4h.01M9 16h.01M9 12h.01M13 16h.01M13 12h.01M13 8h.01M9 8h.01"/></svg>
                            </div>
                            <select wire:model="branch_id" class="w-full pl-10 pr-10 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white focus:bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-sm font-medium text-slate-800 transition appearance-none cursor-pointer">
                                <option value="">-- No Specific Branch (Central / Company-Wide) --</option>
                                @foreach($branches as $b)
                                    <option value="{{ $b->branch_id }}">{{ $b->branch_name }} [{{ $b->branch_code }}]</option>
                                @endforeach
                            </select>
                            <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </div>
                        </div>
                    </div>

                    <!-- Customs Bond Reference -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Customs Bond Reference</label>
                        <input type="text" wire:model="customs_bond_ref" class="w-full rounded-xl border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-sm" placeholder="e.g. BOND-B94852-2026">
                        @error('customs_bond_ref') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Active Status -->
                    <div class="pt-2">
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="checkbox" wire:model="is_active" class="rounded border-slate-300 text-rmsc-red focus:ring-rmsc-red">
                            <span class="ml-2 text-xs font-semibold text-slate-700">Active Status</span>
                        </label>
                    </div>

                    <!-- Action Buttons -->
                    <div class="pt-4 border-t border-slate-100 flex justify-end space-x-3">
                        <button type="button" @click="open = false; $wire.set('isModalOpen', false)" class="px-4 py-2 border border-slate-200 text-slate-700 font-semibold text-xs rounded-xl hover:bg-slate-50 transition">Cancel</button>
                        <button type="submit" class="px-5 py-2 bg-rmsc-red hover:bg-rmsc-red-dark text-white font-semibold text-xs rounded-xl shadow-md transition">{{ $editingWarehouseId ? 'Update Warehouse' : 'Save Warehouse' }}</button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <!-- Delete Confirmation Modal (Warehouse Record) -->
    <template x-teleport="body">
        <div x-show="deleteOpen" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
             style="display: none;">
            <div @click.away="deleteOpen = false; $wire.set('isDeleteModalOpen', false)" 
                 class="bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden transform transition-all w-full mx-auto"
                 style="max-width: 400px;">
                <div class="p-6 text-center">
                    <div class="w-12 h-12 rounded-full bg-red-100 text-red-600 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 mb-1">Delete Warehouse?</h3>
                    <p class="text-xs text-slate-500 mb-6">Are you sure you want to delete <span class="font-semibold text-slate-800">"{{ $deletingWarehouseName }}"</span>? This action cannot be undone and will fail if stocks or transactions exist.</p>

                    <div class="flex items-center justify-center space-x-3">
                        <button type="button" @click="deleteOpen = false; $wire.set('isDeleteModalOpen', false)" class="px-4 py-2 border border-slate-200 text-slate-700 font-semibold text-xs rounded-xl hover:bg-slate-50 transition">Cancel</button>
                        <button type="button" wire:click="deleteWarehouse" class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold text-xs rounded-xl shadow-md transition">Delete Warehouse</button>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <!-- Custom Item Delete Confirmation Modal (Warehouse Type) -->
    <template x-teleport="body">
        <div x-show="$wire.showItemDeleteModal" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
             style="display: none;">
            <div @click.away="$wire.set('showItemDeleteModal', false)" 
                 class="bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden transform transition-all w-full mx-auto"
                 style="max-width: 400px;">
                <div class="p-6 text-center">
                    <div class="w-12 h-12 rounded-full bg-red-100 text-red-600 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 mb-1">Delete {{ $itemToDeleteType }}?</h3>
                    <p class="text-xs text-slate-500 mb-6">Are you sure you want to delete <span class="font-semibold text-slate-800">"{{ $itemToDeleteName }}"</span>? This action cannot be undone.</p>

                    <div class="flex items-center justify-center space-x-3">
                        <button type="button" @click="$wire.set('showItemDeleteModal', false)" class="px-4 py-2 border border-slate-200 text-slate-700 font-semibold text-xs rounded-xl hover:bg-slate-50 transition">Cancel</button>
                        <button type="button" wire:click="executeDeleteItem" class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold text-xs rounded-xl shadow-md transition">Delete {{ $itemToDeleteType }}</button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
