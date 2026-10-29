<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Counter;
use App\Models\PermitVerification;
use App\Models\Product;
use App\Models\RetailSale;
use App\Models\RetailSaleItem;
use App\Models\StockBatch;
use App\Models\StockLedger;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Services\Permit\PermitVerificationService;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PosCheckoutService
{
    protected ShiftService $shiftService;
    protected StockLedgerService $stockLedgerService;
    protected PermitVerificationService $permitVerificationService;
    protected ?PermitService $permitService;

    public function __construct(
        ShiftService $shiftService,
        StockLedgerService $stockLedgerService,
        PermitVerificationService $permitVerificationService,
        ?PermitService $permitService = null
    ) {
        $this->shiftService = $shiftService;
        $this->stockLedgerService = $stockLedgerService;
        $this->permitVerificationService = $permitVerificationService;
        $this->permitService = $permitService;
    }

    /**
     * Generate sequential receipt_no under a transaction with pessimistic locking
     * on a per-branch-per-day counter row: RS-{BRANCH}-{YYYYMMDD}-{seq}.
     */
    public function generateReceiptNumber(int $branchId, ?string $branchCode = null): string
    {
        $code = str_replace('BR-', '', $branchCode ?: 'HQ');
        $todayDate = now()->toDateString();
        $todayStr = now()->format('Ymd');

        // Ensure daily branch sequence row exists
        DB::table('receipt_sequences')->insertOrIgnore([
            'branch_id' => $branchId,
            'sequence_date' => $todayDate,
            'last_sequence' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Lock for update to ensure concurrency safety
        $seqRow = DB::table('receipt_sequences')
            ->where('branch_id', $branchId)
            ->where('sequence_date', $todayDate)
            ->lockForUpdate()
            ->first();

        $nextSeq = ($seqRow ? (int) $seqRow->last_sequence : 0) + 1;

        DB::table('receipt_sequences')
            ->where('id', $seqRow->id)
            ->update([
                'last_sequence' => $nextSeq,
                'updated_at' => now(),
            ]);

        $receiptNo = sprintf("RS-%s-%s-%04d", $code, $todayStr, $nextSeq);

        // Fallback retry loop if duplicate somehow exists
        while (RetailSale::where('receipt_no', $receiptNo)->exists()) {
            $nextSeq++;
            DB::table('receipt_sequences')
                ->where('id', $seqRow->id)
                ->update([
                    'last_sequence' => $nextSeq,
                    'updated_at' => now(),
                ]);
            $receiptNo = sprintf("RS-%s-%s-%04d", $code, $todayStr, $nextSeq);
        }

        return $receiptNo;
    }

    /**
     * Process POS sale checkout in a single atomic database transaction.
     *
     * @throws Exception|ValidationException
     */
    public function checkout(
        User $user,
        Counter $counter,
        array $cart,
        array $payment,
        mixed $civilIdOrPermit = null,
        ?array $permitData = null
    ): RetailSale {
        if (empty($cart)) {
            throw new Exception("Cart is empty. Please add products before checking out.");
        }

        return DB::transaction(function () use ($user, $counter, $cart, $payment, $civilIdOrPermit, $permitData) {
            // a. Require open shift
            $shift = $this->shiftService->getCurrentShift($user);
            if (! $shift) {
                // If super admin override, find any open shift for counter or throw
                if ($user->hasRole('Super Admin')) {
                    $shift = \App\Models\CashierShift::where('counter_id', $counter->counter_id)
                        ->where('status', 'OPEN')
                        ->first();
                }

                if (! $shift) {
                    throw new Exception("An active cashier shift is required to perform POS checkout.");
                }
            }

            // b & c. Resolve line items, pricing tiers, VAT from master data, and permit requirements
            $preparedLines = [];
            $subtotal = 0.000;
            $vatTotal = 0.000;
            $permitControlledTotal = 0.000;

            foreach ($cart as $item) {
                $productId = (int) ($item['product_id'] ?? $item['id']);
                $qty = (float) ($item['quantity'] ?? $item['qty'] ?? 1);

                if ($qty <= 0) {
                    throw new Exception("Product quantity must be greater than zero.");
                }

                $product = Product::with(['taxRate', 'uom', 'pricingTiers', 'category'])
                    ->findOrFail($productId);

                // Price tier resolution: RETAIL tier takes precedence
                $unitPrice = null;
                if (! empty($item['unit_price'])) {
                    $unitPrice = (float) $item['unit_price'];
                } else {
                    $retailTier = $product->pricingTiers->where('price_tier', 'RETAIL')->first();
                    if ($retailTier) {
                        $unitPrice = (float) $retailTier->unit_price;
                    } elseif ($firstTier = $product->pricingTiers->first()) {
                        $unitPrice = (float) $firstTier->unit_price;
                    } else {
                        $unitPrice = 1.000;
                    }
                }

                // Dynamic VAT from master data
                $vatRate = (float) ($product->taxRate?->rate_percent ?? 0.000);
                $lineSubtotal = round($qty * $unitPrice, 3);
                $lineVat = round($lineSubtotal * ($vatRate / 100), 3);
                $lineTotal = $lineSubtotal + $lineVat;

                $subtotal += $lineSubtotal;
                $vatTotal += $lineVat;

                // Permit requirement check: controlled lines only, VAT-inclusive
                $isControlled = $product->requiresPermit();
                if ($isControlled) {
                    $permitControlledTotal += $lineTotal;
                }

                $preparedLines[] = [
                    'product' => $product,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'vat_rate' => $vatRate,
                    'vat_amount' => $lineVat,
                    'line_total' => $lineTotal,
                    'requested_batch_id' => $item['batch_id'] ?? null,
                    'is_controlled' => $isControlled,
                ];
            }

            $grandTotal = round($subtotal + $vatTotal, 3);

            // c. Permit verification handling (Manual entry or Screenshot extraction)
            $validatedPermit = null;
            if ($permitControlledTotal > 0) {
                // Determine permit payload
                $payload = $permitData;
                if (! $payload && is_array($civilIdOrPermit)) {
                    $payload = $civilIdOrPermit;
                }

                $civilIdInput = is_string($civilIdOrPermit) ? trim($civilIdOrPermit) : ($payload['civil_id'] ?? null);

                if (! empty($payload)) {
                    $validatedPermit = $this->permitVerificationService->validatePayload($payload, $permitControlledTotal);
                } elseif (! empty($civilIdInput)) {
                    // Check for shift-level reuse
                    $reusable = $this->permitVerificationService->findReusableVerification(
                        $civilIdInput,
                        $shift->shift_id,
                        $permitControlledTotal
                    );

                    if ($reusable) {
                        $validatedPermit = [
                            'civil_id' => $civilIdInput,
                            'holder_name' => $reusable->holder_name,
                            'permit_no' => $reusable->permit_no,
                            'annual_limit' => $reusable->annual_limit,
                            'used_amount' => $reusable->used_amount,
                            'remaining_amount' => $reusable->remaining_amount,
                            'valid_from' => $reusable->valid_from?->format('Y-m-d'),
                            'valid_to' => $reusable->valid_to?->format('Y-m-d'),
                            'verified_at' => $reusable->verified_at?->format('Y-m-d H:i:s') ?? now()->format('Y-m-d H:i:s'),
                            'entry_method' => $reusable->entry_method,
                            'screenshot_path' => $reusable->screenshot_path,
                            'extraction_confidence' => $reusable->extraction_confidence,
                            'extraction_raw' => $reusable->extraction_raw,
                            'fields_edited_after_extraction' => $reusable->fields_edited_after_extraction,
                            'attested' => true,
                        ];
                    } else {
                        throw ValidationException::withMessages([
                            'permit' => ['A valid attested Oman permit verification is required to purchase permit-controlled items.'],
                            'permit_verification' => ['A valid attested Oman permit verification is required to purchase permit-controlled items.'],
                        ]);
                    }
                } else {
                    throw ValidationException::withMessages([
                        'permit' => ['A valid Oman Civil ID / Liquor Permit verification is required to purchase permit-controlled items.'],
                        'permit_verification' => ['A valid Oman Civil ID / Liquor Permit verification is required to purchase permit-controlled items.'],
                    ]);
                }
            }

            // d. Deduct stock via StockLedgerService::deductSaleStock(..., 'RETAIL')
            $warehouse = Warehouse::where('branch_id', $counter->branch_id)
                ->where('is_active', true)
                ->where('warehouse_type', '!=', 'DUTY_FREE_BONDED')
                ->first();

            if (! $warehouse) {
                $warehouse = Warehouse::where('branch_id', $counter->branch_id)
                    ->where('is_active', true)
                    ->first();
            }

            if (! $warehouse) {
                $warehouse = Warehouse::where('is_active', true)->firstOrFail();
            }

            // e. Validate payment
            $method = strtoupper($payment['method'] ?? 'CASH');
            $cashAmount = 0.000;
            $cardAmount = 0.000;
            $cardRef = $payment['card_reference'] ?? null;
            $changeGiven = 0.000;

            if ($method === 'CASH') {
                $cashAmount = (float) ($payment['cash_amount'] ?? $payment['amount'] ?? 0);
                if ($cashAmount < $grandTotal) {
                    throw new Exception(sprintf(
                        "Insufficient cash tendered! Grand total: OMR %.3f, Cash tendered: OMR %.3f",
                        $grandTotal,
                        $cashAmount
                    ));
                }
                $changeGiven = round($cashAmount - $grandTotal, 3);
            } elseif ($method === 'CARD') {
                $cardAmount = (float) ($payment['card_amount'] ?? $payment['amount'] ?? 0);
                if (abs($cardAmount - $grandTotal) > 0.001) {
                    throw new Exception(sprintf(
                        "Card payment must exactly match grand total of OMR %.3f. Tendered: OMR %.3f",
                        $grandTotal,
                        $cardAmount
                    ));
                }
                $changeGiven = 0.000;
            } elseif ($method === 'SPLIT') {
                $cashAmount = (float) ($payment['cash_amount'] ?? 0);
                $cardAmount = (float) ($payment['card_amount'] ?? 0);
                $totalPaid = $cashAmount + $cardAmount;

                if ($totalPaid < $grandTotal) {
                    throw new Exception(sprintf(
                        "Split payment underpaid! Grand total: OMR %.3f, Total Tendered: OMR %.3f",
                        $grandTotal,
                        $totalPaid
                    ));
                }
                $changeGiven = round($totalPaid - $grandTotal, 3);
            } else {
                throw new Exception("Unsupported payment method '{$method}'. Must be CASH, CARD, or SPLIT.");
            }

            // Generate sequential receipt_no
            $receiptNo = $this->generateReceiptNumber(
                $counter->branch_id,
                $counter->branch->branch_code ?? 'HQ'
            );

            // f. Create retail_sale inside transaction
            $customerCivilId = $validatedPermit ? $validatedPermit['civil_id'] : (is_string($civilIdOrPermit) ? trim($civilIdOrPermit) : null);

            $sale = RetailSale::create([
                'receipt_no' => $receiptNo,
                'branch_id' => $counter->branch_id,
                'counter_id' => $counter->counter_id,
                'cashier_shift_id' => $shift->shift_id,
                'user_id' => $user->user_id,
                'oman_permit_id' => null,
                'customer_civil_id' => $customerCivilId,
                'subtotal' => $subtotal,
                'vat_total' => $vatTotal,
                'grand_total' => $grandTotal,
                'payment_method' => $method,
                'cash_amount' => $cashAmount,
                'card_amount' => $cardAmount,
                'card_reference' => $cardRef,
                'change_given' => $changeGiven,
                'status' => 'COMPLETED',
            ]);

            // d. Deduct stock via StockLedgerService::deductSaleStock(..., 'RETAIL') with reference linking
            foreach ($preparedLines as $line) {
                $prod = $line['product'];
                $qtyNeeded = $line['quantity'];

                if (! empty($line['requested_batch_id'])) {
                    // Specific batch requested
                    $batch = StockBatch::findOrFail($line['requested_batch_id']);
                    if ($batch->duty_status !== 'DUTY_PAID') {
                        throw new Exception("Batch #{$batch->batch_number} is duty-suspended ({$batch->duty_status}). Retail POS sales may only deduct duty-paid stock.");
                    }

                    $this->stockLedgerService->deductSaleStock(
                        $warehouse->warehouse_id,
                        $prod->product_id,
                        $batch->batch_id,
                        $qtyNeeded,
                        'RETAIL',
                        false,
                        [
                            'reference_type' => 'retail_sale',
                            'reference_id' => $sale->id,
                            'reference_no' => $sale->receipt_no,
                            'branch_id' => $counter->branch_id,
                            'user_id' => $user->user_id,
                        ]
                    );

                    RetailSaleItem::create([
                        'retail_sale_id' => $sale->id,
                        'product_id' => $prod->product_id,
                        'batch_id' => $batch->batch_id,
                        'uom_id' => $prod->base_uom_id,
                        'quantity' => $qtyNeeded,
                        'unit_price' => $line['unit_price'],
                        'vat_rate' => $line['vat_rate'],
                        'vat_amount' => $line['vat_amount'],
                        'line_total' => $line['line_total'],
                    ]);
                } else {
                    // FEFO resolution: Find duty-paid batches ordered by expiry_date ASC, batch_id ASC
                    // Note: lockForUpdate() ensures row-level locking on MySQL during batch allocation
                    $stocks = WarehouseStock::with('batch')
                        ->where('warehouse_stock.warehouse_id', $warehouse->warehouse_id)
                        ->where('warehouse_stock.product_id', $prod->product_id)
                        ->where('warehouse_stock.quantity_available', '>', 0)
                        ->whereHas('batch', fn($q) => $q->where('duty_status', 'DUTY_PAID'))
                        ->join('stock_batches', 'warehouse_stock.batch_id', '=', 'stock_batches.batch_id')
                        ->orderByRaw('CASE WHEN stock_batches.expiry_date IS NULL THEN 1 ELSE 0 END, stock_batches.expiry_date ASC, stock_batches.batch_id ASC')
                        ->select('warehouse_stock.*')
                        ->lockForUpdate()
                        ->get();

                    $totalAvailable = $stocks->sum('quantity_available');
                    if ($totalAvailable < $qtyNeeded) {
                        throw new Exception("Insufficient duty-paid stock on hand for '{$prod->product_name}' in warehouse '{$warehouse->warehouse_name}'. Required: {$qtyNeeded}, Available: {$totalAvailable}. Sale aborted.");
                    }

                    $remainingToDeduct = $qtyNeeded;
                    foreach ($stocks as $stk) {
                        if ($remainingToDeduct <= 0) break;

                        $take = min($remainingToDeduct, (float) $stk->quantity_available);
                        $this->stockLedgerService->deductSaleStock(
                            $warehouse->warehouse_id,
                            $prod->product_id,
                            $stk->batch_id,
                            $take,
                            'RETAIL',
                            false,
                            [
                                'reference_type' => 'retail_sale',
                                'reference_id' => $sale->id,
                                'reference_no' => $sale->receipt_no,
                                'branch_id' => $counter->branch_id,
                                'user_id' => $user->user_id,
                            ]
                        );

                        $portion = $take / $qtyNeeded;
                        RetailSaleItem::create([
                            'retail_sale_id' => $sale->id,
                            'product_id' => $prod->product_id,
                            'batch_id' => $stk->batch_id,
                            'uom_id' => $prod->base_uom_id,
                            'quantity' => $take,
                            'unit_price' => $line['unit_price'],
                            'vat_rate' => $line['vat_rate'],
                            'vat_amount' => round($line['vat_amount'] * $portion, 3),
                            'line_total' => round($line['line_total'] * $portion, 3),
                        ]);

                        $remainingToDeduct -= $take;
                    }
                }
            }

            // g. Create PermitVerification record linked to this sale
            if ($validatedPermit && $permitControlledTotal > 0) {
                $screenshotPath = null;
                if (! empty($validatedPermit['screenshot_token'])) {
                    $cached = Cache::get("permit_token_{$validatedPermit['screenshot_token']}");
                    if ($cached && isset($cached['relative_path'])) {
                        $screenshotPath = $cached['relative_path'];
                    }
                }
                if (! $screenshotPath && ! empty($validatedPermit['screenshot_path'])) {
                    $screenshotPath = $validatedPermit['screenshot_path'];
                }

                $verification = PermitVerification::create([
                    'retail_sale_id' => $sale->id,
                    'branch_id' => $counter->branch_id,
                    'counter_id' => $counter->counter_id,
                    'cashier_shift_id' => $shift->shift_id,
                    'entered_by' => $user->user_id,
                    'civil_id' => $validatedPermit['civil_id'],
                    'holder_name' => $validatedPermit['holder_name'],
                    'permit_no' => $validatedPermit['permit_no'] ?? null,
                    'annual_limit' => $validatedPermit['annual_limit'] ?? null,
                    'used_amount' => $validatedPermit['used_amount'] ?? null,
                    'remaining_amount' => $validatedPermit['remaining_amount'] ?? null,
                    'valid_from' => $validatedPermit['valid_from'] ?? null,
                    'valid_to' => $validatedPermit['valid_to'] ?? null,
                    'verified_at' => $validatedPermit['verified_at'] ?? now(),
                    'entry_method' => $validatedPermit['entry_method'] ?? 'MANUAL',
                    'screenshot_path' => $screenshotPath,
                    'extraction_confidence' => $validatedPermit['extraction_confidence'] ?? null,
                    'extraction_raw' => $validatedPermit['extraction_raw'] ?? null,
                    'fields_edited_after_extraction' => $validatedPermit['fields_edited_after_extraction'] ?? null,
                    'controlled_total' => $permitControlledTotal,
                    'status' => 'USED',
                    'attested_at' => now(),
                    'attested_by' => $user->user_id,
                    'attestation_text' => 'I have verified these details on the Oman government website',
                ]);

                // Audit log for permit verification created
                AuditLog::create([
                    'user_id' => $user->user_id,
                    'module_name' => 'Retail & Wholesale Sales',
                    'action' => 'PERMIT_VERIFICATION_CREATED',
                    'record_table' => 'permit_verifications',
                    'record_id' => $verification->id,
                    'details_json' => [
                        'verification_id' => $verification->id,
                        'entry_method' => $verification->entry_method,
                        'confidence' => $verification->extraction_confidence,
                        'civil_id' => $verification->masked_civil_id,
                        'receipt_no' => $sale->receipt_no,
                        'controlled_total' => $verification->controlled_total,
                    ],
                    'ip_address' => request()->ip(),
                ]);

                if (! empty($validatedPermit['fields_edited_after_extraction']) && is_array($validatedPermit['fields_edited_after_extraction'])) {
                    AuditLog::create([
                        'user_id' => $user->user_id,
                        'module_name' => 'Retail & Wholesale Sales',
                        'action' => 'PERMIT_FIELDS_EDITED',
                        'record_table' => 'permit_verifications',
                        'record_id' => $verification->id,
                        'details_json' => [
                            'verification_id' => $verification->id,
                            'edited_fields' => array_values($validatedPermit['fields_edited_after_extraction']),
                            'civil_id' => $verification->masked_civil_id,
                        ],
                        'ip_address' => request()->ip(),
                    ]);
                }
            }

            // Sale checkout audit log (masked Civil ID)
            AuditLog::create([
                'user_id' => $user->user_id,
                'module_name' => 'Retail & Wholesale Sales',
                'action' => 'SALE_CHECKOUT',
                'record_table' => 'retail_sales',
                'record_id' => $sale->id,
                'details_json' => [
                    'receipt_no' => $sale->receipt_no,
                    'grand_total' => $sale->grand_total,
                    'payment_method' => $sale->payment_method,
                    'civil_id' => $sale->masked_civil_id,
                ],
                'ip_address' => request()->ip(),
            ]);

            return $sale->fresh(['items.product', 'items.batch', 'items.uom', 'permitVerification', 'counter', 'branch', 'cashier']);
        });
    }

    /**
     * Void a retail sale in one atomic transaction.
     * Reverses stock back to the original batches.
     * Marks linked PermitVerification as VOIDED (retaining screenshot for audit).
     *
     * @throws Exception
     */
    public function void(RetailSale $sale, string $reason, ?User $user = null): RetailSale
    {
        if (empty(trim($reason))) {
            throw new Exception("A void reason is required.");
        }

        $user = $user ?? auth()->user();
        if (! $user) {
            throw new Exception("Authentication required to void a sale.");
        }

        // Supervisor authorization check
        $isSupervisor = $user->hasRole('Super Admin')
            || $user->hasRole('Supervisor')
            || $user->hasRole('Branch Admin')
            || $user->hasRole('HQ Admin')
            || $user->hasPermission('Retail & Wholesale Sales', 'APPROVE')
            || $user->hasPermission('Branch & Shift Operations', 'APPROVE')
            || $user->can('void', $sale);

        if (! $isSupervisor) {
            throw new Exception("Unauthorized. Sale voiding requires supervisor or administrative authorization.");
        }

        return DB::transaction(function () use ($sale, $reason, $user) {
            $sale = RetailSale::with(['items.product', 'cashierShift', 'permitVerification'])
                ->where('id', $sale->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($sale->status === 'VOIDED' || StockLedger::where('reference_type', 'retail_sale')->where('reference_id', $sale->id)->where('movement_type', 'SALES_RETAIL_VOID')->exists()) {
                throw new Exception("Sale #{$sale->receipt_no} has already been voided.");
            }

            // Block voiding in a closed, reconciled or approved shift
            $shift = $sale->cashierShift;
            if ($shift && in_array($shift->status, ['CLOSED', 'APPROVED', 'RECONCILED'])) {
                throw new Exception("Cannot void sale #{$sale->receipt_no}: Cashier shift #{$shift->shift_id} is {$shift->status}. Voids are only permitted while the shift is OPEN.");
            }

            // Resolve branch warehouse for stock reversal
            $warehouse = Warehouse::where('branch_id', $sale->branch_id)
                ->where('is_active', true)
                ->where('warehouse_type', '!=', 'DUTY_FREE_BONDED')
                ->first();

            if (! $warehouse) {
                $warehouse = Warehouse::where('branch_id', $sale->branch_id)
                    ->where('is_active', true)
                    ->first();
            }

            if (! $warehouse) {
                $warehouse = Warehouse::where('is_active', true)->firstOrFail();
            }

            // Reverse stock deductions back to the same batches
            foreach ($sale->items as $item) {
                $this->stockLedgerService->reverseSaleStock(
                    $warehouse->warehouse_id,
                    $item->product_id,
                    $item->batch_id,
                    (float) $item->quantity,
                    'SALES_RETAIL_VOID',
                    [
                        'reference_type' => 'retail_sale',
                        'reference_id' => $sale->id,
                        'reference_no' => $sale->receipt_no,
                        'branch_id' => $sale->branch_id,
                        'user_id' => $user->user_id,
                    ]
                );
            }

            // Mark linked permit verification as VOIDED (preserving screenshot for audit)
            $verification = PermitVerification::where('retail_sale_id', $sale->id)->first();
            if ($verification) {
                $verification->status = 'VOIDED';
                $verification->save();
            }

            // Update sale status to VOIDED
            $sale->status = 'VOIDED';
            $sale->save();

            // Audit log with masked Civil ID
            AuditLog::create([
                'user_id' => $user->user_id,
                'module_name' => 'Retail & Wholesale Sales',
                'action' => 'SALE_VOID',
                'record_table' => 'retail_sales',
                'record_id' => $sale->id,
                'details_json' => [
                    'receipt_no' => $sale->receipt_no,
                    'reason' => $reason,
                    'voided_by' => $user->user_id,
                    'branch_id' => $sale->branch_id,
                    'grand_total' => $sale->grand_total,
                    'civil_id' => $sale->masked_civil_id,
                ],
                'ip_address' => request()->ip(),
            ]);

            return $sale->fresh(['items.product', 'items.batch', 'items.uom', 'permitVerification', 'counter', 'branch', 'cashier']);
        });
    }
}
