<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\SalesInvoice;
use App\Models\SalesOrder;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class CreditService
{
    /**
     * Get customer's current total outstanding amount:
     * sum(POSTED/PARTIALLY_PAID invoice balances) + open confirmed SO value not yet invoiced.
     */
    public function getOutstandingBalance(Customer $customer): float
    {
        // 1. Outstanding balances on posted/partially paid invoices
        $invoiceBalances = (float) SalesInvoice::where('customer_id', $customer->customer_id)
            ->whereIn('status', ['POSTED', 'PARTIALLY_PAID'])
            ->selectRaw('SUM(grand_total - amount_paid) as outstanding')
            ->value('outstanding') ?? 0.0;

        // 2. Open confirmed sales orders not yet invoiced
        $openOrders = SalesOrder::where('customer_id', $customer->customer_id)
            ->whereIn('status', ['CONFIRMED', 'PARTIALLY_DISPATCHED', 'DISPATCHED'])
            ->with(['invoices' => function ($q) {
                $q->where('status', '!=', 'VOIDED');
            }])
            ->get();

        $openSoUninvoiced = 0.0;
        foreach ($openOrders as $so) {
            $invoicedTotal = (float) $so->invoices->sum('grand_total');
            $openSoUninvoiced += max(0.0, (float) $so->grand_total - $invoicedTotal);
        }

        return round($invoiceBalances + $openSoUninvoiced, 3);
    }

    /**
     * Check if customer has any invoice overdue beyond configurable grace days.
     */
    public function hasOverdueInvoices(Customer $customer): bool
    {
        $graceDays = (int) config('rmsc.credit_grace_days', 0);
        $today = now()->startOfDay();

        $overdueInvoices = SalesInvoice::where('customer_id', $customer->customer_id)
            ->whereIn('status', ['POSTED', 'PARTIALLY_PAID'])
            ->whereRaw('(grand_total - amount_paid) > 0.001')
            ->get();

        foreach ($overdueInvoices as $invoice) {
            $dueDateWithGrace = $invoice->due_date->copy()->addDays($graceDays)->startOfDay();
            if ($today->isAfter($dueDateWithGrace)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Perform credit check for an order amount.
     *
     * @param Customer $customer
     * @param float $newAmount
     * @return array
     */
    public function check(Customer $customer, float $newAmount): array
    {
        $creditLimit = $customer->getEffectiveCreditLimit();
        $outstanding = $this->getOutstandingBalance($customer);
        $available = max(0.0, $creditLimit - $outstanding);
        $isOverdue = $this->hasOverdueInvoices($customer);
        $isOnHold = ($customer->credit_status === 'ON_HOLD');

        $blockedReasons = [];

        if ($isOnHold) {
            $blockedReasons[] = 'Customer account is ON HOLD.';
        }

        if ($isOverdue) {
            $blockedReasons[] = 'Customer has overdue invoices beyond the allowed grace period.';
        }

        if (($outstanding + $newAmount) > ($creditLimit + 0.001)) {
            $excess = round(($outstanding + $newAmount) - $creditLimit, 3);
            $blockedReasons[] = "Credit limit exceeded by {$excess} OMR (Limit: {$creditLimit} OMR, Outstanding + New: " . round($outstanding + $newAmount, 3) . " OMR).";
        }

        $allowed = empty($blockedReasons);

        return [
            'allowed' => $allowed,
            'reason' => $allowed ? null : implode(' ', $blockedReasons),
            'credit_limit' => $creditLimit,
            'outstanding' => $outstanding,
            'available' => $available,
            'overdue' => $isOverdue,
            'on_hold' => $isOnHold,
            'new_amount' => $newAmount,
        ];
    }

    /**
     * Override credit check for a SalesOrder by an authorized user with reason.
     */
    public function override(SalesOrder $order, User $user, string $reason): SalesOrder
    {
        $canApprove = $user->hasRole('Super Admin')
            || $user->hasRole('HQ Admin')
            || $user->hasPermission('Retail & Wholesale Sales', 'APPROVE');

        if (! $canApprove) {
            throw new Exception('Unauthorized: Only users with Retail & Wholesale Sales APPROVE permission can override credit limit blocks.');
        }

        if (trim($reason) === '') {
            throw new Exception('A valid reason is required to override credit limit blocks.');
        }

        return DB::transaction(function () use ($order, $user, $reason) {
            $order->credit_check_status = 'OVERRIDDEN';
            $order->credit_override_by = $user->user_id;
            $order->credit_override_at = now();
            $order->credit_override_reason = $reason;
            $order->save();

            AuditLog::create([
                'user_id' => $user->user_id,
                'module_name' => 'Retail & Wholesale Sales',
                'action' => 'CREDIT_OVERRIDE',
                'record_table' => 'sales_orders',
                'record_id' => $order->order_id,
                'details_json' => [
                    'so_no' => $order->so_no,
                    'customer_id' => $order->customer_id,
                    'grand_total' => $order->grand_total,
                    'override_reason' => $reason,
                    'overridden_by' => $user->full_name,
                ],
                'ip_address' => request()->ip(),
            ]);

            return $order;
        });
    }
}
