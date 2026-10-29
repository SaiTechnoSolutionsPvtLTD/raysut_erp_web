<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <a href="{{ route('wholesale.orders.index') }}" class="text-xs font-semibold text-slate-500 hover:text-rmsc-navy flex items-center gap-1 mb-1">
                    &larr; Back to Sales Orders
                </a>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">{{ $order->so_no }}</h1>
                    @php
                        $soBadges = [
                            'DRAFT' => 'bg-slate-100 text-slate-700 border-slate-200',
                            'CONFIRMED' => 'bg-blue-50 text-blue-700 border-blue-200',
                            'PARTIALLY_DISPATCHED' => 'bg-amber-50 text-amber-700 border-amber-200',
                            'DISPATCHED' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                            'INVOICED' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                            'CANCELLED' => 'bg-rose-50 text-rose-700 border-rose-200',
                        ];
                    @endphp
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold border {{ $soBadges[$order->status] ?? 'bg-slate-100 text-slate-700 border-slate-200' }}">
                        {{ str_replace('_', ' ', $order->status) }}
                    </span>
                </div>
            </div>

            <!-- Header Actions -->
            <div class="flex flex-wrap items-center gap-2">
                @if(Route::has('wholesale.orders.print'))
                    <a href="{{ route('wholesale.orders.print', $order) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold rounded-xl transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H7v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        Print Order
                    </a>
                @endif

                @if($order->quotation && Route::has('wholesale.quotations.show'))
                    <a href="{{ route('wholesale.quotations.show', $order->quotation) }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold rounded-xl transition">
                        View Quotation &rarr;
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
                    <span class="font-bold">Read-Only Document View:</span> To confirm, cancel, create delivery notes, or post invoices, please <strong>use the action icons on the list</strong>.
                </div>
            </div>
            <a href="{{ route('wholesale.orders.index') }}" class="font-semibold text-blue-700 hover:text-blue-900 hover:underline shrink-0 flex items-center gap-1">
                Go to Sales Orders List &rarr;
            </a>
        </div>

        <!-- 3 Info Cards: Customer, Facility & Terms, Financial Summary -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <!-- Customer Card -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs space-y-2">
                <span class="text-xs font-semibold uppercase text-slate-400">Customer Master</span>
                <p class="text-sm font-bold text-slate-900">{{ $order->customer?->customer_name ?? 'N/A' }}</p>
                <p class="text-xs text-slate-500 font-mono">Code: {{ $order->customer?->customer_code ?? 'N/A' }}</p>
                <p class="text-xs text-slate-600">Phone: {{ $order->customer?->phone ?? 'N/A' }}</p>
                <div class="pt-1 text-xs">
                    <span class="text-slate-500">Credit Limit:</span>
                    <span class="font-mono font-semibold text-slate-700">{{ number_format((float) ($order->customer?->getEffectiveCreditLimit() ?? 0), 3) }} OMR</span>
                </div>
            </div>

            <!-- Facility & Terms Card -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs space-y-2">
                <span class="text-xs font-semibold uppercase text-slate-400">Facility & Fulfillment</span>
                <p class="text-sm text-slate-700"><span class="font-semibold">Branch:</span> {{ $order->branch?->branch_name ?? 'HQ' }}</p>
                <p class="text-sm text-slate-700"><span class="font-semibold">Warehouse:</span> {{ $order->warehouse?->warehouse_name ?? 'N/A' }}</p>
                <p class="text-sm text-slate-700"><span class="font-semibold">Order Date:</span> {{ $order->order_date ? $order->order_date->format('d M Y') : 'N/A' }}</p>
                <p class="text-sm text-slate-700"><span class="font-semibold">Payment Terms:</span> {{ $order->payment_terms_days ?? 0 }} Days</p>
                <p class="text-sm text-slate-700"><span class="font-semibold">Created By:</span> {{ $order->creator?->full_name ?? 'System' }}</p>
            </div>

            <!-- Financial Summary Card -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs space-y-2">
                <span class="text-xs font-semibold uppercase text-slate-400">Financial Summary</span>
                <div class="flex justify-between text-sm text-slate-600">
                    <span>Subtotal:</span>
                    <span class="font-mono font-semibold">{{ number_format((float) ($order->subtotal ?? 0), 3) }} OMR</span>
                </div>
                <div class="flex justify-between text-sm text-slate-600">
                    <span>VAT Total:</span>
                    <span class="font-mono font-semibold">{{ number_format((float) ($order->vat_total ?? 0), 3) }} OMR</span>
                </div>
                <div class="flex justify-between text-base font-bold text-rmsc-navy border-t border-slate-100 pt-2">
                    <span>Grand Total:</span>
                    <span class="font-mono text-rmsc-red">{{ number_format((float) ($order->grand_total ?? 0), 3) }} OMR</span>
                </div>
            </div>
        </div>

        @if($order->notes)
            <div class="bg-white rounded-2xl p-4 border border-slate-200/80 text-sm text-slate-600 shadow-xs">
                <span class="font-semibold text-slate-800">Order Notes:</span>
                <p class="mt-1 whitespace-pre-line">{{ $order->notes }}</p>
            </div>
        @endif

        <!-- Order Items & Allocations Table -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="p-4 bg-slate-50 border-b border-slate-100">
                <h3 class="text-sm font-bold uppercase text-slate-700">Order Items & Inventory Reservations</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-50/50 text-xs font-bold uppercase text-slate-500 tracking-wider border-b border-slate-100">
                        <tr>
                            <th class="py-3 px-5">#</th>
                            <th class="py-3 px-5">Product Description</th>
                            <th class="py-3 px-5">UOM</th>
                            <th class="py-3 px-5 text-right">Quantity Ordered</th>
                            <th class="py-3 px-5 text-right">Unit Price (OMR)</th>
                            <th class="py-3 px-5 text-right">Line Total (OMR)</th>
                            <th class="py-3 px-5">Allocated Batches</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($order->items as $i => $item)
                            <tr>
                                <td class="py-3 px-5 text-slate-400">{{ $i + 1 }}</td>
                                <td class="py-3 px-5">
                                    <span class="font-semibold text-slate-900">{{ $item->product?->product_name ?? 'N/A' }}</span>
                                    <span class="block text-xs font-mono text-slate-400">{{ $item->product?->sku_code ?? '' }}</span>
                                </td>
                                <td class="py-3 px-5 text-slate-600">{{ $item->uom?->uom_name ?? 'Units' }}</td>
                                <td class="py-3 px-5 text-right font-mono font-bold text-slate-900">{{ number_format((float) ($item->quantity ?? 0), 2) }}</td>
                                <td class="py-3 px-5 text-right font-mono">{{ number_format((float) ($item->unit_price ?? 0), 3) }}</td>
                                <td class="py-3 px-5 text-right font-mono font-bold text-slate-900">{{ number_format((float) ($item->line_total ?? 0), 3) }}</td>
                                <td class="py-3 px-5 text-xs">
                                    @if($item->reservations && $item->reservations->isNotEmpty())
                                        @foreach($item->reservations as $res)
                                            <div class="font-mono text-slate-700">
                                                Batch #{{ $res->batch?->batch_no ?? $res->batch_id }} (Res: {{ $res->quantity_reserved }}, Disp: {{ $res->quantity_dispatched }})
                                            </div>
                                        @endforeach
                                    @else
                                        <span class="text-slate-400 italic">No batches allocated</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-6 text-center text-slate-400 text-xs">No items recorded for this order.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Related Delivery Notes & Invoices Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <!-- Related Delivery Notes -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="p-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="text-sm font-bold uppercase text-slate-700">Related Delivery Notes ({{ $order->deliveryNotes ? $order->deliveryNotes->count() : 0 }})</h3>
                </div>
                <div class="divide-y divide-slate-100 text-sm">
                    @forelse($order->deliveryNotes ?? [] as $dn)
                        <div class="p-3.5 flex items-center justify-between hover:bg-slate-50 transition">
                            <div>
                                @if(Route::has('wholesale.delivery-notes.show'))
                                    <a href="{{ route('wholesale.delivery-notes.show', $dn) }}" class="font-mono font-bold text-rmsc-navy hover:underline">
                                        {{ $dn->dn_no }}
                                    </a>
                                @else
                                    <span class="font-mono font-bold text-slate-900">{{ $dn->dn_no }}</span>
                                @endif
                                <span class="block text-xs text-slate-500">
                                    {{ $dn->dispatched_at ? $dn->dispatched_at->format('d M Y H:i') : 'Draft' }}
                                    @if($dn->vehicle_no) &bull; Vehicle: {{ $dn->vehicle_no }} @endif
                                </span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $dn->status === 'DISPATCHED' ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'bg-slate-100 text-slate-600 border border-slate-200' }}">
                                    {{ $dn->status }}
                                </span>
                                @if(Route::has('wholesale.delivery-notes.show'))
                                    <a href="{{ route('wholesale.delivery-notes.show', $dn) }}" class="text-xs font-semibold text-slate-600 hover:text-slate-900">View &rarr;</a>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="p-4 text-center text-xs text-slate-400">No delivery notes generated yet.</div>
                    @endforelse
                </div>
            </div>

            <!-- Related Invoices -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="p-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="text-sm font-bold uppercase text-slate-700">Related Invoices ({{ $order->invoices ? $order->invoices->count() : 0 }})</h3>
                </div>
                <div class="divide-y divide-slate-100 text-sm">
                    @forelse($order->invoices ?? [] as $inv)
                        <div class="p-3.5 flex items-center justify-between hover:bg-slate-50 transition">
                            <div>
                                @if(Route::has('wholesale.invoices.show'))
                                    <a href="{{ route('wholesale.invoices.show', $inv) }}" class="font-mono font-bold text-rmsc-navy hover:underline">
                                        {{ $inv->invoice_no }}
                                    </a>
                                @else
                                    <span class="font-mono font-bold text-slate-900">{{ $inv->invoice_no }}</span>
                                @endif
                                <span class="block text-xs text-slate-500">
                                    Due: {{ $inv->due_date ? $inv->due_date->format('d M Y') : 'N/A' }}
                                </span>
                            </div>
                            <div class="text-right">
                                <span class="font-mono font-bold text-slate-900 block">{{ number_format((float) ($inv->grand_total ?? 0), 3) }} OMR</span>
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $inv->status === 'POSTED' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200' }}">
                                    {{ $inv->status }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="p-4 text-center text-xs text-slate-400">No invoices posted yet.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Stock Movements (Immutable Ledger Traceability) -->
        @php
            $stockMovements = \App\Models\StockLedger::with(['warehouse', 'product', 'batch'])
                ->where(function($q) use ($order) {
                    $q->where('reference_type', 'sales_order')->where('reference_id', $order->order_id)
                      ->orWhere('sales_order_id', $order->order_id);
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

