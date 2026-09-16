<div>
    <!-- Page Header -->
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="font-heading font-bold text-2xl text-slate-900 tracking-tight">RMSC Enterprise Core — System Dashboard</h1>
                <p class="text-xs text-slate-500 mt-1 font-medium">Operational overview — Raysut Marketing &amp; Supply Co. LLC</p>
            </div>
            <div class="flex items-center space-x-3">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs text-slate-600 font-medium shadow-2xs">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Live as of {{ now()->format('d M Y · H:i') }}</span>
                </div>
                <button wire:click="$refresh" class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold rounded-lg transition flex items-center space-x-1.5 border border-slate-200 shadow-2xs">
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    <span>Refresh</span>
                </button>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if (session('warning') || session('error'))
            <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-xs font-semibold flex items-center justify-between shadow-2xs">
                <div class="flex items-center space-x-2">
                    <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <span>{{ session('warning') ?? session('error') }}</span>
                </div>
            </div>
        @endif

        <!-- Top KPI Row (5 Stat Cards: Clean, Executive Layout) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            
            <!-- Stat 1: Total Stock Value -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-2xs flex flex-col justify-between hover:border-slate-300 transition">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Stock Value</span>
                    <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="font-heading font-extrabold text-xl text-slate-900 truncate" title="{{ $this->formatOMR($totalStockOnHandValue) }}">
                        {{ $this->formatOMR($totalStockOnHandValue) }}
                    </div>
                    <p class="text-[11px] text-slate-500 font-medium mt-1">Across all warehouses</p>
                </div>
            </div>

            <!-- Stat 2: Open Purchase Orders -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-2xs flex flex-col justify-between hover:border-slate-300 transition">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Open POs</span>
                    <div class="w-8 h-8 rounded-lg bg-sky-50 text-sky-600 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="font-heading font-extrabold text-2xl text-slate-900">
                        {{ $openPurchaseOrdersCount }}
                    </div>
                    <p class="text-[11px] text-slate-500 font-medium mt-1">Draft, Issued &amp; In-Transit</p>
                </div>
            </div>

            <!-- Stat 3: Shipments in Customs -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-2xs flex flex-col justify-between hover:border-slate-300 transition">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">In Customs</span>
                    <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/></svg>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="font-heading font-extrabold text-2xl text-slate-900">
                        {{ $shipmentsInCustomsCount }}
                    </div>
                    <p class="text-[11px] text-amber-700 font-medium mt-1">Bayan port clearance active</p>
                </div>
            </div>

            <!-- Stat 4: Pending Discrepancies -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-2xs flex flex-col justify-between hover:border-slate-300 transition">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">GRN Discrepancies</span>
                    <div class="w-8 h-8 rounded-lg bg-rose-50 text-rmsc-red flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="font-heading font-extrabold text-2xl text-slate-900">
                        {{ $discrepancyGrnsCount }}
                    </div>
                    <p class="text-[11px] text-slate-500 font-medium mt-1">Physical intake variances</p>
                </div>
            </div>

            <!-- Stat 5: Active Users -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-2xs flex flex-col justify-between hover:border-slate-300 transition">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Active Users</span>
                    <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="font-heading font-extrabold text-2xl text-slate-900">
                        {{ $activeUsersCount }}
                    </div>
                    <p class="text-[11px] text-slate-500 font-medium mt-1">Authorized RBAC accounts</p>
                </div>
            </div>

        </div>

        <!-- Main Structured Grid (2 Columns: Left ~2/3, Right ~1/3) -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Left Main Column (~2/3 width) -->
            <div class="lg:col-span-2 space-y-6">

                <!-- 1. Warehouse & Inventory Operations Panel -->
                @if(auth()->user()->hasPermission('Warehouse & Inventory', 'VIEW'))
                <div class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 rounded-lg bg-rmsc-red/10 text-rmsc-red flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            </div>
                            <div>
                                <h3 class="font-heading font-bold text-slate-900 text-base">Warehouse &amp; Inventory Operations</h3>
                                <p class="text-xs text-slate-500 font-medium">Live stock levels across bonded warehouses, main depots, and batch registries</p>
                            </div>
                        </div>
                        <a href="{{ route('warehouse-inventory.stock') }}" class="text-xs font-bold text-rmsc-red hover:underline flex items-center space-x-1">
                            <span>View all stock</span>
                            <span>→</span>
                        </a>
                    </div>

                    <div class="p-6 space-y-6">
                        
                        <!-- Core Facilities Snapshot Cards Grid -->
                        <div>
                            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-3">Core Facilities Snapshot</span>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                @foreach($warehouses as $wh)
                                    @php
                                        $whQty = $wh->stocks_sum_quantity_on_hand ?? 0;
                                        $pct = $totalWarehouseStockQty > 0 ? ($whQty / $totalWarehouseStockQty) * 100 : 0;
                                    @endphp
                                    <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 flex flex-col justify-between space-y-3 hover:bg-white hover:border-slate-300 transition">
                                        <div class="flex items-center justify-between">
                                            <span class="font-mono text-[10px] font-bold px-2 py-0.5 border border-slate-200 rounded text-slate-700 bg-white">{{ $wh->warehouse_code }}</span>
                                            @if($wh->warehouse_type === 'DUTY_FREE_BONDED')
                                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-sky-100 text-sky-800">Duty-Free Bonded</span>
                                            @else
                                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-slate-200 text-slate-700">Duty-Paid</span>
                                            @endif
                                        </div>
                                        <div>
                                            <h4 class="font-bold text-xs text-slate-900 truncate" title="{{ $wh->warehouse_name }}">{{ $wh->warehouse_name }}</h4>
                                            <div class="mt-2 flex items-baseline justify-between">
                                                <span class="text-xs text-slate-500 font-medium">On Hand Qty:</span>
                                                <span class="font-mono font-bold text-slate-900 text-sm">
                                                    {{ number_format($whQty, 2) }}
                                                </span>
                                            </div>
                                            <div class="w-full h-1.5 bg-slate-200 rounded-full overflow-hidden mt-2">
                                                <div class="h-full bg-rmsc-red rounded-full" style="width: {{ min(100, max(5, $pct)) }}%"></div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Products Nearing Expiry -->
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Products Nearing Expiry (Next 30–60 Days)</span>
                                <a href="{{ route('warehouse-inventory.batches') }}" class="text-xs text-rmsc-red hover:underline font-semibold">Batch Registry →</a>
                            </div>
                            <div class="border border-slate-200 rounded-xl overflow-hidden">
                                <table class="w-full text-left border-collapse text-xs">
                                    <thead class="bg-slate-50/80 border-b border-slate-200 text-slate-500 font-semibold">
                                        <tr>
                                            <th class="py-2.5 px-4">Batch Number</th>
                                            <th class="py-2.5 px-4">Product Name</th>
                                            <th class="py-2.5 px-4">Expiry Date</th>
                                            <th class="py-2.5 px-4">Duty Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 text-slate-700">
                                        @forelse($expiringBatches as $b)
                                            <tr class="hover:bg-slate-50/80 transition">
                                                <td class="py-2.5 px-4 font-mono font-bold text-rmsc-red">{{ $b->batch_number }}</td>
                                                <td class="py-2.5 px-4 font-semibold text-slate-900">{{ $b->product?->product_name }}</td>
                                                <td class="py-2.5 px-4 font-mono font-semibold text-slate-800">{{ $b->expiry_date?->format('Y-m-d') }}</td>
                                                <td class="py-2.5 px-4">
                                                    @if($b->duty_status === 'DUTY_FREE_BONDED')
                                                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-sky-100 text-sky-800">Bonded</span>
                                                    @else
                                                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-emerald-100 text-emerald-800">Duty Paid</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="py-4 text-center text-slate-400 font-medium">No expiring stock batches recorded.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Recent Stock Movements -->
                        <div>
                            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-3">Recent Stock Movements &amp; Ledger Status</span>
                            <div class="border border-slate-200 rounded-xl overflow-hidden">
                                <table class="w-full text-left border-collapse text-xs">
                                    <thead class="bg-slate-50/80 border-b border-slate-200 text-slate-500 font-semibold">
                                        <tr>
                                            <th class="py-2.5 px-4">Product SKU</th>
                                            <th class="py-2.5 px-4">Warehouse</th>
                                            <th class="py-2.5 px-4 text-right">On Hand</th>
                                            <th class="py-2.5 px-4 text-right">Available</th>
                                            <th class="py-2.5 px-4">Last Updated</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 text-slate-700">
                                        @forelse($recentStockMovements as $stk)
                                            <tr class="hover:bg-slate-50/80 transition">
                                                <td class="py-2.5 px-4 font-mono font-bold text-slate-900">{{ $stk->product?->sku_code }}</td>
                                                <td class="py-2.5 px-4 font-medium text-slate-800">{{ $stk->warehouse?->warehouse_name }}</td>
                                                <td class="py-2.5 px-4 text-right font-mono font-bold text-slate-900">{{ number_format($stk->quantity_on_hand, 2) }}</td>
                                                <td class="py-2.5 px-4 text-right font-mono font-bold text-emerald-600">{{ number_format($stk->quantity_available, 2) }}</td>
                                                <td class="py-2.5 px-4 font-mono text-[11px] text-slate-500">{{ $stk->last_updated_at?->format('Y-m-d H:i') ?? '-' }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="py-4 text-center text-slate-400 font-medium">No stock ledger entries recorded.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>
                </div>
                @endif

                <!-- 2. Purchase & Import Operations Panel -->
                @if(auth()->user()->hasPermission('Purchase & Import', 'VIEW'))
                <div class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 rounded-lg bg-sky-50 text-sky-600 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/></svg>
                            </div>
                            <div>
                                <h3 class="font-heading font-bold text-slate-900 text-base">Purchase &amp; Import Operations</h3>
                                <p class="text-xs text-slate-500 font-medium">Overseas PO pipeline, Bayan customs status, and landed cost assessments</p>
                            </div>
                        </div>
                        <a href="{{ route('purchase-import.shipments') }}" class="text-xs font-bold text-rmsc-red hover:underline flex items-center space-x-1">
                            <span>View shipments</span>
                            <span>→</span>
                        </a>
                    </div>

                    <div class="p-6 space-y-6">
                        
                        <!-- PO Pipeline Status Pills -->
                        <div>
                            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-3">Purchase Orders Pipeline</span>
                            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                                @foreach($poStatusCounts as $status => $count)
                                    <div class="p-3 bg-slate-50/60 rounded-xl border border-slate-200 text-center flex flex-col justify-between">
                                        <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wide">{{ $status }}</span>
                                        <span class="font-heading font-extrabold text-lg text-slate-900 mt-1">{{ $count }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Shipments in Transit Table -->
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Shipments in Transit &amp; Customs</span>
                                <a href="{{ route('purchase-import.shipments') }}" class="text-xs text-rmsc-red hover:underline font-semibold">Manage Shipments →</a>
                            </div>
                            <div class="border border-slate-200 rounded-xl overflow-hidden">
                                <table class="w-full text-left border-collapse text-xs">
                                    <thead class="bg-slate-50/80 border-b border-slate-200 text-slate-500 font-semibold">
                                        <tr>
                                            <th class="py-2.5 px-4">Shipment Ref</th>
                                            <th class="py-2.5 px-4">Bayan Declaration</th>
                                            <th class="py-2.5 px-4">Port of Entry</th>
                                            <th class="py-2.5 px-4">Clearance Stage</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 text-slate-700">
                                        @forelse($shipmentsInTransit as $s)
                                            <tr class="hover:bg-slate-50/80 transition">
                                                <td class="py-2.5 px-4 font-mono font-bold text-slate-900">{{ $s->shipment_ref }}</td>
                                                <td class="py-2.5 px-4 font-mono font-semibold text-slate-700">{{ $s->bayan_declaration_no ?? '-' }}</td>
                                                <td class="py-2.5 px-4 font-medium text-slate-700">{{ $s->port_of_entry ?? '-' }}</td>
                                                <td class="py-2.5 px-4">
                                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-amber-100 text-amber-800">{{ $s->stage }}</span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="py-4 text-center text-slate-400 font-medium">No shipments currently in transit.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Latest Duty Assessments -->
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Latest Customs Duty &amp; Landed Costs</span>
                                <a href="{{ route('purchase-import.duty-assessments') }}" class="text-xs text-rmsc-red hover:underline font-semibold">Duty Assessments →</a>
                            </div>
                            <div class="border border-slate-200 rounded-xl overflow-hidden">
                                <table class="w-full text-left border-collapse text-xs">
                                    <thead class="bg-slate-50/80 border-b border-slate-200 text-slate-500 font-semibold">
                                        <tr>
                                            <th class="py-2.5 px-4">Assessment Ref</th>
                                            <th class="py-2.5 px-4 text-right">CIF Value (OMR)</th>
                                            <th class="py-2.5 px-4 text-right">Duty Amount (OMR)</th>
                                            <th class="py-2.5 px-4 text-right">Total Landed Cost (OMR)</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 text-slate-700">
                                        @forelse($latestDutyAssessments as $da)
                                            <tr class="hover:bg-slate-50/80 transition">
                                                <td class="py-2.5 px-4 font-mono font-bold text-slate-900">#ASSESS-{{ str_pad($da->assessment_id, 4, '0', STR_PAD_LEFT) }}</td>
                                                <td class="py-2.5 px-4 text-right font-mono font-medium text-slate-600">{{ number_format($da->cif_value, 3) }}</td>
                                                <td class="py-2.5 px-4 text-right font-mono font-bold text-slate-900">{{ number_format($da->duty_amount, 3) }}</td>
                                                <td class="py-2.5 px-4 text-right font-mono font-bold text-emerald-600">{{ number_format($da->total_landed_cost, 3) }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="py-4 text-center text-slate-400 font-medium">No duty assessments recorded.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>
                </div>
                @endif

            </div>

            <!-- Right Companion Column (~1/3 width) -->
            <div class="space-y-6">

                <!-- 3. Master Data Panel -->
                @if(auth()->user()->hasPermission('Master Data', 'VIEW'))
                <div class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                            </div>
                            <div>
                                <h3 class="font-heading font-bold text-slate-900 text-base">Master Data Summary</h3>
                                <p class="text-xs text-slate-500 font-medium">Products, credit terms, and top suppliers</p>
                            </div>
                        </div>
                        <a href="{{ route('master-data.products') }}" class="text-xs font-bold text-rmsc-red hover:underline">View →</a>
                    </div>

                    <div class="p-6 space-y-6">
                        
                        <!-- Mini Stat Cards Grid -->
                        <div class="grid grid-cols-3 divide-x divide-slate-200 border border-slate-200 rounded-xl overflow-hidden bg-slate-50/50">
                            <div class="p-3 text-center">
                                <span class="text-[10px] font-bold text-slate-400 uppercase block">Products</span>
                                <span class="font-heading font-extrabold text-xl text-slate-900 mt-1 block">{{ $totalProducts }}</span>
                            </div>
                            <div class="p-3 text-center">
                                <span class="text-[10px] font-bold text-slate-400 uppercase block">Suppliers</span>
                                <span class="font-heading font-extrabold text-xl text-slate-900 mt-1 block">{{ $totalSuppliers }}</span>
                            </div>
                            <div class="p-3 text-center">
                                <span class="text-[10px] font-bold text-slate-400 uppercase block">Customers</span>
                                <span class="font-heading font-extrabold text-xl text-slate-900 mt-1 block">{{ $totalCustomers }}</span>
                            </div>
                        </div>

                        <!-- Customer Accounts & Credit Limits -->
                        <div>
                            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-3">Customer Accounts &amp; Credit Limits</span>
                            <div class="space-y-3">
                                @forelse($customerCreditAlerts as $ct)
                                    @php
                                        $limit = max(1, (float) $ct->credit_limit);
                                        $out = (float) $ct->outstanding_balance;
                                        $utilPct = min(100, max(0, ($out / $limit) * 100));
                                    @endphp
                                    <div class="p-3.5 bg-slate-50/80 rounded-xl border border-slate-200 text-xs space-y-2">
                                        <div class="flex items-center justify-between">
                                            <span class="font-bold text-slate-900 truncate max-w-[170px]" title="{{ $ct->customer?->customer_name }}">{{ $ct->customer?->customer_name }}</span>
                                            <span class="font-mono text-[10px] font-bold px-2 py-0.5 border border-slate-200 rounded text-slate-700 bg-white">{{ $ct->customer?->customer_segment }}</span>
                                        </div>
                                        <div class="flex items-center justify-between font-mono text-slate-600 font-medium">
                                            <span>Out: <strong class="text-amber-600">{{ number_format($out, 3) }}</strong></span>
                                            <span>Limit: <strong class="text-slate-900">{{ number_format($limit, 3) }}</strong></span>
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-xs text-slate-400 py-2 text-center font-medium">No customer credit limits available.</div>
                                @endforelse
                            </div>
                        </div>

                        <!-- Top Suppliers List -->
                        <div>
                            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-3">Top Suppliers By Orders</span>
                            <div class="space-y-2">
                                @forelse($topSuppliers as $sup)
                                    <div class="flex items-center justify-between p-3 rounded-xl border border-slate-200 bg-white text-xs hover:border-slate-300 transition">
                                        <div>
                                            <div class="font-bold text-slate-900">{{ $sup->supplier_name }}</div>
                                            <div class="flex items-center gap-2 mt-0.5">
                                                <span class="font-mono text-[10px] text-slate-500 font-medium">{{ $sup->country }}</span>
                                                <span class="font-mono text-[10px] px-1.5 py-0.2 border border-slate-200 rounded text-slate-600 bg-slate-50 font-semibold">{{ $sup->currency_code }}</span>
                                            </div>
                                        </div>
                                        <span class="font-mono text-[11px] font-bold px-2.5 py-1 rounded-lg bg-slate-100 border border-slate-200 text-slate-800">
                                            {{ $sup->purchase_orders_count }} POs
                                        </span>
                                    </div>
                                @empty
                                    <div class="text-xs text-slate-400 py-2 text-center font-medium">No suppliers recorded.</div>
                                @endforelse
                            </div>
                        </div>

                    </div>
                </div>
                @endif

                <!-- 4. System & Security Panel -->
                @if(auth()->user()->hasPermission('System, Security & Setup', 'VIEW'))
                <div class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 rounded-lg bg-slate-100 text-slate-700 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                            </div>
                            <div>
                                <h3 class="font-heading font-bold text-slate-900 text-base">System &amp; Audit Security</h3>
                                <p class="text-xs text-slate-500 font-medium">Tamper-proof log trail &amp; user security</p>
                            </div>
                        </div>
                        <a href="{{ route('system.audit-logs') }}" class="text-xs font-bold text-rmsc-red hover:underline">Full log →</a>
                    </div>

                    <div class="p-6 space-y-4">
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Latest System Audit Entries</span>
                        <div class="space-y-2.5">
                            @forelse($latestAuditLogs as $log)
                                <div class="p-3 bg-slate-50/80 rounded-xl border border-slate-200 text-xs">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="font-bold text-slate-900">{{ $log->user?->full_name ?? 'System' }}</span>
                                        @if($log->action === 'CREATE')
                                            <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-emerald-100 text-emerald-800">Create</span>
                                        @elseif($log->action === 'UPDATE')
                                            <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-amber-100 text-amber-800">Update</span>
                                        @else
                                            <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-rose-100 text-rmsc-red">Delete</span>
                                        @endif
                                    </div>
                                    <div class="flex items-center justify-between font-mono text-[11px] text-slate-500 mt-1">
                                        <span>Table: <strong class="text-slate-800">{{ $log->record_table }}</strong></span>
                                        <span>{{ $log->created_at->diffForHumans() }}</span>
                                    </div>
                                </div>
                            @empty
                                <div class="text-xs text-slate-400 py-2 text-center font-medium">No audit logs recorded yet.</div>
                            @endforelse
                        </div>

                        <!-- Quick System Links -->
                        <div class="pt-3 border-t border-slate-200 grid grid-cols-2 gap-2">
                            <a href="{{ route('system.users') }}" class="p-2.5 bg-slate-50 hover:bg-slate-100 rounded-xl border border-slate-200 text-center text-xs font-semibold text-slate-700 transition">
                                Users &amp; RBAC
                            </a>
                            <a href="{{ route('system.company') }}" class="p-2.5 bg-slate-50 hover:bg-slate-100 rounded-xl border border-slate-200 text-center text-xs font-semibold text-slate-700 transition">
                                Company setup
                            </a>
                        </div>
                    </div>
                </div>
                @endif

            </div>
        </div>
    </div>
</div>