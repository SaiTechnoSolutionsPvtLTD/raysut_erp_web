<div>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-heading font-bold text-2xl text-slate-900 leading-tight">Immutable System Audit Trail</h2>
                <p class="text-xs text-slate-500 mt-1">Real-time tamper-proof record of user actions across all ERP modules</p>
            </div>
            <span class="px-3 py-1 bg-slate-800 text-white rounded-full text-xs font-mono font-bold">
                Auto Logged
            </span>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Search & Filters Header -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex flex-wrap gap-4 items-center justify-between">
            <div class="flex items-center space-x-3 flex-1 min-w-[300px]">
                <div class="relative flex-1">
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search by user, module, table or ID..." class="w-full pl-9 pr-4 py-2 rounded-xl border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-xs">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <input type="text" wire:model.live.debounce.300ms="searchModule" placeholder="Filter by module..." class="w-44 py-2 rounded-xl border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-xs">
                <select wire:model.live="searchAction" class="py-2 rounded-xl border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-xs">
                    <option value="">All Actions</option>
                    <option value="CREATE">CREATE</option>
                    <option value="UPDATE">UPDATE</option>
                    <option value="DELETE">DELETE</option>
                </select>
            </div>
            <div class="text-xs text-slate-500 font-medium flex items-center space-x-2">
                <span>Total Recorded Events:</span>
                <span class="px-2.5 py-0.5 rounded-full bg-slate-100 font-bold text-slate-800 font-mono border border-slate-200">{{ $logs->total() }}</span>
            </div>
        </div>

        <!-- Audit Log Activity Table -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-4 w-16">ID</th>
                            <th class="px-6 py-4">Activity Description</th>
                            <th class="px-6 py-4">Performed By</th>
                            <th class="px-6 py-4">Action</th>
                            <th class="px-6 py-4">Date & Time</th>
                            <th class="px-6 py-4">Payload Details</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        @forelse($logs as $log)
                            @php
                                $userName = $log->user?->full_name ?? 'System Seeder';
                                $tableName = \Illuminate\Support\Str::headline(\Illuminate\Support\Str::singular($log->record_table));
                                $actionWord = match($log->action) {
                                    'CREATE' => 'created new',
                                    'UPDATE' => 'updated',
                                    'DELETE' => 'deleted',
                                    default => strtolower($log->action)
                                };
                                $recordRef = $log->record_id ? "#{$log->record_id}" : '';
                                $formattedDate = $log->created_at ? $log->created_at->format('Y-m-d H:i:s') : 'N/A';
                                $readableDate = $log->created_at ? $log->created_at->format('M d, Y \a\t h:i:s A') : '';
                            @endphp
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="px-6 py-4 font-mono font-bold text-slate-400">#{{ $log->audit_id }}</td>
                                
                                <td class="px-6 py-4">
                                    <div class="font-medium text-slate-900 text-xs leading-snug">
                                        <span class="font-bold text-slate-900">{{ $userName }}</span>
                                        <span class="text-slate-600">has {{ $actionWord }}</span>
                                        <span class="font-bold text-slate-800">{{ $tableName }}</span>
                                        @if($recordRef)
                                            <span class="font-mono text-slate-500 font-semibold">({{ $recordRef }})</span>
                                        @endif
                                        <span class="text-slate-500">at</span>
                                        <span class="font-mono text-slate-700 font-semibold">{{ $formattedDate }}</span>
                                    </div>
                                    <div class="text-[11px] text-slate-400 mt-0.5 flex items-center space-x-2">
                                        <span>Target: <strong class="text-slate-600 font-mono">{{ $log->record_table }}</strong></span>
                                        <span>•</span>
                                        <span>{{ $readableDate }}</span>
                                    </div>
                                </td>

                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-2.5">
                                        <div class="w-7 h-7 rounded-full bg-slate-200 text-slate-700 font-bold flex items-center justify-center text-xs shrink-0">
                                            {{ strtoupper(substr($userName, 0, 1)) }}
                                        </div>
                                        <div>
                                            <div class="font-bold text-slate-800">{{ $userName }}</div>
                                            <div class="text-[11px] text-slate-400 font-mono">{{ $log->user?->username ?? 'system' }}</div>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-4">
                                    @if($log->action === 'CREATE')
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">CREATE</span>
                                    @elseif($log->action === 'UPDATE')
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">UPDATE</span>
                                    @else
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-red-100 text-rmsc-red border border-red-200">DELETE</span>
                                    @endif
                                </td>

                                <td class="px-6 py-4 font-mono text-xs text-slate-700 whitespace-nowrap">
                                    <div class="font-bold text-slate-800">{{ $formattedDate }}</div>
                                    <div class="text-[10px] text-slate-400 font-sans">{{ $log->created_at?->diffForHumans() }}</div>
                                </td>

                                <td class="px-6 py-4 max-w-xs truncate font-mono text-[11px] text-slate-500" title="{{ is_array($log->details_json) ? json_encode($log->details_json, JSON_PRETTY_PRINT) : $log->details_json }}">
                                    <code class="bg-slate-50 px-2 py-1 rounded border border-slate-200/60 block truncate">
                                        {{ is_array($log->details_json) ? json_encode($log->details_json) : $log->details_json }}
                                    </code>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-slate-400 font-sans">
                                    <div class="max-w-xs mx-auto space-y-2">
                                        <svg class="w-8 h-8 text-slate-300 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        <p class="text-xs text-slate-500">No audit events match your search criteria.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-slate-100">
                {{ $logs->links() }}
            </div>
        </div>
    </div>
</div>
