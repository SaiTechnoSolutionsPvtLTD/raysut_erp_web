<?php

namespace App\Http\Controllers\Sales\Wholesale;

use App\Http\Controllers\Controller;
use App\Models\SalesOrder;

class SalesOrderController extends Controller
{
    public function show(SalesOrder $order)
    {
        $this->authorize('view', $order);

        $order->load([
            'items.product',
            'items.uom',
            'items.reservations.batch',
            'customer',
            'branch',
            'warehouse',
            'creator',
            'deliveryNotes.items',
            'invoices',
            'quotation',
        ]);

        return view('sales.wholesale.orders.show', compact('order'));
    }

    public function print(SalesOrder $order)
    {
        $this->authorize('view', $order);

        $order->load([
            'items.product.uom',
            'customer',
            'branch.company',
            'warehouse',
            'creator',
        ]);

        return view('sales.wholesale.orders.print', compact('order'));
    }
}

