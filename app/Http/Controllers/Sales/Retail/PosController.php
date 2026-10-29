<?php

namespace App\Http\Controllers\Sales\Retail;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Counter;
use App\Models\PermitVerification;
use App\Models\Product;
use App\Models\RetailSale;
use App\Models\TaxVatRate;
use App\Models\Uom;
use App\Services\Permit\PermitScreenshotExtractor;
use App\Services\Permit\PermitVerificationService;
use App\Services\PosCheckoutService;
use App\Services\ShiftService;
use App\Services\StockLedgerService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PosController extends Controller
{
    protected ShiftService $shiftService;
    protected StockLedgerService $stockLedgerService;

    public function __construct(ShiftService $shiftService, StockLedgerService $stockLedgerService)
    {
        $this->shiftService = $shiftService;
        $this->stockLedgerService = $stockLedgerService;
    }

    public function terminal(Request $request)
    {
        $this->authorize('create', RetailSale::class);

        $user = auth()->user();
        $currentShift = $this->shiftService->getCurrentShift($user);

        // Fetch products with Master Data UOM, Tax Rates, Pricing Tiers, and Category
        $products = Product::with(['uom', 'taxRate', 'pricingTiers', 'category'])
            ->where('is_active', true)
            ->get();

        $taxRates = TaxVatRate::where('is_active', true)->get();
        $uoms = Uom::all();

        return view('sales.retail.pos.terminal', compact('currentShift', 'products', 'taxRates', 'uoms'));
    }

    /**
     * Check if a valid verification already exists in the current shift for this Civil ID.
     */
    public function lookupPermit(Request $request, PermitVerificationService $verificationService)
    {
        $this->authorize('viewAny', PermitVerification::class);

        $civilId = trim((string) $request->input('civil_id'));
        if (empty($civilId)) {
            return response()->json([
                'success' => false,
                'message' => 'Civil ID parameter is required.',
            ], 422);
        }

        $shift = $this->shiftService->getCurrentShift(auth()->user());
        $shiftId = $shift?->shift_id ?? 0;

        $reusable = $verificationService->findReusableVerification($civilId, $shiftId, 0.000);

        if ($reusable) {
            return response()->json([
                'success' => true,
                'found' => true,
                'is_reusable' => true,
                'verification' => [
                    'civil_id' => $reusable->civil_id,
                    'holder_name' => $reusable->holder_name,
                    'permit_no' => $reusable->permit_no,
                    'annual_limit' => (float) $reusable->annual_limit,
                    'used_amount' => (float) $reusable->used_amount,
                    'remaining_amount' => (float) $reusable->remaining_amount,
                    'valid_from' => $reusable->valid_from?->format('Y-m-d'),
                    'valid_to' => $reusable->valid_to?->format('Y-m-d'),
                    'verified_at' => $reusable->verified_at?->format('Y-m-d H:i:s'),
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'found' => false,
            'message' => 'No prior verification found in this shift. Please enter or upload permit details.',
        ]);
    }

    /**
     * Upload screenshot, store on private disk, strip EXIF, and run AI/OCR extraction.
     */
    public function extractPermitScreenshot(
        Request $request,
        PermitVerificationService $verificationService,
        PermitScreenshotExtractor $extractor
    ) {
        $this->authorize('create', RetailSale::class);

        $request->validate([
            'screenshot' => 'required|file|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $user = auth()->user();
        $shift = $this->shiftService->getCurrentShift($user);
        $branchId = $shift?->branch_id ?? $user->default_branch_id ?? 1;

        $file = $request->file('screenshot');

        try {
            // 1. Store securely on private disk & strip EXIF
            $stored = $verificationService->storeScreenshot($file, $branchId);

            // 2. Extract permit data
            $result = $extractor->extract($file);

            // 3. Log audit event
            AuditLog::create([
                'user_id' => $user->user_id,
                'module_name' => 'Retail & Wholesale Sales',
                'action' => 'PERMIT_SCREENSHOT_EXTRACTED',
                'record_table' => 'permit_verifications',
                'record_id' => 0,
                'details_json' => [
                    'entry_method' => 'SCREENSHOT',
                    'confidence' => $result->overallConfidence,
                ],
                'ip_address' => request()->ip(),
            ]);

            $responseData = [
                'fields' => $result->fields,
                'confidences' => $result->confidences,
                'overall_confidence' => $result->overallConfidence,
                'raw_snippet' => $result->rawSnippet,
                'screenshot_token' => $stored['token'],
                'screenshot_path' => $stored['path'],
                'low_confidence_fields' => array_keys(array_filter($result->confidences, fn ($conf) => (float) $conf < (float) config('rmsc.permit_min_confidence', 0.8))),
                'error' => $result->errorMessage,
                'warnings' => $result->warnings,
                'driver' => $result->driver,
            ];

            return response()->json(array_merge([
                'success' => $result->success,
                'data' => $responseData,
            ], $responseData), $result->success ? 200 : 422);
        } catch (ValidationException $ve) {
            return response()->json([
                'success' => false,
                'error' => $ve->getMessage(),
                'errors' => $ve->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Screenshot extraction failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function checkout(Request $request, PosCheckoutService $checkoutService)
    {
        $this->authorize('create', RetailSale::class);

        $validated = $request->validate([
            'cart' => 'required|array|min:1',
            'cart.*.product_id' => 'required|integer|exists:products,product_id',
            'cart.*.quantity' => 'required|numeric|min:0.001',
            'cart.*.batch_id' => 'nullable|integer',
            'payment' => 'required|array',
            'payment.method' => 'required|string|in:CASH,CARD,SPLIT',
            'payment.cash_amount' => 'nullable|numeric|min:0',
            'payment.card_amount' => 'nullable|numeric|min:0',
            'payment.card_reference' => 'nullable|string|max:100',
            'civil_id' => 'nullable|string|max:50',
            'permit_verification' => 'nullable|array',
            'permit' => 'nullable|array',
            'counter_id' => 'nullable|integer|exists:counters,counter_id',
        ]);

        $user = auth()->user();
        $counter = null;

        if (! empty($validated['counter_id'])) {
            $counter = Counter::findOrFail($validated['counter_id']);
        } else {
            $currentShift = $this->shiftService->getCurrentShift($user);
            if ($currentShift) {
                $counter = $currentShift->counter;
            } elseif ($user->hasRole('Super Admin')) {
                $counter = Counter::where('is_active', true)->first();
            }
        }

        if (! $counter) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'No active POS counter associated with this session. Please open a cashier shift.',
                ], 422);
            }

            return redirect()->back()->with('error', 'No active POS counter found.');
        }

        try {
            $permitPayload = $validated['permit_verification'] ?? $validated['permit'] ?? null;
            $civilId = $validated['civil_id'] ?? ($permitPayload['civil_id'] ?? null);

            $sale = $checkoutService->checkout(
                $user,
                $counter,
                $validated['cart'],
                $validated['payment'],
                $civilId,
                $permitPayload
            );

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Sale {$sale->receipt_no} completed successfully.",
                    'sale_id' => $sale->id,
                    'receipt_no' => $sale->receipt_no,
                    'grand_total' => $sale->grand_total,
                    'change_given' => $sale->change_given,
                    'receipt_url' => route('pos.receipt', $sale->id),
                ]);
            }

            return redirect()->route('pos.receipt', $sale->id)
                ->with('message', "Sale {$sale->receipt_no} completed successfully.");
        } catch (ValidationException $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage(),
                    'errors' => $e->errors(),
                ], 422);
            }

            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (Exception $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage(),
                ], 422);
            }

            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function receipt(RetailSale $sale)
    {
        $this->authorize('view', $sale);

        $sale->load(['items.product.taxRate', 'items.batch', 'items.uom', 'permitVerification', 'branch', 'counter', 'cashier']);

        return view('sales.retail.pos.receipt', compact('sale'));
    }

    public function show(RetailSale $sale)
    {
        $this->authorize('view', $sale);

        $sale->load([
            'items.product.taxRate',
            'items.batch',
            'items.uom',
            'permitVerification',
            'branch',
            'counter',
            'cashier',
            'stockLedgerEntries.batch',
            'stockLedgerEntries.warehouse',
            'stockLedgerEntries.user',
        ]);

        return view('sales.retail.show', compact('sale'));
    }

    public function sales(Request $request)
    {
        $this->authorize('viewAny', RetailSale::class);

        $user = auth()->user();
        $query = RetailSale::with(['branch', 'counter', 'cashier', 'permitVerification', 'items.product'])
            ->orderBy('id', 'desc');

        if (! $user->hasRole('Super Admin') && ! $user->hasRole('HQ Admin')) {
            $accessibleBranchIds = $user->branches->pluck('branch_id')->push($user->default_branch_id)->filter();
            $query->whereIn('branch_id', $accessibleBranchIds);
        }

        if ($request->filled('search')) {
            $term = $request->input('search');
            $query->where(function ($q) use ($term) {
                $q->where('receipt_no', 'like', "%{$term}%")
                    ->orWhere('customer_civil_id', 'like', "%{$term}%");
            });
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->input('branch_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $sales = $query->paginate(20);

        return view('sales.retail.pos.sales', compact('sales'));
    }

    public function voidSale(Request $request, RetailSale $sale, PosCheckoutService $checkoutService)
    {
        $this->authorize('void', $sale);

        $validated = $request->validate([
            'reason' => 'required|string|min:3|max:500',
        ]);

        try {
            $voided = $checkoutService->void($sale, $validated['reason'], auth()->user());

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Sale {$voided->receipt_no} has been voided successfully. Stock restored.",
                    'status' => $voided->status,
                ]);
            }

            return redirect()->back()->with('message', "Sale {$voided->receipt_no} has been voided successfully.");
        } catch (Exception $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage(),
                ], 422);
            }

            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
