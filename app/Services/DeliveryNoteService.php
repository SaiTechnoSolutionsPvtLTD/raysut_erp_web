<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\DeliveryNote;
use App\Models\DeliveryNoteItem;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\SalesOrderReservation;
use App\Models\StockBatch;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class DeliveryNoteService
{
    protected DocumentSequenceService $sequenceService;

    public function __construct(DocumentSequenceService $sequenceService)
    {
        $this->sequenceService = $sequenceService;
    }

    /**
     * Create and dispatch a delivery note against a confirmed sales order.
     * Supports partial dispatches, prevents over-dispatch, and enforces reserved duty-paid batches.
     */
    public function createAndDispatch(SalesOrder $order, array $itemsData, array $meta, User $user): DeliveryNote
    {
        $dn = $this->createDraft($order, $itemsData, $meta, $user);
        return $this->dispatch($dn, $user);
    }

    /**
     * Create draft Delivery Note.
     */
    public function createDraft(SalesOrder $order, array $itemsData, array $meta, User $user): DeliveryNote
    {
        if (! in_array($order->status, ['CONFIRMED', 'PARTIALLY_DISPATCHED'])) {
            throw new Exception("Delivery notes can only be created for CONFIRMED or PARTIALLY_DISPATCHED orders (current: {$order->status}).");
        }

        if (empty($itemsData)) {
            throw new Exception('Delivery Note must contain at least one item to dispatch.');
        }

        return DB::transaction(function () use ($order, $itemsData, $meta, $user) {
            $dnNo = $this->sequenceService->generateNumber($order->branch_id, 'DN');

            $dn = DeliveryNote::create([
                'dn_no' => $dnNo,
                'sales_order_id' => $order->order_id,
                'branch_id' => $order->branch_id,
                'warehouse_id' => $order->warehouse_id,
                'vehicle_no' => $meta['vehicle_no'] ?? null,
                'driver_name' => $meta['driver_name'] ?? null,
                'status' => 'DRAFT',
                'notes' => $meta['notes'] ?? null,
            ]);

            foreach ($itemsData as $entry) {
                $orderItemId = (int) $entry['order_item_id'];
                $dispatchQty = (float) $entry['quantity'];

                if ($dispatchQty <= 0) {
                    continue;
                }

                $orderItem = SalesOrderItem::where('order_id', $order->order_id)
                    ->where('item_id', $orderItemId)
                    ->lockForUpdate()
                    ->firstOrFail();

                $remainingOrdered = (float) $orderItem->quantity - (float) $orderItem->quantity_dispatched;

                if ($dispatchQty > ($remainingOrdered + 0.0001)) {
                    $prodName = $orderItem->product?->product_name ?? "Product #{$orderItem->product_id}";
                    throw new Exception("Over-dispatch blocked: Quantity {$dispatchQty} exceeds remaining order quantity {$remainingOrdered} for '{$prodName}'.");
                }

                // Match with reservations
                $neededToAllocate = $dispatchQty;

                if (! empty($entry['batch_id'])) {
                    $specificBatchId = (int) $entry['batch_id'];
                    $batch = StockBatch::findOrFail($specificBatchId);
                    if ($batch->duty_status !== 'DUTY_PAID') {
                        throw new Exception("Duty-suspended batch #{$batch->batch_no} cannot be dispatched for wholesale.");
                    }

                    $reservation = SalesOrderReservation::where('order_item_id', $orderItem->item_id)
                        ->where('batch_id', $specificBatchId)
                        ->lockForUpdate()
                        ->first();

                    if (! $reservation) {
                        throw new Exception("Batch #{$batch->batch_no} was not reserved for order item #{$orderItem->item_id}.");
                    }

                    $availReserved = (float) $reservation->quantity_reserved - (float) $reservation->quantity_dispatched;
                    if ($availReserved < $dispatchQty) {
                        throw new Exception("Insufficient reserved quantity in batch #{$batch->batch_no}. Reserved available: {$availReserved}, Required: {$dispatchQty}.");
                    }

                    DeliveryNoteItem::create([
                        'delivery_note_id' => $dn->delivery_note_id,
                        'order_item_id' => $orderItem->item_id,
                        'product_id' => $orderItem->product_id,
                        'batch_id' => $specificBatchId,
                        'quantity_dispatched' => $dispatchQty,
                    ]);
                } else {
                    // Auto-allocate from reserved batches for this order item
                    $reservations = SalesOrderReservation::where('order_item_id', $orderItem->item_id)
                        ->whereRaw('(quantity_reserved - quantity_dispatched) > 0')
                        ->lockForUpdate()
                        ->get();

                    $totalAvailReserved = (float) $reservations->sum(function ($r) {
                        return (float) $r->quantity_reserved - (float) $r->quantity_dispatched;
                    });

                    if ($totalAvailReserved < $neededToAllocate) {
                        throw new Exception("Insufficient reserved stock for item #{$orderItem->item_id}. Available reserved: {$totalAvailReserved}, Dispatch requested: {$neededToAllocate}.");
                    }

                    foreach ($reservations as $res) {
                        if ($neededToAllocate <= 0) {
                            break;
                        }

                        $batch = StockBatch::find($res->batch_id);
                        if (! $batch || $batch->duty_status !== 'DUTY_PAID') {
                            throw new Exception("Duty-suspended batch #{$res->batch_id} cannot be dispatched for wholesale order.");
                        }

                        $availRes = (float) $res->quantity_reserved - (float) $res->quantity_dispatched;
                        $take = min($availRes, $neededToAllocate);

                        DeliveryNoteItem::create([
                            'delivery_note_id' => $dn->delivery_note_id,
                            'order_item_id' => $orderItem->item_id,
                            'product_id' => $orderItem->product_id,
                            'batch_id' => $res->batch_id,
                            'quantity_dispatched' => $take,
                        ]);

                        $neededToAllocate -= $take;
                    }
                }
            }

            return $dn->load(['items.product', 'items.batch', 'salesOrder']);
        });
    }

    /**
     * Dispatch Delivery Note.
     * Updates quantities dispatched on order items and reservations, and updates sales order status.
     */
    public function dispatch(DeliveryNote $dn, User $user): DeliveryNote
    {
        if ($dn->status !== 'DRAFT') {
            throw new Exception("Only DRAFT delivery notes can be dispatched (current: {$dn->status}).");
        }

        return DB::transaction(function () use ($dn, $user) {
            $order = SalesOrder::where('order_id', $dn->sales_order_id)->lockForUpdate()->firstOrFail();

            foreach ($dn->items as $dnItem) {
                $orderItem = SalesOrderItem::where('item_id', $dnItem->order_item_id)->lockForUpdate()->firstOrFail();

                // Re-verify remaining ordered qty
                $remaining = (float) $orderItem->quantity - (float) $orderItem->quantity_dispatched;
                if ((float) $dnItem->quantity_dispatched > ($remaining + 0.0001)) {
                    throw new Exception("Over-dispatch blocked: Quantity {$dnItem->quantity_dispatched} exceeds remaining ordered {$remaining}.");
                }

                $orderItem->quantity_dispatched = (float) $orderItem->quantity_dispatched + (float) $dnItem->quantity_dispatched;
                $orderItem->save();

                // Update reservation dispatched quantity
                $reservation = SalesOrderReservation::where('order_item_id', $dnItem->order_item_id)
                    ->where('batch_id', $dnItem->batch_id)
                    ->lockForUpdate()
                    ->first();

                if ($reservation) {
                    $reservation->quantity_dispatched = (float) $reservation->quantity_dispatched + (float) $dnItem->quantity_dispatched;
                    $reservation->save();
                }
            }

            // Update Sales Order status
            $order->refresh();
            $totalOrdered = (float) $order->items->sum('quantity');
            $totalDispatched = (float) $order->items->sum('quantity_dispatched');

            if ($totalDispatched >= ($totalOrdered - 0.0001)) {
                $order->status = 'DISPATCHED';
            } elseif ($totalDispatched > 0) {
                $order->status = 'PARTIALLY_DISPATCHED';
            }
            $order->save();

            $dn->status = 'DISPATCHED';
            $dn->dispatched_by = $user->user_id;
            $dn->dispatched_at = now();
            $dn->save();

            AuditLog::create([
                'user_id' => $user->user_id,
                'module_name' => 'Retail & Wholesale Sales',
                'action' => 'DISPATCH',
                'record_table' => 'delivery_notes',
                'record_id' => $dn->delivery_note_id,
                'details_json' => [
                    'dn_no' => $dn->dn_no,
                    'so_no' => $order->so_no,
                    'so_status' => $order->status,
                    'dispatched_by' => $user->full_name,
                ],
                'ip_address' => request()->ip(),
            ]);

            return $dn->load(['items.product', 'items.batch', 'salesOrder', 'dispatcher']);
        });
    }

    /**
     * Cancel a draft delivery note.
     */
    public function cancel(DeliveryNote $dn, User $user): DeliveryNote
    {
        if ($dn->status !== 'DRAFT') {
            throw new Exception("Only DRAFT delivery notes can be cancelled.");
        }

        $dn->status = 'CANCELLED';
        $dn->save();

        AuditLog::create([
            'user_id' => $user->user_id,
            'module_name' => 'Retail & Wholesale Sales',
            'action' => 'CANCEL',
            'record_table' => 'delivery_notes',
            'record_id' => $dn->delivery_note_id,
            'details_json' => ['dn_no' => $dn->dn_no],
            'ip_address' => request()->ip(),
        ]);

        return $dn;
    }
}
