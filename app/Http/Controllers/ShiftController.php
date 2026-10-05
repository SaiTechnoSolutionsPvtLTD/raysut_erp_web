<?php

namespace App\Http\Controllers;

use App\Http\Requests\CloseShiftRequest;
use App\Http\Requests\OpenShiftRequest;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\CashierShift;
use App\Models\Counter;
use App\Services\ShiftService;
use Exception;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    protected ShiftService $shiftService;

    public function __construct(ShiftService $shiftService)
    {
        $this->shiftService = $shiftService;
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', CashierShift::class);

        $user = auth()->user();
        $query = CashierShift::with(['user', 'counter', 'branch', 'approver']);

        if (! $user->hasRole('Super Admin') && ! $user->hasRole('HQ Admin')) {
            $query->where('branch_id', $user->default_branch_id);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($varianceStatus = $request->input('variance_status')) {
            $query->where('variance_status', $varianceStatus);
        }

        $shifts = $query->orderBy('opened_at', 'desc')->paginate(15);
        $currentShift = $this->shiftService->getCurrentShift($user);

        return view('shifts.index', compact('shifts', 'currentShift'));
    }

    public function showOpenForm()
    {
        $user = auth()->user();
        $currentShift = $this->shiftService->getCurrentShift($user);

        if ($currentShift) {
            return redirect()->route('shifts.show-close', $currentShift->shift_id)
                ->with('info', 'You already have an active open shift. Please close it when your shift ends.');
        }

        $branchId = $user->default_branch_id;
        $counters = Counter::where('is_active', true)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->get();

        return view('shifts.open', compact('counters'));
    }

    public function open(OpenShiftRequest $request)
    {
        $user = auth()->user();
        $validated = $request->validated();

        try {
            $shift = $this->shiftService->open(
                $user,
                (int) $validated['counter_id'],
                (float) $validated['opening_float'],
                $validated['notes'] ?? null
            );

            AuditLog::create([
                'user_id' => $user->user_id,
                'module_name' => 'Branch & Shift Operations',
                'action' => 'SHIFT_OPEN',
                'record_table' => 'cashier_shifts',
                'record_id' => $shift->shift_id,
                'details_json' => ['counter_id' => $shift->counter_id, 'float' => $shift->opening_float],
                'ip_address' => $request->ip(),
            ]);

            return redirect()->route('dashboard')->with('success', "Shift #{$shift->shift_id} opened successfully with float OMR " . number_format($shift->opening_float, 3));
        } catch (Exception $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function showCloseForm(CashierShift $shift)
    {
        $this->authorize('close', $shift);

        return view('shifts.close', compact('shift'));
    }

    public function close(CloseShiftRequest $request, CashierShift $shift)
    {
        $this->authorize('close', $shift);
        $validated = $request->validated();

        try {
            $closedShift = $this->shiftService->close(
                $shift,
                (float) $validated['counted_cash'],
                (float) $validated['card_total'],
                $validated['notes'] ?? null
            );

            AuditLog::create([
                'user_id' => auth()->id(),
                'module_name' => 'Branch & Shift Operations',
                'action' => 'SHIFT_CLOSE',
                'record_table' => 'cashier_shifts',
                'record_id' => $closedShift->shift_id,
                'details_json' => ['counted_cash' => $closedShift->counted_cash, 'variance' => $closedShift->variance, 'variance_status' => $closedShift->variance_status],
                'ip_address' => $request->ip(),
            ]);

            return redirect()->route('shifts.print', $closedShift->shift_id)
                ->with('success', "Shift #{$closedShift->shift_id} closed successfully. Variance: OMR " . number_format($closedShift->variance, 3));
        } catch (Exception $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function approve(Request $request, CashierShift $shift)
    {
        $this->authorize('approve', $shift);

        try {
            $approvedShift = $this->shiftService->approve($shift, auth()->user(), $request->input('notes'));

            AuditLog::create([
                'user_id' => auth()->id(),
                'module_name' => 'Branch & Shift Operations',
                'action' => 'SHIFT_APPROVE',
                'record_table' => 'cashier_shifts',
                'record_id' => $approvedShift->shift_id,
                'details_json' => ['approved_by' => auth()->id(), 'variance' => $approvedShift->variance],
                'ip_address' => $request->ip(),
            ]);

            return redirect()->back()->with('success', "Shift #{$approvedShift->shift_id} variance approved and reconciled successfully.");
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function printSummary(CashierShift $shift)
    {
        $this->authorize('view', $shift);
        $shift->load(['user', 'counter', 'branch', 'approver']);

        return view('shifts.print', compact('shift'));
    }
}
