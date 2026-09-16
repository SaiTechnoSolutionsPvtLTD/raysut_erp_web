<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:150', 'unique:'.User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $username = strtolower(str_replace(' ', '.', trim($validated['name']))) . rand(10, 99);

        $user = User::create([
            'username' => $username,
            'full_name' => $validated['name'],
            'email' => $validated['email'],
            'password_hash' => Hash::make($validated['password']),
            'is_active' => true,
        ]);

        event(new Registered($user));

        Auth::login($user);

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div class="space-y-6">
    <div class="text-center">
        <div class="inline-flex p-3 rounded-2xl bg-rmsc-red text-white shadow-lg shadow-rmsc-red/30 mb-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M8 22h8" />
                <path d="M12 15v7" />
                <path d="M12 15a7 7 0 0 0 7-7V3H5v5a7 7 0 0 0 7 7Z" />
                <path d="M5 6h14" />
            </svg>
        </div>
        <h2 class="font-heading font-extrabold text-2xl text-slate-900 tracking-wide">RMSC ERP</h2>
        <p class="text-xs text-slate-500 font-medium">Create User Account</p>
    </div>

    <form wire:submit="register" class="space-y-4">
        <!-- Name -->
        <div>
            <label for="name" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Full Name</label>
            <input wire:model="name" id="name" class="w-full rounded-xl border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-sm shadow-2xs" type="text" name="name" required autofocus autocomplete="name" placeholder="e.g. Salim Al Hinai" />
            <x-input-error :messages="$errors->get('name')" class="mt-2 text-xs text-rmsc-red" />
        </div>

        <!-- Email Address -->
        <div>
            <label for="email" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Email Address</label>
            <input wire:model="email" id="email" class="w-full rounded-xl border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-sm shadow-2xs" type="email" name="email" required autocomplete="username" placeholder="e.g. salim@rmsc.om" />
            <x-input-error :messages="$errors->get('email')" class="mt-2 text-xs text-rmsc-red" />
        </div>

        <!-- Password -->
        <div x-data="{ showPassword: false }">
            <label for="password" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Password</label>
            <div class="relative">
                <input wire:model="password" id="password" type="password" x-bind:type="showPassword ? 'text' : 'password'" class="w-full pr-10 rounded-xl border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-sm shadow-2xs"
                                name="password"
                                required autocomplete="new-password" placeholder="••••••••" />
                <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 focus:outline-hidden">
                    <svg x-show="!showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    <svg x-show="showPassword" class="w-4 h-4 text-rmsc-red" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858-5.908a10.018 10.018 0 013.842-.813c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21M3 3l18 18"/></svg>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2 text-xs text-rmsc-red" />
        </div>

        <!-- Confirm Password -->
        <div x-data="{ showConfirmPassword: false }">
            <label for="password_confirmation" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Confirm Password</label>
            <div class="relative">
                <input wire:model="password_confirmation" id="password_confirmation" type="password" x-bind:type="showConfirmPassword ? 'text' : 'password'" class="w-full pr-10 rounded-xl border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-sm shadow-2xs"
                                name="password_confirmation" required autocomplete="new-password" placeholder="••••••••" />
                <button type="button" @click="showConfirmPassword = !showConfirmPassword" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 focus:outline-hidden">
                    <svg x-show="!showConfirmPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    <svg x-show="showConfirmPassword" class="w-4 h-4 text-rmsc-red" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858-5.908a10.018 10.018 0 013.842-.813c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21M3 3l18 18"/></svg>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2 text-xs text-rmsc-red" />
        </div>

        <div class="flex items-center justify-between pt-2">
            <a class="underline text-xs text-slate-600 hover:text-rmsc-red font-medium" href="{{ route('login') }}" wire:navigate>
                {{ __('Already registered?') }}
            </a>

            <button type="submit" class="px-5 py-2.5 bg-rmsc-red hover:bg-rmsc-red-dark text-white font-bold text-xs rounded-xl shadow-md transition">
                Register Account
            </button>
        </div>
    </form>
</div>
