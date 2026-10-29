<?php

namespace App\Http\Controllers\Sales\Wholesale;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\SalesInvoice;
use App\Services\CreditService;

class CustomerCreditController extends Controller
{
    public function show(Customer $customer, CreditService $creditService)
    {
        $this->authorize('view', $customer);

        $limit = $customer->getEffectiveCreditLimit();
        $outstanding = $creditService->getOutstandingBalance($customer);
        $available = max(0.0, $limit - $outstanding);
        $hasOverdue = $creditService->hasOverdueInvoices($customer);

        $invoices = SalesInvoice::where('customer_id', $customer->customer_id)
            ->whereIn('status', ['POSTED', 'PARTIALLY_PAID'])
            ->orderBy('due_date', 'asc')
            ->get();

        $recentPayments = CustomerPayment::where('customer_id', $customer->customer_id)
            ->with(['invoice', 'receivedBy'])
            ->orderBy('received_at', 'desc')
            ->limit(10)
            ->get();

        return view('sales.wholesale.credit.show', compact(
            'customer',
            'limit',
            'outstanding',
            'available',
            'hasOverdue',
            'invoices',
            'recentPayments'
        ));
    }

    public function print(Customer $customer, CreditService $creditService)
    {
        $this->authorize('view', $customer);

        $limit = $customer->getEffectiveCreditLimit();
        $outstanding = $creditService->getOutstandingBalance($customer);
        $available = max(0.0, $limit - $outstanding);
        $hasOverdue = $creditService->hasOverdueInvoices($customer);

        $invoices = SalesInvoice::where('customer_id', $customer->customer_id)
            ->whereIn('status', ['POSTED', 'PARTIALLY_PAID'])
            ->orderBy('due_date', 'asc')
            ->get();

        return view('sales.wholesale.credit.print', compact(
            'customer',
            'limit',
            'outstanding',
            'available',
            'hasOverdue',
            'invoices'
        ));
    }
}

