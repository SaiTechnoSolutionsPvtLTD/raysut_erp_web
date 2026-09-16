<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Purchase Orders</h1>
            <p class="text-sm text-slate-500">Overseas liquor import purchase orders, supplier costs, and order statuses.</p>
        </div>
    </div>

    <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search PO Number..." class="w-full md:w-1/3 text-sm rounded-lg border-slate-300 focus:ring-rmsc-red focus:border-rmsc-red">
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 font-semibold border-b border-slate-200">
                        <th class="py-3 px-4">PO Number</th>
                        <th class="py-3 px-4">Supplier</th>
                        <th class="py-3 px-4">Destination Warehouse</th>
                        <th class="py-3 px-4">Order Date</th>
                        <th class="py-3 px-4">Expected Delivery</th>
                        <th class="py-3 px-4">Items Count</th>
                        <th class="py-3 px-4">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($purchaseOrders as $po)
                        <tr class="hover:bg-slate-50/80">
                            <td class="py-3 px-4 font-mono font-bold text-slate-900">{{ $po->po_number }}</td>
                            <td class="py-3 px-4 font-medium text-slate-900">{{ $po->supplier?->supplier_name }}</td>
                            <td class="py-3 px-4">{{ $po->warehouse?->warehouse_name }}</td>
                            <td class="py-3 px-4 font-mono text-xs">{{ $po->order_date?->format('Y-m-d') }}</td>
                            <td class="py-3 px-4 font-mono text-xs">{{ $po->expected_delivery_date?->format('Y-m-d') ?? '-' }}</td>
                            <td class="py-3 px-4 font-mono text-xs">{{ $po->items->count() }} Items</td>
                            <td class="py-3 px-4">
                                <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-800">
                                    {{ $po->status }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-6 text-center text-slate-400">No purchase orders found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-200">
            {{ $purchaseOrders->links() }}
        </div>
    </div>
</div>
