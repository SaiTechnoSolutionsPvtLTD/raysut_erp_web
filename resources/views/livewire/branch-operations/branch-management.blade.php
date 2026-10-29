<div x-data="{ open: @entangle('showModal') }" class="space-y-6">
    <!-- Single Row Header Banner (Title, Search, Filters & Action Button) -->
    <div class="flex flex-wrap items-center justify-between gap-3 pb-4 border-b border-slate-200/80">
        <!-- Title & Subtitle -->
        <div>
            <h1 class="font-heading font-bold text-xl text-slate-900 leading-tight">Branch & Counter Operations</h1>
            <p class="text-[11px] text-slate-500 mt-0.5">Multi-branch facilities, POS counters, and operational statuses</p>
        </div>

        <!-- Controls Group (Search, Filters, Button) -->
        <div class="flex items-center gap-2 shrink-0">
            <!-- Search Bar -->
            <div class="relative w-52">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 z-10">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search code, name, city..." style="padding-left: 2.5rem !important;" class="w-52 h-9 !pl-10 pr-8 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white focus:bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-medium text-slate-800 transition">
                @if($search)
                    <button wire:click="$set('search', '')" type="button" class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600 transition cursor-pointer" title="Clear search">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                @endif
            </div>

            <!-- Type Filter -->
            <select wire:model.live="type_filter" class="h-9 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white focus:bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-medium text-slate-800 transition pl-3 pr-8 cursor-pointer">
                <option value="">All Types</option>
                <option value="RETAIL">Retail</option>
                <option value="WHOLESALE">Wholesale</option>
                <option value="HARBOR">Harbor</option>
                <option value="GROCERY">Grocery</option>
                <option value="MIXED">Mixed</option>
            </select>

            <!-- Status Filter -->
            <select wire:model.live="status_filter" class="h-9 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white focus:bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-medium text-slate-800 transition pl-3 pr-8 cursor-pointer">
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>

            <!-- Clear Filters Button -->
            @if($search || $type_filter || $status_filter !== '')
                <button wire:click="clearFilters" type="button" class="h-9 px-2.5 bg-slate-100 hover:bg-slate-200/80 text-slate-600 hover:text-slate-900 text-xs font-medium rounded-xl transition flex items-center space-x-1 cursor-pointer shrink-0" title="Clear filters">
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    <span>Clear</span>
                </button>
            @endif

            <!-- Add Branch Button -->
            @can('create', App\Models\Branch::class)
            <button wire:click="openCreateModal" @click="open = true" type="button" class="h-9 px-3 bg-rmsc-red hover:bg-rmsc-red-dark text-white font-semibold text-xs rounded-xl shadow-xs transition flex items-center space-x-1 shrink-0 cursor-pointer">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Add Branch</span>
            </button>
            @endcan
        </div>
    </div>

    <!-- Page-level flash messages -->
    @if(session()->has('message'))
        <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-xs font-semibold text-emerald-800">{{ session('message') }}</div>
    @endif
    @if(session()->has('error'))
        <div class="p-3 bg-red-50 border border-red-200 rounded-xl text-xs font-semibold text-rmsc-red">{{ session('error') }}</div>
    @endif

    <!-- Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                    <tr>
                        <th class="px-6 py-4">Code</th>
                        <th class="px-6 py-4">Branch Name</th>
                        <th class="px-6 py-4">Type</th>
                        <th class="px-6 py-4">City</th>
                        <th class="px-6 py-4">POS Counters</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($branches as $branch)
                        <tr wire:key="branch-{{ $branch->branch_id }}" class="hover:bg-slate-50/60 transition">
                            <td class="px-6 py-4 font-mono font-bold text-slate-800 text-xs">{{ $branch->branch_code }}</td>
                            <td class="px-6 py-4 font-semibold text-slate-900">
                                <div>{{ $branch->branch_name }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 font-semibold text-[11px] uppercase">
                                    {{ $branch->branch_type }}
                                </span>
                            </td>
                            <td class="px-6 py-4 font-medium text-slate-800 text-xs">{{ $branch->city ?? '-' }}</td>
                            <td class="px-6 py-4 font-mono text-xs">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-blue-50 text-blue-700 font-semibold text-[11px] border border-blue-200">
                                    {{ $branch->counters_count }} Counter(s)
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $statusClasses = $branch->is_active
                                        ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                                        : 'bg-slate-100 text-slate-600 border border-slate-200';
                                @endphp
                                @can('update', $branch)
                                    <button type="button" wire:click="toggleStatus({{ $branch->branch_id }})" title="Click to {{ $branch->is_active ? 'deactivate' : 'activate' }}" class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold cursor-pointer {{ $statusClasses }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $branch->is_active ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                        {{ $branch->is_active ? 'Active' : 'Inactive' }}
                                    </button>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $statusClasses }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $branch->is_active ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                        {{ $branch->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                @endcan
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end space-x-1">
                                    @can('update', $branch)
                                        <button type="button" wire:click="editBranch({{ $branch->branch_id }})" @click="open = true" class="p-1.5 text-slate-500 hover:text-rmsc-red hover:bg-red-50 rounded-lg transition" title="Edit Branch">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>
                                    @endcan
                                    @can('delete', $branch)
                                        <button type="button" wire:click="confirmDeleteBranch({{ $branch->branch_id }})" class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition" title="Delete Branch">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-slate-400">No branches found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-200/80">
            {{ $branches->links() }}
        </div>
    </div>

    <!-- Create/Edit Branch Modal -->
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
            <div @click.away="if (!$wire.showItemDeleteModal) { open = false; $wire.closeModal(); }"
                 class="bg-white rounded-2xl max-w-2xl w-full mx-auto shadow-2xl border border-slate-200 transform transition-all relative"
                 style="max-width: 672px;">
                <div class="px-6 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between rounded-t-2xl">
                    <h3 class="font-bold text-slate-900 text-base">{{ $isEdit ? 'Edit Branch' : 'Create Branch' }}</h3>
                    <button type="button" @click="open = false; $wire.closeModal()" class="text-slate-400 hover:text-slate-600 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form wire:submit.prevent="saveBranch" class="p-6 space-y-4">
                    <!-- Code & Name -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Branch Code *</label>
                            <input type="text" wire:model="branch_code" readonly class="w-full rounded-xl border-slate-200 bg-slate-100/90 text-slate-600 font-mono text-sm cursor-not-allowed shadow-2xs focus:ring-0 focus:border-slate-200" placeholder="Auto-generated (e.g. BR-001)">
                            <p class="text-[11px] text-slate-400 mt-1 flex items-center">
                                <svg class="w-3.5 h-3.5 mr-1 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                <span>System Auto-Generated Code (Read-Only)</span>
                            </p>
                            @error('branch_code') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Branch Name *</label>
                            <input type="text" wire:model.blur="branch_name" class="w-full rounded-xl border border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-sm font-semibold text-slate-900" placeholder="e.g. Muscat HQ, Salalah Depot">
                            @error('branch_name') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Type & City -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Facility Type *</label>
                            <select wire:model="branch_type" class="w-full rounded-xl border border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-sm font-medium text-slate-800">
                                <option value="RETAIL">Retail Branch</option>
                                <option value="WHOLESALE">Wholesale Facility</option>
                                <option value="HARBOR">Harbor Terminal</option>
                                <option value="GROCERY">Grocery / Depot</option>
                                <option value="MIXED">Mixed Facility</option>
                            </select>
                            @error('branch_type') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">City / Region</label>
                            <input type="text" wire:model.blur="city" class="w-full rounded-xl border border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-sm font-medium text-slate-800" placeholder="e.g. Muscat, Salalah, Sohar">
                            @error('city') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Address -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Full Address / Location Details</label>
                        <textarea wire:model.blur="address" rows="3" class="w-full rounded-xl border border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-sm text-slate-800" placeholder="Enter physical street address or GPS coordinates..."></textarea>
                        @error('address') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Status -->
                    <div class="pt-2">
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="checkbox" wire:model="is_active" class="rounded border-slate-300 text-rmsc-red focus:ring-rmsc-red">
                            <span class="ml-2 text-xs font-semibold text-slate-700">Active Status</span>
                        </label>
                    </div>

                    <!-- POS Counters (edit mode only) -->
                    @if($isEdit)
                        <div class="pt-4 border-t border-slate-100 space-y-3">
                            <div class="flex items-center justify-between">
                                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500">POS Counters</h4>
                                <span class="text-[11px] font-mono font-semibold px-2 py-0.5 bg-slate-100 text-slate-600 rounded-md">{{ $counters->count() }} Registered</span>
                            </div>

                            @if(session()->has('counter_message'))
                                <div class="p-2.5 bg-emerald-50 border border-emerald-200 rounded-xl text-xs font-semibold text-emerald-800">{{ session('counter_message') }}</div>
                            @endif
                            @if(session()->has('counter_error'))
                                <div class="p-2.5 bg-red-50 border border-red-200 rounded-xl text-xs font-semibold text-rmsc-red">{{ session('counter_error') }}</div>
                            @endif

                            <div class="space-y-2 max-h-52 overflow-y-auto pr-1">
                                @forelse($counters as $cnt)
                                    <div wire:key="counter-{{ $cnt->counter_id }}" class="p-3 bg-slate-50 rounded-xl border border-slate-200/60 flex items-center justify-between text-xs">
                                        <div>
                                            <span class="font-mono font-bold text-slate-800">{{ $cnt->counter_code }}</span>
                                            <p class="text-slate-500 font-medium">{{ $cnt->counter_name }}</p>
                                        </div>
                                        <div class="flex items-center gap-1.5">
                                            <button type="button" wire:click="toggleCounter({{ $cnt->counter_id }})" class="px-2 py-1 rounded text-[10px] font-bold uppercase tracking-wider cursor-pointer {{ $cnt->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600' }}">
                                                {{ $cnt->is_active ? 'Active' : 'Disabled' }}
                                            </button>
                                            @can('delete', $cnt)
                                                <button type="button" wire:click="confirmDeleteCounter({{ $cnt->counter_id }})" class="p-1 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded transition" title="Delete Counter">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            @endcan
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-xs text-slate-400 italic">No POS counters configured for this branch.</p>
                                @endforelse
                            </div>

                            <!-- Quick Add Counter (Enter adds the counter, it does not submit the branch form) -->
                            <div class="p-3 bg-slate-50/70 border border-slate-200/70 rounded-xl space-y-2.5">
                                <h5 class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Add New POS Counter</h5>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                                    <div>
                                        <input type="text" wire:model="new_counter_code" wire:keydown.enter.prevent="addCounter" placeholder="Code (e.g. CNT-01)" class="w-full h-9 rounded-lg border border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-xs font-mono">
                                        @error('new_counter_code') <span class="text-[11px] text-rmsc-red block mt-0.5">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <input type="text" wire:model="new_counter_name" wire:keydown.enter.prevent="addCounter" placeholder="Name (e.g. Checkout 1)" class="w-full h-9 rounded-lg border border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-xs">
                                        @error('new_counter_name') <span class="text-[11px] text-rmsc-red block mt-0.5">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <input type="text" wire:model="new_counter_terminal_id" wire:keydown.enter.prevent="addCounter" placeholder="Terminal ID / IP (optional)" class="w-full h-9 rounded-lg border border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-xs font-mono">
                                        @error('new_counter_terminal_id') <span class="text-[11px] text-rmsc-red block mt-0.5">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                                <button type="button" wire:click="addCounter" class="w-full py-2 bg-slate-800 hover:bg-slate-900 text-white text-xs font-semibold rounded-lg transition cursor-pointer">+ Add Counter</button>
                            </div>
                        </div>
                    @endif

                    <div class="pt-4 border-t border-slate-100 flex justify-end space-x-3">
                        <button type="button" @click="open = false; $wire.closeModal()" class="px-4 py-2 border border-slate-200 text-slate-700 font-semibold text-xs rounded-xl hover:bg-slate-50 transition">Cancel</button>
                        <button type="submit" class="px-5 py-2 bg-rmsc-red hover:bg-rmsc-red-dark text-white font-semibold text-xs rounded-xl shadow-md transition">{{ $isEdit ? 'Update Branch' : 'Save Branch' }}</button>
                    </div>
                </form>
            </div>
        </div>
    </template>
    @endif

    <!-- Item Delete Confirmation Modal (Branch / Counter) -->
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