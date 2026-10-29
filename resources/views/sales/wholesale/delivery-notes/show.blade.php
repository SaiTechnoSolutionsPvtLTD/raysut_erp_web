<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <a href="{{ route('wholesale.delivery-notes.index') }}" class="text-xs font-semibold text-slate-500 hover:text-rmsc-navy flex items-center gap-1 mb-1">
                    &larr; Back to Delivery Notes
                </a>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">{{ $deliveryNote->dn_no }}</h1>
                    @php
                        $dnBadges = [
                            'DRAFT' => 'bg-slate-100 text-slate-700 border-slate-200',
                            'DISPATCHED' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                            'CANCELLED' => 'bg-rose-50 text-rose-700 border-rose-200',
                        ];
                    @endphp
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold border {{ $dnBadges[$deliveryNote->status] ?? 'bg-slate-100 text-slate-700 border-slate-200' }}">
                        {{ $deliveryNote->status }}
                    </span>
                </div>
            </div>

            <!-- Action Controls -->
            <div class="flex flex-wrap items-center gap-2">
                @if(Route::has('wholesale.delivery-notes.print'))
                    <a href="{{ route('wholesale.delivery-notes.print', $deliveryNote) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold rounded-xl transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H7v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        Print Delivery Note
                    </a>
                @endif

                @if($deliveryNote->salesOrder && Route::has('wholesale.orders.show'))
                    <a href="{{ route('wholesale.orders.show', $deliveryNote->salesOrder) }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold rounded-xl transition">
                        View Order Ref &rarr;
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Read-Only Action Banner -->
        <div class="p-4 bg-blue-50 border border-blue-200/80 rounded-2xl flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs text-blue-900 shadow-xs">
            <div class="flex items-center gap-2.5">
                <svg class="w-5 h-5 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div>
                    <span class="font-bold">Read-Only Document View:</span> To dispatch stock or generate invoices, please <strong>use the action icons on the list</strong>.
                </div>
            </div>
            <a href="{{ route('wholesale.delivery-notes.index') }}" class="font-semibold text-blue-700 hover:text-blue-900 hover:underline shrink-0 flex items-center gap-1">
                Go to Delivery Notes List &rarr;
            </a>
        </div>

        <!-- 3 Info Cards: Consignee, Logistics, References -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <!-- Consignee Customer Card -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs space-y-2">
                <span class="text-xs font-semibold uppercase text-slate-400">Consignee Customer</span>
                <p class="text-sm font-bold text-slate-900">{{ $deliveryNote->salesOrder?->customer?->customer_name ?? 'N/A' }}</p>
                <p class="text-xs text-slate-500 font-mono">Code: {{ $deliveryNote->salesOrder?->customer?->customer_code ?? 'N/A' }}</p>
                <p class="text-xs text-slate-600">Phone: {{ $deliveryNote->salesOrder?->customer?->phone ?? 'N/A' }}</p>
            </div>

            <!-- Logistics & Dispatch Card -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs space-y-2">
                <span class="text-xs font-semibold uppercase text-slate-400">Logistics & Transportation</span>
                <p class="text-sm text-slate-700"><span class="font-semibold">Branch:</span> {{ $deliveryNote->branch?->branch_name ?? 'HQ' }}</p>
                <p class="text-sm text-slate-700"><span class="font-semibold">Warehouse:</span> {{ $deliveryNote->warehouse?->warehouse_name ?? 'Domestic Depot' }}</p>
                <p class="text-sm text-slate-700"><span class="font-semibold">Vehicle:</span> {{ $deliveryNote->vehicle_no ?? 'N/A' }}</p>
                <p class="text-sm text-slate-700"><span class="font-semibold">Driver:</span> {{ $deliveryNote->driver_name ?? 'N/A' }}</p>
                <p class="text-sm text-slate-700"><span class="font-semibold">Dispatched By:</span> {{ $deliveryNote->dispatcher?->full_name ?? 'System' }}</p>
            </div>

            <!-- References Card -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs space-y-2">
                <span class="text-xs font-semibold uppercase text-slate-400">Fulfillment & Links</span>
                <div class="text-sm text-slate-700">
                    <span class="font-semibold">Sales Order:</span>
                    @if($deliveryNote->salesOrder && Route::has('wholesale.orders.show'))
                        <a href="{{ route('wholesale.orders.show', $deliveryNote->salesOrder) }}" class="font-mono text-rmsc-navy font-bold hover:underline">
                            {{ $deliveryNote->salesOrder->so_no }}
                        </a>
                    @else
                        <span class="font-mono">{{ $deliveryNote->salesOrder?->so_no ?? 'N/A' }}</span>
                    @endif
                </div>
                <div class="text-sm text-slate-700">
                    <span class="font-semibold">Dispatched At:</span>
                    <span>{{ $deliveryNote->dispatched_at ? $deliveryNote->dispatched_at->format('d M Y H:i') : 'Draft' }}</span>
                </div>
                <div class="text-sm text-slate-700 pt-1">
                    <span class="font-semibold">Linked Invoice:</span>
                    @if($deliveryNote->invoice && Route::has('wholesale.invoices.show'))
                        <a href="{{ route('wholesale.invoices.show', $deliveryNote->invoice) }}" class="font-mono text-emerald-700 font-bold hover:underline">
                            {{ $deliveryNote->invoice->invoice_no }}
                        </a>
                    @elseif($deliveryNote->invoice)
                        <span class="font-mono text-emerald-700">{{ $deliveryNote->invoice->invoice_no }}</span>
                    @else
                        <span class="text-slate-400 italic">Not yet invoiced</span>
                    @endif
                </div>
            </div>
        </div>

        @if($deliveryNote->notes)
            <div class="bg-white rounded-2xl p-4 border border-slate-200/80 text-sm text-slate-600 shadow-xs">
                <span class="font-semibold text-slate-800">Dispatch Notes / Gate Pass:</span>
                <p class="mt-1 whitespace-pre-line">{{ $deliveryNote->notes }}</p>
            </div>
        @endif

        <!-- Dispatched Items Table -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="p-4 bg-slate-50 border-b border-slate-100">
                <h3 class="text-sm font-bold uppercase text-slate-700">Dispatched Items & Batches</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-50/50 text-xs font-bold uppercase text-slate-500 tracking-wider border-b border-slate-100">
                        <tr>
                            <th class="py-3 px-5">#</th>
                            <th class="py-3 px-5">Product Description</th>
                            <th class="py-3 px-5">Allocated Batch / Lot</th>
                            <th class="py-3 px-5">UOM</th>
                            <th class="py-3 px-5 text-right">Dispatched Quantity</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($deliveryNote->items as $i => $item)
                            <tr>
                                <td class="py-3 px-5 text-slate-400">{{ $i + 1 }}</td>
                                <td class="py-3 px-5">
                                    <span class="font-semibold text-slate-900">{{ $item->product?->product_name ?? 'N/A' }}</span>
                                    <span class="block text-xs font-mono text-slate-400">{{ $item->product?->sku_code ?? '' }}</span>
                                </td>
                                <td class="py-3 px-5 font-mono text-slate-700">
                                    {{ $item->batch?->batch_no ?? $item->batch?->batch_number ?? ($item->batch_id ? "#{$item->batch_id}" : 'N/A') }}
                                </td>
                                <td class="py-3 px-5 text-slate-600">{{ $item->product?->uom?->uom_name ?? 'Units' }}</td>
                                <td class="py-3 px-5 text-right font-mono font-bold text-slate-900">
                                    {{ number_format((float) ($item->quantity_dispatched ?? $item->quantity ?? 0), 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-6 text-center text-slate-400 text-xs">No items recorded for this delivery note.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Stock Movements (Immutable Ledger Traceability) -->
        @php
            $stockMovements = \App\Models\StockLedger::with(['warehouse', 'product', 'batch'])
                ->where(function($q) use ($deliveryNote) {
                    $q->where('reference_type', 'delivery_note')->where('reference_id', $deliveryNote->delivery_note_id)
                      ->orWhere('delivery_note_id', $deliveryNote->delivery_note_id);
                })
                ->orderBy('ledger_id', 'asc')
                ->get();
        @endphp
        @if($stockMovements && $stockMovements->isNotEmpty())
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="p-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="text-sm font-bold uppercase text-slate-700">Stock Movements ({{ $stockMovements->count() }})</h3>
                    <span class="text-xs text-slate-400 font-mono">Immutable Ledger Audit</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600">
                        <thead class="bg-slate-50/50 text-xs font-bold uppercase text-slate-500 tracking-wider border-b border-slate-100">
                            <tr>
                                <th class="py-3 px-5">Date/Time</th>
                                <th class="py-3 px-5">Warehouse</th>
                                <th class="py-3 px-5">Product Description</th>
                                <th class="py-3 px-5">Batch #</th>
                                <th class="py-3 px-5">Movement</th>
                                <th class="py-3 px-5 text-right">Quantity</th>
                                <th class="py-3 px-5 text-right">Balance After</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($stockMovements as $mov)
                                <tr>
                                    <td class="py-3 px-5 text-slate-700 font-mono text-xs">{{ $mov->created_at?->format('d M Y H:i') }}</td>
                                    <td class="py-3 px-5 text-slate-800 font-semibold">{{ $mov->warehouse?->warehouse_code ?? "WH-{$mov->warehouse_id}" }}</td>
                                    <td class="py-3 px-5 text-slate-900 font-medium">{{ $mov->product?->product_name ?? 'N/A' }}</td>
                                    <td class="py-3 px-5 font-mono text-xs text-slate-600">{{ $mov->batch?->batch_number ?? "B-{$mov->batch_id}" }}</td>
                                    <td class="py-3 px-5">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold {{ (float)$mov->quantity < 0 ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800' }}">
                                            {{ $mov->movement_type }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-5 text-right font-mono font-bold {{ (float)$mov->quantity < 0 ? 'text-amber-800' : 'text-emerald-700' }}">
                                        {{ (float)$mov->quantity > 0 ? '+' : '' }}{{ number_format((float)$mov->quantity, 2) }}
                                    </td>
                                    <td class="py-3 px-5 text-right font-mono text-slate-700 font-semibold">{{ number_format((float)$mov->balance_after, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>

