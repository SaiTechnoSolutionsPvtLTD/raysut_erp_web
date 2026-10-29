<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\DeliveryNote;
use App\Models\DeliveryNoteItem;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\StockBatch;
use App\Models\StockLedger;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    protected DocumentSequenceService $sequenceService;
    protected StockLedgerService $stockLedgerService;
    protected CreditService $creditService;

    public function __construct(
        DocumentSequenceService $sequenceService,
        StockLedgerService $stockLedgerService,
        CreditService $creditService
    ) {
        $this->sequenceService = $sequenceService;
        $this->stockLedgerService = $stockLedgerService;
        $this->creditService = $creditService;
    }

    /**
     * Post a Sales Invoice from a dispatched Delivery Note.
     * Enforces dispatched quantities only, master data VAT rates, payment terms due dates,
     * atomic & idempotent stock deduction (fromReserved = true), and credit checks.
     */
    public function postFromDeliveryNote(DeliveryNote $dn, User $user, array $meta = []): SalesInvoice
    {
        if ($dn->status !== 'DISPATCHED') {
            throw new Exception("Invoices can only be created from DISPATCHED delivery notes (current: {$dn->status}).");
        }

        // Idempotency: check if invoice already posted for this delivery note
        $existing = SalesInvoice::where('delivery_note_id', $dn->delivery_note_id)
            ->whereIn('status', ['POSTED', 'PARTIALLY_PAID', 'PAID'])
            ->first();

        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($dn, $user, $meta) {
            $order = SalesOrder::with(['items', 'customer'])->where('order_id', $dn->sales_order_id)->firstOrFail();
            $customer = $order->customer;

            $branchId = $order->branch_id;
            $invoiceNo = $this->sequenceService->generateNumber($branchId, 'INV');

            $invoiceDate = isset($meta['invoice_date']) ? \Carbon\Carbon::parse($meta['invoice_date']) : now();
            $paymentTermsDays = (int) ($meta['payment_terms_days'] ?? $order->payment_terms_days ?? $customer->getEffectivePaymentTermsDays());
            $dueDate = $invoiceDate->copy()->addDays($paymentTermsDays);

            $invoice = SalesInvoice::create([
                'invoice_no' => $invoiceNo,
                'sales_order_id' => $order->order_id,
                'delivery_note_id' => $dn->delivery_note_id,
                'customer_id' => $customer->customer_id,
                'branch_id' => $branchId,
                'warehouse_id' => $dn->warehouse_id,
                'invoice_date' => $invoiceDate->toDateString(),
                'due_date' => $dueDate->toDateString(),
                'payment_terms_days' => $paymentTermsDays,
                'subtotal' => 0,
                'vat_total' => 0,
                'grand_total' => 0,
                'amount_paid' => 0,
                'status' => 'DRAFT',
                'notes' => $meta['notes'] ?? "Invoice for Delivery Note {$dn->dn_no}",
                'created_by' => $user->user_id,
            ]);

            $subtotal = 0.0;
            $vatTotal = 0.0;

            foreach ($dn->items as $dnItem) {
                $orderItem = SalesOrderItem::findOrFail($dnItem->order_item_id);
                $product = Product::with('taxRate', 'uom')->findOrFail($dnItem->product_id);
                $batch = StockBatch::findOrFail($dnItem->batch_id);

                if ($batch->duty_status !== 'DUTY_PAID') {
                    throw new Exception("Duty-suspended batch #{$batch->batch_no} cannot be invoiced.");
                }

                $qty = (float) $dnItem->quantity_dispatched;
                $unitPrice = (float) $orderItem->unit_price;
                $vatRate = (float) $orderItem->vat_rate;

                $lineSubtotal = round($qty * $unitPrice, 3);
                $vatAmount = round($lineSubtotal * ($vatRate / 100), 3);
                $lineTotal = round($lineSubtotal + $vatAmount, 3);

                SalesInvoiceItem::create([
                    'invoice_id' => $invoice->invoice_id,
                    'order_item_id' => $orderItem->item_id,
                    'delivery_note_item_id' => $dnItem->item_id,
                    'product_id' => $product->product_id,
                    'batch_id' => $batch->batch_id,
                    'uom_id' => $orderItem->uom_id,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'vat_rate' => $vatRate,
                    'vat_amount' => $vatAmount,
                    'line_total' => $lineTotal,
                ]);

                $subtotal += $lineSubtotal;
                $vatTotal += $vatAmount;
            }

            $invoice->subtotal = $subtotal;
            $invoice->vat_total = $vatTotal;
            $invoice->grand_total = round($subtotal + $vatTotal, 3);
            $invoice->save();

            // Perform atomic stock deduction & reservation release
            return $this->executePosting($invoice, $user, $meta);
        });
    }

    /**
     * Post a Sales Invoice directly from a confirmed Sales Order.
     */
    public function postFromSalesOrder(SalesOrder $order, User $user, array $meta = []): SalesInvoice
    {
        if (! in_array($order->status, ['CONFIRMED', 'PARTIALLY_DISPATCHED', 'DISPATCHED'])) {
            throw new Exception("Invoices can only be posted for confirmed or dispatched sales orders (current: {$order->status}).");
        }

        // If delivery notes exist for this order, prefer posting via delivery notes
        $latestDn = DeliveryNote::where('sales_order_id', $order->order_id)
            ->where('status', 'DISPATCHED')
            ->whereDoesntHave('invoice')
            ->first();

        if ($latestDn) {
            return $this->postFromDeliveryNote($latestDn, $user, $meta);
        }

        // If not dispatched via DN yet, auto-create DN for remaining quantities and post
        $dnService = app(DeliveryNoteService::class);
        $itemsToDispatch = [];
        foreach ($order->items as $item) {
            $rem = (float) $item->quantity - (float) $item->quantity_dispatched;
            if ($rem > 0) {
                $itemsToDispatch[] = [
                    'order_item_id' => $item->item_id,
                    'quantity' => $rem,
                ];
            }
        }

        if (empty($itemsToDispatch)) {
            throw new Exception("Order {$order->so_no} has no remaining dispatchable quantities to invoice.");
        }

        $dn = $dnService->createAndDispatch($order, $itemsToDispatch, ['notes' => 'Auto-dispatched for direct invoicing'], $user);
        return $this->postFromDeliveryNote($dn, $user, $meta);
    }

    /**
     * Atomic execution of invoice posting:
     * 1. Re-runs CreditService::check() on customer (with override support).
     * 2. StockLedgerService::deductSaleStock(..., 'WHOLESALE', fromReserved = true).
     * 3. Sets invoice status to POSTED.
     * 4. Updates SalesOrder status to INVOICED if all items invoiced.
     */
    protected function executePosting(SalesInvoice $invoice, User $user, array $meta = []): SalesInvoice
    {
        if ($invoice->status === 'POSTED' || StockLedger::where('reference_type', 'sales_invoice')->where('reference_id', $invoice->invoice_id)->where('movement_type', 'SALES_WHOLESALE')->exists()) {
            return $invoice->load(['items.product', 'items.batch', 'customer', 'branch', 'salesOrder']); // Idempotent
        }

        // Re-run credit check on customer before posting
        $creditCheck = $this->creditService->check($invoice->customer, 0);
        if (! $creditCheck['allowed']) {
            $order = $invoice->salesOrder;
            $hasOrderOverride = $order && $order->credit_check_status === 'OVERRIDDEN';
            $hasExplicitOverride = ! empty($meta['credit_override_reason']) && (
                $user->hasRole('Super Admin') ||
                $user->hasRole('HQ Admin') ||
                $user->hasPermission('Retail & Wholesale Sales', 'APPROVE')
            );

            if ($hasOrderOverride) {
                AuditLog::create([
                    'user_id' => $user->user_id,
                    'module_name' => 'Retail & Wholesale Sales',
                    'action' => 'CREDIT_OVERRIDE_INVOICE',
                    'record_table' => 'sales_invoices',
                    'record_id' => $invoice->invoice_id,
                    'details_json' => [
                        'invoice_no' => $invoice->invoice_no,
                        'reason' => 'Bypassed due to approved sales order override: ' . ($order->credit_override_reason ?? 'Approved on SO'),
                        'credit_warning' => $creditCheck['reason'],
                    ],
                    'ip_address' => request()->ip(),
                ]);
            } elseif ($hasExplicitOverride) {
                AuditLog::create([
                    'user_id' => $user->user_id,
                    'module_name' => 'Retail & Wholesale Sales',
                    'action' => 'CREDIT_OVERRIDE_INVOICE',
                    'record_table' => 'sales_invoices',
                    'record_id' => $invoice->invoice_id,
                    'details_json' => [
                        'invoice_no' => $invoice->invoice_no,
                        'reason' => $meta['credit_override_reason'],
                        'credit_warning' => $creditCheck['reason'],
                    ],
                    'ip_address' => request()->ip(),
                ]);
            } else {
                throw new Exception("Invoice posting blocked by credit check: {$creditCheck['reason']}. An authorized supervisor override is required.");
            }
        }

        $warehouseId = $invoice->warehouse_id ?? $invoice->salesOrder?->warehouse_id;

        foreach ($invoice->items as $item) {
            // Deduct stock from reserved duty-paid inventory and record in stock_ledger
            $this->stockLedgerService->deductSaleStock(
                $warehouseId,
                $item->product_id,
                $item->batch_id,
                (float) $item->quantity,
                'WHOLESALE',
                true, // fromReserved = true (clears reserved & decrements on-hand)
                [
                    'reference_type' => 'sales_invoice',
                    'reference_id' => $invoice->invoice_id,
                    'reference_no' => $invoice->invoice_no,
                    'sales_order_id' => $invoice->sales_order_id,
                    'delivery_note_id' => $invoice->delivery_note_id,
                    'branch_id' => $invoice->branch_id,
                    'user_id' => $user->user_id,
                ]
            );
        }

        $invoice->status = 'POSTED';
        $invoice->posted_at = now();
        $invoice->save();

        // Check if sales order is fully invoiced
        $order = $invoice->salesOrder;
        if ($order) {
            $totalOrdered = (float) $order->items->sum('quantity');
            $totalInvoiced = (float) SalesInvoiceItem::whereHas('invoice', function ($q) use ($order) {
                $q->where('sales_order_id', $order->order_id)
                  ->whereIn('status', ['POSTED', 'PARTIALLY_PAID', 'PAID']);
            })->sum('quantity');

            if ($totalInvoiced >= ($totalOrdered - 0.0001)) {
                $order->status = 'INVOICED';
                $order->save();
            }
        }

        AuditLog::create([
            'user_id' => $user->user_id,
            'module_name' => 'Retail & Wholesale Sales',
            'action' => 'POST_INVOICE',
            'record_table' => 'sales_invoices',
            'record_id' => $invoice->invoice_id,
            'details_json' => [
                'invoice_no' => $invoice->invoice_no,
                'customer' => $invoice->customer->customer_name,
                'grand_total' => $invoice->grand_total,
                'due_date' => $invoice->due_date->toDateString(),
            ],
            'ip_address' => request()->ip(),
        ]);

        return $invoice->load(['items.product', 'items.batch', 'customer', 'branch', 'salesOrder']);
    }

    /**
     * Void a posted sales invoice:
     * 1. Requires APPROVE permission.
     * 2. Reverses stock via StockLedgerService back to the same batches.
     * 3. Blocks voiding if payments exist (must reverse payments first).
     * 4. Sets status to VOIDED.
     */
    public function void(SalesInvoice $invoice, User $user, string $reason): SalesInvoice
    {
        $canVoid = $user->hasRole('Super Admin')
            || $user->hasRole('HQ Admin')
            || $user->hasPermission('Retail & Wholesale Sales', 'APPROVE');

        if (! $canVoid) {
            throw new Exception('Unauthorized: Only users with Retail & Wholesale Sales APPROVE permission can void sales invoices.');
        }

        if (in_array($invoice->status, ['VOIDED']) || StockLedger::where('reference_type', 'sales_invoice')->where('reference_id', $invoice->invoice_id)->where('movement_type', 'SALES_WHOLESALE_VOID')->exists()) {
            throw new Exception("Invoice {$invoice->invoice_no} is already voided.");
        }

        if ((float) $invoice->amount_paid > 0.001 || $invoice->payments()->exists() || CustomerPayment::where('invoice_id', $invoice->invoice_id)->exists()) {
            throw new Exception("Cannot void invoice {$invoice->invoice_no} because payments have already been recorded. Reverse payments first.");
        }

        return DB::transaction(function () use ($invoice, $user, $reason) {
            $warehouseId = $invoice->warehouse_id ?? $invoice->salesOrder?->warehouse_id;

            // Reverse stock for all invoice items back to the same batches
            foreach ($invoice->items as $item) {
                $this->stockLedgerService->reverseSaleStock(
                    $warehouseId,
                    $item->product_id,
                    $item->batch_id,
                    (float) $item->quantity,
                    'SALES_WHOLESALE_VOID',
                    [
                        'reference_type' => 'sales_invoice',
                        'reference_id' => $invoice->invoice_id,
                        'reference_no' => $invoice->invoice_no,
                        'sales_order_id' => $invoice->sales_order_id,
                        'delivery_note_id' => $invoice->delivery_note_id,
                        'branch_id' => $invoice->branch_id,
                        'user_id' => $user->user_id,
                    ]
                );
            }

            $invoice->status = 'VOIDED';
            $invoice->voided_at = now();
            $invoice->void_reason = $reason;
            $invoice->save();

            // Revert sales order status back to DISPATCHED if it was marked INVOICED
            $order = $invoice->salesOrder;
            if ($order && $order->status === 'INVOICED') {
                $order->status = 'DISPATCHED';
                $order->save();
            }

            AuditLog::create([
                'user_id' => $user->user_id,
                'module_name' => 'Retail & Wholesale Sales',
                'action' => 'VOID_INVOICE',
                'record_table' => 'sales_invoices',
                'record_id' => $invoice->invoice_id,
                'details_json' => [
                    'invoice_no' => $invoice->invoice_no,
                    'void_reason' => $reason,
                    'voided_by' => $user->full_name,
                ],
                'ip_address' => request()->ip(),
            ]);

            return $invoice->load(['items', 'customer']);
        });
    }

    /**
     * Record payment against a sales invoice.
     */
    public function recordPayment(SalesInvoice $invoice, array $data, User $user): CustomerPayment
    {
        if (! in_array($invoice->status, ['POSTED', 'PARTIALLY_PAID'])) {
            throw new Exception("Payments can only be applied to POSTED or PARTIALLY_PAID invoices (current: {$invoice->status}).");
        }

        $amount = round((float) $data['amount'], 3);
        if ($amount <= 0) {
            throw new Exception('Payment amount must be greater than zero.');
        }

        $balanceDue = $invoice->balance_due;
        if ($amount > ($balanceDue + 0.001)) {
            throw new Exception("Payment amount ({$amount} OMR) cannot exceed invoice balance due ({$balanceDue} OMR).");
        }

        return DB::transaction(function () use ($invoice, $data, $amount, $user) {
            $payment = CustomerPayment::create([
                'invoice_id' => $invoice->invoice_id,
                'customer_id' => $invoice->customer_id,
                'amount' => $amount,
                'method' => $data['method'] ?? $data['payment_method'] ?? 'BANK_TRANSFER',
                'reference' => $data['reference'] ?? null,
                'received_by' => $user->user_id,
                'received_at' => $data['received_at'] ?? now(),
                'notes' => $data['notes'] ?? null,
            ]);

            $newAmountPaid = round((float) $invoice->amount_paid + $amount, 3);
            $invoice->amount_paid = $newAmountPaid;

            if ($newAmountPaid >= ((float) $invoice->grand_total - 0.001)) {
                $invoice->status = 'PAID';
            } else {
                $invoice->status = 'PARTIALLY_PAID';
            }
            $invoice->save();

            AuditLog::create([
                'user_id' => $user->user_id,
                'module_name' => 'Retail & Wholesale Sales',
                'action' => 'RECORD_PAYMENT',
                'record_table' => 'customer_payments',
                'record_id' => $payment->payment_id,
                'details_json' => [
                    'invoice_no' => $invoice->invoice_no,
                    'amount' => $amount,
                    'method' => $payment->method,
                    'new_invoice_status' => $invoice->status,
                ],
                'ip_address' => request()->ip(),
            ]);

            return $payment->load(['invoice', 'customer']);
        });
    }
}
