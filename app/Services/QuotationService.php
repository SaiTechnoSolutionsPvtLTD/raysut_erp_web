<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\TaxVatRate;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WholesaleQuotation;
use App\Models\WholesaleQuotationItem;
use Exception;
use Illuminate\Support\Facades\DB;

class QuotationService
{
    protected DocumentSequenceService $sequenceService;

    public function __construct(DocumentSequenceService $sequenceService)
    {
        $this->sequenceService = $sequenceService;
    }

    /**
     * Create a new wholesale quotation.
     */
    public function create(array $data, array $itemsData, User $user): WholesaleQuotation
    {
        if (empty($itemsData)) {
            throw new Exception('A quotation must contain at least one line item.');
        }

        return DB::transaction(function () use ($data, $itemsData, $user) {
            $branchId = (int) ($data['branch_id'] ?? $user->default_branch_id);
            $customer = Customer::findOrFail($data['customer_id']);

            $quoteNo = $this->sequenceService->generateNumber($branchId, 'WQ');

            $quotation = WholesaleQuotation::create([
                'quote_no' => $quoteNo,
                'customer_id' => $customer->customer_id,
                'branch_id' => $branchId,
                'valid_until' => $data['valid_until'] ?? now()->addDays(30)->toDateString(),
                'status' => 'DRAFT',
                'payment_terms_days' => $data['payment_terms_days'] ?? $customer->getEffectivePaymentTermsDays(),
                'notes' => $data['notes'] ?? null,
                'created_by' => $user->user_id,
                'subtotal' => 0,
                'vat_total' => 0,
                'grand_total' => 0,
            ]);

            $subtotal = 0.0;
            $vatTotal = 0.0;

            foreach ($itemsData as $item) {
                $product = Product::with('taxRate', 'uom')->findOrFail($item['product_id']);
                $qty = (float) $item['quantity'];
                $unitPrice = (float) $item['unit_price'];

                if ($qty <= 0) {
                    throw new Exception("Quantity for product {$product->product_name} must be greater than zero.");
                }

                // VAT rate from TaxVatRate or item override
                $vatRate = isset($item['vat_rate'])
                    ? (float) $item['vat_rate']
                    : (float) ($product->taxRate?->rate_percent ?? 5.0);

                $lineSubtotal = round($qty * $unitPrice, 3);
                $vatAmount = round($lineSubtotal * ($vatRate / 100), 3);
                $lineTotal = round($lineSubtotal + $vatAmount, 3);

                WholesaleQuotationItem::create([
                    'quotation_id' => $quotation->quotation_id,
                    'product_id' => $product->product_id,
                    'uom_id' => $item['uom_id'] ?? $product->base_uom_id,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'vat_rate' => $vatRate,
                    'vat_amount' => $vatAmount,
                    'line_total' => $lineTotal,
                ]);

                $subtotal += $lineSubtotal;
                $vatTotal += $vatAmount;
            }

            $quotation->subtotal = $subtotal;
            $quotation->vat_total = $vatTotal;
            $quotation->grand_total = round($subtotal + $vatTotal, 3);
            $quotation->save();

            AuditLog::create([
                'user_id' => $user->user_id,
                'module_name' => 'Retail & Wholesale Sales',
                'action' => 'CREATE',
                'record_table' => 'wholesale_quotations',
                'record_id' => $quotation->quotation_id,
                'details_json' => [
                    'quote_no' => $quotation->quote_no,
                    'customer' => $customer->customer_name,
                    'grand_total' => $quotation->grand_total,
                ],
                'ip_address' => request()->ip(),
            ]);

            return $quotation->load(['items.product', 'items.uom', 'customer', 'branch']);
        });
    }

    /**
     * Update quotation (only allowed if DRAFT or SENT).
     */
    public function update(WholesaleQuotation $quotation, array $data, array $itemsData, User $user): WholesaleQuotation
    {
        if (in_array($quotation->status, ['CONVERTED', 'REJECTED'])) {
            throw new Exception("Quotation {$quotation->quote_no} is locked and cannot be edited in {$quotation->status} status.");
        }

        if (empty($itemsData)) {
            throw new Exception('A quotation must contain at least one line item.');
        }

        return DB::transaction(function () use ($quotation, $data, $itemsData, $user) {
            $quotation->valid_until = $data['valid_until'] ?? $quotation->valid_until;
            $quotation->payment_terms_days = $data['payment_terms_days'] ?? $quotation->payment_terms_days;
            $quotation->notes = $data['notes'] ?? $quotation->notes;

            // Recreate items
            $quotation->items()->delete();

            $subtotal = 0.0;
            $vatTotal = 0.0;

            foreach ($itemsData as $item) {
                $product = Product::with('taxRate', 'uom')->findOrFail($item['product_id']);
                $qty = (float) $item['quantity'];
                $unitPrice = (float) $item['unit_price'];

                $vatRate = isset($item['vat_rate'])
                    ? (float) $item['vat_rate']
                    : (float) ($product->taxRate?->rate_percent ?? 5.0);

                $lineSubtotal = round($qty * $unitPrice, 3);
                $vatAmount = round($lineSubtotal * ($vatRate / 100), 3);
                $lineTotal = round($lineSubtotal + $vatAmount, 3);

                WholesaleQuotationItem::create([
                    'quotation_id' => $quotation->quotation_id,
                    'product_id' => $product->product_id,
                    'uom_id' => $item['uom_id'] ?? $product->base_uom_id,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'vat_rate' => $vatRate,
                    'vat_amount' => $vatAmount,
                    'line_total' => $lineTotal,
                ]);

                $subtotal += $lineSubtotal;
                $vatTotal += $vatAmount;
            }

            $quotation->subtotal = $subtotal;
            $quotation->vat_total = $vatTotal;
            $quotation->grand_total = round($subtotal + $vatTotal, 3);
            $quotation->save();

            AuditLog::create([
                'user_id' => $user->user_id,
                'module_name' => 'Retail & Wholesale Sales',
                'action' => 'UPDATE',
                'record_table' => 'wholesale_quotations',
                'record_id' => $quotation->quotation_id,
                'details_json' => [
                    'quote_no' => $quotation->quote_no,
                    'grand_total' => $quotation->grand_total,
                ],
                'ip_address' => request()->ip(),
            ]);

            return $quotation->load(['items.product', 'items.uom', 'customer', 'branch']);
        });
    }

    /**
     * Mark quotation as SENT.
     */
    public function send(WholesaleQuotation $quotation, User $user): WholesaleQuotation
    {
        if ($quotation->status !== 'DRAFT') {
            throw new Exception("Only DRAFT quotations can be marked as SENT.");
        }

        $quotation->status = 'SENT';
        $quotation->save();

        $this->logStatusTransition($quotation, 'SENT', $user);
        return $quotation;
    }

    /**
     * Mark quotation as ACCEPTED.
     */
    public function accept(WholesaleQuotation $quotation, User $user): WholesaleQuotation
    {
        if (! in_array($quotation->status, ['DRAFT', 'SENT'])) {
            throw new Exception("Only DRAFT or SENT quotations can be accepted.");
        }

        $quotation->status = 'ACCEPTED';
        $quotation->save();

        $this->logStatusTransition($quotation, 'ACCEPTED', $user);
        return $quotation;
    }

    /**
     * Mark quotation as REJECTED.
     */
    public function reject(WholesaleQuotation $quotation, User $user, ?string $reason = null): WholesaleQuotation
    {
        if (in_array($quotation->status, ['CONVERTED'])) {
            throw new Exception("Converted quotation cannot be rejected.");
        }

        $quotation->status = 'REJECTED';
        if ($reason) {
            $quotation->notes = ($quotation->notes ? $quotation->notes . "\n" : '') . "Rejection reason: {$reason}";
        }
        $quotation->save();

        $this->logStatusTransition($quotation, 'REJECTED', $user, ['reason' => $reason]);
        return $quotation;
    }

    /**
     * Mark quotation as EXPIRED.
     */
    public function expire(WholesaleQuotation $quotation, User $user): WholesaleQuotation
    {
        if ($quotation->status === 'CONVERTED') {
            throw new Exception("Converted quotation cannot be expired.");
        }

        $quotation->status = 'EXPIRED';
        $quotation->save();

        $this->logStatusTransition($quotation, 'EXPIRED', $user);
        return $quotation;
    }

    /**
     * Convert quotation to Sales Order.
     * Copies prices, terms, and items.
     * Quotation is permanently locked once CONVERTED.
     */
    public function convertToSalesOrder(WholesaleQuotation $quotation, int $warehouseId, User $user): SalesOrder
    {
        if ($quotation->status === 'CONVERTED') {
            throw new Exception("Quotation {$quotation->quote_no} has already been converted to a Sales Order.");
        }

        if ($quotation->status === 'REJECTED') {
            throw new Exception("Rejected quotation {$quotation->quote_no} cannot be converted.");
        }

        $warehouse = Warehouse::findOrFail($warehouseId);

        return DB::transaction(function () use ($quotation, $warehouse, $user) {
            $soNo = $this->sequenceService->generateNumber($quotation->branch_id, 'SO');

            $order = SalesOrder::create([
                'so_no' => $soNo,
                'quotation_id' => $quotation->quotation_id,
                'customer_id' => $quotation->customer_id,
                'branch_id' => $quotation->branch_id,
                'warehouse_id' => $warehouse->warehouse_id,
                'status' => 'DRAFT',
                'subtotal' => $quotation->subtotal,
                'vat_total' => $quotation->vat_total,
                'grand_total' => $quotation->grand_total,
                'payment_terms_days' => $quotation->payment_terms_days,
                'credit_check_status' => 'PENDING',
                'notes' => "Converted from Quotation {$quotation->quote_no}" . ($quotation->notes ? " | {$quotation->notes}" : ''),
                'created_by' => $user->user_id,
            ]);

            foreach ($quotation->items as $item) {
                SalesOrderItem::create([
                    'order_id' => $order->order_id,
                    'product_id' => $item->product_id,
                    'uom_id' => $item->uom_id,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'vat_rate' => $item->vat_rate,
                    'vat_amount' => $item->vat_amount,
                    'line_total' => $item->line_total,
                    'quantity_reserved' => 0,
                    'quantity_dispatched' => 0,
                ]);
            }

            // Lock quotation
            $quotation->status = 'CONVERTED';
            $quotation->save();

            AuditLog::create([
                'user_id' => $user->user_id,
                'module_name' => 'Retail & Wholesale Sales',
                'action' => 'CONVERT_TO_SO',
                'record_table' => 'wholesale_quotations',
                'record_id' => $quotation->quotation_id,
                'details_json' => [
                    'quote_no' => $quotation->quote_no,
                    'so_no' => $order->so_no,
                    'order_id' => $order->order_id,
                ],
                'ip_address' => request()->ip(),
            ]);

            return $order->load(['items.product', 'customer', 'branch', 'warehouse']);
        });
    }

    protected function logStatusTransition(WholesaleQuotation $quotation, string $newStatus, User $user, array $extra = []): void
    {
        AuditLog::create([
            'user_id' => $user->user_id,
            'module_name' => 'Retail & Wholesale Sales',
            'action' => 'STATUS_CHANGE',
            'record_table' => 'wholesale_quotations',
            'record_id' => $quotation->quotation_id,
            'details_json' => array_merge([
                'quote_no' => $quotation->quote_no,
                'new_status' => $newStatus,
            ], $extra),
            'ip_address' => request()->ip(),
        ]);
    }
}
