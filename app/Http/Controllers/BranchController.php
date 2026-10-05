<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBranchRequest;
use App\Http\Requests\UpdateBranchRequest;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Company;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Branch::class);

        $user = auth()->user();

        $query = Branch::with(['company', 'counters', 'warehouses']);

        // Non-super-admin users see only their assigned branches
        if (! $user->hasRole('Super Admin')) {
            $accessibleBranchIds = $user->branches->pluck('branch_id')->push($user->default_branch_id)->filter();
            $query->whereIn('branch_id', $accessibleBranchIds);
        }

        if ($search = $request->input('search')) {
            $query->where(function($q) use ($search) {
                $q->where('branch_name', 'like', "%{$search}%")
                  ->orWhere('branch_code', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%");
            });
        }

        $branches = $query->orderBy('branch_code')->paginate(10);

        return view('branches.index', compact('branches'));
    }

    public function create()
    {
        $this->authorize('create', Branch::class);

        $companies = Company::all();
        $nextId = (Branch::max('branch_id') ?? 0) + 1;
        $suggestedCode = 'BR-' . str_pad((string)$nextId, 3, '0', STR_PAD_LEFT);

        return view('branches.create', compact('companies', 'suggestedCode'));
    }

    public function store(StoreBranchRequest $request)
    {
        $validated = $request->validated();

        $branch = Branch::create($validated);

        AuditLog::create([
            'user_id' => auth()->id(),
            'module_name' => 'Branch & Shift Operations',
            'action' => 'CREATE',
            'record_table' => 'branches',
            'record_id' => $branch->branch_id,
            'details_json' => ['code' => $branch->branch_code, 'name' => $branch->branch_name],
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('system.branches.index')->with('success', 'Branch created successfully.');
    }

    public function edit(Branch $branch)
    {
        $this->authorize('update', $branch);

        $companies = Company::all();

        return view('branches.edit', compact('branch', 'companies'));
    }

    public function update(UpdateBranchRequest $request, Branch $branch)
    {
        $validated = $request->validated();

        $branch->update($validated);

        AuditLog::create([
            'user_id' => auth()->id(),
            'module_name' => 'Branch & Shift Operations',
            'action' => 'UPDATE',
            'record_table' => 'branches',
            'record_id' => $branch->branch_id,
            'details_json' => ['code' => $branch->branch_code, 'name' => $branch->branch_name],
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('system.branches.index')->with('success', 'Branch updated successfully.');
    }

    public function toggleStatus(Branch $branch)
    {
        $this->authorize('update', $branch);

        $branch->is_active = ! $branch->is_active;
        $branch->save();

        AuditLog::create([
            'user_id' => auth()->id(),
            'action_type' => 'TOGGLE_STATUS',
            'entity_type' => 'Branch',
            'entity_id' => $branch->branch_id,
            'details_json' => json_encode(['is_active' => $branch->is_active]),
            'ip_address' => request()->ip(),
        ]);

        return redirect()->back()->with('success', "Branch status updated to " . ($branch->is_active ? 'Active' : 'Inactive') . ".");
    }
}
