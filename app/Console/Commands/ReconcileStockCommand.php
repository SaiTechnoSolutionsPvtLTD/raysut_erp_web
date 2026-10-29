<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\SalesOrderReservation;
use App\Models\StockLedger;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReconcileStockCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stock:reconcile {--warehouse= : Filter by warehouse ID or warehouse code} {--fix : Auto-fix mismatches via ADJUSTMENT ledger entries and reservation updates}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reconcile on-hand inventory against the immutable stock ledger and active reservations';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $warehouseFilter = $this->option('warehouse');
        $shouldFix = (bool) $this->option('fix');

        $query = WarehouseStock::with(['warehouse.branch', 'product', 'batch']);

        if ($warehouseFilter) {
            $wh = Warehouse::where('warehouse_id', $warehouseFilter)
                ->orWhere('warehouse_code', $warehouseFilter)
                ->first();

            if (! $wh) {
                $this->error("Warehouse '{$warehouseFilter}' not found.");
                return 1;
            }

            $query->where('warehouse_id', $wh->warehouse_id);
        }

        $stocks = $query->get();

        if ($stocks->isEmpty()) {
            $this->info("No warehouse stock records found to reconcile.");
            return 0;
        }

        $mismatches = [];
        $fixedRows = [];

        foreach ($stocks as $ws) {
            $warehouseId = $ws->warehouse_id;
            $batchId = $ws->batch_id;

            $onHand = (float) $ws->quantity_on_hand;
            $reserved = (float) $ws->quantity_reserved;

            // 1. Calculate sum of signed ledger movements for this warehouse and batch
            $ledgerSum = (float) StockLedger::where('warehouse_id', $warehouseId)
                ->where('batch_id', $batchId)
                ->sum('quantity');

            // 2. Calculate sum of active reservations for confirmed/partially-dispatched orders
            $activeReservations = (float) SalesOrderReservation::where('warehouse_id', $warehouseId)
                ->where('batch_id', $batchId)
                ->whereHas('order', function ($q) {
                    $q->whereIn('status', ['CONFIRMED', 'PARTIALLY_DISPATCHED']);
                })
                ->sum('quantity_reserved');

            $onHandDiff = round($onHand - $ledgerSum, 3);
            $reservedDiff = round($reserved - $activeReservations, 3);

            $hasOnHandMismatch = abs($onHandDiff) > 0.001;
            $hasReservedMismatch = abs($reservedDiff) > 0.001;

            if ($hasOnHandMismatch || $hasReservedMismatch) {
                $mismatchDetail = [
                    'warehouse' => $ws->warehouse?->warehouse_code ?? "WH-{$warehouseId}",
                    'product' => ($ws->product?->product_code ?? 'SKU') . ' - ' . ($ws->product?->product_name ?? 'N/A'),
                    'batch' => $ws->batch?->batch_number ?? "B-{$batchId}",
                    'on_hand_actual_vs_ledger' => sprintf("%.2f / %.2f (diff: %+.2f)", $onHand, $ledgerSum, $onHandDiff),
                    'reserved_actual_vs_active' => sprintf("%.2f / %.2f (diff: %+.2f)", $reserved, $activeReservations, $reservedDiff),
                    'status' => $shouldFix ? 'FIXED' : 'MISMATCH',
                ];

                $mismatches[] = $mismatchDetail;

                if ($shouldFix) {
                    DB::transaction(function () use ($ws, $warehouseId, $batchId, $onHand, $reserved, $ledgerSum, $onHandDiff, $hasOnHandMismatch, $activeReservations, $hasReservedMismatch) {
                        // Fix on-hand mismatch by aligning warehouse_stock to immutable ledger sum
                        if ($hasOnHandMismatch) {
                            $ws->quantity_on_hand = $ledgerSum;
                            $ws->quantity_available = max(0, $ledgerSum - ($hasReservedMismatch ? $activeReservations : $reserved));
                            $ws->last_updated_at = now();
                            $ws->save();

                            AuditLog::create([
                                'user_id' => auth()->id(),
                                'module_name' => 'Warehouse & Inventory',
                                'action' => 'STOCK_RECONCILE_ON_HAND_FIX',
                                'record_table' => 'warehouse_stock',
                                'record_id' => $ws->stock_id,
                                'details_json' => [
                                    'warehouse_id' => $warehouseId,
                                    'batch_id' => $batchId,
                                    'previous_on_hand' => $onHand,
                                    'restored_to_ledger_sum' => $ledgerSum,
                                ],
                                'ip_address' => 'CLI',
                            ]);
                        }

                        // Fix reserved mismatch by updating warehouse_stock to match active reservations
                        if ($hasReservedMismatch) {
                            $ws->quantity_reserved = $activeReservations;
                            $ws->quantity_available = max(0, $onHand - $activeReservations);
                            $ws->last_updated_at = now();
                            $ws->save();

                            AuditLog::create([
                                'user_id' => auth()->id(),
                                'module_name' => 'Warehouse & Inventory',
                                'action' => 'STOCK_RECONCILE_RESERVATION_FIX',
                                'record_table' => 'warehouse_stock',
                                'record_id' => $ws->stock_id,
                                'details_json' => [
                                    'warehouse_id' => $warehouseId,
                                    'batch_id' => $batchId,
                                    'previous_reserved' => $ws->quantity_reserved,
                                    'active_reservations' => $activeReservations,
                                ],
                                'ip_address' => 'CLI',
                            ]);
                        }
                    });
                }
            }
        }

        if (empty($mismatches)) {
            $this->info("Stock reconciliation passed: 0 mismatches found across {$stocks->count()} stock records.");
            return 0;
        }

        $this->table(
            ['Warehouse', 'Product', 'Batch', 'On-Hand (WH / Ledger)', 'Reserved (WH / Active)', 'Status'],
            $mismatches
        );

        if ($shouldFix) {
            $this->info("Reconciliation complete: " . count($mismatches) . " mismatch(es) successfully corrected.");
            return 0;
        }

        $this->error("Reconciliation failed: " . count($mismatches) . " mismatch(es) found. Pass --fix to correct.");
        return 1;
    }
}
