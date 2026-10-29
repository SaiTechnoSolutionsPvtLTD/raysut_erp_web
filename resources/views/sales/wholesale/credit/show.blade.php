<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <a href="{{ route('wholesale.credit.index') }}" class="text-xs font-semibold text-slate-500 hover:text-rmsc-navy flex items-center gap-1 mb-1">
                    &larr; Back to Credit Management
                </a>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">{{ $customer->customer_name }}</h1>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold border {{ ($customer->credit_status === 'ACTIVE') ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200' }}">
                        {{ $customer->credit_status ?? 'ACTIVE' }}
                    </span>
                    @if(!empty($hasOverdue))
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200">
                            Overdue Invoices
                        </span>
                    @endif
                </div>
            </div>

            @if(Route::has('wholesale.credit.print'))
                <div>
                    <a href="{{ route('wholesale.credit.print', $customer) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold rounded-xl transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H7v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        Print Credit Statement
                    </a>
                </div>
            @endif
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Read-Only Action Banner -->
        <div class="p-4 bg-blue-50 border border-blue-200/80 rounded-2xl flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs text-blue-900 shadow-xs">
            <div class="flex items-center gap-2.5">
                <svg class="w-5 h-5 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div>
                    <span class="font-bold">Read-Only Credit Overview:</span> To modify credit limits, toggle credit hold, or record customer payments, please <strong>use the action icons on the list</strong>.
                </div>
            </div>
            <a href="{{ route('wholesale.credit.index') }}" class="font-semibold text-blue-700 hover:text-blue-900 hover:underline shrink-0 flex items-center gap-1">
                Go to Credit Management List &rarr;
            </a>
        </div>

        <!-- 3 Info Cards: Customer Master, Credit Facilities, Credit Financial Standing -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <!-- Customer Master Card -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs space-y-2">
                <span class="text-xs font-semibold uppercase text-slate-400">Customer Master</span>
                <p class="text-sm font-bold text-slate-900">{{ $customer->customer_name }}</p>
                <p class="text-xs text-slate-500 font-mono">Code: {{ $customer->customer_code ?? 'N/A' }}</p>
                <p class="text-xs text-slate-600">Segment: {{ $customer->customer_segment ?? 'WHOLESALE' }}</p>
                <p class="text-xs text-slate-600">VAT TIN: <span class="font-mono">{{ $customer->vat_number ?? 'N/A' }}</span></p>
                <p class="text-xs text-slate-600">Phone: {{ $customer->phone ?? 'N/A' }}</p>
            </div>

            <!-- Credit Facility & Terms Card -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs space-y-2">
                <span class="text-xs font-semibold uppercase text-slate-400">Facility Terms</span>
                <p class="text-sm text-slate-700"><span class="font-semibold">Approved Limit:</span> {{ number_format((float) ($limit ?? $customer->getEffectiveCreditLimit()), 3) }} OMR</p>
                <p class="text-sm text-slate-700"><span class="font-semibold">Payment Terms:</span> {{ $customer->payment_terms_days ?? 30 }} Days</p>
                <p class="text-sm text-slate-700"><span class="font-semibold">Credit Status:</span> {{ $customer->credit_status ?? 'ACTIVE' }}</p>
                <div class="text-sm pt-1">
                    <span class="font-semibold text-slate-700">Aging Risk:</span>
                    @if(!empty($hasOverdue))
                        <span class="text-rose-600 font-bold">Overdue Invoices Exist</span>
                    @else
                        <span class="text-emerald-600 font-semibold">Account Current (No Overdues)</span>
                    @endif
                </div>
            </div>

            <!-- Financial Standing Card -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs space-y-2">
                <span class="text-xs font-semibold uppercase text-slate-400">Credit Financial Standing</span>
                <div class="flex justify-between text-sm text-slate-600">
                    <span>Approved Limit:</span>
                    <span class="font-mono font-semibold">{{ number_format((float) ($limit ?? 0), 3) }} OMR</span>
                </div>
                <div class="flex justify-between text-sm text-slate-600">
                    <span>Total Outstanding:</span>
                    <span class="font-mono font-semibold text-amber-700">{{ number_format((float) ($outstanding ?? 0), 3) }} OMR</span>
                </div>
                <div class="flex justify-between text-base font-bold text-rmsc-navy border-t border-slate-100 pt-2">
                    <span>Available Credit:</span>
                    <span class="font-mono text-emerald-700">{{ number_format((float) ($available ?? 0), 3) }} OMR</span>
                </div>
            </div>
        </div>

        <!-- Open / Unpaid Invoices Table -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="p-4 bg-slate-50 border-b border-slate-100">
                <h3 class="text-sm font-bold uppercase text-slate-700">Open / Outstanding Invoices ({{ $invoices ? $invoices->count() : 0 }})</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-50/50 text-xs font-bold uppercase text-slate-500 tracking-wider border-b border-slate-100">
                        <tr>
                            <th class="py-3 px-5">Invoice No</th>
                            <th class="py-3 px-5">Invoice Date</th>
                            <th class="py-3 px-5">Due Date</th>
                            <th class="py-3 px-5 text-right">Grand Total (OMR)</th>
                            <th class="py-3 px-5 text-right">Balance Due (OMR)</th>
                            <th class="py-3 px-5">Status</th>
                            <th class="py-3 px-5 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($invoices ?? [] as $inv)
                            <tr>
                                <td class="py-3 px-5 font-mono font-bold text-rmsc-navy">
                                    {{ $inv->invoice_no }}
                                </td>
                                <td class="py-3 px-5 text-slate-700">{{ $inv->invoice_date ? $inv->invoice_date->format('d M Y') : 'N/A' }}</td>
                                <td class="py-3 px-5">
                                    <span class="{{ $inv->isOverdue() ? 'text-rose-600 font-bold' : 'text-slate-700' }}">
                                        {{ $inv->due_date ? $inv->due_date->format('d M Y') : 'N/A' }}
                                    </span>
                                </td>
                                <td class="py-3 px-5 text-right font-mono font-medium">{{ number_format((float) ($inv->grand_total ?? 0), 3) }}</td>
                                <td class="py-3 px-5 text-right font-mono font-bold text-rose-600">{{ number_format((float) ($inv->balance_due ?? 0), 3) }}</td>
                                <td class="py-3 px-5">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $inv->status === 'POSTED' ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                                        {{ str_replace('_', ' ', $inv->status) }}
                                    </span>
                                </td>
                                <td class="py-3 px-5 text-right">
                                    @if(Route::has('wholesale.invoices.show'))
                                        <a href="{{ route('wholesale.invoices.show', $inv) }}" class="text-xs font-semibold text-slate-600 hover:text-rmsc-navy hover:underline">View &rarr;</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-6 text-center text-slate-400 text-xs">No open or outstanding invoices found for this customer.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Payments Table -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="p-4 bg-slate-50 border-b border-slate-100">
                <h3 class="text-sm font-bold uppercase text-slate-700">Recent Payment Transactions ({{ $recentPayments ? $recentPayments->count() : 0 }})</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-50/50 text-xs font-bold uppercase text-slate-500 tracking-wider border-b border-slate-100">
                        <tr>
                            <th class="py-3 px-5">Receipt Date</th>
                            <th class="py-3 px-5">Invoice Reference</th>
                            <th class="py-3 px-5">Payment Method</th>
                            <th class="py-3 px-5">Reference</th>
                            <th class="py-3 px-5">Received By</th>
                            <th class="py-3 px-5 text-right">Amount (OMR)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($recentPayments ?? [] as $pmt)
                            <tr>
                                <td class="py-3 px-5 text-slate-700">{{ $pmt->received_at ? \Carbon\Carbon::parse($pmt->received_at)->format('d M Y H:i') : ($pmt->created_at ? $pmt->created_at->format('d M Y H:i') : 'N/A') }}</td>
                                <td class="py-3 px-5 font-mono text-slate-900 font-semibold">{{ $pmt->invoice?->invoice_no ?? 'Account Credit' }}</td>
                                <td class="py-3 px-5 font-semibold text-slate-800">{{ $pmt->method ?? $pmt->payment_method ?? 'BANK_TRANSFER' }}</td>
                                <td class="py-3 px-5 font-mono text-slate-600">{{ $pmt->reference ?? 'Direct Deposit' }}</td>
                                <td class="py-3 px-5 text-slate-600">{{ $pmt->receivedBy?->full_name ?? 'System' }}</td>
                                <td class="py-3 px-5 text-right font-mono font-bold text-emerald-700">{{ number_format((float) ($pmt->amount ?? 0), 3) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-6 text-center text-slate-400 text-xs">No recent payment records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>

