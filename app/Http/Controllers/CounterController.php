<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCounterRequest;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Counter;
use Illuminate\Http\Request;

class CounterController extends Controller
{
    public function store(StoreCounterRequest $request)
    {
        $validated = $request->validated();

        $counter = Counter::create($validated);

        AuditLog::create([
            'user_id' => auth()->id(),
            'module_name' => 'Branch & Shift Operations',
            'action' => 'CREATE',
            'record_table' => 'counters',
            'record_id' => $counter->counter_id,
            'details_json' => ['code' => $counter->counter_code, 'name' => $counter->counter_name],
            'ip_address' => $request->ip(),
        ]);

        return redirect()->back()->with('success', 'POS Counter added successfully.');
    }

    public function toggleStatus(Counter $counter)
    {
        $this->authorize('update', $counter);

        $counter->is_active = ! $counter->is_active;
        $counter->save();

        AuditLog::create([
            'user_id' => auth()->id(),
            'module_name' => 'Branch & Shift Operations',
            'action' => 'UPDATE',
            'record_table' => 'counters',
            'record_id' => $counter->counter_id,
            'details_json' => ['is_active' => $counter->is_active],
            'ip_address' => request()->ip(),
        ]);

        return redirect()->back()->with('success', "Counter status updated to " . ($counter->is_active ? 'Active' : 'Inactive') . ".");
    }
}
