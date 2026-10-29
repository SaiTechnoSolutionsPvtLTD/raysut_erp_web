<?php

namespace App\Http\Controllers\Sales\Wholesale;

use App\Http\Controllers\Controller;
use App\Models\DeliveryNote;

class DeliveryNoteController extends Controller
{
    public function show(DeliveryNote $deliveryNote)
    {
        $this->authorize('view', $deliveryNote);

        $deliveryNote->load([
            'salesOrder.customer',
            'items.product.uom',
            'items.batch',
            'branch',
            'warehouse',
            'dispatcher',
            'invoice',
        ]);

        return view('sales.wholesale.delivery-notes.show', compact('deliveryNote'));
    }

    public function print(DeliveryNote $deliveryNote)
    {
        $this->authorize('view', $deliveryNote);

        $deliveryNote->load([
            'salesOrder.customer',
            'items.product.uom',
            'items.batch',
            'branch',
            'warehouse',
            'dispatcher',
        ]);

        return view('sales.wholesale.delivery-notes.print', compact('deliveryNote'));
    }
}
