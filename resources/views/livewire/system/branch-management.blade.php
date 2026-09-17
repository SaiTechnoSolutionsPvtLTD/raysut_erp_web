<div x-data="{ open: @entangle('isModalOpen'), deleteOpen: @entangle('isDeleteModalOpen') }" class="space-y-6">
    <!-- Header Banner -->
    <div class="flex items-center justify-between gap-4 pb-4 border-b border-slate-200/80">
        <div>
            <h1 class="font-heading font-bold text-2xl text-slate-900 leading-tight">Branch Management</h1>
            <p class="text-xs text-slate-500 mt-1">Physical retail, wholesale, harbor, and depot branches in Oman</p>
        </div>
        @if(auth()->user()?->hasPermission('Branch & Shift Operations', 'CREATE'))
        <button wire:click="create" @click="open = true" type="button" class="px-4 py-2 bg-rmsc-red hover:bg-rmsc-red-dark text-white font-semibold text-xs rounded-xl shadow-md shadow-rmsc-red/20 transition flex items-center space-x-2 shrink-0 cursor-pointer">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>Add New Branch</span>
        </button>
        @endif
    </div>

    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                    <tr>
                        <th class="px-6 py-4">Branch Code</th>
                        <th class="px-6 py-4">Branch Name</th>
                        <th class="px-6 py-4">Type</th>
                        <th class="px-6 py-4">City</th>
                        <th class="px-6 py-4">Status</th>
                        @if(auth()->user()?->hasPermission('Branch & Shift Operations', 'EDIT') || auth()->user()?->hasPermission('Branch & Shift Operations', 'DELETE'))
                        <th class="px-6 py-4 text-right">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($branches as $branch)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="px-6 py-4 font-mono font-bold text-slate-800 text-xs">{{ $branch->branch_code }}</td>
                            <td class="px-6 py-4 font-semibold text-slate-900">{{ $branch->branch_name }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                    {{ $branch->branch_type }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-slate-600">{{ $branch->city ?? 'N/A' }}</td>
                            <td class="px-6 py-4">
                                @if($branch->is_active)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                        Inactive
                                    </span>
                                @endif
                            </td>
                            @if(auth()->user()?->hasPermission('Branch & Shift Operations', 'EDIT') || auth()->user()?->hasPermission('Branch & Shift Operations', 'DELETE'))
                            <td class="px-6 py-4 text-right space-x-2">
                                @if(auth()->user()?->hasPermission('Branch & Shift Operations', 'EDIT'))
                                <button wire:click="edit({{ $branch->branch_id }})" @click="open = true" class="p-1.5 text-slate-500 hover:text-rmsc-red hover:bg-red-50 rounded-lg transition" title="Edit Branch">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                @endif
                                @if(auth()->user()?->hasPermission('Branch & Shift Operations', 'DELETE'))
                                <button wire:click="confirmDelete({{ $branch->branch_id }})" @click="deleteOpen = true" class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition" title="Delete Branch">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                                @endif
                            </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-slate-400">No branches registered.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Edit/Create Branch Modal -->
    @if(auth()->user()?->hasPermission('Branch & Shift Operations', 'CREATE') || auth()->user()?->hasPermission('Branch & Shift Operations', 'EDIT'))
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
                 class="bg-white rounded-2xl max-w-lg w-full shadow-2xl border border-slate-200 overflow-hidden transform transition-all relative">
                <div class="px-6 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between rounded-t-2xl">
                    <h3 class="font-bold text-slate-900 text-base">{{ $editingBranchId ? 'Edit Branch' : 'Add New Branch' }}</h3>
                    <button type="button" @click="open = false; $wire.set('isModalOpen', false)" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form wire:submit.prevent="save" class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Branch Code *</label>
                        <input type="text" wire:model="branch_code" readonly class="w-full rounded-xl border-slate-200 bg-slate-100/90 text-slate-600 font-mono text-sm cursor-not-allowed shadow-2xs focus:ring-0 focus:border-slate-200" placeholder="Auto-generated (e.g. BR-004)">
                        <p class="text-[11px] text-slate-400 mt-1 flex items-center">
                            <svg class="w-3.5 h-3.5 mr-1 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                            <span>System Auto-Generated Code (Read-Only)</span>
                        </p>
                        @error('branch_code') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Branch Name *</label>
                        <input type="text" wire:model="branch_name" class="w-full rounded-xl border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-sm" placeholder="e.g. Muscat Headquarters & Distribution">
                        @error('branch_name') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Branch Type *</label>
                        <div x-data="{ open: false }" class="relative">
                            <button type="button" @click="open = !open" class="w-full flex items-center justify-between rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-left font-medium text-slate-800 focus:outline-none focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 transition shadow-2xs">
                                <span class="truncate">
                                    @if($branch_type === 'ADD_NEW')
                                        <span class="text-amber-700 font-semibold">+ Add Custom Branch Type...</span>
                                    @elseif($branch_type)
                                        {{ $branch_type }}
                                    @else
                                        <span class="text-slate-400">Select Branch Type</span>
                                    @endif
                                </span>
                                <svg class="w-4 h-4 text-slate-400 shrink-0 ml-1 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>

                            <div x-show="open" @click.outside="open = false" x-transition class="absolute z-50 mt-1 w-full rounded-xl bg-white shadow-xl border border-slate-200 py-1 max-h-48 overflow-y-auto overscroll-contain shadow-2xl [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden" style="display: none;">
                                @foreach($branchTypes as $type)
                                    <div class="flex items-center justify-between px-3 py-2 hover:bg-slate-50 transition cursor-pointer group {{ $branch_type == $type ? 'bg-red-50/50 font-semibold text-rmsc-red' : 'text-slate-700 text-xs' }}">
                                        <span @click="$wire.set('branch_type', '{{ $type }}'); open = false" class="flex-1 truncate pr-2 py-0.5 text-xs">
                                            {{ $type }}
                                        </span>
                                        @if(!in_array($type, ['HEADQUARTERS', 'RETAIL_STORE', 'DUTY_FREE', 'WHOLESALE_DEPOT', 'RETAIL']))
                                            <button type="button" 
                                                    wire:click.stop="confirmDeleteBranchType('{{ addslashes($type) }}')" 
                                                    class="p-1 text-slate-400 hover:text-red-600 hover:bg-red-100/70 rounded-lg transition" 
                                                    title="Delete Custom Branch Type">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        @endif
                                    </div>
                                @endforeach

                                <div @click="$wire.set('branch_type', 'ADD_NEW'); open = false" class="px-3 py-2 text-xs font-semibold text-amber-700 hover:bg-amber-50 cursor-pointer border-t border-slate-100 flex items-center">
                                    <svg class="w-3.5 h-3.5 mr-1.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    <span>+ Add Custom Branch Type...</span>
                                </div>
                            </div>
                        </div>
                        @error('branch_type') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror

                        @if($isCustomType || $branch_type === 'ADD_NEW')
                            <div class="mt-2.5 p-3 bg-amber-50/70 border border-amber-200/90 rounded-xl space-y-1.5">
                                <label class="block text-[11px] font-bold text-amber-900 uppercase tracking-wider">New Branch Type Name *</label>
                                <input type="text" wire:model="custom_branch_type" class="w-full rounded-lg border-amber-200 bg-white focus:border-rmsc-red focus:ring-rmsc-red text-sm uppercase font-semibold text-slate-800" placeholder="e.g. DEPOT, LOGISTICS, KIOSK, AGENT">
                                @error('custom_branch_type') <span class="text-xs text-rmsc-red block font-medium">{{ $message }}</span> @enderror
                            </div>
                        @endif
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">City / Region in Oman</label>
                        <input type="text" wire:model="city" class="w-full rounded-xl border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-sm" placeholder="e.g. Muscat, Salalah, Sohar">
                    </div>

                    <div>
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="checkbox" wire:model="is_active" class="rounded border-slate-300 text-rmsc-red focus:ring-rmsc-red">
                            <span class="ml-2 text-xs font-semibold text-slate-700">Active Status</span>
                        </label>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex justify-end space-x-3">
                        <button type="button" @click="open = false; $wire.set('isModalOpen', false)" class="px-4 py-2 border border-slate-200 text-slate-700 font-semibold text-xs rounded-xl hover:bg-slate-50 transition">Cancel</button>
                        <button type="submit" class="px-5 py-2 bg-rmsc-red hover:bg-rmsc-red-dark text-white font-semibold text-xs rounded-xl shadow-md transition">{{ $editingBranchId ? 'Update Branch' : 'Save Branch' }}</button>
                    </div>
                </form>
            </div>
        </div>
    </template>
    @endif

    <!-- Delete Confirmation Modal -->
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
                    <h3 class="text-base font-bold text-slate-900 mb-1">Delete Branch?</h3>
                    <p class="text-xs text-slate-500 mb-6">Are you sure you want to delete <span class="font-semibold text-slate-800">"{{ $deletingBranchName }}"</span>? This action cannot be undone and will fail if linked to users or transactions.</p>

                    <div class="flex items-center justify-center space-x-3">
                        <button type="button" @click="deleteOpen = false; $wire.set('isDeleteModalOpen', false)" class="px-4 py-2 border border-slate-200 text-slate-700 font-semibold text-xs rounded-xl hover:bg-slate-50 transition">Cancel</button>
                        <button type="button" wire:click="deleteBranch" class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold text-xs rounded-xl shadow-md transition">Delete Branch</button>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <!-- Custom Item Delete Confirmation Modal (Branch Type) -->
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
