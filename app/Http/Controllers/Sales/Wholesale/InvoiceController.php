<?php

namespace App\Http\Controllers\Sales\Wholesale;

use App\Http\Controllers\Controller;
use App\Models\SalesInvoice;

class InvoiceController extends Controller
{
    public function show(SalesInvoice $invoice)
    {
        $this->authorize('view', $invoice);

        $invoice->load([
            'items.product',
            'items.batch',
            'items.uom',
            'customer',
            'branch.company',
            'salesOrder',
            'deliveryNote',
            'payments.receivedBy',
        ]);

        return view('sales.wholesale.invoices.show', compact('invoice'));
    }

    public function print(SalesInvoice $invoice)
    {
        $this->authorize('view', $invoice);

        $invoice->load([
            'items.product',
            'items.batch',
            'items.uom',
            'customer',
            'branch.company',
            'salesOrder',
            'deliveryNote',
        ]);

        return view('sales.wholesale.invoices.print', compact('invoice'));
    }
}
