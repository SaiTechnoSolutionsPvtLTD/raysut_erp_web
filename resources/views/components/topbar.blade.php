<header x-data="{ 
    facilityOpen: false, 
    quickOpen: false, 
    userOpen: false,
    searchModalOpen: false,
    selectedFacility: 'Muscat Central Bonded Warehouse',
    facilities: [
        { name: 'Muscat Central Bonded Warehouse', code: 'MCBW-01', type: 'Customs Bonded', active: true },
        { name: 'Salalah Regional Logistics Depot', code: 'SRLD-02', type: 'Regional Hub', active: false },
        { name: 'Sohar Port Customs Terminal', code: 'SPCT-03', type: 'Duty Paid Pool', active: false }
    ]
}" 
@keydown.window.prevent.cmd.k="searchModalOpen = true"
@keydown.window.prevent.ctrl.k="searchModalOpen = true"
class="sticky top-0 z-30 h-16 bg-white/95 backdrop-blur-md border-b border-slate-200/80 px-4 sm:px-6 flex items-center justify-between shadow-2xs shrink-0 transition-all">

    <!-- Left Section: Mobile Toggle, Facility Switcher & Quick Search -->
    <div class="flex items-center space-x-3 sm:space-x-4">
        <!-- Mobile Sidebar Toggle -->
        <button @click="mobileSidebarOpen = !mobileSidebarOpen" 
                title="Toggle Menu"
                class="lg:hidden p-2 rounded-lg text-slate-500 hover:text-slate-800 hover:bg-slate-100 focus:outline-none transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>

        <!-- Facility Switcher Dropdown -->
        <div class="relative">
            <button @click="facilityOpen = !facilityOpen" @click.away="facilityOpen = false"
                    class="group flex items-center space-x-2.5 bg-slate-50 hover:bg-slate-100 px-3 py-1.5 rounded-xl border border-slate-200/90 text-xs transition duration-150 focus:outline-none focus:ring-2 focus:ring-rmsc-red/20">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse shrink-0"></span>
                <div class="flex items-center space-x-1.5 min-w-0">
                    <span class="hidden xl:inline text-slate-500 font-medium">Facility:</span>
                    <span class="font-bold text-slate-800 truncate max-w-[140px] sm:max-w-[200px]" x-text="selectedFacility">Muscat Central Bonded Warehouse</span>
                </div>
                <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-slate-600 transition-transform duration-200" :class="facilityOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <!-- Facility Dropdown Panel -->
            <div x-show="facilityOpen" 
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                 x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                 class="absolute left-0 mt-2 w-72 bg-white rounded-2xl shadow-xl border border-slate-200/90 p-2 z-50 text-xs">
                <div class="px-3 py-2 border-b border-slate-100 mb-1">
                    <p class="font-bold text-slate-800">Switch Active Facility</p>
                    <p class="text-[10px] text-slate-400">Select active inventory & duty pool context</p>
                </div>
                <template x-for="fac in facilities" :key="fac.code">
                    <button @click="selectedFacility = fac.name; facilityOpen = false" 
                            class="w-full text-left px-3 py-2.5 rounded-xl hover:bg-slate-50 flex items-center justify-between transition group">
                        <div>
                            <div class="flex items-center space-x-2">
                                <span class="font-semibold text-slate-800 group-hover:text-rmsc-red" x-text="fac.name"></span>
                            </div>
                            <div class="flex items-center space-x-2 mt-0.5">
                                <span class="font-mono text-[10px] text-slate-400 bg-slate-100 px-1.5 py-0.5 rounded" x-text="fac.code"></span>
                                <span class="text-[10px] text-slate-500" x-text="fac.type"></span>
                            </div>
                        </div>
                        <svg x-show="selectedFacility === fac.name" class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                        </svg>
                    </button>
                </template>
            </div>
        </div>

        <!-- Global Search Button -->
        <button @click="searchModalOpen = true" 
                class="hidden md:flex items-center space-x-2 bg-slate-100/80 hover:bg-slate-100 text-slate-500 hover:text-slate-700 px-3 py-1.5 rounded-xl border border-slate-200/80 text-xs transition duration-150 group">
            <svg class="w-4 h-4 text-slate-400 group-hover:text-rmsc-red transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <span class="font-medium">Search SKUs, POs, Partners...</span>
            <kbd class="hidden lg:inline-block px-1.5 py-0.5 text-[10px] font-mono font-semibold bg-white text-slate-400 border border-slate-200 rounded-md shadow-2xs">Ctrl K</kbd>
        </button>
    </div>

    <!-- Right Section: Status Pills, Quick Actions, Audit & Profile Menu -->
    <div class="flex items-center space-x-3 sm:space-x-5">
        <!-- Quick Action "+ New" Button Dropdown -->
        <div class="relative">
            <button @click="quickOpen = !quickOpen" @click.away="quickOpen = false"
                    title="Quick Action Menu"
                    class="flex items-center space-x-1.5 bg-rmsc-red hover:bg-rmsc-red-dark text-white px-3 py-1.5 rounded-xl font-medium text-xs shadow-xs transition duration-150 focus:outline-none">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                </svg>
                <span class="hidden sm:inline font-bold">New</span>
                <svg class="w-3 h-3 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <!-- Quick Action Dropdown Menu -->
            <div x-show="quickOpen" 
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                 x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                 class="absolute right-0 mt-2 w-56 bg-white rounded-2xl shadow-xl border border-slate-200/90 p-2 z-50 text-xs">
                <div class="px-3 py-1.5 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Quick Action</div>
                <a href="{{ route('purchase-import.purchase-orders') }}" class="flex items-center space-x-2 px-3 py-2 rounded-xl text-slate-700 hover:bg-slate-50 hover:text-rmsc-red font-medium transition">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span>New Purchase Order</span>
                </a>
                <a href="{{ route('purchase-import.grns') }}" class="flex items-center space-x-2 px-3 py-2 rounded-xl text-slate-700 hover:bg-slate-50 hover:text-rmsc-red font-medium transition">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    <span>Create Goods Receipt</span>
                </a>
                <a href="{{ route('master-data.products') }}" class="flex items-center space-x-2 px-3 py-2 rounded-xl text-slate-700 hover:bg-slate-50 hover:text-rmsc-red font-medium transition">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    <span>Add New Product SKU</span>
                </a>
                <a href="{{ route('warehouse-inventory.transfers') }}" class="flex items-center space-x-2 px-3 py-2 rounded-xl text-slate-700 hover:bg-slate-50 hover:text-rmsc-red font-medium transition">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                    <span>Transfer Stock</span>
                </a>
            </div>
        </div>

        <!-- Quick Audit Trail Shortcut -->
        <a href="{{ route('system.audit-logs') }}" title="Audit Logs & Activity Trail" 
           class="p-2 text-slate-500 hover:text-rmsc-red hover:bg-slate-100 rounded-xl transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </a>

        <!-- User Profile Dropdown Menu -->
        <div class="relative border-l border-slate-200/80 pl-3 sm:pl-4">
            <button @click="userOpen = !userOpen" @click.away="userOpen = false"
                    class="flex items-center space-x-3 focus:outline-none group">
                <div class="w-9 h-9 rounded-full bg-slate-900 text-white flex items-center justify-center font-bold text-xs shadow-xs ring-2 ring-slate-200 group-hover:ring-rmsc-red transition">
                    {{ strtoupper(substr(auth()->user()->full_name ?? 'Admin', 0, 2)) }}
                </div>
                <div class="text-left hidden md:block">
                    <span class="text-xs font-bold text-slate-800 block leading-tight group-hover:text-rmsc-red transition">{{ auth()->user()->full_name ?? 'Super Admin' }}</span>
                    <span class="text-[10px] text-slate-400 block leading-tight font-medium">{{ auth()->user()->roles->first()?->role_name ?? 'Administrator' }}</span>
                </div>
                <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-slate-600 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <!-- Profile Dropdown Panel -->
            <div x-show="userOpen" 
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                 x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                 class="absolute right-0 mt-2 w-64 bg-white rounded-2xl shadow-xl border border-slate-200/90 p-2 z-50 text-xs">
                <!-- User Profile Summary Header -->
                <div class="px-3 py-3 border-b border-slate-100 mb-1 bg-slate-50/60 rounded-xl">
                    <p class="font-bold text-slate-900 truncate">{{ auth()->user()->full_name ?? 'Super Admin' }}</p>
                    <p class="text-[11px] text-slate-500 truncate">{{ auth()->user()->email ?? 'admin@rmsc.om' }}</p>
                    <div class="mt-2 inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-red-50 text-rmsc-red border border-red-100">
                        {{ auth()->user()->roles->first()?->role_name ?? 'Super Admin' }}
                    </div>
                </div>

                <!-- Navigation Links -->
                <a href="{{ route('profile') }}" class="flex items-center space-x-2.5 px-3 py-2 rounded-xl text-slate-700 hover:bg-slate-50 hover:text-rmsc-red font-medium transition">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    <span>My Account Profile</span>
                </a>
                <a href="{{ route('system.company') }}" class="flex items-center space-x-2.5 px-3 py-2 rounded-xl text-slate-700 hover:bg-slate-50 hover:text-rmsc-red font-medium transition">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    <span>Company Configuration</span>
                </a>
                <a href="{{ route('system.audit-logs') }}" class="flex items-center space-x-2.5 px-3 py-2 rounded-xl text-slate-700 hover:bg-slate-50 hover:text-rmsc-red font-medium transition">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span>Audit Trail Log</span>
                </a>

                <!-- Sign Out Action -->
                <div class="pt-1 mt-1 border-t border-slate-100">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full flex items-center space-x-2.5 px-3 py-2 rounded-xl text-rose-600 hover:bg-rose-50 font-medium transition text-left">
                            <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                            <span>Sign Out of ERP</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Global Command Palette / Search Modal -->
    <div x-show="searchModalOpen" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @keydown.escape.window="searchModalOpen = false"
         class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-start justify-center pt-20 px-4" style="display: none;">
        <div @click.away="searchModalOpen = false" 
             class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-xl overflow-hidden transform transition-all">
            <div class="p-4 border-b border-slate-200 flex items-center space-x-3">
                <svg class="w-5 h-5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" placeholder="Type to search SKU, PO number, Customer or Branch..." 
                       class="w-full text-sm border-0 focus:ring-0 focus:outline-none text-slate-800 placeholder-slate-400" autofocus>
                <button @click="searchModalOpen = false" class="text-xs font-semibold text-slate-400 hover:text-slate-600 px-2 py-1 bg-slate-100 rounded-md">ESC</button>
            </div>
            <div class="p-4 max-h-80 overflow-y-auto space-y-2 text-xs">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Quick Shortcuts</p>
                <a href="{{ route('master-data.products') }}" class="flex items-center justify-between p-2.5 rounded-xl hover:bg-slate-50 transition group">
                    <div class="flex items-center space-x-3">
                        <span class="p-1.5 bg-blue-50 text-blue-600 rounded-lg"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg></span>
                        <span class="font-medium text-slate-700 group-hover:text-slate-900">Products & SKU Master Catalog</span>
                    </div>
                    <span class="text-[10px] text-slate-400 font-mono">Module 2</span>
                </a>
                <a href="{{ route('purchase-import.purchase-orders') }}" class="flex items-center justify-between p-2.5 rounded-xl hover:bg-slate-50 transition group">
                    <div class="flex items-center space-x-3">
                        <span class="p-1.5 bg-emerald-50 text-emerald-600 rounded-lg"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></span>
                        <span class="font-medium text-slate-700 group-hover:text-slate-900">Purchase Orders & Customs Bayan</span>
                    </div>
                    <span class="text-[10px] text-slate-400 font-mono">Module 3</span>
                </a>
                <a href="{{ route('warehouse-inventory.stock') }}" class="flex items-center justify-between p-2.5 rounded-xl hover:bg-slate-50 transition group">
                    <div class="flex items-center space-x-3">
                        <span class="p-1.5 bg-purple-50 text-purple-600 rounded-lg"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg></span>
                        <span class="font-medium text-slate-700 group-hover:text-slate-900">Warehouse Stock On Hand</span>
                    </div>
                    <span class="text-[10px] text-slate-400 font-mono">Module 4</span>
                </a>
            </div>
        </div>
    </div>
</header>

