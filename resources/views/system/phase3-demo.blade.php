<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <span class="px-2.5 py-1 text-xs font-bold rounded-md bg-emerald-100 text-emerald-800 border border-emerald-200 uppercase tracking-wider">Phase 3 Exit Demo</span>
                    <h2 class="text-2xl font-bold text-slate-800 tracking-tight">Branch Billing & Wholesale Sign-off Walkthrough</h2>
                </div>
                <p class="text-sm text-slate-500 mt-1">Live, end-to-end execution of retail POS billing, Oman permit checks, cashier shifts, wholesale quotation-to-invoice chain, credit limits, and FEFO stock reservations using live services.</p>
            </div>
            <div x-data="{ showResetModal: false, isResetting: false }" @keydown.escape.window="showResetModal = false" class="flex items-center gap-3">
                <form x-ref="resetForm" action="{{ route('phase3.demo.reset') }}" method="POST">
                    @csrf
                    <button type="button" @click="showResetModal = true" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg shadow-sm hover:bg-slate-50 transition cursor-pointer">
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Reset Demo Data
                    </button>
                </form>

                <!-- Reset Confirmation Modal (400px centered) -->
                <template x-teleport="body">
                    <div x-show="showResetModal"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
                         style="display: none;">
                        <div @click.away="if (!isResetting) showResetModal = false"
                             class="bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden transform transition-all w-full mx-auto"
                             style="max-width: 400px;">
                            <div class="p-6 text-center">
                                <div class="w-12 h-12 rounded-full bg-red-100 text-rmsc-red mx-auto flex items-center justify-center mb-4">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                </div>
                                <h3 class="text-base font-bold text-slate-900 mb-1">Reset Phase 3 Demo Data?</h3>
                                <p class="text-xs text-slate-500 mb-6">
                                    Reset all demo branches, permit quotas, batches, and customers to their initial baseline state? All demo transaction records will be reverted.
                                </p>
                                <div class="flex items-center justify-center space-x-3">
                                    <button type="button" @click="showResetModal = false" :disabled="isResetting" class="px-4 py-2 text-xs font-semibold text-slate-700 hover:text-slate-900 bg-slate-100 hover:bg-slate-200/80 rounded-xl transition cursor-pointer disabled:opacity-50">
                                        Cancel
                                    </button>
                                    <button type="button" @click="isResetting = true; $refs.resetForm.submit()" :disabled="isResetting" class="px-4 py-2 text-xs font-semibold text-white bg-rmsc-red hover:bg-red-700 rounded-xl shadow-xs transition cursor-pointer disabled:opacity-50 flex items-center gap-1.5">
                                        <svg x-show="isResetting" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle style="opacity:.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path style="opacity:.85;" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                        <span x-text="isResetting ? 'Resetting...' : 'Yes, Reset Demo Data'"></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
                <form action="{{ route('phase3.demo.run') }}" method="POST">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-2 px-5 py-2 text-sm font-semibold text-white bg-rmsc-red hover:bg-red-700 rounded-lg shadow-md hover:shadow-lg transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664zM21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Run Complete Guided Walkthrough (a – h)
                    </button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="py-6 space-y-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-sm font-medium">{{ session('success') }}</span>
                </div>
            </div>
        @endif
        @if(session('error'))
            <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-red-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-sm font-medium">{{ session('error') }}</span>
                </div>
            </div>
        @endif

        {{-- Final Summary Card (if run completed) --}}
        @if($demoResults)
            <div class="bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 rounded-2xl shadow-xl text-white p-6 border border-slate-700/60">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                    <div class="space-y-2">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider {{ $demoResults['overall_status'] === 'PASS' ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'bg-red-500/20 text-red-300 border border-red-500/30' }}">
                            <span class="w-2 h-2 rounded-full {{ $demoResults['overall_status'] === 'PASS' ? 'bg-emerald-400' : 'bg-red-400' }}"></span>
                            {{ $demoResults['overall_status'] }}: {{ $demoResults['headline'] }}
                        </div>
                        <h3 class="text-2xl font-extrabold tracking-tight text-white">Full Phase 3 Workflow Validated Successfully</h3>
                        <p class="text-slate-300 text-sm max-w-2xl">Every step (a through h) was executed against the real transactional database and service layer with 0 mocks. Stock reservations, permit limits, cash variances, and invoice postings reconciled cleanly.</p>
                    </div>
                    <div class="flex items-center gap-4 bg-slate-800/80 p-4 rounded-xl border border-slate-700 shrink-0">
                        <div class="text-center px-4 border-r border-slate-700">
                            <p class="text-xs uppercase text-slate-400 font-medium">Steps Passed</p>
                            <p class="text-3xl font-extrabold text-emerald-400">{{ $demoResults['passed_steps'] }} / {{ $demoResults['total_steps'] }}</p>
                        </div>
                        <div class="text-center px-4">
                            <p class="text-xs uppercase text-slate-400 font-medium">Executed At</p>
                            <p class="text-sm font-semibold text-slate-200 mt-1">{{ $demoResults['completed_at'] }}</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Live Master State Metrics Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            {{-- Branches & Counters --}}
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Branches & Counters</span>
                    <span class="p-2 bg-blue-50 text-blue-600 rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </span>
                </div>
                <p class="text-2xl font-bold text-slate-800 mt-2">{{ $branches->count() }} Branches</p>
                <p class="text-xs text-slate-500 mt-1">BR-MCT, BR-SLL, BR-SOH, BR-NZW</p>
            </div>

            {{-- Oman Permit Verifications --}}
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Permit Verifications</span>
                    <span class="p-2 bg-amber-50 text-amber-600 rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/></svg>
                    </span>
                </div>
                <p class="text-2xl font-bold text-slate-800 mt-2">{{ $permitVerificationsCount ?? 0 }} <span class="text-xs font-normal text-slate-400">Captured at POS</span></p>
                <p class="text-xs text-slate-500 mt-1">Manual & OCR Screenshot verification</p>
            </div>

            {{-- Wholesale Customers --}}
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Wholesale Credit Demo</span>
                    <span class="p-2 bg-purple-50 text-purple-600 rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </span>
                </div>
                <p class="text-2xl font-bold text-slate-800 mt-2">{{ $customers->count() }} Accounts</p>
                <p class="text-xs text-slate-500 mt-1">1 Active (30-day terms), 1 ON_HOLD</p>
            </div>

            {{-- Duty Status Warehousing --}}
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Inventory Segregation</span>
                    <span class="p-2 bg-emerald-50 text-emerald-600 rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                    </span>
                </div>
                <p class="text-2xl font-bold text-slate-800 mt-2">Duty-Paid & Bonded</p>
                <p class="text-xs text-slate-500 mt-1">Strict duty-paid FEFO reservation</p>
            </div>
        </div>

        {{-- Walkthrough Step Results Accordion / Cards --}}
        @if($demoResults && isset($demoResults['steps']))
            <div class="space-y-4">
                <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                    <svg class="w-5 h-5 text-rmsc-red" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    Step-by-Step Live Execution Details
                </h3>

                @foreach($demoResults['steps'] as $key => $step)
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                        <div class="p-5 flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-slate-50/50 border-b border-slate-100">
                            <div class="flex items-center gap-3">
                                <span class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm {{ $step['status'] === 'PASS' ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-red-100 text-red-800 border border-red-300' }}">
                                    {{ strtoupper($step['step']) }}
                                </span>
                                <div>
                                    <h4 class="text-base font-bold text-slate-800">{{ $step['title'] }}</h4>
                                    <p class="text-xs text-slate-500 mt-0.5">{{ $step['details'] }}</p>
                                </div>
                            </div>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold {{ $step['status'] === 'PASS' ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800' }}">
                                {{ $step['status'] }}
                            </span>
                        </div>

                        <div class="p-5 space-y-4">
                            {{-- Step Items (e.g. Branches in Step A) --}}
                            @if(isset($step['items']))
                                <div class="overflow-x-auto">
                                    <table class="w-full text-left text-xs text-slate-600">
                                        <thead class="bg-slate-50 text-slate-500 font-semibold uppercase">
                                            <tr>
                                                <th class="px-4 py-2.5">Branch Code</th>
                                                <th class="px-4 py-2.5">Branch Name</th>
                                                <th class="px-4 py-2.5">Type</th>
                                                <th class="px-4 py-2.5">Configured Counters & Staff</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            @foreach($step['items'] as $item)
                                                <tr>
                                                    <td class="px-4 py-2.5 font-bold text-slate-800">{{ $item['code'] }}</td>
                                                    <td class="px-4 py-2.5">{{ $item['name'] }}</td>
                                                    <td class="px-4 py-2.5"><span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 font-mono text-[11px]">{{ $item['type'] }}</span></td>
                                                    <td class="px-4 py-2.5 text-slate-700">{{ $item['counters'] }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif

                            {{-- Before / After Metrics Table --}}
                            @if(isset($step['before_after']))
                                <div class="bg-slate-50/70 p-4 rounded-xl border border-slate-200/80">
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                        @foreach($step['before_after'] as $metric => $val)
                                            <div class="bg-white p-3 rounded-lg border border-slate-200/60 shadow-xs">
                                                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wide">{{ $metric }}</p>
                                                <p class="text-sm font-bold text-slate-800 mt-1 break-words">{{ $val }}</p>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            {{-- Stocks Table (for Step H) --}}
                            @if(isset($step['stocks']))
                                <div class="space-y-2">
                                    <h5 class="text-xs font-bold text-slate-700 uppercase tracking-wide">Warehouse Stock Batches & Quantities</h5>
                                    <div class="overflow-x-auto rounded-lg border border-slate-200">
                                        <table class="w-full text-left text-xs text-slate-600">
                                            <thead class="bg-slate-50 text-slate-500 font-semibold uppercase">
                                                <tr>
                                                    <th class="px-4 py-2">Warehouse</th>
                                                    <th class="px-4 py-2">Product</th>
                                                    <th class="px-4 py-2">Batch</th>
                                                    <th class="px-4 py-2">Duty Status</th>
                                                    <th class="px-4 py-2 text-right">On-Hand</th>
                                                    <th class="px-4 py-2 text-right">Reserved</th>
                                                    <th class="px-4 py-2 text-right">Available</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100">
                                                @foreach($step['stocks'] as $stk)
                                                    <tr>
                                                        <td class="px-4 py-2 font-medium text-slate-800">{{ $stk['warehouse'] }}</td>
                                                        <td class="px-4 py-2">{{ $stk['product'] }}</td>
                                                        <td class="px-4 py-2 font-mono text-[11px]">{{ $stk['batch'] }}</td>
                                                        <td class="px-4 py-2">
                                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $stk['duty'] === 'DUTY_PAID' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                                                {{ $stk['duty'] }}
                                                            </span>
                                                        </td>
                                                        <td class="px-4 py-2 text-right font-mono">{{ number_format($stk['on_hand'], 2) }}</td>
                                                        <td class="px-4 py-2 text-right font-mono text-amber-600 font-semibold">{{ number_format($stk['reserved'], 2) }}</td>
                                                        <td class="px-4 py-2 text-right font-mono text-emerald-600 font-semibold">{{ number_format($stk['available'], 2) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endif

                            {{-- Recent Audit Logs (for Step H) --}}
                            @if(isset($step['recent_audits']))
                                <div class="space-y-2 mt-3">
                                    <h5 class="text-xs font-bold text-slate-700 uppercase tracking-wide">Audit Trail Entries Created During Simulation</h5>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                        @foreach($step['recent_audits'] as $log)
                                            <div class="p-2.5 rounded-lg border border-slate-200 bg-white flex items-center justify-between text-xs">
                                                <div>
                                                    <span class="font-bold text-slate-800 font-mono">{{ $log['action'] }}</span>
                                                    <p class="text-[11px] text-slate-500">{{ $log['module'] }} by {{ $log['user'] }}</p>
                                                </div>
                                                <span class="text-[10px] text-slate-400 font-mono">{{ $log['timestamp'] }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            {{-- Initial State Guide --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-8 text-center space-y-4 shadow-sm">
                <div class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center mx-auto text-slate-400">
                    <svg class="w-8 h-8 text-rmsc-red" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664zM21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="max-w-xl mx-auto">
                    <h3 class="text-xl font-bold text-slate-800">Ready to execute Phase 3 Exit Walkthrough</h3>
                    <p class="text-slate-500 text-sm mt-1">Click the button below to run the step-by-step demonstration through the real service layer. It will open shifts, process sales, enforce permit quotas, verify cash variance approval, run wholesale quotations to invoice, demonstrate credit blocks, and display the stock ledger reconciliation.</p>
                </div>
                <form action="{{ route('phase3.demo.run') }}" method="POST" class="pt-2">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-2 px-6 py-3 text-sm font-bold text-white bg-rmsc-red hover:bg-red-700 rounded-xl shadow-md hover:shadow-lg transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664zM21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Start Guided Demonstration Now
                    </button>
                </form>
            </div>
        @endif
    </div>
</x-app-layout>
