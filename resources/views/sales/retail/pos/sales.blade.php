<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Retail POS Sales History</h1>
                <p class="text-sm text-slate-500 mt-1">Branch-isolated sales transaction ledger and thermal receipt retrieval.</p>
            </div>
            <a href="{{ route('pos.terminal') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-rmsc-navy hover:bg-slate-900 text-white text-sm font-semibold rounded-xl shadow-xs transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                POS Terminal
            </a>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Search and Filter Bar -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
            <form method="GET" action="{{ route('pos.sales.index') }}" class="flex-1 max-w-md relative">
                <svg class="w-5 h-5 absolute left-3.5 top-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search receipt number or Civil ID..." class="w-full pl-10 pr-4 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-rmsc-red font-mono">
            </form>
            <div class="flex items-center gap-2 text-xs font-semibold px-3 py-1.5 bg-slate-100 text-slate-700 rounded-lg border border-slate-200">
                <span>Total Recorded Sales: {{ $sales->total() }}</span>
            </div>
        </div>

        <!-- Sales Table -->
        <div x-data="{ showVoidModal: false, voidAction: '', voidReceiptNo: '', voidReason: '', isSubmitting: false }"
             @keydown.escape.window="showVoidModal = false"
             class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-50 text-xs font-bold uppercase text-slate-500 tracking-wider border-b border-slate-100">
                        <tr>
                            <th class="py-3.5 px-5">Receipt No</th>
                            <th class="py-3.5 px-5">Branch / Counter</th>
                            <th class="py-3.5 px-5">Cashier</th>
                            <th class="py-3.5 px-5">Civil ID / Permit</th>
                            <th class="py-3.5 px-5">Subtotal</th>
                            <th class="py-3.5 px-5">VAT Total</th>
                            <th class="py-3.5 px-5">Grand Total</th>
                            <th class="py-3.5 px-5">Method</th>
                            <th class="py-3.5 px-5">Date</th>
                            <th class="py-3.5 px-5 text-right">Receipt</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($sales as $sale)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3.5 px-5 font-mono font-bold text-slate-900">{{ $sale->receipt_no }}</td>
                                <td class="py-3.5 px-5">
                                    <div class="font-semibold text-slate-800">{{ $sale->branch->branch_name ?? 'Branch' }}</div>
                                    <div class="text-2xs font-mono text-slate-400">{{ $sale->counter->counter_code ?? 'CNT' }}</div>
                                </td>
                                <td class="py-3.5 px-5 text-slate-700">{{ $sale->cashier->full_name ?? 'Staff' }}</td>
                                <td class="py-3.5 px-5 font-mono text-xs">
                                    @if($sale->customer_civil_id)
                                        <span class="font-bold text-slate-800">{{ $sale->masked_civil_id }}</span>
                                        @if($sale->permit)
                                            <span class="block text-3xs text-emerald-700 font-semibold">Permit #{{ $sale->permit->permit_no }}</span>
                                        @endif
                                    @else
                                        <span class="text-slate-400">&mdash;</span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-5 font-mono">OMR {{ number_format($sale->subtotal, 3) }}</td>
                                <td class="py-3.5 px-5 font-mono text-amber-700 font-semibold">OMR {{ number_format($sale->vat_total, 3) }}</td>
                                <td class="py-3.5 px-5 font-mono font-bold text-slate-900">OMR {{ number_format($sale->grand_total, 3) }}</td>
                                <td class="py-3.5 px-5">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span class="px-2 py-0.5 rounded text-2xs font-bold uppercase {{ $sale->payment_method === 'CASH' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-700' }}">
                                            {{ $sale->payment_method }}
                                        </span>
                                        @if($sale->status === 'VOIDED')
                                            <span class="px-2 py-0.5 rounded text-2xs font-bold uppercase bg-rose-50 text-rose-700 border border-rose-200">
                                                VOIDED
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="py-3.5 px-5 text-2xs font-mono text-slate-500">{{ $sale->created_at->format('Y-m-d H:i') }}</td>
                                <td class="py-3.5 px-5 text-right space-x-1.5 whitespace-nowrap">
                                    <a href="{{ route('pos.sales.show', $sale->id) }}" class="px-2.5 py-1 text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg transition inline-flex items-center gap-1">
                                        <span>👁️ View</span>
                                    </a>
                                    <a href="{{ route('pos.receipt', $sale->id) }}" target="_blank" class="px-2.5 py-1 text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg transition inline-flex items-center gap-1">
                                        <span>🖨️ Receipt</span>
                                    </a>
                                    @if($sale->status === 'COMPLETED')
                                        @can('void', $sale)
                                            <button type="button"
                                                @click="voidAction = '{{ route('pos.sales.void', $sale->id) }}'; voidReceiptNo = '{{ $sale->receipt_no }}'; voidReason = ''; showVoidModal = true"
                                                class="px-2 py-1 text-xs font-semibold bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-lg transition cursor-pointer">
                                                Void
                                            </button>
                                        @endcan
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="py-12 text-center text-slate-400">
                                    No retail sales recorded for this branch.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($sales->hasPages())
                <div class="p-4 border-t border-slate-100">
                    {{ $sales->links() }}
                </div>
            @endif

            <!-- Void Retail Sale Modal (400px centered) -->
            <template x-teleport="body">
                <div x-show="showVoidModal"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
                     style="display: none;">
                    <div @click.away="if (!isSubmitting) showVoidModal = false"
                         class="bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden transform transition-all w-full mx-auto"
                         style="max-width: 400px;">
                        <div class="p-6 text-center">
                            <div class="w-12 h-12 rounded-full bg-rose-100 text-rose-600 mx-auto flex items-center justify-center mb-4">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                            </div>
                            <h3 class="text-base font-bold text-slate-900 mb-1">Void Retail Sale?</h3>
                            <p class="text-xs text-slate-500 mb-4">
                                Are you sure you want to void sale <span class="font-bold text-slate-800" x-text="voidReceiptNo"></span>? This will reverse inventory ledgers and permit quota deductions.
                            </p>
                            <form method="POST" :action="voidAction" @submit="isSubmitting = true">
                                @csrf
                                <div class="mb-4 text-left">
                                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Supervisor Void Reason *</label>
                                    <textarea name="reason" x-model="voidReason" required rows="2" placeholder="e.g. Customer return, wrong items scanned..." class="w-full rounded-xl border border-slate-200 text-xs px-3 py-2 focus:border-rmsc-red focus:ring-rmsc-red"></textarea>
                                </div>
                                <div class="flex items-center justify-center space-x-3">
                                    <button type="button" @click="showVoidModal = false" :disabled="isSubmitting" class="px-4 py-2 text-xs font-semibold text-slate-700 hover:text-slate-900 bg-slate-100 hover:bg-slate-200/80 rounded-xl transition cursor-pointer disabled:opacity-50">
                                        Cancel
                                    </button>
                                    <button type="submit" :disabled="isSubmitting || !voidReason.trim()" class="px-4 py-2 text-xs font-semibold text-white bg-rose-600 hover:bg-rose-700 rounded-xl shadow-xs transition cursor-pointer disabled:opacity-50 flex items-center gap-1.5">
                                        <svg x-show="isSubmitting" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle style="opacity:.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path style="opacity:.85;" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                        <span x-text="isSubmitting ? 'Voiding...' : 'Yes, Void Sale'"></span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</x-app-layout>
