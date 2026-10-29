<div x-data="{ open: @entangle('isModalOpen'), deleteOpen: @entangle('isDeleteModalOpen') }" class="space-y-6">
    <!-- Header Banner -->
    <div class="flex items-center justify-between gap-4 pb-4 border-b border-slate-200/80">
        <div>
            <h1 class="font-heading font-bold text-2xl text-slate-900 leading-tight">Roles & Permissions Catalogue</h1>
            <p class="text-xs text-slate-500 mt-1">Granular RBAC role matrices across all 13 ERP modules</p>
        </div>
        @if(auth()->user()?->hasPermission('System, Security & Setup', 'CREATE'))
        <button wire:click="create" @click="open = true" type="button" class="px-4 py-2 bg-rmsc-red hover:bg-rmsc-red-dark text-white font-semibold text-xs rounded-xl shadow-md shadow-rmsc-red/20 transition flex items-center space-x-2 shrink-0 cursor-pointer">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>Create New Role</span>
        </button>
        @endif
    </div>

    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                    <tr>
                        <th class="px-6 py-4">Role Name</th>
                        <th class="px-6 py-4">Description</th>
                        <th class="px-6 py-4">Assigned Permissions</th>
                        @if(auth()->user()?->hasPermission('System, Security & Setup', 'EDIT') || auth()->user()?->hasPermission('System, Security & Setup', 'DELETE'))
                        <th class="px-6 py-4 text-right">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($roles as $role)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="px-6 py-4 font-bold text-slate-900">{{ $role->role_name }}</td>
                            <td class="px-6 py-4 text-slate-600 text-xs">{{ $role->description ?? 'N/A' }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                    {{ $role->permissions->count() }} Permissions
                                </span>
                            </td>
                            @if(auth()->user()?->hasPermission('System, Security & Setup', 'EDIT') || auth()->user()?->hasPermission('System, Security & Setup', 'DELETE'))
                            <td class="px-6 py-4 text-right space-x-2">
                                @if(auth()->user()?->hasPermission('System, Security & Setup', 'EDIT'))
                                <button wire:click="edit({{ $role->role_id }})" @click="open = true" class="p-1.5 text-slate-500 hover:text-rmsc-red hover:bg-red-50 rounded-lg transition" title="Edit Role">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                @endif
                                @if(auth()->user()?->hasPermission('System, Security & Setup', 'DELETE'))
                                <button type="button" @click="deleteOpen = true" wire:click="confirmDelete({{ $role->role_id }})" class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition" title="Delete Role">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                                @endif
                            </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-slate-400">No system roles defined. Click "Create New Role" above.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Edit/Create Role Modal -->
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
                 class="bg-white rounded-2xl max-w-2xl w-full mx-auto shadow-2xl border border-slate-200 overflow-hidden transform transition-all relative"
                 style="max-width: 672px;">
                <div class="px-6 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between rounded-t-2xl">
                    <h3 class="font-bold text-slate-900 text-base">{{ $editingRoleId ? 'Edit Role' : 'Create New Role' }}</h3>
                    <button type="button" @click="open = false; $wire.set('isModalOpen', false)" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form wire:submit.prevent="save" class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Role Name *</label>
                        <input type="text" wire:model="role_name" class="w-full rounded-xl border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-sm font-semibold" placeholder="e.g. Warehouse Inventory Supervisor">
                        @error('role_name') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Description</label>
                        <input type="text" wire:model="description" class="w-full rounded-xl border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-sm" placeholder="e.g. Manages warehouse stock receipts and transfers">
                    </div>

                    <!-- Permissions Matrix Grouped by Module -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Granular Module Permissions Catalogue</label>
                        <div class="max-h-96 overflow-y-auto space-y-4 p-4 bg-slate-50 rounded-xl border border-slate-200">
                            @foreach($permissionsGrouped as $moduleName => $pList)
                                <div class="bg-white p-3 rounded-lg border border-slate-200 shadow-2xs">
                                    <div class="font-bold text-xs text-slate-800 uppercase tracking-wider mb-2 border-b border-slate-100 pb-1 flex items-center justify-between">
                                        <span>{{ $moduleName }}</span>
                                        <span class="text-[10px] text-slate-400 font-normal">({{ count($pList) }} actions)</span>
                                    </div>
                                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                        @foreach($pList as $perm)
                                            <label class="inline-flex items-center text-xs text-slate-700 font-medium cursor-pointer">
                                                <input type="checkbox" wire:model="selectedPermissionIds" value="{{ $perm->permission_id }}" class="rounded border-slate-300 text-rmsc-red focus:ring-rmsc-red mr-1.5">
                                                <span>{{ $perm->action_code }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex justify-end space-x-3">
                        <button type="button" @click="open = false; $wire.set('isModalOpen', false)" class="px-4 py-2 border border-slate-200 text-slate-700 font-semibold text-xs rounded-xl hover:bg-slate-50 transition">Cancel</button>
                        <button type="submit" class="px-5 py-2 bg-rmsc-red hover:bg-rmsc-red-dark text-white font-semibold text-xs rounded-xl shadow-md transition">Save Role & Permissions</button>
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

                <h3 class="font-bold text-slate-900 text-lg">Delete Role Confirmation</h3>
                <p class="text-xs text-slate-500 mt-2 leading-relaxed">
                    Are you sure you want to delete <span class="font-bold text-slate-800" x-text="$wire.deletingRoleName || 'this role'"></span>? This action cannot be reversed and will revoke permissions assigned to this role.
                </p>

                <div class="mt-6 flex items-center justify-center space-x-3">
                    <button type="button" @click="deleteOpen = false; $wire.set('isDeleteModalOpen', false)" 
                            class="px-4 py-2.5 border border-slate-200 text-slate-700 font-semibold text-xs rounded-xl hover:bg-slate-50 transition w-full">
                        Cancel
                    </button>
                    <button type="button" wire:click="deleteRole" 
                            class="px-4 py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-semibold text-xs rounded-xl shadow-md shadow-rose-600/20 transition w-full">
                        Yes, Delete Role
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>
