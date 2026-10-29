<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductPricingTier;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\SalesOrderReservation;
use App\Models\StockBatch;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Exception;
use Illuminate\Support\Facades\DB;

class SalesOrderService
{
    protected DocumentSequenceService $sequenceService;
    protected CreditService $creditService;
    protected StockLedgerService $stockLedgerService;

    public function __construct(
        DocumentSequenceService $sequenceService,
        CreditService $creditService,
        StockLedgerService $stockLedgerService
    ) {
        $this->sequenceService = $sequenceService;
        $this->creditService = $creditService;
        $this->stockLedgerService = $stockLedgerService;
    }

    /**
     * Create a new sales order directly.
     */
    public function create(array $data, array $itemsData, User $user): SalesOrder
    {
        if (empty($itemsData)) {
            throw new Exception('A sales order must contain at least one line item.');
        }

        return DB::transaction(function () use ($data, $itemsData, $user) {
            $branchId = (int) ($data['branch_id'] ?? $user->default_branch_id);
            $customer = Customer::findOrFail($data['customer_id']);
            $warehouseId = (int) $data['warehouse_id'];

            $warehouse = Warehouse::where('warehouse_id', $warehouseId)
                ->where('branch_id', $branchId)
                ->first();

            if (! $warehouse) {
                // Fallback to warehouse directly if branch matches or HQ
                $warehouse = Warehouse::findOrFail($warehouseId);
            }

            $soNo = $this->sequenceService->generateNumber($branchId, 'SO');

            $order = SalesOrder::create([
                'so_no' => $soNo,
                'quotation_id' => $data['quotation_id'] ?? null,
                'customer_id' => $customer->customer_id,
                'branch_id' => $branchId,
                'warehouse_id' => $warehouse->warehouse_id,
                'status' => 'DRAFT',
                'subtotal' => 0,
                'vat_total' => 0,
                'grand_total' => 0,
                'payment_terms_days' => $data['payment_terms_days'] ?? $customer->getEffectivePaymentTermsDays(),
                'credit_check_status' => 'PENDING',
                'notes' => $data['notes'] ?? null,
                'created_by' => $user->user_id,
            ]);

            $subtotal = 0.0;
            $vatTotal = 0.0;

            foreach ($itemsData as $item) {
                $product = Product::with('taxRate', 'uom')->findOrFail($item['product_id']);
                $qty = (float) $item['quantity'];

                if ($qty <= 0) {
                    throw new Exception("Quantity for product {$product->product_name} must be greater than zero.");
                }

                $unitPrice = isset($item['unit_price'])
                    ? (float) $item['unit_price']
                    : $this->resolveWholesalePrice($product->product_id, $qty);

                $vatRate = isset($item['vat_rate'])
                    ? (float) $item['vat_rate']
                    : (float) ($product->taxRate?->rate_percent ?? 5.0);

                $lineSubtotal = round($qty * $unitPrice, 3);
                $vatAmount = round($lineSubtotal * ($vatRate / 100), 3);
                $lineTotal = round($lineSubtotal + $vatAmount, 3);

                SalesOrderItem::create([
                    'order_id' => $order->order_id,
                    'product_id' => $product->product_id,
                    'uom_id' => $item['uom_id'] ?? $product->base_uom_id,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'vat_rate' => $vatRate,
                    'vat_amount' => $vatAmount,
                    'line_total' => $lineTotal,
                    'quantity_reserved' => 0,
                    'quantity_dispatched' => 0,
                ]);

                $subtotal += $lineSubtotal;
                $vatTotal += $vatAmount;
            }

            $order->subtotal = $subtotal;
            $order->vat_total = $vatTotal;
            $order->grand_total = round($subtotal + $vatTotal, 3);
            $order->save();

            AuditLog::create([
                'user_id' => $user->user_id,
                'module_name' => 'Retail & Wholesale Sales',
                'action' => 'CREATE',
                'record_table' => 'sales_orders',
                'record_id' => $order->order_id,
                'details_json' => [
                    'so_no' => $order->so_no,
                    'customer' => $customer->customer_name,
                    'grand_total' => $order->grand_total,
                ],
                'ip_address' => request()->ip(),
            ]);

            return $order->load(['items.product', 'items.uom', 'customer', 'branch', 'warehouse']);
        });
    }

    /**
     * Confirm sales order:
     * 1. Performs CreditService::check.
     * 2. If blocked, checks if override is provided by an authorized user; otherwise halts.
     * 3. Reserves duty-paid stock only, in FEFO order from the warehouse.
     * 4. Updates order status to CONFIRMED.
     */
    public function confirm(SalesOrder $order, User $user, bool $overrideIfBlocked = false, ?string $overrideReason = null): SalesOrder
    {
        if ($order->status !== 'DRAFT') {
            throw new Exception("Sales Order {$order->so_no} is already {$order->status} and cannot be confirmed.");
        }

        $customer = $order->customer;

        // 1. Credit Check
        if ($order->credit_check_status === 'OVERRIDDEN') {
            // Pre-authorized supervisor override in place
        } else {
            $creditCheck = $this->creditService->check($customer, (float) $order->grand_total);

            if (! $creditCheck['allowed']) {
                if ($overrideIfBlocked && ! empty($overrideReason)) {
                    $this->creditService->override($order, $user, $overrideReason);
                } else {
                    $order->credit_check_status = 'BLOCKED';
                    $order->save();
                    throw new Exception("Credit check failed for {$customer->customer_name}: {$creditCheck['reason']}");
                }
            } else {
                $order->credit_check_status = 'PASSED';
                $order->save();
            }
        }

        return DB::transaction(function () use ($order, $user, $customer) {
            // 2. Reserve Stock (duty-paid batches only, FEFO order)
            $warehouse = $order->warehouse;
            if ($warehouse->warehouse_type === 'DUTY_FREE_BONDED') {
                throw new Exception("Wholesale sales orders cannot fulfill from bonded warehouse ({$warehouse->warehouse_name}). Only duty-paid domestic warehouses are allowed.");
            }

            foreach ($order->items as $item) {
                $neededQty = (float) $item->quantity;

                // Find candidate stocks with duty-paid batches in FEFO order
                $stocks = WarehouseStock::join('stock_batches', 'warehouse_stock.batch_id', '=', 'stock_batches.batch_id')
                    ->where('warehouse_stock.warehouse_id', $order->warehouse_id)
                    ->where('warehouse_stock.product_id', $item->product_id)
                    ->where('warehouse_stock.quantity_available', '>', 0)
                    ->where('stock_batches.duty_status', 'DUTY_PAID')
                    ->select('warehouse_stock.*')
                    ->orderBy('stock_batches.expiry_date', 'asc')
                    ->orderBy('stock_batches.batch_id', 'asc')
                    ->lockForUpdate()
                    ->get();

                $totalAvailable = (float) $stocks->sum('quantity_available');
                if ($totalAvailable < $neededQty) {
                    $prodName = $item->product?->product_name ?? "Product #{$item->product_id}";
                    throw new Exception("Insufficient duty-paid stock available to reserve for '{$prodName}' in warehouse {$warehouse->warehouse_name}. Required: {$neededQty}, Available: {$totalAvailable}.");
                }

                $remainingToReserve = $neededQty;
                foreach ($stocks as $stock) {
                    if ($remainingToReserve <= 0) {
                        break;
                    }

                    $batch = StockBatch::find($stock->batch_id);
                    if (! $batch || $batch->duty_status !== 'DUTY_PAID') {
                        throw new Exception("Duty-suspended batch #{$stock->batch_id} cannot be reserved for wholesale order.");
                    }

                    $allocQty = min((float) $stock->quantity_available, $remainingToReserve);

                    // Call StockLedgerService with onlyDutyPaid = true
                    $this->stockLedgerService->reserveStock(
                        $order->warehouse_id,
                        $item->product_id,
                        $stock->batch_id,
                        $allocQty,
                        true
                    );

                    // Record reservation line
                    SalesOrderReservation::create([
                        'order_id' => $order->order_id,
                        'order_item_id' => $item->item_id,
                        'warehouse_id' => $order->warehouse_id,
                        'product_id' => $item->product_id,
                        'batch_id' => $stock->batch_id,
                        'quantity_reserved' => $allocQty,
                        'quantity_dispatched' => 0,
                    ]);

                    $remainingToReserve -= $allocQty;
                }

                $item->quantity_reserved = $neededQty;
                $item->save();
            }

            $order->status = 'CONFIRMED';
            $order->save();

            AuditLog::create([
                'user_id' => $user->user_id,
                'module_name' => 'Retail & Wholesale Sales',
                'action' => 'CONFIRM',
                'record_table' => 'sales_orders',
                'record_id' => $order->order_id,
                'details_json' => [
                    'so_no' => $order->so_no,
                    'customer' => $customer->customer_name,
                    'grand_total' => $order->grand_total,
                    'credit_check_status' => $order->credit_check_status,
                ],
                'ip_address' => request()->ip(),
            ]);

            return $order->load(['items.reservations.batch', 'customer', 'branch', 'warehouse']);
        });
    }

    /**
     * Cancel sales order:
     * Releases any active reservations back to available stock.
     */
    public function cancel(SalesOrder $order, User $user): SalesOrder
    {
        if (in_array($order->status, ['CANCELLED', 'INVOICED', 'DISPATCHED'])) {
            throw new Exception("Order {$order->so_no} is {$order->status} and cannot be cancelled.");
        }

        return DB::transaction(function () use ($order, $user) {
            // Release all active reservations
            $reservations = SalesOrderReservation::where('order_id', $order->order_id)->get();

            foreach ($reservations as $reservation) {
                $unreleased = (float) $reservation->quantity_reserved - (float) $reservation->quantity_dispatched;
                if ($unreleased > 0) {
                    $this->stockLedgerService->releaseReservation(
                        $reservation->warehouse_id,
                        $reservation->product_id,
                        $reservation->batch_id,
                        $unreleased
                    );

                    $reservation->quantity_reserved = $reservation->quantity_dispatched;
                    $reservation->save();
                }
            }

            foreach ($order->items as $item) {
                $item->quantity_reserved = $item->quantity_dispatched;
                $item->save();
            }

            $order->status = 'CANCELLED';
            $order->save();

            AuditLog::create([
                'user_id' => $user->user_id,
                'module_name' => 'Retail & Wholesale Sales',
                'action' => 'CANCEL',
                'record_table' => 'sales_orders',
                'record_id' => $order->order_id,
                'details_json' => [
                    'so_no' => $order->so_no,
                    'reason' => 'User requested cancellation; reservations released',
                ],
                'ip_address' => request()->ip(),
            ]);

            return $order->load(['items', 'customer', 'branch', 'warehouse']);
        });
    }

    /**
     * Resolve wholesale price for product from ProductPricingTier (WHOLESALE).
     */
    public function resolveWholesalePrice(int $productId, float $quantity = 1.0): float
    {
        $tier = ProductPricingTier::where('product_id', $productId)
            ->where('price_tier', 'WHOLESALE')
            ->where('min_quantity', '<=', $quantity)
            ->where(function ($q) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', now()->toDateString());
            })
            ->orderBy('min_quantity', 'desc')
            ->first();

        if ($tier) {
            return (float) $tier->unit_price;
        }

        // Fallback: any wholesale tier or retail tier
        $fallback = ProductPricingTier::where('product_id', $productId)->first();
        return $fallback ? (float) $fallback->unit_price : 10.000;
    }
}
