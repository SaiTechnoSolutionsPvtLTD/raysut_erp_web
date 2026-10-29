<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <a href="{{ route('wholesale.quotations.index') }}" class="text-xs font-semibold text-slate-500 hover:text-rmsc-navy flex items-center gap-1 mb-1">
                    &larr; Back to Quotations
                </a>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">{{ $quotation->quote_no }}</h1>
                    @php
                        $badgeStyles = [
                            'DRAFT' => 'bg-slate-100 text-slate-700 border-slate-200',
                            'SENT' => 'bg-blue-50 text-blue-700 border-blue-200',
                            'ACCEPTED' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                            'REJECTED' => 'bg-rose-50 text-rose-700 border-rose-200',
                            'EXPIRED' => 'bg-amber-50 text-amber-700 border-amber-200',
                            'CONVERTED' => 'bg-purple-50 text-purple-700 border-purple-200',
                        ];
                    @endphp
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold border {{ $badgeStyles[$quotation->status] ?? 'bg-slate-100 text-slate-700 border-slate-200' }}">
                        {{ $quotation->status }}
                    </span>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @if(Route::has('wholesale.quotations.print'))
                    <a href="{{ route('wholesale.quotations.print', $quotation) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold rounded-xl transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H7v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        Print A4
                    </a>
                @endif

                @if($quotation->status === 'CONVERTED' && $quotation->salesOrder && Route::has('wholesale.orders.show'))
                    <a href="{{ route('wholesale.orders.show', $quotation->salesOrder) }}" class="inline-flex items-center gap-1.5 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-semibold rounded-xl shadow-xs transition">
                        View Sales Order &rarr;
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
                    <span class="font-bold">Read-Only Document View:</span> To send, accept, reject, or convert this quotation, please <strong>use the action icons on the list</strong>.
                </div>
            </div>
            <a href="{{ route('wholesale.quotations.index') }}" class="font-semibold text-blue-700 hover:text-blue-900 hover:underline shrink-0 flex items-center gap-1">
                Go to Quotations List &rarr;
            </a>
        </div>

        <!-- 3 Header Cards: Customer, Terms & Facility, Financial Summary -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <!-- Customer Card -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs space-y-2">
                <span class="text-xs font-semibold uppercase text-slate-400">Customer Details</span>
                <p class="text-sm font-bold text-slate-900">{{ $quotation->customer?->customer_name ?? 'N/A' }}</p>
                <p class="text-xs text-slate-500 font-mono">Code: {{ $quotation->customer?->customer_code ?? 'N/A' }}</p>
                <p class="text-xs text-slate-600">Phone: {{ $quotation->customer?->phone ?? 'N/A' }}</p>
                <p class="text-xs text-slate-600">Email: {{ $quotation->customer?->email ?? 'N/A' }}</p>
            </div>

            <!-- Terms & Facility Card -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs space-y-2">
                <span class="text-xs font-semibold uppercase text-slate-400">Terms & Facility</span>
                <p class="text-sm text-slate-700"><span class="font-semibold">Branch:</span> {{ $quotation->branch?->branch_name ?? 'HQ' }}</p>
                <p class="text-sm text-slate-700"><span class="font-semibold">Valid Until:</span> {{ $quotation->valid_until ? $quotation->valid_until->format('d M Y') : 'N/A' }}</p>
                <p class="text-sm text-slate-700"><span class="font-semibold">Payment Terms:</span> {{ $quotation->payment_terms_days ?? 0 }} Days</p>
                <p class="text-sm text-slate-700"><span class="font-semibold">Created By:</span> {{ $quotation->creator?->full_name ?? 'System' }}</p>
            </div>

            <!-- Financial Summary Card -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs space-y-2">
                <span class="text-xs font-semibold uppercase text-slate-400">Financial Summary</span>
                <div class="flex justify-between text-sm text-slate-600">
                    <span>Subtotal:</span>
                    <span class="font-mono font-semibold">{{ number_format((float) ($quotation->subtotal ?? 0), 3) }} OMR</span>
                </div>
                <div class="flex justify-between text-sm text-slate-600">
                    <span>VAT Total:</span>
                    <span class="font-mono font-semibold">{{ number_format((float) ($quotation->vat_total ?? 0), 3) }} OMR</span>
                </div>
                <div class="flex justify-between text-base font-bold text-rmsc-navy border-t border-slate-100 pt-2">
                    <span>Grand Total:</span>
                    <span class="font-mono text-rmsc-red">{{ number_format((float) ($quotation->grand_total ?? 0), 3) }} OMR</span>
                </div>
            </div>
        </div>

        @if($quotation->notes)
            <div class="bg-white rounded-2xl p-4 border border-slate-200/80 text-sm text-slate-600 shadow-xs">
                <span class="font-semibold text-slate-800">Notes / Remarks:</span>
                <p class="mt-1 whitespace-pre-line">{{ $quotation->notes }}</p>
            </div>
        @endif

        <!-- Items Table -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="p-4 bg-slate-50 border-b border-slate-100">
                <h3 class="text-sm font-bold uppercase text-slate-700">Quotation Line Items</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-50/50 text-xs font-bold uppercase text-slate-500 tracking-wider border-b border-slate-100">
                        <tr>
                            <th class="py-3 px-5">#</th>
                            <th class="py-3 px-5">Product</th>
                            <th class="py-3 px-5">UOM</th>
                            <th class="py-3 px-5 text-right">Quantity</th>
                            <th class="py-3 px-5 text-right">Unit Price (OMR)</th>
                            <th class="py-3 px-5 text-right">VAT Rate</th>
                            <th class="py-3 px-5 text-right">VAT Amount (OMR)</th>
                            <th class="py-3 px-5 text-right">Line Total (OMR)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($quotation->items as $i => $item)
                            <tr>
                                <td class="py-3 px-5 text-slate-400">{{ $i + 1 }}</td>
                                <td class="py-3 px-5">
                                    <span class="font-semibold text-slate-900">{{ $item->product?->product_name ?? 'N/A' }}</span>
                                    <span class="block text-xs font-mono text-slate-400">{{ $item->product?->sku_code ?? '' }}</span>
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
                                <td colspan="8" class="py-6 text-center text-slate-400 text-xs">No items recorded for this quotation.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>

