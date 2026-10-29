<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\StockBatch;
use App\Models\StockLedger;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class StockLedgerService
{
    /**
     * Record stock intake into a warehouse (e.g. GRN receipt).
     */
    public function recordIntake(int $warehouseId, int $productId, int $batchId, float $quantity, array $meta = []): WarehouseStock
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Intake quantity must be greater than zero.');
        }

        return DB::transaction(function () use ($warehouseId, $productId, $batchId, $quantity, $meta) {
            $stock = WarehouseStock::where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->where('batch_id', $batchId)
                ->lockForUpdate()
                ->first();

            if (! $stock) {
                $stock = new WarehouseStock([
                    'warehouse_id' => $warehouseId,
                    'product_id' => $productId,
                    'batch_id' => $batchId,
                    'quantity_on_hand' => 0,
                    'quantity_reserved' => 0,
                    'quantity_available' => 0,
                ]);
            }

            $stock->quantity_on_hand = (float) $stock->quantity_on_hand + $quantity;
            $stock->quantity_available = (float) $stock->quantity_available + $quantity;
            $stock->last_updated_at = now();
            $stock->save();

            $wh = Warehouse::find($warehouseId);
            StockLedger::create([
                'warehouse_id' => $warehouseId,
                'branch_id' => $meta['branch_id'] ?? $wh?->branch_id ?? 1,
                'product_id' => $productId,
                'batch_id' => $batchId,
                'movement_type' => $meta['movement_type'] ?? 'INTAKE_GRN',
                'quantity' => abs($quantity),
                'balance_after' => (float) $stock->quantity_on_hand,
                'reference_type' => $meta['reference_type'] ?? 'goods_receipt_note',
                'reference_id' => $meta['reference_id'] ?? 0,
                'reference_no' => $meta['reference_no'] ?? null,
                'user_id' => $meta['user_id'] ?? auth()->id(),
                'notes' => $meta['notes'] ?? null,
                'created_at' => now(),
            ]);

            return $stock;
        });
    }

    /**
     * Reserve stock for an order or transfer (locks stock rows for update).
     */
    public function reserveStock(int $warehouseId, int $productId, int $batchId, float $quantity, bool $onlyDutyPaid = false): WarehouseStock
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Reservation quantity must be greater than zero.');
        }

        if ($onlyDutyPaid) {
            $batch = StockBatch::find($batchId);
            $warehouse = Warehouse::find($warehouseId);
            if (($batch && $batch->duty_status !== 'DUTY_PAID') || ($warehouse && $warehouse->warehouse_type === 'DUTY_FREE_BONDED')) {
                $batchStatus = $batch ? $batch->duty_status : 'UNKNOWN';
                $whType = $warehouse ? $warehouse->warehouse_type : 'UNKNOWN';
                throw new InvalidArgumentException("Wholesale orders may only reserve duty-paid stock. Cannot reserve duty-suspended batch ({$batchStatus}) or bonded warehouse ({$whType}).");
            }
        }

        return DB::transaction(function () use ($warehouseId, $productId, $batchId, $quantity) {
            $stock = WarehouseStock::where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->where('batch_id', $batchId)
                ->lockForUpdate()
                ->firstOrFail();

            if ((float) $stock->quantity_available < $quantity) {
                throw new InvalidArgumentException("Insufficient available stock in warehouse {$warehouseId}. Required: {$quantity}, Available: {$stock->quantity_available}");
            }

            $stock->quantity_available = (float) $stock->quantity_available - $quantity;
            $stock->quantity_reserved = (float) $stock->quantity_reserved + $quantity;
            $stock->last_updated_at = now();
            $stock->save();

            return $stock;
        });
    }

    /**
     * Release previously reserved stock back to available stock (locks stock rows for update).
     */
    public function releaseReservation(int $warehouseId, int $productId, int $batchId, float $quantity): WarehouseStock
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Release quantity must be greater than zero.');
        }

        return DB::transaction(function () use ($warehouseId, $productId, $batchId, $quantity) {
            $stock = WarehouseStock::where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->where('batch_id', $batchId)
                ->lockForUpdate()
                ->firstOrFail();

            $releaseQty = min((float) $stock->quantity_reserved, $quantity);
            $stock->quantity_reserved = (float) $stock->quantity_reserved - $releaseQty;
            $stock->quantity_available = (float) $stock->quantity_available + $releaseQty;
            $stock->last_updated_at = now();
            $stock->save();

            return $stock;
        });
    }

    /**
     * Deduct stock from warehouse and write immutable stock ledger entry.
     *
     * @param int $warehouseId
     * @param int $productId
     * @param int $batchId
     * @param float $quantity
     * @param bool $fromReserved
     * @param string|null $saleType When specified (e.g. 'RETAIL', 'WHOLESALE'), enforces duty-paid stock.
     * @param array $meta Context metadata (reference_type, reference_id, reference_no, user_id, branch_id, sales_order_id, delivery_note_id)
     * @return WarehouseStock
     */
    public function deductStock(
        int $warehouseId,
        int $productId,
        int $batchId,
        float $quantity,
        bool $fromReserved = false,
        ?string $saleType = null,
        array $meta = []
    ): WarehouseStock {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Deduction quantity must be greater than zero.');
        }

        // Rule 4: Retail and wholesale sales may only deduct duty-paid stock
        if ($saleType !== null) {
            $batch = StockBatch::find($batchId);
            $warehouse = Warehouse::find($warehouseId);

            if (($batch && $batch->duty_status !== 'DUTY_PAID') || ($warehouse && $warehouse->warehouse_type === 'DUTY_FREE_BONDED')) {
                $batchStatus = $batch ? $batch->duty_status : 'UNKNOWN';
                $whType = $warehouse ? $warehouse->warehouse_type : 'UNKNOWN';
                throw new InvalidArgumentException("{$saleType} sales may only deduct duty-paid stock. Cannot deduct stock from duty-suspended batch ({$batchStatus}) or bonded warehouse ({$whType}).");
            }
        }

        return DB::transaction(function () use ($warehouseId, $productId, $batchId, $quantity, $fromReserved, $saleType, $meta) {
            $stock = WarehouseStock::where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->where('batch_id', $batchId)
                ->lockForUpdate()
                ->firstOrFail();

            if ((float) $stock->quantity_on_hand < $quantity) {
                throw new InvalidArgumentException("Insufficient stock on hand in warehouse {$warehouseId}. Required: {$quantity}, On-Hand: {$stock->quantity_on_hand}");
            }

            $stock->quantity_on_hand = (float) $stock->quantity_on_hand - $quantity;

            if ($fromReserved) {
                $stock->quantity_reserved = max(0, (float) $stock->quantity_reserved - $quantity);
            } else {
                $stock->quantity_available = max(0, (float) $stock->quantity_available - $quantity);
            }

            $stock->last_updated_at = now();
            $stock->save();

            // Record immutable stock ledger entry
            $wh = Warehouse::find($warehouseId);
            $branchId = $meta['branch_id'] ?? $wh?->branch_id ?? 1;
            $movementType = $meta['movement_type'] ?? ($saleType === 'WHOLESALE' ? 'SALES_WHOLESALE' : 'SALES_RETAIL');
            $refType = $meta['reference_type'] ?? ($saleType === 'WHOLESALE' ? 'sales_invoice' : 'retail_sale');
            $refId = $meta['reference_id'] ?? 0;

            StockLedger::create([
                'warehouse_id' => $warehouseId,
                'branch_id' => $branchId,
                'product_id' => $productId,
                'batch_id' => $batchId,
                'movement_type' => $movementType,
                'quantity' => -1 * abs($quantity),
                'balance_after' => (float) $stock->quantity_on_hand,
                'reference_type' => $refType,
                'reference_id' => $refId,
                'reference_no' => $meta['reference_no'] ?? null,
                'sales_order_id' => $meta['sales_order_id'] ?? null,
                'delivery_note_id' => $meta['delivery_note_id'] ?? null,
                'user_id' => $meta['user_id'] ?? auth()->id(),
                'notes' => $meta['notes'] ?? null,
                'created_at' => now(),
            ]);

            return $stock;
        });
    }

    /**
     * Deduct stock for retail or wholesale sales, strictly enforcing duty-paid stock.
     */
    public function deductSaleStock(
        int $warehouseId,
        int $productId,
        int $batchId,
        float $quantity,
        string $saleType = 'RETAIL',
        bool $fromReserved = false,
        array $meta = []
    ): WarehouseStock {
        return $this->deductStock($warehouseId, $productId, $batchId, $quantity, $fromReserved, $saleType, $meta);
    }

    /**
     * Reverse stock deduction upon sale void or return (SALES_RETAIL_VOID or SALES_WHOLESALE_VOID).
     */
    public function reverseSaleStock(
        int $warehouseId,
        int $productId,
        int $batchId,
        float $quantity,
        string $ledgerAction = 'SALES_RETAIL_VOID',
        array $meta = []
    ): WarehouseStock {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Reversal quantity must be greater than zero.');
        }

        return DB::transaction(function () use ($warehouseId, $productId, $batchId, $quantity, $ledgerAction, $meta) {
            $stock = WarehouseStock::where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->where('batch_id', $batchId)
                ->lockForUpdate()
                ->first();

            if (! $stock) {
                $stock = new WarehouseStock([
                    'warehouse_id' => $warehouseId,
                    'product_id' => $productId,
                    'batch_id' => $batchId,
                    'quantity_on_hand' => 0,
                    'quantity_reserved' => 0,
                    'quantity_available' => 0,
                ]);
            }

            $stock->quantity_on_hand = (float) $stock->quantity_on_hand + $quantity;
            $stock->quantity_available = (float) $stock->quantity_available + $quantity;
            $stock->last_updated_at = now();
            $stock->save();

            $wh = Warehouse::find($warehouseId);
            $branchId = $meta['branch_id'] ?? $wh?->branch_id ?? 1;
            $refType = $meta['reference_type'] ?? ($ledgerAction === 'SALES_WHOLESALE_VOID' ? 'sales_invoice' : 'retail_sale');
            $refId = $meta['reference_id'] ?? 0;

            StockLedger::create([
                'warehouse_id' => $warehouseId,
                'branch_id' => $branchId,
                'product_id' => $productId,
                'batch_id' => $batchId,
                'movement_type' => $ledgerAction,
                'quantity' => abs($quantity),
                'balance_after' => (float) $stock->quantity_on_hand,
                'reference_type' => $refType,
                'reference_id' => $refId,
                'reference_no' => $meta['reference_no'] ?? null,
                'sales_order_id' => $meta['sales_order_id'] ?? null,
                'delivery_note_id' => $meta['delivery_note_id'] ?? null,
                'user_id' => $meta['user_id'] ?? auth()->id(),
                'notes' => $meta['notes'] ?? null,
                'created_at' => now(),
            ]);

            AuditLog::create([
                'user_id' => $meta['user_id'] ?? auth()->id(),
                'module_name' => 'Warehouse & Inventory',
                'action' => $ledgerAction,
                'record_table' => 'warehouse_stock',
                'record_id' => $stock->stock_id,
                'details_json' => [
                    'warehouse_id' => $warehouseId,
                    'product_id' => $productId,
                    'batch_id' => $batchId,
                    'quantity_reversed' => $quantity,
                    'ledger_action' => $ledgerAction,
                ],
                'ip_address' => request()->ip(),
            ]);

            return $stock;
        });
    }

    /**
     * Transfer stock from source warehouse to destination warehouse.
     */
    public function transferStock(int $sourceWarehouseId, int $destWarehouseId, int $productId, int $batchId, float $quantity, array $meta = []): void
    {
        DB::transaction(function () use ($sourceWarehouseId, $destWarehouseId, $productId, $batchId, $quantity, $meta) {
            $this->deductStock($sourceWarehouseId, $productId, $batchId, $quantity, false, null, array_merge($meta, ['movement_type' => 'TRANSFER_OUT']));
            $this->recordIntake($destWarehouseId, $productId, $batchId, $quantity, array_merge($meta, ['movement_type' => 'TRANSFER_IN']));
        });
    }

    /**
     * Adjust stock on hand to match physical count.
     */
    public function adjustStock(int $warehouseId, int $productId, int $batchId, float $newOnHandsQuantity, array $meta = []): WarehouseStock
    {
        return DB::transaction(function () use ($warehouseId, $productId, $batchId, $newOnHandsQuantity, $meta) {
            $stock = WarehouseStock::where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->where('batch_id', $batchId)
                ->lockForUpdate()
                ->first();

            if (! $stock) {
                $stock = new WarehouseStock([
                    'warehouse_id' => $warehouseId,
                    'product_id' => $productId,
                    'batch_id' => $batchId,
                    'quantity_on_hand' => 0,
                    'quantity_reserved' => 0,
                    'quantity_available' => 0,
                ]);
            }

            $diff = $newOnHandsQuantity - (float) $stock->quantity_on_hand;

            $stock->quantity_on_hand = max(0, $newOnHandsQuantity);
            $stock->quantity_available = max(0, (float) $stock->quantity_available + $diff);
            $stock->last_updated_at = now();
            $stock->save();

            if (abs($diff) > 0.0001) {
                $wh = Warehouse::find($warehouseId);
                StockLedger::create([
                    'warehouse_id' => $warehouseId,
                    'branch_id' => $meta['branch_id'] ?? $wh?->branch_id ?? 1,
                    'product_id' => $productId,
                    'batch_id' => $batchId,
                    'movement_type' => 'ADJUSTMENT',
                    'quantity' => $diff,
                    'balance_after' => (float) $stock->quantity_on_hand,
                    'reference_type' => $meta['reference_type'] ?? 'stock_adjustment',
                    'reference_id' => $meta['reference_id'] ?? 0,
                    'reference_no' => $meta['reference_no'] ?? null,
                    'user_id' => $meta['user_id'] ?? auth()->id(),
                    'notes' => $meta['notes'] ?? 'Physical stock adjustment',
                    'created_at' => now(),
                ]);
            }

            return $stock;
        });
    }

    /**
     * Get branch-wise counter stock location inventory.
     */
    public function getBranchCounterStock(int $branchId, ?int $productId = null, bool $onlyDutyPaid = false): Collection
    {
        $warehouseQuery = Warehouse::where('branch_id', $branchId)
            ->where('is_active', true);

        if ($onlyDutyPaid) {
            $warehouseQuery->where('warehouse_type', '!=', 'DUTY_FREE_BONDED');
        }

        $warehouseIds = $warehouseQuery->pluck('warehouse_id');

        $query = WarehouseStock::with(['product.uom', 'product.taxRate', 'product.pricingTiers', 'batch', 'warehouse'])
            ->whereIn('warehouse_id', $warehouseIds);

        if ($onlyDutyPaid) {
            $query->whereHas('batch', function ($q) {
                $q->where('duty_status', 'DUTY_PAID');
            });
        }

        if ($productId) {
            $query->where('product_id', $productId);
        }

        return $query->get();
    }
}
