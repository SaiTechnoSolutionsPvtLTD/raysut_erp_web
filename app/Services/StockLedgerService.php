<?php

namespace App\Services;

use App\Models\WarehouseStock;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class StockLedgerService
{
    /**
     * Record stock intake into a warehouse (e.g. GRN receipt).
     */
    public function recordIntake(int $warehouseId, int $productId, int $batchId, float $quantity): WarehouseStock
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Intake quantity must be greater than zero.');
        }

        return DB::transaction(function () use ($warehouseId, $productId, $batchId, $quantity) {
            $stock = WarehouseStock::firstOrNew([
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'batch_id' => $batchId,
            ]);

            $stock->quantity_on_hand = (float) $stock->quantity_on_hand + $quantity;
            $stock->quantity_available = (float) $stock->quantity_available + $quantity;
            $stock->last_updated_at = now();
            $stock->save();

            return $stock;
        });
    }

    /**
     * Reserve stock for an order or transfer.
     */
    public function reserveStock(int $warehouseId, int $productId, int $batchId, float $quantity): WarehouseStock
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Reservation quantity must be greater than zero.');
        }

        return DB::transaction(function () use ($warehouseId, $productId, $batchId, $quantity) {
            $stock = WarehouseStock::where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->where('batch_id', $batchId)
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
     * Release previously reserved stock back to available stock.
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
     * Deduct stock from warehouse (e.g. sale, dispatch, write-off).
     */
    public function deductStock(int $warehouseId, int $productId, int $batchId, float $quantity, bool $fromReserved = false): WarehouseStock
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Deduction quantity must be greater than zero.');
        }

        return DB::transaction(function () use ($warehouseId, $productId, $batchId, $quantity, $fromReserved) {
            $stock = WarehouseStock::where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->where('batch_id', $batchId)
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

            return $stock;
        });
    }

    /**
     * Transfer stock from source warehouse to destination warehouse.
     */
    public function transferStock(int $sourceWarehouseId, int $destWarehouseId, int $productId, int $batchId, float $quantity): void
    {
        DB::transaction(function () use ($sourceWarehouseId, $destWarehouseId, $productId, $batchId, $quantity) {
            $this->deductStock($sourceWarehouseId, $productId, $batchId, $quantity, false);
            $this->recordIntake($destWarehouseId, $productId, $batchId, $quantity);
        });
    }

    /**
     * Adjust stock on hand to match physical count.
     */
    public function adjustStock(int $warehouseId, int $productId, int $batchId, float $newOnHandsQuantity): WarehouseStock
    {
        return DB::transaction(function () use ($warehouseId, $productId, $batchId, $newOnHandsQuantity) {
            $stock = WarehouseStock::firstOrNew([
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'batch_id' => $batchId,
            ]);

            $diff = $newOnHandsQuantity - (float) $stock->quantity_on_hand;

            $stock->quantity_on_hand = max(0, $newOnHandsQuantity);
            $stock->quantity_available = max(0, (float) $stock->quantity_available + $diff);
            $stock->last_updated_at = now();
            $stock->save();

            return $stock;
        });
    }
}
