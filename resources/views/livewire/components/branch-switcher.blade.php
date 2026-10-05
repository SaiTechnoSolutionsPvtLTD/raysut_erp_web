<div>
    @if($isHqOrSuperAdmin)
        <div class="relative flex items-center gap-2 bg-slate-100 dark:bg-slate-800 p-1.5 rounded-xl border border-slate-200 dark:border-slate-700">
            <svg class="w-4 h-4 text-slate-500 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0h4m-4 0V9a2 2 0 012-2h2a2 2 0 012 2v12" />
            </svg>
            <select wire:change="switchBranch($event.target.value)" class="text-xs font-semibold bg-transparent border-0 focus:ring-0 text-slate-700 dark:text-slate-200 cursor-pointer pr-8 py-1">
                <option value="all" {{ is_null($selectedBranchId) ? 'selected' : '' }}>🏢 All Branches (HQ View)</option>
                @foreach($branches as $b)
                    <option value="{{ $b->branch_id }}" {{ $selectedBranchId == $b->branch_id ? 'selected' : '' }}>
                        📍 {{ $b->branch_code }} - {{ $b->branch_name }}
                    </option>
                @endforeach
            </select>
        </div>
    @elseif(auth()->user()?->defaultBranch)
        <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-slate-100 text-slate-700 rounded-xl text-xs font-semibold border border-slate-200">
            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
            <span>Branch: {{ auth()->user()->defaultBranch->branch_name }} ({{ auth()->user()->defaultBranch->branch_code }})</span>
        </div>
    @endif
</div>
