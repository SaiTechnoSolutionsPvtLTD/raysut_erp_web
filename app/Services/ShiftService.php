<?php

namespace App\Services;

use App\Models\CashierShift;
use App\Models\Counter;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ShiftService
{
    /**
     * Open a new cashier shift.
     *
     * @throws Exception
     */
    public function open(User $user, int $counterId, float $openingFloat, ?string $notes = null): CashierShift
    {
        return DB::transaction(function () use ($user, $counterId, $openingFloat, $notes) {
            // Check 1: One open shift per cashier
            $existingUserShift = CashierShift::where('user_id', $user->user_id)
                ->where('status', 'OPEN')
                ->first();

            if ($existingUserShift) {
                throw new Exception("Cashier already has an active open shift (#{$existingUserShift->shift_id}). Please close it before opening a new shift.");
            }

            // Check 2: One open shift per counter
            $existingCounterShift = CashierShift::where('counter_id', $counterId)
                ->where('status', 'OPEN')
                ->first();

            if ($existingCounterShift) {
                throw new Exception("Counter #{$counterId} already has an active open shift by user #{$existingCounterShift->user_id}.");
            }

            $counter = Counter::findOrFail($counterId);

            if (! $counter->is_active) {
                throw new Exception("Cannot open shift: Counter '{$counter->counter_name}' is currently inactive.");
            }

            // Check 3: Cashier-to-counter assignment (enforced for cashiers; Super Admin and HQ Admin have managerial override)
            if (! $user->hasRole('Super Admin') && ! $user->hasRole('HQ Admin')) {
                if ($counter->assigned_user_id !== null && (int) $counter->assigned_user_id !== (int) $user->user_id) {
                    throw new Exception("Counter '{$counter->counter_name}' is assigned to another cashier.");
                }
            }

            return CashierShift::create([
                'user_id' => $user->user_id,
                'counter_id' => $counter->counter_id,
                'branch_id' => $counter->branch_id,
                'opened_at' => now(),
                'opening_float' => $openingFloat,
                'expected_cash' => $openingFloat, // Initial expected cash equals float
                'card_total' => 0.000,
                'variance' => 0.000,
                'variance_status' => 'NONE',
                'status' => 'OPEN',
                'notes' => $notes,
            ]);
        });
    }

    /**
     * Close and reconcile a cashier shift.
     *
     * @throws Exception
     */
    public function close(CashierShift $shift, float $countedCash, float $cardTotal, ?string $notes = null, float $varianceThreshold = 5.000): CashierShift
    {
        if ($shift->status !== 'OPEN') {
            throw new Exception("Shift #{$shift->shift_id} is not open and cannot be closed.");
        }

        return DB::transaction(function () use ($shift, $countedCash, $cardTotal, $notes, $varianceThreshold) {
            // Calculate cash net and card sales from retail_sales table for this shift
            $cashNetSales = 0.000;
            $cardSalesFromDb = 0.000;

            if (Schema::hasTable('retail_sales')) {
                $completedSales = DB::table('retail_sales')
                    ->where('cashier_shift_id', $shift->shift_id)
                    ->where('status', 'COMPLETED')
                    ->get();

                foreach ($completedSales as $sale) {
                    $cashNet = (float) $sale->cash_amount - (float) $sale->change_given;
                    $cashNetSales += max(0, $cashNet);
                    $cardSalesFromDb += (float) $sale->card_amount;
                }
            }

            // Expected cash in drawer = opening float + net cash collected from retail sales
            $expectedCash = (float) $shift->opening_float + $cashNetSales;
            $variance = $countedCash - $expectedCash;

            // Card total: use cardSalesFromDb if sales exist, otherwise use card slips count provided
            $finalCardTotal = $cardSalesFromDb > 0 ? $cardSalesFromDb : $cardTotal;

            // Determine variance status based on threshold
            $varianceStatus = 'BALANCED';
            if (abs($variance) > $varianceThreshold) {
                $varianceStatus = 'FLAGGED';
            } elseif ($variance > 0) {
                $varianceStatus = 'OVER';
            } elseif ($variance < 0) {
                $varianceStatus = 'SHORT';
            }

            $shift->update([
                'closed_at' => now(),
                'expected_cash' => $expectedCash,
                'counted_cash' => $countedCash,
                'card_total' => $finalCardTotal,
                'variance' => $variance,
                'variance_status' => $varianceStatus,
                'status' => $varianceStatus === 'FLAGGED' ? 'CLOSED' : 'RECONCILED',
                'notes' => $notes ? trim($shift->notes . "\nClosing Notes: " . $notes) : $shift->notes,
            ]);

            return $shift->fresh();
        });
    }

    /**
     * Supervisor approval workflow for flagged shifts.
     *
     * @throws Exception
     */
    public function approve(CashierShift $shift, User $approver, ?string $notes = null): CashierShift
    {
        if ($shift->variance_status !== 'FLAGGED') {
            throw new Exception("Only shifts flagged for variance require supervisor approval.");
        }

        $shift->update([
            'approved_by' => $approver->user_id,
            'approved_at' => now(),
            'variance_status' => 'APPROVED',
            'status' => 'RECONCILED',
            'notes' => $notes ? trim($shift->notes . "\nSupervisor Approval: " . $notes) : $shift->notes,
        ]);

        return $shift->fresh();
    }

    /**
     * Supervisor rejection workflow for flagged shifts.
     *
     * @throws Exception
     */
    public function reject(CashierShift $shift, User $approver, ?string $notes = null): CashierShift
    {
        if ($shift->variance_status !== 'FLAGGED') {
            throw new Exception("Only shifts flagged for variance require supervisor review.");
        }

        $shift->update([
            'approved_by' => $approver->user_id,
            'approved_at' => now(),
            'variance_status' => 'REJECTED',
            'status' => 'FLAGGED',
            'notes' => $notes ? trim($shift->notes . "\nSupervisor Rejection: " . $notes) : $shift->notes,
        ]);

        return $shift->fresh();
    }

    /**
     * Get active shift for a given user.
     */
    public function getCurrentShift(User $user): ?CashierShift
    {
        return CashierShift::where('user_id', $user->user_id)
            ->where('status', 'OPEN')
            ->first();
    }
}
