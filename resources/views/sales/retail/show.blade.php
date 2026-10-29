<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <a href="{{ route('pos.sales.index') }}" class="text-xs font-semibold text-slate-500 hover:text-rmsc-navy flex items-center gap-1 mb-1">
                    &larr; Back to Retail Sales History
                </a>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">{{ $sale->receipt_no }}</h1>
                    @if($sale->status === 'COMPLETED')
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            COMPLETED
                        </span>
                    @else
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                            VOIDED
                        </span>
                    @endif
                </div>
            </div>

            <!-- Action Controls -->
            <div class="flex flex-wrap items-center gap-2">
                @if(Route::has('pos.receipt'))
                    <a href="{{ route('pos.receipt', $sale->id) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold rounded-xl transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H7v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        Print 80mm Receipt
                    </a>
                @endif
                @if(Route::has('pos.terminal'))
                    <a href="{{ route('pos.terminal') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-rmsc-navy hover:bg-slate-900 text-white text-sm font-semibold rounded-xl transition">
                        POS Terminal &rarr;
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- 4 Info Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Facility & Counter -->
            <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs space-y-1.5">
                <span class="text-xs font-semibold uppercase text-slate-400">Branch & Counter</span>
                <p class="text-sm font-bold text-slate-900">{{ $sale->branch?->branch_name ?? 'Branch' }}</p>
                <p class="text-xs text-slate-500 font-mono">Counter: {{ $sale->counter?->counter_code ?? 'CNT' }}</p>
                <p class="text-xs text-slate-600">Terminal: <span class="font-mono font-semibold">{{ $sale->counter?->terminal_id ?? 'N/A' }}</span></p>
            </div>

            <!-- Staff & Shift -->
            <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs space-y-1.5">
                <span class="text-xs font-semibold uppercase text-slate-400">Staff & Shift</span>
                <p class="text-sm font-bold text-slate-900">{{ $sale->cashier?->full_name ?? 'Staff' }}</p>
                <p class="text-xs text-slate-500 font-mono">Shift #{{ $sale->cashier_shift_id ?? 'N/A' }}</p>
                <p class="text-xs text-slate-600">Time: <span class="font-mono">{{ $sale->created_at?->format('d M Y H:i') }}</span></p>
            </div>

            <!-- Customer & Permit -->
            <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs space-y-1.5">
                <span class="text-xs font-semibold uppercase text-slate-400">Customer Identity</span>
                @if($sale->customer_civil_id)
                    <p class="text-sm font-bold font-mono text-slate-900">{{ $sale->masked_civil_id }}</p>
                    @if($sale->permitVerification)
                        <p class="text-xs text-emerald-700 font-semibold">Permit #{{ $sale->permitVerification->permit_no }}</p>
                        <p class="text-2xs text-slate-500 font-mono">Holder: {{ $sale->permitVerification->holder_name }}</p>
                    @else
                        <p class="text-xs text-slate-500">Unrestricted Retail Customer</p>
                    @endif
                @else
                    <p class="text-sm text-slate-400 italic">Walk-in Customer</p>
                    <p class="text-xs text-slate-400">No permit required</p>
                @endif
            </div>

            <!-- Payment & Totals -->
            <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs space-y-1.5">
                <span class="text-xs font-semibold uppercase text-slate-400">Payment Breakdown</span>
                <div class="flex justify-between text-xs text-slate-600">
                    <span>Method:</span>
                    <span class="font-bold text-slate-800">{{ $sale->payment_method }}</span>
                </div>
                <div class="flex justify-between text-xs text-slate-600">
                    <span>VAT (5%):</span>
                    <span class="font-mono font-semibold">OMR {{ number_format((float)$sale->vat_total, 3) }}</span>
                </div>
                <div class="flex justify-between text-sm font-bold text-slate-900 pt-1 border-t border-slate-100">
                    <span>Grand Total:</span>
                    <span class="font-mono text-rmsc-red">OMR {{ number_format((float)$sale->grand_total, 3) }}</span>
                </div>
            </div>
        </div>

        <!-- Line Items Table -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="p-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-sm font-bold uppercase text-slate-700">Sale Line Items ({{ $sale->items->count() }})</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-50/50 text-xs font-bold uppercase text-slate-500 tracking-wider border-b border-slate-100">
                        <tr>
                            <th class="py-3 px-5">#</th>
                            <th class="py-3 px-5">Product Description</th>
                            <th class="py-3 px-5">Batch #</th>
                            <th class="py-3 px-5">UOM</th>
                            <th class="py-3 px-5 text-right">Quantity</th>
                            <th class="py-3 px-5 text-right">Unit Price</th>
                            <th class="py-3 px-5 text-right">VAT</th>
                            <th class="py-3 px-5 text-right">Line Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($sale->items as $i => $item)
                            <tr>
                                <td class="py-3 px-5 text-slate-400">{{ $i + 1 }}</td>
                                <td class="py-3 px-5">
                                    <span class="font-semibold text-slate-900">{{ $item->product?->product_name ?? 'N/A' }}</span>
                                    <span class="block text-xs font-mono text-slate-400">{{ $item->product?->product_code }}</span>
                                </td>
                                <td class="py-3 px-5 font-mono text-slate-700">
                                    {{ $item->batch?->batch_number ?? ($item->batch_id ? "#{$item->batch_id}" : 'N/A') }}
                                </td>
                                <td class="py-3 px-5 text-slate-600">{{ $item->uom?->uom_name ?? $item->product?->uom?->uom_name ?? 'Units' }}</td>
                                <td class="py-3 px-5 text-right font-mono font-bold text-slate-900">
                                    {{ number_format((float)$item->quantity, 2) }}
                                </td>
                                <td class="py-3 px-5 text-right font-mono text-slate-700">
                                    {{ number_format((float)$item->unit_price, 3) }}
                                </td>
                                <td class="py-3 px-5 text-right font-mono text-amber-700">
                                    {{ number_format((float)$item->vat_amount, 3) }}
                                </td>
                                <td class="py-3 px-5 text-right font-mono font-bold text-slate-900">
                                    {{ number_format((float)$item->line_total, 3) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-6 text-center text-slate-400 text-xs">No line items recorded for this sale.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Stock Movements (Immutable Ledger Traceability) -->
        @php
            $stockMovements = \App\Models\StockLedger::with(['warehouse', 'product', 'batch'])
                ->where('reference_type', 'retail_sale')
                ->where('reference_id', $sale->id)
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

