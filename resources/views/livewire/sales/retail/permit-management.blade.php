<div class="space-y-6">
    <!-- Single Row Header Banner (Title, Search, Filters & Action Button) -->
    <div class="flex flex-wrap items-center justify-between gap-3 pb-4 border-b border-slate-200/80">
        <!-- Title & Subtitle -->
        <div>
            <h1 class="font-heading font-bold text-xl text-slate-900 leading-tight">Oman Permits</h1>
            <p class="text-[11px] text-slate-500 mt-0.5">Read-only register of Oman government liquor permit verifications captured during POS retail billing</p>
        </div>

        <!-- Controls Group -->
        <div class="flex items-center gap-2 shrink-0 flex-wrap">
            <!-- Search Bar -->
            <div class="relative w-52">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 z-10">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search receipt, holder, permit..." style="padding-left: 2.5rem !important;" class="w-52 h-9 !pl-10 pr-8 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white focus:bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-medium text-slate-800 transition">
                @if($search)
                    <button wire:click="$set('search', '')" type="button" class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600 transition cursor-pointer" title="Clear search">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                @endif
            </div>

            <!-- Status Filter -->
            <select wire:model.live="status_filter" class="h-9 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white focus:bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-medium text-slate-800 transition pl-3 pr-8 cursor-pointer">
                <option value="">All Statuses</option>
                <option value="USED">Used (Billed)</option>
                <option value="VOIDED">Voided</option>
                <option value="PENDING">Pending</option>
            </select>

            <!-- Method Filter -->
            <select wire:model.live="method_filter" class="h-9 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white focus:bg-white focus:border-rmsc-red focus:ring-2 focus:ring-rmsc-red/10 text-xs font-medium text-slate-800 transition pl-3 pr-8 cursor-pointer">
                <option value="">All Methods</option>
                <option value="MANUAL">Manual Entry</option>
                <option value="SCREENSHOT">Screenshot OCR</option>
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
            @if($search || $branch_filter || $method_filter || $status_filter || $date_from || $date_to)
                <button wire:click="clearFilters" type="button" class="h-9 px-2.5 bg-slate-100 hover:bg-slate-200/80 text-slate-600 hover:text-slate-900 text-xs font-medium rounded-xl transition flex items-center space-x-1 cursor-pointer shrink-0" title="Clear filters">
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    <span>Clear</span>
                </button>
            @endif

            <!-- Export CSV Button -->
            <a href="{{ route('permits.export') }}" target="_blank" class="h-9 px-3 bg-rmsc-red hover:bg-rmsc-red-dark text-white font-semibold text-xs rounded-xl shadow-xs transition flex items-center space-x-1 shrink-0 cursor-pointer" title="Export verifications to CSV">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                <span>Export CSV</span>
            </a>
        </div>
    </div>

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
                        <th class="px-6 py-3.5">Receipt #</th>
                        <th class="px-6 py-3.5">Holder</th>
                        <th class="px-6 py-3.5">Permit #</th>
                        <th class="px-6 py-3.5">Branch & Cashier</th>
                        <th class="px-6 py-3.5">Verified At</th>
                        <th class="px-6 py-3.5 text-right">Annual Limit</th>
                        <th class="px-6 py-3.5 text-right">Remaining</th>
                        <th class="px-6 py-3.5 text-right">Controlled Total</th>
                        <th class="px-6 py-3.5">Method</th>
                        <th class="px-6 py-3.5">Status</th>
                        <th class="px-6 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse($verifications as $v)
                        <tr class="hover:bg-slate-50/60 transition group">
                            <!-- Receipt No -->
                            <td class="px-6 py-4 font-mono font-bold text-xs text-slate-900">
                                @if($v->retailSale)
                                    <a href="{{ route('pos.receipt', $v->retailSale->id) }}" target="_blank" class="text-rmsc-red hover:underline" title="View Printable Receipt">
                                        {{ $v->retailSale->receipt_no }}
                                    </a>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>

                            <!-- Holder + Civil ID -->
                            <td class="px-6 py-4">
                                <div class="font-semibold text-slate-900 text-xs">{{ $v->holder_name }}</div>
                                <div class="text-[11px] font-mono text-slate-400">
                                    @if($canViewFullCivilId)
                                        <span class="text-slate-600" title="Authorized unmasked Civil ID">{{ $v->civil_id }}</span>
                                    @else
                                        {{ $v->masked_civil_id }}
                                    @endif
                                </div>
                            </td>

                            <!-- Permit No -->
                            <td class="px-6 py-4 font-mono text-xs text-slate-700">
                                {{ $v->permit_no ?? '-' }}
                            </td>

                            <!-- Branch & Cashier -->
                            <td class="px-6 py-4">
                                <div class="font-semibold text-slate-900 text-xs">{{ $v->branch?->branch_name ?? '-' }}</div>
                                <div class="text-[11px] text-slate-500 mt-0.5">{{ $v->enteredBy?->full_name ?? '-' }}</div>
                            </td>

                            <!-- Verified At -->
                            <td class="px-6 py-4 text-xs font-mono text-slate-600">
                                <div class="font-medium text-slate-800">{{ $v->verified_at ? $v->verified_at->format('d M H:i') : '-' }}</div>
                            </td>

                            <!-- Annual Limit -->
                            <td class="px-6 py-4 font-mono text-xs text-slate-700 text-right">
                                {{ $v->annual_limit !== null ? number_format($v->annual_limit, 3) : '-' }}
                            </td>

                            <!-- Remaining -->
                            <td class="px-6 py-4 font-mono text-xs text-right">
                                @if($v->remaining_amount !== null)
                                    <span class="{{ (float)$v->remaining_amount > 0 ? 'text-emerald-700 font-semibold' : 'text-rose-700 font-bold' }}">
                                        {{ number_format($v->remaining_amount, 3) }}
                                    </span>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>

                            <!-- Controlled Total -->
                            <td class="px-6 py-4 font-mono text-xs text-right">
                                <span class="font-bold text-slate-900">{{ number_format($v->controlled_total, 3) }}</span>
                                <span class="text-[10px] font-sans text-slate-400">OMR</span>
                            </td>

                            <!-- Entry Method -->
                            <td class="px-6 py-4">
                                @if($v->entry_method === 'SCREENSHOT')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-mono font-semibold border bg-purple-50 text-purple-700 border-purple-200">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        OCR
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-mono font-semibold border bg-slate-100 text-slate-600 border-slate-200">
                                        MANUAL
                                    </span>
                                @endif
                            </td>

                            <!-- Status -->
                            <td class="px-6 py-4">
                                @php
                                    $statusPills = [
                                        'USED' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        'VOIDED' => 'bg-rose-50 text-rose-700 border-rose-200',
                                        'PENDING' => 'bg-amber-50 text-amber-700 border-amber-200',
                                    ];
                                    $pillClass = $statusPills[$v->status] ?? 'bg-slate-100 text-slate-600 border-slate-200';
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold border {{ $pillClass }}">
                                    {{ $v->status }}
                                </span>
                            </td>

                            <!-- Actions -->
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end space-x-1">
                                    <!-- View Verification Details -->
                                    <button type="button" wire:click="openDetailModal({{ $v->id }})" class="p-1.5 text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded-lg transition cursor-pointer" title="View Verification Details">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </button>

                                    <!-- Print Receipt Link -->
                                    @if($v->retailSale)
                                        <a href="{{ route('pos.receipt', $v->retailSale->id) }}" target="_blank" class="p-1.5 text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded-lg transition" title="View Receipt">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="px-6 py-12 text-center text-slate-500">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-10 h-10 text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    <p class="text-sm font-semibold text-slate-700">No permit verifications found.</p>
                                    <p class="text-xs text-slate-400 mt-1">Verifications are automatically captured when customers purchase permit-controlled items at POS billing.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-200/80">
            {{ $verifications->links() }}
        </div>
    </div>

    <!-- Modal: View Permit Verification Details (620px) -->
    <template x-teleport="body">
        <div x-show="$wire.showDetailModal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center px-4"
             style="display: none; padding-top: 2.5rem; padding-bottom: 2.5rem;">
            <div @click.away="$wire.closeDetailModal()"
                 class="bg-white rounded-2xl w-full mx-auto shadow-2xl border border-slate-200 transform transition-all relative overflow-hidden flex flex-col"
                 style="max-width: 620px; max-height: calc(100vh - 5rem);">
                <!-- Header -->
                <div class="px-6 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-red-50 text-rmsc-red flex items-center justify-center font-bold text-xs">
                            #{{ $selectedVerification?->id }}
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-900 text-base flex items-center gap-2">
                                Permit Verification Details
                                @if($selectedVerification)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold border {{ $selectedVerification->status === 'USED' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : ($selectedVerification->status === 'VOIDED' ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-amber-50 text-amber-700 border-amber-200') }}">
                                        {{ $selectedVerification->status }}
                                    </span>
                                @endif
                            </h3>
                            <p class="text-[11px] text-slate-500 font-mono mt-0.5">
                                Receipt: {{ $selectedVerification?->retailSale?->receipt_no ?? 'N/A' }} &bull; {{ $selectedVerification?->branch?->branch_name }}
                            </p>
                        </div>
                    </div>
                    <button type="button" wire:click="closeDetailModal" class="text-slate-400 hover:text-slate-600 transition cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Body -->
                <div class="p-6 space-y-4" style="flex: 1 1 auto; min-height: 0; overflow-y: auto;">
                    <!-- General Details Grid -->
                    <div class="p-3.5 bg-slate-50 border border-slate-200/80 rounded-xl grid grid-cols-2 gap-3 text-xs">
                        <div>
                            <span class="text-slate-400 block text-[10px] uppercase font-bold tracking-wider">Civil ID / Resident Card</span>
                            <span class="font-mono font-bold text-slate-900 block mt-0.5">
                                @if($canViewFullCivilId)
                                    {{ $selectedVerification?->civil_id }}
                                @else
                                    {{ $selectedVerification?->masked_civil_id }}
                                @endif
                            </span>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[10px] uppercase font-bold tracking-wider">Holder Name</span>
                            <span class="font-semibold text-slate-900 block mt-0.5">{{ $selectedVerification?->holder_name }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[10px] uppercase font-bold tracking-wider">Permit Number</span>
                            <span class="font-mono text-slate-700 block mt-0.5">{{ $selectedVerification?->permit_no ?? '-' }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[10px] uppercase font-bold tracking-wider">Verified At</span>
                            <span class="font-mono text-slate-700 block mt-0.5">{{ $selectedVerification?->verified_at?->format('d M Y, H:i:s') ?? '-' }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[10px] uppercase font-bold tracking-wider">Validity Window</span>
                            <span class="font-mono text-slate-700 block mt-0.5">
                                {{ $selectedVerification?->valid_from?->format('d M Y') ?? 'N/A' }} &rarr; {{ $selectedVerification?->valid_to?->format('d M Y') ?? 'N/A' }}
                            </span>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[10px] uppercase font-bold tracking-wider">Cashier / POS Counter</span>
                            <span class="font-semibold text-slate-900 block mt-0.5">{{ $selectedVerification?->enteredBy?->full_name ?? '-' }}</span>
                            <span class="text-[11px] font-mono text-slate-400">{{ $selectedVerification?->counter?->counter_code }}</span>
                        </div>
                    </div>

                    <!-- Quota & Sale Financial Summary -->
                    <div class="border border-slate-200 rounded-xl p-4 space-y-2.5">
                        <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Quota & Verification Amounts</h4>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs pt-1">
                            <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-100">
                                <span class="text-slate-500 block text-[10px] font-medium">Annual Limit</span>
                                <span class="font-mono font-bold text-slate-800 text-sm mt-0.5 block">
                                    {{ $selectedVerification?->annual_limit !== null ? 'OMR ' . number_format($selectedVerification->annual_limit, 3) : '-' }}
                                </span>
                            </div>
                            <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-100">
                                <span class="text-slate-500 block text-[10px] font-medium">Used Quota</span>
                                <span class="font-mono font-bold text-slate-800 text-sm mt-0.5 block">
                                    {{ $selectedVerification?->used_amount !== null ? 'OMR ' . number_format($selectedVerification->used_amount, 3) : '-' }}
                                </span>
                            </div>
                            <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-100">
                                <span class="text-slate-500 block text-[10px] font-medium">Remaining</span>
                                <span class="font-mono font-bold text-emerald-700 text-sm mt-0.5 block">
                                    {{ $selectedVerification?->remaining_amount !== null ? 'OMR ' . number_format($selectedVerification->remaining_amount, 3) : '-' }}
                                </span>
                            </div>
                            <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-100">
                                <span class="text-slate-500 block text-[10px] font-medium">Controlled Total</span>
                                <span class="font-mono font-bold text-rmsc-red text-sm mt-0.5 block">
                                    OMR {{ number_format($selectedVerification?->controlled_total ?? 0, 3) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Capture Method -->
                    <div class="border border-slate-200 rounded-xl p-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Verification Capture Method</h4>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-mono font-semibold border {{ $selectedVerification?->entry_method === 'SCREENSHOT' ? 'bg-purple-50 text-purple-700 border-purple-200' : 'bg-slate-100 text-slate-600 border-slate-200' }}">
                                {{ $selectedVerification?->entry_method === 'SCREENSHOT' ? 'SCREENSHOT OCR' : 'MANUAL ENTRY' }}
                            </span>
                        </div>

                        @if($selectedVerification?->entry_method === 'SCREENSHOT')
                            <div class="text-xs space-y-2">
                                <div class="flex items-center justify-between text-slate-600 bg-slate-50 p-2.5 rounded-lg border border-slate-100">
                                    <span>AI Extraction Confidence:</span>
                                    <strong class="font-mono font-bold {{ (float)($selectedVerification?->extraction_confidence ?? 0) >= 0.8 ? 'text-emerald-700' : 'text-amber-700' }}">
                                        {{ $selectedVerification?->extraction_confidence !== null ? round($selectedVerification->extraction_confidence * 100) . '%' : 'N/A' }}
                                    </strong>
                                </div>

                                @if(!empty($selectedVerification?->fields_edited_after_extraction))
                                    <div class="p-3 bg-amber-50/60 border border-amber-200/80 rounded-xl text-xs">
                                        <span class="font-bold text-amber-900 block text-[11px] uppercase">Fields Edited by Cashier Post-Extraction:</span>
                                        <p class="text-amber-800 mt-0.5">{{ implode(', ', (array) $selectedVerification->fields_edited_after_extraction) }}</p>
                                    </div>
                                @endif

                                @if($selectedVerification?->screenshot_path)
                                    <div class="pt-2 border-t border-slate-100">
                                        <span class="text-slate-500 block text-[10px] font-bold uppercase mb-1.5">Government Page Screenshot (Private Storage)</span>
                                        @if($canViewFullCivilId)
                                            <div class="flex items-center gap-3">
                                                <a href="{{ route('permits.screenshot', $selectedVerification->id) }}" target="_blank" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-xl transition inline-flex items-center gap-1.5">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                    <span>View Protected Screenshot</span>
                                                </a>
                                                <span class="text-[11px] text-slate-400">Streamed securely through policy authorization</span>
                                            </div>
                                        @else
                                            <div class="p-2 bg-slate-100 rounded-lg text-[11px] text-slate-500 italic">
                                                Screenshot image restricted to compliance supervisors and administrators.
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>

                    <!-- Attestation -->
                    <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-xs">
                        <span class="font-bold text-emerald-900 block text-[11px] uppercase">Cashier Legal Attestation:</span>
                        <p class="text-emerald-800 mt-0.5">"I have verified these details on the Oman government website."</p>
                        <p class="text-[11px] text-emerald-700 font-mono mt-1">
                            Attested by {{ $selectedVerification?->attestedBy?->full_name ?? $selectedVerification?->enteredBy?->full_name }}
                            at {{ $selectedVerification?->attested_at?->format('d M Y, H:i:s') ?? $selectedVerification?->verified_at?->format('d M Y, H:i:s') }}
                        </p>
                    </div>

                    <!-- Modal Actions -->
                    <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
                        <div>
                            @if($selectedVerification?->retailSale)
                                <a href="{{ route('pos.receipt', $selectedVerification->retailSale->id) }}" target="_blank" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-xl transition inline-flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                    <span>View Receipt</span>
                                </a>
                            @endif
                        </div>
                        <button type="button" wire:click="closeDetailModal" class="px-4 py-1.5 border border-slate-200 text-slate-700 font-semibold text-xs rounded-xl hover:bg-slate-50 transition cursor-pointer">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>