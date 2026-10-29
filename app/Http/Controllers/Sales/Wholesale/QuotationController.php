<?php

namespace App\Http\Controllers\Sales\Wholesale;

use App\Http\Controllers\Controller;
use App\Models\WholesaleQuotation;

class QuotationController extends Controller
{
    public function show(WholesaleQuotation $quotation)
    {
        $this->authorize('view', $quotation);

        $quotation->load([
            'items.product',
            'items.uom',
            'customer',
            'branch',
            'creator',
            'salesOrder',
        ]);

        return view('sales.wholesale.quotations.show', compact('quotation'));
    }

    public function print(WholesaleQuotation $quotation)
    {
        $this->authorize('view', $quotation);

        $quotation->load([
            'items.product',
            'items.uom',
            'customer',
            'branch',
            'creator',
        ]);

        return view('sales.wholesale.quotations.print', compact('quotation'));
    }
}
