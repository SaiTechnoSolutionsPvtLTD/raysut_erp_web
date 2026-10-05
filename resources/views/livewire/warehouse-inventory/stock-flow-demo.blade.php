<div>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-purple-100 text-purple-800 border border-purple-200 uppercase">Phase 2 Exit Milestone</span>
                    <span class="text-xs font-semibold text-slate-500">Milestone Verification</span>
                </div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight mt-1">End-to-End Stock Flow Demonstration</h1>
                <p class="text-sm text-slate-500">Live Demonstration: Inbound Import ➔ 3 Duty-Free Bonded Warehouses ➔ Duty-Paid Conversion ➔ Main Warehouse Stock Ledger</p>
            </div>
            <div>
                <button wire:click="runEndToEndSimulation" wire:loading.attr="disabled" class="inline-flex items-center gap-2.5 px-5 py-3 bg-gradient-to-r from-rmsc-red via-red-600 to-rose-700 hover:from-red-700 hover:to-rose-800 text-white font-bold text-sm rounded-xl shadow-md transition transform hover:-translate-y-0.5 active:translate-y-0">
                    <svg wire:loading.remove class="w-5 h-5 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <svg wire:loading class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    <span>Run End-to-End Live Demonstration</span>
                </button>
            </div>
        </div>
    </x-slot>

    <div class="space-y-8">
        <!-- Metric Cards Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <!-- 3 Duty-Free Warehouses Card -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs relative overflow-hidden group hover:border-amber-400 transition">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Duty-Free Warehouses</p>
                        <h3 class="text-2xl font-extrabold text-slate-900 mt-1 font-mono">{{ $dutyFreeWarehousesCount }} Bonded Warehouses</h3>
                        <p class="text-xs text-amber-600 font-medium mt-1">Muscat, Salalah, Sohar</p>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-600 border border-amber-200 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </div>
                </div>
            </div>

            <!-- Bonded Stock Total Card -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs relative overflow-hidden group hover:border-sky-400 transition">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Bonded Duty-Free Stock</p>
                        <h3 class="text-2xl font-extrabold text-slate-900 mt-1 font-mono">{{ number_format($bondedStockTotal, 0) }} Units</h3>
                        <p class="text-xs text-sky-600 font-medium mt-1">Held in Customs Bond</p>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-sky-50 text-sky-600 border border-sky-200 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </div>
                </div>
            </div>

            <!-- Duty Paid Conversions Card -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs relative overflow-hidden group hover:border-emerald-400 transition">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Duty Conversions</p>
                        <h3 class="text-2xl font-extrabold text-slate-900 mt-1 font-mono">{{ $dutyPaidConversionsCount }} Executed</h3>
                        <p class="text-xs text-emerald-600 font-medium mt-1">OMR {{ number_format($dutyPaidTotalPaid, 2) }} Duty Paid</p>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-200 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                </div>
            </div>

            <!-- Main Warehouse Stock Card -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs relative overflow-hidden group hover:border-purple-400 transition">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Main Warehouse Stock</p>
                        <h3 class="text-2xl font-extrabold text-slate-900 mt-1 font-mono">{{ number_format($mainWarehouseStockTotal, 0) }} Units</h3>
                        <p class="text-xs text-purple-600 font-medium mt-1">Duty-Paid Cleared Inventory</p>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-purple-50 text-purple-600 border border-purple-200 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/></svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Interactive Pipeline Diagram -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs">
            <h3 class="text-lg font-bold text-slate-900 mb-2">Phase 2 Exit Milestone Pipeline Diagram</h3>
            <p class="text-xs text-slate-500 mb-6">Demonstrating end-to-end stock movement from Port of Entry to Duty-Free Warehouse, Customs Duty Payment, and Main Distribution Warehouse.</p>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 relative">
                <!-- Step 1 -->
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 relative flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-3">
                        <span class="w-7 h-7 rounded-full bg-slate-800 text-white font-bold text-xs flex items-center justify-center">1</span>
                        <span class="text-[10px] font-bold px-2 py-0.5 bg-slate-200 text-slate-700 rounded-md">IMPORT & PO</span>
                    </div>
                    <h4 class="font-bold text-slate-800 text-sm">Port Container Arrival</h4>
                    <p class="text-xs text-slate-500 mt-1">Supplier PO ➔ Inbound Container ➔ Bayan Customs Manifest</p>
                    <div class="mt-4 pt-3 border-t border-slate-200/80 text-[11px] font-mono text-slate-600">
                        Status: In Transit to Bond
                    </div>
                </div>

                <!-- Step 2 -->
                <div class="p-4 rounded-xl bg-amber-50/70 border border-amber-200 relative flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-3">
                        <span class="w-7 h-7 rounded-full bg-amber-600 text-white font-bold text-xs flex items-center justify-center">2</span>
                        <span class="text-[10px] font-bold px-2 py-0.5 bg-amber-200 text-amber-900 rounded-md">DUTY-FREE BONDED</span>
                    </div>
                    <h4 class="font-bold text-amber-950 text-sm">3 Duty-Free Warehouses</h4>
                    <p class="text-xs text-amber-800 mt-1">Muscat Central, Salalah Freezone, Sohar Port Terminals</p>
                    <div class="mt-4 pt-3 border-t border-amber-200 text-[11px] font-mono text-amber-900">
                        Batch: DUTY_FREE_BONDED
                    </div>
                </div>

                <!-- Step 3 -->
                <div class="p-4 rounded-xl bg-purple-50/70 border border-purple-200 relative flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-3">
                        <span class="w-7 h-7 rounded-full bg-purple-600 text-white font-bold text-xs flex items-center justify-center">3</span>
                        <span class="text-[10px] font-bold px-2 py-0.5 bg-purple-200 text-purple-900 rounded-md">DUTY CONVERSION</span>
                    </div>
                    <h4 class="font-bold text-purple-950 text-sm">Customs Duty Payment</h4>
                    <p class="text-xs text-purple-800 mt-1">ROP Customs 100% Duty Assessment ➔ Duty-Paid Conversion</p>
                    <div class="mt-4 pt-3 border-t border-purple-200 text-[11px] font-mono text-purple-900">
                        Bayan Clearing #
                    </div>
                </div>

                <!-- Step 4 -->
                <div class="p-4 rounded-xl bg-emerald-50/70 border border-emerald-200 relative flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-3">
                        <span class="w-7 h-7 rounded-full bg-emerald-600 text-white font-bold text-xs flex items-center justify-center">4</span>
                        <span class="text-[10px] font-bold px-2 py-0.5 bg-emerald-200 text-emerald-900 rounded-md">MAIN WAREHOUSE</span>
                    </div>
                    <h4 class="font-bold text-emerald-950 text-sm">Real-Time Inventory</h4>
                    <p class="text-xs text-emerald-800 mt-1">Muscat Main Distribution Center ➔ Available for Sales</p>
                    <div class="mt-4 pt-3 border-t border-emerald-200 text-[11px] font-mono text-emerald-900">
                        Batch: DUTY_PAID
                    </div>
                </div>
            </div>
        </div>

        <!-- Simulation Result Live Timeline (If run) -->
        @if (count($simulatedSteps) > 0)
            <div class="bg-slate-900 text-white rounded-2xl p-6 shadow-xl border border-slate-800 animate-in fade-in duration-300">
                <div class="flex items-center justify-between mb-6 border-b border-slate-800 pb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-3 h-3 rounded-full bg-emerald-500 animate-ping"></div>
                        <h3 class="text-base font-bold text-white">Live Stock Flow Demonstration Results</h3>
                    </div>
                    <span class="font-mono text-xs font-bold text-emerald-400 bg-emerald-950/60 px-3 py-1 rounded-full border border-emerald-800">
                        REF: {{ $lastSimulatedRef }}
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    @foreach ($simulatedSteps as $step)
                        <div class="p-4 bg-slate-800/80 rounded-xl border border-slate-700/80 flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-400 bg-emerald-950 px-2 py-0.5 rounded">Step {{ $step['step'] }}</span>
                                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                </div>
                                <h4 class="font-bold text-sm text-slate-100">{{ $step['title'] }}</h4>
                                <p class="text-xs text-slate-300 mt-1.5 leading-relaxed">{{ $step['desc'] }}</p>
                            </div>
                            <div class="mt-4 pt-2 border-t border-slate-700 text-[10px] font-mono text-slate-400 uppercase">
                                Verified Success
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Registered Warehouses Status Overview -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5">
            <h3 class="font-bold text-slate-800 text-base mb-4">Facility & Stock Distribution Overview</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach ($warehousesList as $wh)
                    <div class="p-4 rounded-xl border border-slate-200/80 bg-slate-50 flex items-center justify-between">
                        <div>
                            <span class="font-mono text-xs font-bold text-slate-500">{{ $wh->warehouse_code }}</span>
                            <h4 class="font-bold text-slate-900 text-sm mt-0.5">{{ $wh->warehouse_name }}</h4>
                            <span class="inline-block text-[11px] font-semibold mt-1 px-2 py-0.5 rounded-full {{ $wh->warehouse_type === 'DUTY_FREE_BONDED' ? 'bg-amber-100 text-amber-800' : 'bg-sky-100 text-sky-800' }}">
                                {{ $wh->warehouse_type === 'DUTY_FREE_BONDED' ? 'Duty-Free Bonded' : 'Main Warehouse' }}
                            </span>
                        </div>
                        <div class="text-right">
                            <span class="block text-lg font-mono font-bold text-slate-800">{{ $wh->stocks_count }}</span>
                            <span class="text-[10px] text-slate-400">Batches Held</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
