<?php

namespace App\Http\Controllers\BranchOperations;

use App\Http\Controllers\Controller;
use App\Models\CashierShift;

class ShiftController extends Controller
{
    public function printSummary(CashierShift $shift)
    {
        $this->authorize('view', $shift);
        $shift->load(['user', 'counter', 'branch', 'approver']);

        return view('branch-operations.shifts.print', compact('shift'));
    }
}
