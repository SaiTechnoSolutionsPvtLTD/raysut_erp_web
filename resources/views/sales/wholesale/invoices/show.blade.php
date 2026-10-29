<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <a href="{{ route('wholesale.invoices.index') }}" class="text-xs font-semibold text-slate-500 hover:text-rmsc-navy flex items-center gap-1 mb-1">
                    &larr; Back to Sales Invoices
                </a>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">{{ $invoice->invoice_no }}</h1>
                    @php
                        $invBadges = [
                            'DRAFT' => 'bg-slate-100 text-slate-700 border-slate-200',
                            'POSTED' => 'bg-blue-50 text-blue-700 border-blue-200',
                            'PARTIALLY_PAID' => 'bg-amber-50 text-amber-700 border-amber-200',
                            'PAID' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                            'VOID' => 'bg-rose-50 text-rose-700 border-rose-200',
                        ];
                    @endphp
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold border {{ $invBadges[$invoice->status] ?? 'bg-slate-100 text-slate-700 border-slate-200' }}">
                        {{ str_replace('_', ' ', $invoice->status) }}
                    </span>
                </div>
            </div>

            <!-- Action Controls -->
            <div class="flex flex-wrap items-center gap-2">
                @if(Route::has('wholesale.invoices.print'))
                    <a href="{{ route('wholesale.invoices.print', $invoice) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold rounded-xl transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H7v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        Print Tax Invoice (A4)
                    </a>
                @endif

                @if($invoice->salesOrder && Route::has('wholesale.orders.show'))
                    <a href="{{ route('wholesale.orders.show', $invoice->salesOrder) }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold rounded-xl transition">
                        View Order &rarr;
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
                    <span class="font-bold">Read-Only Document View:</span> To record customer payments or void this invoice, please <strong>use the action icons on the list</strong>.
                </div>
            </div>
            <a href="{{ route('wholesale.invoices.index') }}" class="font-semibold text-blue-700 hover:text-blue-900 hover:underline shrink-0 flex items-center gap-1">
                Go to Sales Invoices List &rarr;
            </a>
        </div>

        <!-- 3 Info Cards: Customer, Terms & Fulfillments, Financials -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <!-- Customer Card -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs space-y-2">
                <span class="text-xs font-semibold uppercase text-slate-400">Customer & Tax Identity</span>
                <p class="text-sm font-bold text-slate-900">{{ $invoice->customer?->customer_name ?? 'N/A' }}</p>
                <p class="text-xs text-slate-500 font-mono">Code: {{ $invoice->customer?->customer_code ?? 'N/A' }}</p>
                <p class="text-xs text-slate-600">VAT TIN: <span class="font-mono font-semibold">{{ $invoice->customer?->vat_number ?? 'Not Registered' }}</span></p>
                <p class="text-xs text-slate-600">Phone: {{ $invoice->customer?->phone ?? 'N/A' }}</p>
            </div>

            <!-- Terms & Logistics Card -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs space-y-2">
                <span class="text-xs font-semibold uppercase text-slate-400">Terms & Logistics Reference</span>
                <p class="text-sm text-slate-700"><span class="font-semibold">Branch:</span> {{ $invoice->branch?->branch_name ?? 'HQ' }}</p>
                <p class="text-sm text-slate-700"><span class="font-semibold">Invoice Date:</span> {{ $invoice->invoice_date ? $invoice->invoice_date->format('d M Y') : 'N/A' }}</p>
                <p class="text-sm text-slate-700"><span class="font-semibold">Due Date:</span> {{ $invoice->due_date ? $invoice->due_date->format('d M Y') : 'N/A' }}</p>
                <div class="text-sm text-slate-700">
                    <span class="font-semibold">Order Ref:</span>
                    @if($invoice->salesOrder && Route::has('wholesale.orders.show'))
                        <a href="{{ route('wholesale.orders.show', $invoice->salesOrder) }}" class="font-mono text-rmsc-navy font-bold hover:underline">
                            {{ $invoice->salesOrder->so_no }}
                        </a>
                    @else
                        <span class="font-mono">{{ $invoice->salesOrder?->so_no ?? 'Direct Post' }}</span>
                    @endif
                </div>
                <div class="text-sm text-slate-700">
                    <span class="font-semibold">Delivery Note:</span>
                    @if($invoice->deliveryNote && Route::has('wholesale.delivery-notes.show'))
                        <a href="{{ route('wholesale.delivery-notes.show', $invoice->deliveryNote) }}" class="font-mono text-rmsc-navy font-bold hover:underline">
                            {{ $invoice->deliveryNote->dn_no }}
                        </a>
                    @else
                        <span class="font-mono">{{ $invoice->deliveryNote?->dn_no ?? 'N/A' }}</span>
                    @endif
                </div>
            </div>

            <!-- Financials & Balances Card -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs space-y-2">
                <span class="text-xs font-semibold uppercase text-slate-400">Financial Standing</span>
                <div class="flex justify-between text-sm text-slate-600">
                    <span>Subtotal:</span>
                    <span class="font-mono font-semibold">{{ number_format((float) ($invoice->subtotal ?? 0), 3) }} OMR</span>
                </div>
                <div class="flex justify-between text-sm text-slate-600">
                    <span>VAT Total:</span>
                    <span class="font-mono font-semibold">{{ number_format((float) ($invoice->vat_total ?? 0), 3) }} OMR</span>
                </div>
                <div class="flex justify-between text-sm font-bold text-slate-800 border-t border-slate-100 pt-1">
                    <span>Grand Total:</span>
                    <span class="font-mono">{{ number_format((float) ($invoice->grand_total ?? 0), 3) }} OMR</span>
                </div>
                <div class="flex justify-between text-sm text-emerald-700 font-semibold">
                    <span>Amount Paid:</span>
                    <span class="font-mono">{{ number_format((float) ($invoice->amount_paid ?? 0), 3) }} OMR</span>
                </div>
                <div class="flex justify-between text-base font-bold text-rmsc-red border-t border-slate-100 pt-1">
                    <span>Balance Due:</span>
                    <span class="font-mono">{{ number_format((float) ($invoice->balance_due ?? max(0, (float)$invoice->grand_total - (float)$invoice->amount_paid)), 3) }} OMR</span>
                </div>
            </div>
        </div>

        @if($invoice->notes)
            <div class="bg-white rounded-2xl p-4 border border-slate-200/80 text-sm text-slate-600 shadow-xs">
                <span class="font-semibold text-slate-800">Invoice Remarks:</span>
                <p class="mt-1 whitespace-pre-line">{{ $invoice->notes }}</p>
            </div>
        @endif

        <!-- Invoice Line Items Table -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="p-4 bg-slate-50 border-b border-slate-100">
                <h3 class="text-sm font-bold uppercase text-slate-700">Tax Invoice Line Items</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-50/50 text-xs font-bold uppercase text-slate-500 tracking-wider border-b border-slate-100">
                        <tr>
                            <th class="py-3 px-5">#</th>
                            <th class="py-3 px-5">Product Description</th>
                            <th class="py-3 px-5">Batch No</th>
                            <th class="py-3 px-5">UOM</th>
                            <th class="py-3 px-5 text-right">Quantity</th>
                            <th class="py-3 px-5 text-right">Unit Price (OMR)</th>
                            <th class="py-3 px-5 text-right">VAT Rate</th>
                            <th class="py-3 px-5 text-right">VAT Amount (OMR)</th>
                            <th class="py-3 px-5 text-right">Line Total (OMR)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($invoice->items as $i => $item)
                            <tr>
                                <td class="py-3 px-5 text-slate-400">{{ $i + 1 }}</td>
                                <td class="py-3 px-5">
                                    <span class="font-semibold text-slate-900">{{ $item->product?->product_name ?? 'N/A' }}</span>
                                    <span class="block text-xs font-mono text-slate-400">{{ $item->product?->sku_code ?? '' }}</span>
                                </td>
                                <td class="py-3 px-5 font-mono text-slate-700">
                                    {{ $item->batch?->batch_no ?? $item->batch?->batch_number ?? ($item->batch_id ? "#{$item->batch_id}" : 'N/A') }}
                                </td>
                                <td class="py-3 px-5 text-slate-600">{{ $item->uom?->uom_name ?? 'Units' }}</td>
                                <td class="py-3 px-5 text-right font-mono font-medium text-slate-900">{{ number_format((float) ($item->quantity ?? 0), 2) }}</td>
                                <td class="py-3 px-5 text-right font-mono">{{ number_format((float) ($item->unit_price ?? 0), 3) }}</td>
                                <td class="py-3 px-5 text-right font-mono text-slate-500">{{ number_format((float) ($item->vat_rate ?? 0), 2) }}%</td>
                                <td class="py-3 px-5 text-right font-mono text-slate-500">{{ number_format((float) ($item->vat_amount ?? 0), 3) }}</td>
                                <td class="py-3 px-5 text-right font-mono font-bold text-slate-900">{{ number_format((float) ($item->line_total ?? 0), 3) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="py-6 text-center text-slate-400 text-xs">No items recorded for this invoice.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Payments Ledger Table -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="p-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-sm font-bold uppercase text-slate-700">Recorded Customer Payments ({{ $invoice->payments ? $invoice->payments->count() : 0 }})</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-50/50 text-xs font-bold uppercase text-slate-500 tracking-wider border-b border-slate-100">
                        <tr>
                            <th class="py-3 px-5">Receipt Date</th>
                            <th class="py-3 px-5">Payment Method</th>
                            <th class="py-3 px-5">Reference</th>
                            <th class="py-3 px-5">Recorded By</th>
                            <th class="py-3 px-5 text-right">Amount (OMR)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($invoice->payments ?? [] as $payment)
                            <tr>
                                <td class="py-3 px-5 text-slate-700">
                                    {{ $payment->received_at ? \Carbon\Carbon::parse($payment->received_at)->format('d M Y H:i') : ($payment->created_at ? $payment->created_at->format('d M Y H:i') : 'N/A') }}
                                </td>
                                <td class="py-3 px-5 font-semibold text-slate-800">{{ $payment->method ?? $payment->payment_method ?? 'BANK_TRANSFER' }}</td>
                                <td class="py-3 px-5 font-mono text-slate-600">{{ $payment->reference ?? 'Direct Deposit' }}</td>
                                <td class="py-3 px-5 text-slate-600">{{ $payment->receivedBy?->full_name ?? 'System' }}</td>
                                <td class="py-3 px-5 text-right font-mono font-bold text-emerald-700">{{ number_format((float) ($payment->amount ?? 0), 3) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-6 text-center text-slate-400 text-xs">No payments recorded against this invoice yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Stock Movements (Immutable Ledger Traceability) -->
        @php
            $stockMovements = \App\Models\StockLedger::with(['warehouse', 'product', 'batch'])
                ->where('reference_type', 'sales_invoice')
                ->where('reference_id', $invoice->invoice_id)
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

