<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-heading font-bold text-2xl text-slate-900 leading-tight">RMSC Enterprise Core — System Dashboard</h2>
                <p class="text-xs text-slate-500 mt-1">Raysut Marketing & Supply Co. LLC (RMSC) Monolith ERP System</p>
            </div>
            <div class="flex items-center space-x-3">
                <span class="px-3 py-1 bg-emerald-500/10 text-emerald-700 text-xs font-bold rounded-full border border-emerald-200 flex items-center">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 mr-2 animate-ping"></span>
                    Phase 1 Operational
                </span>
            </div>
        </div>
    </x-slot>

    <div class="space-y-8">
        <!-- Quick Stats Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Stat 1: Branches -->
            <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs hover:shadow-md transition">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Branches</span>
                    <div class="w-9 h-9 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center font-bold">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                </div>
                <div class="font-heading font-extrabold text-3xl text-slate-900">
                    {{ \App\Models\Branch::count() }}
                </div>
                <div class="text-xs text-slate-500 mt-2">Muscat, Salalah & Sohar</div>
            </div>

            <!-- Stat 2: Warehouses -->
            <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs hover:shadow-md transition">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Bonded Facilities</span>
                    <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/></svg>
                    </div>
                </div>
                <div class="font-heading font-extrabold text-3xl text-slate-900">
                    {{ \App\Models\Warehouse::count() }}
                </div>
                <div class="text-xs text-amber-700 font-medium mt-2">Customs Bonded Depots</div>
            </div>

            <!-- Stat 3: Users -->
            <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs hover:shadow-md transition">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Active Staff Accounts</span>
                    <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    </div>
                </div>
                <div class="font-heading font-extrabold text-3xl text-slate-900">
                    {{ \App\Models\User::count() }}
                </div>
                <div class="text-xs text-slate-500 mt-2">With RBAC Permissions</div>
            </div>

            <!-- Stat 4: Audit Entries -->
            <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs hover:shadow-md transition">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Audit Log Trail</span>
                    <div class="w-9 h-9 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center font-bold">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                </div>
                <div class="font-heading font-extrabold text-3xl text-slate-900">
                    {{ \App\Models\AuditLog::count() }}
                </div>
                <div class="text-xs text-slate-500 mt-2">Immutable Action Trail</div>
            </div>
        </div>

        <!-- Named Facilities Showcase -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="font-heading font-bold text-lg text-slate-900">RMSC Named Core Facilities & Depots</h3>
                    <p class="text-xs text-slate-500">Key bonded and duty-paid infrastructure across Sultanate of Oman</p>
                </div>
                <a href="{{ route('system.warehouses') }}" class="text-xs font-bold text-rmsc-red hover:underline">Manage All Facilities →</a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @foreach(\App\Models\Warehouse::with('branch')->get() as $wh)
                    <div class="p-5 rounded-xl border border-slate-200 bg-slate-50/50 flex flex-col justify-between space-y-4 hover:border-slate-300 transition">
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-slate-200 text-slate-700">{{ $wh->warehouse_code }}</span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">{{ $wh->warehouse_type }}</span>
                            </div>
                            <h4 class="font-bold text-slate-900 text-sm">{{ $wh->warehouse_name }}</h4>
                            <p class="text-xs text-slate-500 mt-1">Bond Ref: <span class="font-mono text-slate-700 font-semibold">{{ $wh->customs_bond_ref }}</span></p>
                        </div>
                        <div class="pt-3 border-t border-slate-200/60 flex items-center justify-between text-xs text-slate-600">
                            <span>Branch: <strong class="text-slate-800">{{ $wh->branch?->branch_name ?? 'Central Pool' }}</strong></span>
                            <span class="text-emerald-600 font-semibold">Active</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Quick Access Module Matrix -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs">
            <h3 class="font-heading font-bold text-lg text-slate-900 mb-4">Module 1 — System, Security & Setup Overview</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                <a href="{{ route('system.company') }}" class="p-4 rounded-xl border border-slate-200 hover:border-rmsc-red hover:bg-red-50/30 transition block group">
                    <div class="font-bold text-slate-900 text-sm group-hover:text-rmsc-red">1. Company Profile</div>
                    <div class="text-xs text-slate-500 mt-1">Legal entity, CR/liquor licenses, OMR base currency</div>
                </a>
                <a href="{{ route('system.branches') }}" class="p-4 rounded-xl border border-slate-200 hover:border-rmsc-red hover:bg-red-50/30 transition block group">
                    <div class="font-bold text-slate-900 text-sm group-hover:text-rmsc-red">2. Branches</div>
                    <div class="text-xs text-slate-500 mt-1">Muscat, Salalah, Sohar physical branch depots</div>
                </a>
                <a href="{{ route('system.warehouses') }}" class="p-4 rounded-xl border border-slate-200 hover:border-rmsc-red hover:bg-red-50/30 transition block group">
                    <div class="font-bold text-slate-900 text-sm group-hover:text-rmsc-red">3. Warehouses</div>
                    <div class="text-xs text-slate-500 mt-1">Muscat Central Bonded, Salalah Depot, Sohar Terminal</div>
                </a>
                <a href="{{ route('system.counters') }}" class="p-4 rounded-xl border border-slate-200 hover:border-rmsc-red hover:bg-red-50/30 transition block group">
                    <div class="font-bold text-slate-900 text-sm group-hover:text-rmsc-red">4. POS Counters</div>
                    <div class="text-xs text-slate-500 mt-1">Cashier billing terminals & shift counters</div>
                </a>
                <a href="{{ route('system.users') }}" class="p-4 rounded-xl border border-slate-200 hover:border-rmsc-red hover:bg-red-50/30 transition block group">
                    <div class="font-bold text-slate-900 text-sm group-hover:text-rmsc-red">5. Users & Access</div>
                    <div class="text-xs text-slate-500 mt-1">Staff accounts, default branch & multi-branch access</div>
                </a>
                <a href="{{ route('system.roles') }}" class="p-4 rounded-xl border border-slate-200 hover:border-rmsc-red hover:bg-red-50/30 transition block group">
                    <div class="font-bold text-slate-900 text-sm group-hover:text-rmsc-red">6. Roles & Permissions</div>
                    <div class="text-xs text-slate-500 mt-1">Granular RBAC catalogue across all ERP modules</div>
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
