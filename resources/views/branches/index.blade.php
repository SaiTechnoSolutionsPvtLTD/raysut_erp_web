<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Branch & Counter Operations</h1>
                <p class="text-sm text-slate-500 mt-1">Sprint 3.1: Manage multi-branch facilities, POS counters, and operational statuses.</p>
            </div>
            @can('create', App\Models\Branch::class)
                <div>
                    <a href="{{ route('system.branches.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-rmsc-red hover:bg-rmsc-red-hover text-white text-sm font-semibold rounded-xl shadow-xs transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        + Add New Branch
                    </a>
                </div>
            @endcan
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Search & Filter Bar -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
            <form method="GET" action="{{ route('system.branches.index') }}" class="relative flex-1 max-w-md">
                <svg class="w-5 h-5 absolute left-3.5 top-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search branch code, name, city..." class="w-full pl-10 pr-4 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-rmsc-red">
            </form>
            <div class="flex items-center gap-2 text-xs font-semibold px-3 py-1.5 bg-slate-100 text-slate-700 rounded-lg border border-slate-200">
                <span>Total Active Branches: {{ $branches->total() }}</span>
            </div>
        </div>

        <!-- Branch Table -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-50 text-xs font-bold uppercase text-slate-500 tracking-wider border-b border-slate-100">
                        <tr>
                            <th class="py-3.5 px-5">Code</th>
                            <th class="py-3.5 px-5">Branch Name</th>
                            <th class="py-3.5 px-5">Type</th>
                            <th class="py-3.5 px-5">City</th>
                            <th class="py-3.5 px-5">POS Counters</th>
                            <th class="py-3.5 px-5">Status</th>
                            <th class="py-3.5 px-5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($branches as $branch)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3.5 px-5 font-mono font-bold text-rmsc-navy">{{ $branch->branch_code }}</td>
                                <td class="py-3.5 px-5 font-semibold text-slate-900">{{ $branch->branch_name }}</td>
                                <td class="py-3.5 px-5">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200 uppercase">
                                        {{ $branch->branch_type }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-5 font-medium text-slate-600">{{ $branch->city ?? 'N/A' }}</td>
                                <td class="py-3.5 px-5 font-mono text-xs text-slate-700">
                                    {{ $branch->counters->count() }} Counter(s)
                                </td>
                                <td class="py-3.5 px-5">
                                    <form method="POST" action="{{ route('system.branches.toggle-status', $branch->branch_id) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $branch->is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-500' }}">
                                            <span class="w-1.5 h-1.5 rounded-full {{ $branch->is_active ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                            {{ $branch->is_active ? 'ACTIVE' : 'INACTIVE' }}
                                        </button>
                                    </form>
                                </td>
                                <td class="py-3.5 px-5 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('system.branches.edit', $branch->branch_id) }}" class="p-1.5 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-lg transition" title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-slate-400">No branch records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-slate-100">
                {{ $branches->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
