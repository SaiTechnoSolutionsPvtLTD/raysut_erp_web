<div x-data="{ open: @entangle('showModal') }" class="space-y-6">
    <!-- Single Row Header Banner (Title, Search, Filters & Action Button) -->
    <div class="flex flex-wrap items-center justify-between gap-3 pb-4 border-b border-slate-200/80">
        <!-- Title & Subtitle -->
        <div>
            <h1 class="font-heading font-bold text-xl text-slate-900 leading-tight">POS Counters</h1>
            <p class="text-[11px] text-slate-500 mt-0.5">Point of sale cash desks, terminal IDs, and assigned cashiers</p>
        </div>

        <!-- Controls Group (Search, Filters, Button) -->
        <div class="flex items-center gap-2 shrink-0">
            <!-- Search Bar -->
            <div class="relative w-52">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 z-10">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search counter, terminal..." style="padding-left: 2.5rem !important;" class="w-52 h-9 !pl-10 pr-8 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white focus:bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-medium text-slate-800 transition">
                @if($search)
                    <button wire:click="$set('search', '')" type="button" class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600 transition cursor-pointer" title="Clear search">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                @endif
            </div>

            <!-- Branch Filter -->
            <select wire:model.live="branch_filter" class="h-9 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white focus:bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-medium text-slate-800 transition pl-3 pr-8 cursor-pointer">
                <option value="">All Branches</option>
                @foreach($branches as $b)
                    <option value="{{ $b->branch_id }}">{{ $b->branch_code }} - {{ $b->branch_name }}</option>
                @endforeach
            </select>

            <!-- Status Filter -->
            <select wire:model.live="status_filter" class="h-9 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white focus:bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-medium text-slate-800 transition pl-3 pr-8 cursor-pointer">
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>

            <!-- Clear Filters Button -->
            @if($search || $branch_filter || $status_filter !== '')
                <button wire:click="clearFilters" type="button" class="h-9 px-2.5 bg-slate-100 hover:bg-slate-200/80 text-slate-600 hover:text-slate-900 text-xs font-medium rounded-xl transition flex items-center space-x-1 cursor-pointer shrink-0" title="Clear filters">
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    <span>Clear</span>
                </button>
            @endif

            <!-- Add Counter Button -->
            @if(auth()->user()?->hasPermission('Branch & Shift Operations', 'CREATE'))
            <button wire:click="openCreateModal" @click="open = true" type="button" class="h-9 px-3 bg-rmsc-red hover:bg-rmsc-red-dark text-white font-semibold text-xs rounded-xl shadow-xs transition flex items-center space-x-1 shrink-0 cursor-pointer">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Add Counter</span>
            </button>
            @endif
        </div>
    </div>

    <!-- Page-level flash messages -->
    @if(session()->has('message'))
        <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-xs font-semibold text-emerald-800 flex items-center justify-between">
            <span>{{ session('message') }}</span>
        </div>
    @endif
    @if(session()->has('error'))
        <div class="p-3 bg-red-50 border border-red-200 rounded-xl text-xs font-semibold text-rmsc-red flex items-center justify-between">
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Table Card -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                    <tr>
                        <th class="px-6 py-4">Counter Code</th>
                        <th class="px-6 py-4">Counter Name</th>
                        <th class="px-6 py-4">Branch Facility</th>
                        <th class="px-6 py-4">Terminal ID</th>
                        <th class="px-6 py-4">Assigned Cashier</th>
                        <th class="px-6 py-4">Shift History</th>
                        <th class="px-6 py-4">Status</th>
                        @if(auth()->user()?->hasPermission('Branch & Shift Operations', 'EDIT') || auth()->user()?->hasPermission('Branch & Shift Operations', 'DELETE'))
                        <th class="px-6 py-4 text-right">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($counters as $cnt)
                        <tr wire:key="counter-{{ $cnt->counter_id }}" class="hover:bg-slate-50/60 transition">
                            <td class="px-6 py-4 font-mono font-bold text-slate-800 text-xs">{{ $cnt->counter_code }}</td>
                            <td class="px-6 py-4 font-semibold text-slate-900">
                                <div>{{ $cnt->counter_name }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="font-semibold text-slate-800 text-xs">{{ $cnt->branch?->branch_name ?? '-' }}</span>
                                <span class="text-[11px] font-mono text-slate-400 block">{{ $cnt->branch?->branch_code }}</span>
                            </td>
                            <td class="px-6 py-4 font-mono text-xs text-slate-600">
                                {{ $cnt->terminal_id ?? '-' }}
                            </td>
                            <td class="px-6 py-4">
                                @if($cnt->assignedCashier)
                                    <div class="font-medium text-slate-800 text-xs flex items-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span>
                                        <span>{{ $cnt->assignedCashier->full_name }}</span>
                                    </div>
                                    <div class="text-[10px] text-slate-400 font-mono">{{ $cnt->assignedCashier->email }}</div>
                                @else
                                    <span class="text-xs text-slate-400 italic">Unassigned</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 font-mono text-xs">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-blue-50 text-blue-700 font-semibold text-[11px] border border-blue-200">
                                    {{ $cnt->shifts_count ?? 0 }} Shift(s)
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $statusClasses = $cnt->is_active
                                        ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                                        : 'bg-slate-100 text-slate-600 border border-slate-200';
                                @endphp
                                @if(auth()->user()?->hasPermission('Branch & Shift Operations', 'EDIT'))
                                    <button type="button" wire:click="toggleStatus({{ $cnt->counter_id }})" title="Click to toggle status" class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold cursor-pointer {{ $statusClasses }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $cnt->is_active ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                        {{ $cnt->is_active ? 'Active' : 'Inactive' }}
                                    </button>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $statusClasses }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $cnt->is_active ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                        {{ $cnt->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                @endif
                            </td>
                            @if(auth()->user()?->hasPermission('Branch & Shift Operations', 'EDIT') || auth()->user()?->hasPermission('Branch & Shift Operations', 'DELETE'))
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end space-x-1">
                                    @if(auth()->user()?->hasPermission('Branch & Shift Operations', 'EDIT'))
                                    <button type="button" wire:click="editCounter({{ $cnt->counter_id }})" @click="open = true" class="p-1.5 text-slate-500 hover:text-rmsc-red hover:bg-red-50 rounded-lg transition" title="Edit Counter">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                    @endif
                                    @if(auth()->user()?->hasPermission('Branch & Shift Operations', 'DELETE'))
                                    <button type="button" wire:click="confirmDeleteCounter({{ $cnt->counter_id }}, '{{ addslashes($cnt->counter_name) }}')" class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition" title="Delete Counter">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                    @endif
                                </div>
                            </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-8 text-center text-slate-400">No POS counters found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-200/80">
            {{ $counters->links() }}
        </div>
    </div>

    <!-- Create/Edit Counter Modal -->
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
            <div @click.away="if (!$wire.showItemDeleteModal) { open = false; $wire.set('showModal', false); }" 
                 class="bg-white rounded-2xl max-w-2xl w-full mx-auto shadow-2xl border border-slate-200 transform transition-all relative"
                 style="max-width: 672px;">
                <div class="px-6 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between rounded-t-2xl">
                    <h3 class="font-bold text-slate-900 text-base">{{ $isEdit ? 'Edit POS Counter' : 'Add New POS Counter' }}</h3>
                    <button type="button" @click="open = false; $wire.set('showModal', false)" class="text-slate-400 hover:text-slate-600 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form wire:submit.prevent="saveCounter" class="p-6 space-y-4">
                    @if(session()->has('message'))
                        <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-xs font-semibold text-emerald-800 flex items-center justify-between">
                            <span>{{ session('message') }}</span>
                        </div>
                    @endif
                    @if(session()->has('error'))
                        <div class="p-3 bg-red-50 border border-red-200 rounded-xl text-xs font-semibold text-rmsc-red flex items-center justify-between">
                            <span>{{ session('error') }}</span>
                        </div>
                    @endif

                    <!-- Code & Name -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Counter Code *</label>
                            <input type="text" wire:model.blur="counter_code" class="w-full rounded-xl border border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-sm font-mono font-bold text-slate-800" placeholder="e.g. CNT-MCT-01">
                            @error('counter_code') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Counter Name *</label>
                            <input type="text" wire:model.blur="counter_name" class="w-full rounded-xl border border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-sm font-semibold text-slate-900" placeholder="e.g. Muscat Cash Desk 01">
                            @error('counter_name') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Branch & Terminal ID -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Branch Facility *</label>
                            <select wire:model="branch_id" class="w-full rounded-xl border border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-sm font-medium text-slate-800">
                                <option value="">Select Branch</option>
                                @foreach($branches as $b)
                                    <option value="{{ $b->branch_id }}">{{ $b->branch_code }} - {{ $b->branch_name }}</option>
                                @endforeach
                            </select>
                            @error('branch_id') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Terminal Hardware ID</label>
                            <input type="text" wire:model.blur="terminal_id" class="w-full rounded-xl border border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-sm font-mono" placeholder="e.g. POS-T101">
                            @error('terminal_id') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Assigned Cashier -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Default Assigned Cashier</label>
                        <select wire:model="assigned_user_id" class="w-full rounded-xl border border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-sm font-medium text-slate-800">
                            <option value="">Unassigned (Open to any cashier)</option>
                            @foreach($cashiers as $c)
                                <option value="{{ $c->user_id }}">{{ $c->full_name }} ({{ $c->username }})</option>
                            @endforeach
                        </select>
                        <p class="text-[11px] text-slate-400 mt-1">Cashiers assigned to this counter have priority for shift opening.</p>
                        @error('assigned_user_id') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Status -->
                    <div class="pt-2">
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="checkbox" wire:model="is_active" class="rounded border-slate-300 text-rmsc-red focus:ring-rmsc-red">
                            <span class="ml-2 text-xs font-semibold text-slate-700">Active Status</span>
                        </label>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex justify-end space-x-3">
                        <button type="button" @click="open = false; $wire.set('showModal', false)" class="px-4 py-2 border border-slate-200 text-slate-700 font-semibold text-xs rounded-xl hover:bg-slate-50 transition">Cancel</button>
                        <button type="submit" class="px-5 py-2 bg-rmsc-red hover:bg-rmsc-red-dark text-white font-semibold text-xs rounded-xl shadow-md transition">Save Counter</button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <!-- Item Delete Confirmation Modal -->
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
