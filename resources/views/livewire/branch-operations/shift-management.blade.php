<div x-data="{ open: @entangle('showOpenModal') }" class="space-y-6">
    <!-- Single Row Header Banner (Title, Search, Filters & Action Button) -->
    <div class="flex flex-wrap items-center justify-between gap-3 pb-4 border-b border-slate-200/80">
        <!-- Title & Subtitle -->
        <div>
            <h1 class="font-heading font-bold text-xl text-slate-900 leading-tight">Cashier Shifts & Floats</h1>
            <p class="text-[11px] text-slate-500 mt-0.5">Manage POS cashier shifts, cash drawer floats, variance balancing, and supervisor reconciliations</p>
        </div>

        <!-- Controls Group -->
        <div class="flex items-center gap-2 shrink-0 flex-wrap">
            <!-- Search Bar -->
            <div class="relative w-52">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 z-10">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search cashier, counter..." style="padding-left: 2.5rem !important;" class="w-52 h-9 !pl-10 pr-8 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white focus:bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-medium text-slate-800 transition">
                @if($search)
                    <button wire:click="$set('search', '')" type="button" class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600 transition cursor-pointer" title="Clear search">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                @endif
            </div>

            <!-- Status Filter -->
            <select wire:model.live="status_filter" class="h-9 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white focus:bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-medium text-slate-800 transition pl-3 pr-8 cursor-pointer">
                <option value="">All Statuses</option>
                <option value="OPEN">Open</option>
                <option value="CLOSED">Closed (Pending Approval)</option>
                <option value="RECONCILED">Reconciled</option>
            </select>

            <!-- Branch Filter (HQ only) -->
            @if(auth()->user()?->hasRole('Super Admin') || auth()->user()?->hasRole('HQ Admin'))
            <select wire:model.live="branch_filter" class="h-9 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white focus:bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-medium text-slate-800 transition pl-3 pr-8 cursor-pointer">
                <option value="">All Branches</option>
                @foreach($branches as $b)
                    <option value="{{ $b->branch_id }}">{{ $b->branch_code }} - {{ $b->branch_name }}</option>
                @endforeach
            </select>
            @endif

            <!-- Clear Filters Button -->
            @if($search || $status_filter !== '' || $branch_filter !== '' || $date_filter)
                <button wire:click="clearFilters" type="button" class="h-9 px-2.5 bg-slate-100 hover:bg-slate-200/80 text-slate-600 hover:text-slate-900 text-xs font-medium rounded-xl transition flex items-center space-x-1 cursor-pointer shrink-0" title="Clear filters">
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    <span>Clear</span>
                </button>
            @endif

            <!-- Shift Actions Button (Open or Close Shift) -->
            @if($currentShift)
                <button wire:click="openCloseModal({{ $currentShift->shift_id }})" type="button" class="h-9 px-3 bg-amber-600 hover:bg-amber-700 text-white font-semibold text-xs rounded-xl shadow-xs transition flex items-center space-x-1 shrink-0 cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    <span>Close My Shift (#{{ $currentShift->shift_id }})</span>
                </button>
            @else
                @if(auth()->user()?->hasPermission('Branch & Shift Operations', 'CREATE') || auth()->user()?->hasRole('Cashier') || auth()->user()?->hasRole('Super Admin') || auth()->user()?->hasRole('HQ Admin') || auth()->user()?->hasRole('Branch Admin'))
                <button wire:click="openShiftModal" @click="open = true" type="button" class="h-9 px-3 bg-rmsc-red hover:bg-rmsc-red-dark text-white font-semibold text-xs rounded-xl shadow-xs transition flex items-center space-x-1 shrink-0 cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>Open New Shift</span>
                </button>
                @endif
            @endif
        </div>
    </div>

    <!-- Active Shift Reminder Banner -->
    @if($currentShift)
        <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center justify-between text-xs">
            <div class="flex items-center space-x-2">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                <span class="font-bold text-emerald-900">Active Shift Open:</span>
                <span class="text-emerald-800">Shift #{{ $currentShift->shift_id }} on Counter <span class="font-mono font-bold">{{ $currentShift->counter?->counter_code }}</span> (Float: {{ number_format($currentShift->opening_float, 3) }} OMR)</span>
            </div>
            <div class="flex items-center space-x-2">
                <a href="{{ route('pos.terminal') }}" class="px-2.5 py-1 bg-emerald-600 text-white font-semibold rounded-lg hover:bg-emerald-700 transition">
                    Open POS Terminal &rarr;
                </a>
            </div>
        </div>
    @endif

    <!-- Page-level Flash Alerts -->
    @if(session()->has('message'))
        <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-xs font-semibold text-emerald-800 flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span>{{ session('message') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700">&times;</button>
        </div>
    @endif
    @if(session()->has('error'))
        <div class="p-3 bg-red-50 border border-red-200 rounded-xl text-xs font-semibold text-rmsc-red flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <svg class="w-4 h-4 text-rmsc-red shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <span>{{ session('error') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-red-400 hover:text-red-600">&times;</button>
        </div>
    @endif

    <!-- Table Card -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-slate-50 uppercase text-xs font-semibold text-slate-500 border-b border-slate-200/80">
                    <tr>
                        <th class="px-6 py-3.5">Shift #</th>
                        <th class="px-6 py-3.5">Cashier</th>
                        <th class="px-6 py-3.5">Counter & Branch</th>
                        <th class="px-6 py-3.5">Opened / Closed</th>
                        <th class="px-6 py-3.5 text-right">Float</th>
                        <th class="px-6 py-3.5 text-right">Counted</th>
                        <th class="px-6 py-3.5 text-right">Variance</th>
                        <th class="px-6 py-3.5">Variance Status</th>
                        <th class="px-6 py-3.5">Status</th>
                        <th class="px-6 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse($shifts as $s)
                        <tr class="hover:bg-slate-50/60 transition group">
                            <td class="px-6 py-4 font-mono font-bold text-xs text-slate-900">
                                #{{ $s->shift_id }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-semibold text-slate-900 text-xs">{{ $s->user?->full_name ?? '-' }}</div>
                                <div class="text-[11px] font-mono text-slate-400">{{ $s->user?->email }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-semibold text-slate-900 text-xs">{{ $s->counter?->counter_name ?? ('Counter #' . $s->counter_id) }}</div>
                                <div class="text-[11px] text-slate-500 font-mono flex items-center gap-1.5 mt-0.5">
                                    <span class="font-semibold text-slate-700">{{ $s->counter?->counter_code ?? '-' }}</span>
                                    <span>&bull;</span>
                                    <span class="text-slate-500">{{ $s->branch?->branch_name ?? '-' }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-xs font-mono text-slate-600">
                                <div class="font-medium text-slate-800">{{ $s->opened_at->format('d M H:i') }}</div>
                                @if($s->closed_at)
                                    <div class="text-[11px] text-slate-400">{{ $s->closed_at->format('d M H:i') }}</div>
                                @else
                                    <span class="inline-flex items-center gap-1 text-emerald-600 font-semibold text-[11px] mt-0.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        ACTIVE
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 font-mono text-xs text-slate-700 text-right">
                                {{ number_format($s->opening_float, 3) }}
                            </td>
                            <td class="px-6 py-4 font-mono text-xs text-right">
                                @if($s->status === 'OPEN')
                                    <span class="text-slate-400 italic text-[11px]" title="Drawer cash will be counted when shift is closed">Pending Close</span>
                                @else
                                    <span class="text-slate-700 font-medium">{{ $s->counted_cash !== null ? number_format($s->counted_cash, 3) : '-' }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 font-mono text-xs text-right">
                                @if($s->status === 'OPEN')
                                    <span class="text-slate-400 italic text-[11px]" title="Calculated upon shift closing">Pending Close</span>
                                @elseif($s->variance !== null)
                                    <span class="{{ (float)$s->variance == 0 ? 'text-emerald-700 font-semibold' : ((float)$s->variance > 0 ? 'text-blue-700 font-bold' : 'text-rose-700 font-bold') }}">
                                        {{ (float)$s->variance > 0 ? '+' : '' }}{{ number_format($s->variance, 3) }}
                                    </span> <span class="text-[10px] font-sans text-slate-400">OMR</span>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($s->status === 'OPEN')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-mono font-medium border bg-blue-50 text-blue-700 border-blue-200">
                                        IN PROGRESS
                                    </span>
                                @else
                                    @php
                                        $varBadge = match($s->variance_status) {
                                            'BALANCED' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                            'FLAGGED' => 'bg-rose-50 text-rose-700 border-rose-200',
                                            'APPROVED' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                            'REJECTED' => 'bg-rose-50 text-rose-700 border-rose-200',
                                            'OVER', 'SHORT' => 'bg-amber-50 text-amber-700 border-amber-200',
                                            default => 'bg-slate-100 text-slate-600 border-slate-200',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-mono font-semibold border {{ $varBadge }}">
                                        {{ $s->variance_status }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $statusPills = [
                                        'OPEN' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        'CLOSED' => 'bg-slate-100 text-slate-600 border-slate-200',
                                        'RECONCILED' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    ];
                                    $pillClass = $statusPills[$s->status] ?? 'bg-slate-100 text-slate-600 border-slate-200';
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold border {{ $pillClass }}">
                                    {{ $s->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end space-x-1">
                                    <!-- View Shift Details -->
                                    <button type="button" wire:click="openDetailsModal({{ $s->shift_id }})" class="p-1.5 text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded-lg transition" title="View Shift Details">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </button>

                                    <!-- Print Summary Link -->
                                    <a href="{{ route('shifts.print', $s) }}" target="_blank" class="p-1.5 text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded-lg transition" title="Print Shift Summary">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                    </a>

                                    <!-- Close Shift (cashier or authorized user) -->
                                    @if($s->status === 'OPEN' && (auth()->id() === $s->user_id || auth()->user()?->hasRole('Super Admin') || auth()->user()?->hasRole('HQ Admin')))
                                        <button type="button" wire:click="openCloseModal({{ $s->shift_id }})" class="p-1.5 text-amber-600 hover:text-amber-800 hover:bg-amber-50 rounded-lg transition" title="Close Shift">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                        </button>
                                    @endif

                                    <!-- Supervisor Approve/Reject for Flagged shifts -->
                                    @if($s->variance_status === 'FLAGGED' && (auth()->user()?->hasRole('Super Admin') || auth()->user()?->hasRole('HQ Admin') || auth()->user()?->hasPermission('Branch & Shift Operations', 'APPROVE')))
                                        <button type="button" wire:click="openApproveModal({{ $s->shift_id }})" class="p-1.5 text-emerald-600 hover:text-emerald-800 hover:bg-emerald-50 rounded-lg transition" title="Approve Variance">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        </button>
                                        <button type="button" wire:click="openRejectModal({{ $s->shift_id }})" class="p-1.5 text-rose-600 hover:text-rose-800 hover:bg-rose-50 rounded-lg transition" title="Reject Variance">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-6 py-12 text-center text-slate-500">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-10 h-10 text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <p class="text-sm font-semibold text-slate-700">No cashier shifts found.</p>
                                    <p class="text-xs text-slate-400 mt-1">Open a new shift to begin POS operations.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-200/80">
            {{ $shifts->links() }}
        </div>
    </div>

    <!-- Modal: Open New Shift (448px) -->
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
            <div @click.away="open = false; $wire.set('showOpenModal', false)"
                 class="bg-white rounded-2xl w-full mx-auto shadow-2xl border border-slate-200 transform transition-all relative"
                 style="max-width: 448px;">
                <div class="px-6 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between rounded-t-2xl">
                    <div>
                        <h3 class="font-bold text-slate-900 text-base">Open POS Shift</h3>
                        <p class="text-[11px] text-slate-500 mt-0.5">Provide initial cash drawer float</p>
                    </div>
                    <button type="button" @click="open = false; $wire.set('showOpenModal', false)" class="text-slate-400 hover:text-slate-600 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="p-6 space-y-4">
                    @if(auth()->user()?->hasRole('Super Admin') || auth()->user()?->hasRole('HQ Admin') || count($branches) > 1)
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Branch Facility</label>
                        <select wire:model.live="openBranchId" class="w-full h-9 rounded-xl border border-slate-200 text-xs font-medium focus:border-rmsc-red focus:ring-rmsc-red">
                            <option value="">All Branches (All Counters)</option>
                            @foreach($branches as $b)
                                <option value="{{ $b->branch_id }}">{{ $b->branch_code }} - {{ $b->branch_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">POS Counter *</label>
                        <select wire:model.live="openCounterId" class="w-full h-9 rounded-xl border border-slate-200 text-xs font-medium focus:border-rmsc-red focus:ring-rmsc-red">
                            <option value="">Select Counter ({{ count($availableCounters) }} available)...</option>
                            @if(empty($openBranchId))
                                @foreach($availableCounters->groupBy(fn($c) => $c->branch?->branch_name ?? 'Branch #' . $c->branch_id) as $branchName => $counters)
                                    <optgroup label="{{ $branchName }}">
                                        @foreach($counters as $cnt)
                                            @php
                                                $isLockedForCashier = (!auth()->user()?->hasRole('Super Admin') && !auth()->user()?->hasRole('HQ Admin'))
                                                    && ($cnt->assigned_user_id && $cnt->assigned_user_id !== auth()->id());
                                            @endphp
                                            <option value="{{ $cnt->counter_id }}" @if($isLockedForCashier) disabled class="text-slate-400 bg-slate-50" @endif>
                                                {{ $cnt->counter_code }} - {{ $cnt->counter_name }}
                                                @if($cnt->assignedCashier)
                                                    [Assigned: {{ $cnt->assignedCashier->full_name }}@if($isLockedForCashier) - Locked @endif]
                                                @else
                                                    [Unassigned]
                                                @endif
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            @else
                                @foreach($availableCounters as $cnt)
                                    @php
                                        $isLockedForCashier = (!auth()->user()?->hasRole('Super Admin') && !auth()->user()?->hasRole('HQ Admin'))
                                            && ($cnt->assigned_user_id && $cnt->assigned_user_id !== auth()->id());
                                    @endphp
                                    <option value="{{ $cnt->counter_id }}" @if($isLockedForCashier) disabled class="text-slate-400 bg-slate-50" @endif>
                                        {{ $cnt->counter_code }} - {{ $cnt->counter_name }}
                                        @if($cnt->assignedCashier)
                                            [Assigned: {{ $cnt->assignedCashier->full_name }}@if($isLockedForCashier) - Locked @endif]
                                        @else
                                            [Unassigned]
                                        @endif
                                    </option>
                                @endforeach
                            @endif
                        </select>
                        @error('openCounterId') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    @if(auth()->user()?->hasRole('Super Admin') || auth()->user()?->hasRole('HQ Admin'))
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Cashier / Shift Operator *</label>
                        <select wire:model.live="openCashierId" class="w-full h-9 rounded-xl border border-slate-200 text-xs font-medium focus:border-rmsc-red focus:ring-rmsc-red">
                            <option value="{{ auth()->id() }}">Myself ({{ auth()->user()->full_name }}) [Admin Operator]</option>
                            @foreach($cashiers as $c)
                                @if($c->user_id !== auth()->id())
                                    <option value="{{ $c->user_id }}">{{ $c->full_name }} ({{ $c->username }})</option>
                                @endif
                            @endforeach
                        </select>
                        <p class="text-[11px] text-slate-400 mt-1">Super Admin can open on behalf of an assigned cashier or as admin operator.</p>
                        @error('openCashierId') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    @endif

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Opening Cash Float (OMR) *</label>
                        <input type="number" step="0.001" min="0" wire:model="openFloat" class="w-full h-9 rounded-xl border border-slate-200 text-xs font-mono font-semibold focus:border-rmsc-red focus:ring-rmsc-red">
                        <p class="text-[11px] text-slate-400 mt-1">Starting cash float in the cash drawer (default 50.000 OMR)</p>
                        @error('openFloat') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Shift Notes</label>
                        <input type="text" wire:model="openNotes" placeholder="Optional notes for shift opening..." class="w-full h-9 rounded-xl border border-slate-200 text-xs focus:border-rmsc-red focus:ring-rmsc-red">
                        @error('openNotes') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex justify-end space-x-3">
                        <button type="button" @click="open = false; $wire.set('showOpenModal', false)" class="px-4 py-2 border border-slate-200 text-slate-700 font-semibold text-xs rounded-xl hover:bg-slate-50 transition">
                            Cancel
                        </button>
                        <button type="button" wire:click="executeOpenShift" class="px-5 py-2 bg-rmsc-red hover:bg-rmsc-red-dark text-white font-semibold text-xs rounded-xl shadow-md transition">
                            Open Shift
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <!-- Modal: Close Shift (512px) -->
    <template x-teleport="body">
        <div x-show="$wire.showCloseModal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
             style="display: none;">
            <div @click.away="$wire.set('showCloseModal', false)"
                 class="bg-white rounded-2xl w-full mx-auto shadow-2xl border border-slate-200 transform transition-all relative"
                 style="max-width: 512px;">
                <div class="px-6 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between rounded-t-2xl">
                    <div>
                        <h3 class="font-bold text-slate-900 text-base">Close Cashier Shift #{{ $closeShift?->shift_id }}</h3>
                        <p class="text-[11px] text-slate-500 font-mono mt-0.5">{{ $closeShift?->counter?->counter_code }} - {{ $closeShift?->branch?->branch_name }}</p>
                    </div>
                    <button type="button" @click="$wire.set('showCloseModal', false)" class="text-slate-400 hover:text-slate-600 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="p-6 space-y-4">
                    <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl grid grid-cols-2 gap-3 text-xs">
                        <div>
                            <span class="text-slate-400 block text-[10px] uppercase font-bold">Opening Float</span>
                            <span class="font-mono font-semibold text-slate-800">{{ number_format($closeShift?->opening_float ?? 0, 3) }} OMR</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[10px] uppercase font-bold">Expected Cash</span>
                            <span class="font-mono font-bold text-rmsc-red">{{ number_format($closeShift?->expected_cash ?? 0, 3) }} OMR</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Counted Cash (OMR) *</label>
                            <input type="number" step="0.001" min="0" wire:model="countedCash" class="w-full h-9 rounded-xl border border-slate-200 text-xs font-mono font-semibold focus:border-rmsc-red focus:ring-rmsc-red">
                            @error('countedCash') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Card Machine Total *</label>
                            <input type="number" step="0.001" min="0" wire:model="cardTotal" class="w-full h-9 rounded-xl border border-slate-200 text-xs font-mono font-semibold focus:border-rmsc-red focus:ring-rmsc-red">
                            @error('cardTotal') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Closing Notes / Discrepancy Reason</label>
                        <textarea wire:model="closeNotes" rows="2" placeholder="Explain any cash overages or shortages..." class="w-full rounded-xl border border-slate-200 text-xs focus:border-rmsc-red focus:ring-rmsc-red"></textarea>
                        @error('closeNotes') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex justify-end space-x-3">
                        <button type="button" @click="$wire.set('showCloseModal', false)" class="px-4 py-2 border border-slate-200 text-slate-700 font-semibold text-xs rounded-xl hover:bg-slate-50 transition">
                            Cancel
                        </button>
                        <button type="button" wire:click="executeCloseShift" class="px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white font-semibold text-xs rounded-xl shadow-md transition">
                            Reconcile & Close Shift
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <!-- Modal: Supervisor Approve Variance (448px) -->
    <template x-teleport="body">
        <div x-show="$wire.showApproveModal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
             style="display: none;">
            <div @click.away="$wire.set('showApproveModal', false)"
                 class="bg-white rounded-2xl w-full mx-auto shadow-2xl border border-slate-200 transform transition-all relative"
                 style="max-width: 448px;">
                <div class="px-6 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between rounded-t-2xl">
                    <div>
                        <h3 class="font-bold text-slate-900 text-base">Approve Shift Variance</h3>
                        <p class="text-[11px] text-slate-500 font-mono mt-0.5">Shift #{{ $reviewShift?->shift_id }} (Variance: {{ number_format($reviewShift?->variance ?? 0, 3) }} OMR)</p>
                    </div>
                    <button type="button" @click="$wire.set('showApproveModal', false)" class="text-slate-400 hover:text-slate-600 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Supervisor Approval Notes</label>
                        <textarea wire:model="supervisorNotes" rows="3" placeholder="State reason for accepting shift variance..." class="w-full rounded-xl border border-slate-200 text-xs focus:border-rmsc-red focus:ring-rmsc-red"></textarea>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex justify-end space-x-3">
                        <button type="button" @click="$wire.set('showApproveModal', false)" class="px-4 py-2 border border-slate-200 text-slate-700 font-semibold text-xs rounded-xl hover:bg-slate-50 transition">
                            Cancel
                        </button>
                        <button type="button" wire:click="executeApproveShift" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs rounded-xl shadow-md transition">
                            Approve Variance
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <!-- Modal: Supervisor Reject Variance (448px) -->
    <template x-teleport="body">
        <div x-show="$wire.showRejectModal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
             style="display: none;">
            <div @click.away="$wire.set('showRejectModal', false)"
                 class="bg-white rounded-2xl w-full mx-auto shadow-2xl border border-slate-200 transform transition-all relative"
                 style="max-width: 448px;">
                <div class="px-6 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between rounded-t-2xl">
                    <div>
                        <h3 class="font-bold text-slate-900 text-base">Reject Shift Variance</h3>
                        <p class="text-[11px] text-slate-500 font-mono mt-0.5">Shift #{{ $reviewShift?->shift_id }}</p>
                    </div>
                    <button type="button" @click="$wire.set('showRejectModal', false)" class="text-slate-400 hover:text-slate-600 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Supervisor Rejection Notes *</label>
                        <textarea wire:model="supervisorNotes" rows="3" placeholder="Provide reason for rejecting variance (audit requirement)..." class="w-full rounded-xl border border-slate-200 text-xs focus:border-rmsc-red focus:ring-rmsc-red"></textarea>
                        @error('supervisorNotes') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex justify-end space-x-3">
                        <button type="button" @click="$wire.set('showRejectModal', false)" class="px-4 py-2 border border-slate-200 text-slate-700 font-semibold text-xs rounded-xl hover:bg-slate-50 transition">
                            Cancel
                        </button>
                        <button type="button" wire:click="executeRejectShift" class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white font-semibold text-xs rounded-xl shadow-md transition">
                            Reject Variance
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <!-- Modal: View Shift Details (560px) -->
    <template x-teleport="body">
        <div x-show="$wire.showDetailsModal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
             style="display: none;">
            <div @click.away="$wire.closeDetailsModal()"
                 class="bg-white rounded-2xl w-full mx-auto shadow-2xl border border-slate-200 transform transition-all relative overflow-hidden"
                 style="max-width: 560px;">
                <!-- Header -->
                <div class="px-6 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-red-50 text-rmsc-red flex items-center justify-center font-bold text-xs">
                            #{{ $selectedShift?->shift_id }}
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-900 text-base flex items-center gap-2">
                                Shift Details
                                @if($selectedShift)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold border {{ $selectedShift->status === 'OPEN' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-600 border-slate-200' }}">
                                        {{ $selectedShift->status }}
                                    </span>
                                @endif
                            </h3>
                            <p class="text-[11px] text-slate-500 font-mono mt-0.5">
                                {{ $selectedShift?->counter?->counter_name }} ({{ $selectedShift?->counter?->counter_code }}) &bull; {{ $selectedShift?->branch?->branch_name }}
                            </p>
                        </div>
                    </div>
                    <button type="button" wire:click="closeDetailsModal" class="text-slate-400 hover:text-slate-600 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Body -->
                <div class="p-6 space-y-4">
                    <!-- General Details Grid -->
                    <div class="p-3.5 bg-slate-50 border border-slate-200/80 rounded-xl grid grid-cols-2 gap-3 text-xs">
                        <div>
                            <span class="text-slate-400 block text-[10px] uppercase font-bold tracking-wider">Cashier / Operator</span>
                            <span class="font-semibold text-slate-900 block mt-0.5">{{ $selectedShift?->user?->full_name ?? '-' }}</span>
                            <span class="text-[11px] font-mono text-slate-400">{{ $selectedShift?->user?->email }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[10px] uppercase font-bold tracking-wider">POS Terminal & Branch</span>
                            <span class="font-semibold text-slate-900 block mt-0.5">{{ $selectedShift?->counter?->terminal_id ?? $selectedShift?->counter?->counter_code }}</span>
                            <span class="text-[11px] text-slate-500">{{ $selectedShift?->branch?->branch_name }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[10px] uppercase font-bold tracking-wider">Opened At</span>
                            <span class="font-mono text-slate-700 block mt-0.5">{{ $selectedShift?->opened_at?->format('d M Y, H:i:s') }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[10px] uppercase font-bold tracking-wider">Closed At</span>
                            @if($selectedShift?->closed_at)
                                <span class="font-mono text-slate-700 block mt-0.5">{{ $selectedShift->closed_at->format('d M Y, H:i:s') }}</span>
                            @else
                                <span class="inline-flex items-center gap-1 text-emerald-600 font-semibold text-xs mt-0.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    Currently Active (Open)
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Financial Breakdown Card -->
                    <div class="border border-slate-200 rounded-xl p-4 space-y-2.5">
                        <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Drawer & Reconciliation Totals</h4>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-xs pt-1">
                            <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-100">
                                <span class="text-slate-500 block text-[10px] font-medium">Opening Float</span>
                                <span class="font-mono font-bold text-slate-800 text-sm mt-0.5 block">OMR {{ number_format($selectedShift?->opening_float ?? 0, 3) }}</span>
                            </div>
                            <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-100">
                                <span class="text-slate-500 block text-[10px] font-medium">Expected Cash</span>
                                <span class="font-mono font-bold text-rmsc-red text-sm mt-0.5 block">OMR {{ number_format($selectedShift?->expected_cash ?? 0, 3) }}</span>
                            </div>
                            <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-100">
                                <span class="text-slate-500 block text-[10px] font-medium">Counted Cash</span>
                                @if($selectedShift?->status === 'OPEN')
                                    <span class="text-slate-400 italic text-xs mt-1 block">Pending Close</span>
                                @else
                                    <span class="font-mono font-bold text-slate-800 text-sm mt-0.5 block">OMR {{ number_format($selectedShift?->counted_cash ?? 0, 3) }}</span>
                                @endif
                            </div>
                            <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-100">
                                <span class="text-slate-500 block text-[10px] font-medium">Card Slips Total</span>
                                <span class="font-mono font-bold text-slate-800 text-sm mt-0.5 block">OMR {{ number_format($selectedShift?->card_total ?? 0, 3) }}</span>
                            </div>
                            <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-100">
                                <span class="text-slate-500 block text-[10px] font-medium">Cash Variance</span>
                                @if($selectedShift?->status === 'OPEN')
                                    <span class="text-slate-400 italic text-xs mt-1 block">Pending Close</span>
                                @else
                                    <span class="font-mono font-bold text-sm mt-0.5 block {{ (float)$selectedShift?->variance == 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                                        {{ (float)$selectedShift?->variance > 0 ? '+' : '' }}{{ number_format($selectedShift?->variance ?? 0, 3) }} OMR
                                    </span>
                                @endif
                            </div>
                            <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-100">
                                <span class="text-slate-500 block text-[10px] font-medium">Variance Status</span>
                                @if($selectedShift?->status === 'OPEN')
                                    <span class="text-blue-700 font-semibold text-xs mt-1 block">IN PROGRESS</span>
                                @else
                                    <span class="font-mono font-bold text-xs mt-1 block text-slate-800">{{ $selectedShift?->variance_status }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if($selectedShift?->notes)
                        <div class="p-3 bg-amber-50/60 border border-amber-200/80 rounded-xl text-xs">
                            <span class="font-bold text-amber-900 block text-[11px] uppercase">Notes:</span>
                            <p class="text-amber-800 mt-0.5">{{ $selectedShift->notes }}</p>
                        </div>
                    @endif

                    @if($selectedShift?->approved_by)
                        <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-xs">
                            <span class="font-bold text-emerald-900 block text-[11px] uppercase">Supervisor Approval:</span>
                            <p class="text-emerald-800 mt-0.5">Approved by {{ $selectedShift->approver?->full_name }} at {{ $selectedShift->approved_at?->format('d M Y, H:i') }}</p>
                        </div>
                    @endif

                    <!-- Modal Actions -->
                    <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
                        <div>
                            @if($selectedShift)
                                <a href="{{ route('shifts.print', $selectedShift) }}" target="_blank" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-xl transition inline-flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                    <span>Print Slip</span>
                                </a>
                            @endif
                        </div>
                        <div class="flex items-center space-x-2">
                            @if($selectedShift?->status === 'OPEN')
                                <a href="{{ route('pos.terminal') }}" class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs rounded-xl shadow-xs transition">
                                    Open POS Terminal &rarr;
                                </a>
                            @endif
                            <button type="button" wire:click="closeDetailsModal" class="px-4 py-1.5 border border-slate-200 text-slate-700 font-semibold text-xs rounded-xl hover:bg-slate-50 transition">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>