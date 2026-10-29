<?php

namespace App\Http\Controllers\BranchOperations;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function switchBranch(Request $request)
    {
        $user = auth()->user();
        $isHqOrSuperAdmin = $user->hasRole('Super Admin') || $user->hasRole('HQ Admin');

        if (! $isHqOrSuperAdmin) {
            abort(403, 'Unauthorized: Branch users are locked to their assigned branch and cannot switch.');
        }

        $branchId = $request->input('branch_id');

        if ($branchId === 'all' || empty($branchId)) {
            session()->forget('active_branch_id');
        } else {
            $branch = Branch::findOrFail($branchId);
            session()->put('active_branch_id', $branch->branch_id);
        }

        return redirect()->back()->with('success', 'Active branch switched successfully.');
    }
}
