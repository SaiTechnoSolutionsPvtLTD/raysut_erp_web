<div x-data="{ open: @entangle('isModalOpen'), deleteOpen: @entangle('isDeleteModalOpen') }" class="space-y-6">
    <!-- Header Banner -->
    <div class="flex items-center justify-between gap-4 pb-4 border-b border-slate-200/80">
        <div>
            <h1 class="font-heading font-bold text-2xl text-slate-900 leading-tight">User Accounts & Branch Access</h1>
            <p class="text-xs text-slate-500 mt-1">Staff logins, assigned roles, default branch, and multi-branch operational access</p>
        </div>
        @if(auth()->user()?->hasPermission('System, Security & Setup', 'CREATE'))
        <button wire:click="create" @click="open = true" type="button" class="px-4 py-2 bg-rmsc-red hover:bg-rmsc-red-dark text-white font-semibold text-xs rounded-xl shadow-md shadow-rmsc-red/20 transition flex items-center space-x-2 shrink-0 cursor-pointer">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>Create User Account</span>
        </button>
        @endif
    </div>

    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                    <tr>
                        <th class="px-6 py-4">User</th>
                        <th class="px-6 py-4">Username / Code</th>
                        <th class="px-6 py-4">Role</th>
                        <th class="px-6 py-4">Default Branch</th>
                        <th class="px-6 py-4">Status</th>
                        @if(auth()->user()?->hasPermission('System, Security & Setup', 'EDIT') || auth()->user()?->hasPermission('System, Security & Setup', 'DELETE'))
                        <th class="px-6 py-4 text-right">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($users as $usr)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 rounded-full bg-slate-800 text-white flex items-center justify-center font-bold text-xs">
                                        {{ strtoupper(substr($usr->full_name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <p class="font-bold text-slate-900 text-sm">{{ $usr->full_name }}</p>
                                        <p class="text-xs text-slate-500">{{ $usr->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 font-mono text-xs text-slate-700 font-semibold">{{ $usr->username }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-purple-50 text-purple-700 border border-purple-200">
                                    {{ $usr->roles->first()?->role_name ?? 'No Role' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-slate-600 font-medium">{{ $usr->defaultBranch?->branch_name ?? 'Central Pool' }}</td>
                            <td class="px-6 py-4">
                                @if($usr->is_active)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                        Inactive
                                    </span>
                                @endif
                            </td>
                            @if(auth()->user()?->hasPermission('System, Security & Setup', 'EDIT') || auth()->user()?->hasPermission('System, Security & Setup', 'DELETE'))
                            <td class="px-6 py-4 text-right space-x-2">
                                @if(auth()->user()?->hasPermission('System, Security & Setup', 'EDIT'))
                                <button wire:click="edit({{ $usr->user_id }})" @click="open = true" class="p-1.5 text-slate-500 hover:text-rmsc-red hover:bg-red-50 rounded-lg transition" title="Edit User">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                @endif
                                @if(auth()->user()?->hasPermission('System, Security & Setup', 'DELETE'))
                                <button type="button" @click="deleteOpen = true" wire:click="confirmDelete({{ $usr->user_id }})" class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition" title="Delete User">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                                @endif
                            </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-slate-400">No user accounts registered. Click "Create User Account" above.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Edit/Create User Modal -->
    <template x-teleport="body">
        <div x-show="open" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
             style="display: none;">
            <div @click.away="open = false; $wire.set('isModalOpen', false)" 
                 class="bg-white rounded-2xl max-w-xl w-full shadow-2xl border border-slate-200 overflow-hidden transform transition-all relative">
                <div class="px-6 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between rounded-t-2xl">
                    <h3 class="font-bold text-slate-900 text-base">{{ $editingUserId ? 'Edit User Account' : 'Create User Account' }}</h3>
                    <button type="button" @click="open = false; $wire.set('isModalOpen', false)" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form wire:submit.prevent="save" class="p-6 space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Username *</label>
                            <input type="text" wire:model="username" class="w-full rounded-xl border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-sm font-mono" placeholder="e.g. ahmed_salim">
                            @error('username') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Full Name *</label>
                            <input type="text" wire:model="full_name" class="w-full rounded-xl border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-sm" placeholder="e.g. Ahmed Al-Salimi">
                            @error('full_name') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Email Address</label>
                            <input type="email" wire:model="email" class="w-full rounded-xl border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-sm" placeholder="e.g. ahmed@rmsc.om">
                            @error('email') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div x-data="{ showPassword: false }">
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Password {{ $editingUserId ? '(Leave blank to keep)' : '*' }}</label>
                            <div class="relative">
                                <input type="password" x-bind:type="showPassword ? 'text' : 'password'" wire:model="password" class="w-full pr-10 rounded-xl border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-sm" placeholder="••••••••">
                                <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 focus:outline-hidden">
                                    <svg x-show="!showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    <svg x-show="showPassword" class="w-4 h-4 text-rmsc-red" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858-5.908a10.018 10.018 0 013.842-.813c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21M3 3l18 18"/></svg>
                                </button>
                            </div>
                            @error('password') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Default Branch</label>
                            <select wire:model="default_branch_id" class="w-full rounded-xl border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-sm">
                                <option value="">-- Central / All Branches --</option>
                                @foreach($branches as $b)
                                    <option value="{{ $b->branch_id }}">{{ $b->branch_name }} ({{ $b->branch_code }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Language Preference</label>
                            <select wire:model="preferred_language" class="w-full rounded-xl border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-sm">
                                <option value="EN">English (EN)</option>
                                <option value="AR">Arabic (AR)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Roles Selection -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Assign System Roles</label>
                        <div class="grid grid-cols-2 gap-2 bg-slate-50 p-3 rounded-xl border border-slate-200">
                            @foreach($roles as $r)
                                <label class="inline-flex items-center text-xs text-slate-800 font-medium cursor-pointer">
                                    <input type="checkbox" wire:model="selectedRoleIds" value="{{ $r->role_id }}" class="rounded border-slate-300 text-rmsc-red focus:ring-rmsc-red mr-2">
                                    {{ $r->role_name }}
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- Branch Access Selection -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Operational Branch Access Rights</label>
                        <div class="grid grid-cols-2 gap-2 bg-slate-50 p-3 rounded-xl border border-slate-200">
                            @foreach($branches as $b)
                                <label class="inline-flex items-center text-xs text-slate-800 font-medium cursor-pointer">
                                    <input type="checkbox" wire:model="selectedBranchIds" value="{{ $b->branch_id }}" class="rounded border-slate-300 text-rmsc-red focus:ring-rmsc-red mr-2">
                                    {{ $b->branch_name }} ({{ $b->city }})
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="checkbox" wire:model="is_active" class="rounded border-slate-300 text-rmsc-red focus:ring-rmsc-red">
                            <span class="ml-2 text-xs font-semibold text-slate-700">Active Account Status</span>
                        </label>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex justify-end space-x-3">
                        <button type="button" @click="open = false; $wire.set('isModalOpen', false)" class="px-4 py-2 border border-slate-200 text-slate-700 font-semibold text-xs rounded-xl hover:bg-slate-50 transition">Cancel</button>
                        <button type="submit" class="px-5 py-2 bg-rmsc-red hover:bg-rmsc-red-dark text-white font-semibold text-xs rounded-xl shadow-md transition">Save User Account</button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <!-- Delete Confirmation Modal -->
    <template x-teleport="body">
        <div x-show="deleteOpen" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
             style="display: none;">
            <div @click.away="deleteOpen = false; $wire.set('isDeleteModalOpen', false)" 
                 class="bg-white rounded-2xl w-full shadow-2xl border border-slate-200 overflow-hidden transform transition-all p-6 text-center mx-auto"
                 style="max-width: 440px;">
                
                <div class="w-14 h-14 bg-rose-50 border border-rose-100 rounded-full flex items-center justify-center mx-auto mb-4 text-rose-600 shadow-inner">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </div>

                <h3 class="font-bold text-slate-900 text-lg">Delete User Confirmation</h3>
                <p class="text-xs text-slate-500 mt-2 leading-relaxed">
                    Are you sure you want to delete account for <span class="font-bold text-slate-800" x-text="$wire.deletingUserName || 'this user'"></span>? This action cannot be reversed and will remove login access.
                </p>

                <div class="mt-6 flex items-center justify-center space-x-3">
                    <button type="button" @click="deleteOpen = false; $wire.set('isDeleteModalOpen', false)" 
                            class="px-4 py-2.5 border border-slate-200 text-slate-700 font-semibold text-xs rounded-xl hover:bg-slate-50 transition w-full">
                        Cancel
                    </button>
                    <button type="button" wire:click="deleteUser" 
                            class="px-4 py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-semibold text-xs rounded-xl shadow-md shadow-rose-600/20 transition w-full">
                        Yes, Delete User
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>
