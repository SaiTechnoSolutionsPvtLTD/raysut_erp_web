<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'RMSC ERP') }} — Enterprise Core</title>

        <!-- Favicon -->
        <link rel="icon" type="image/png" href="{{ asset('images/rmsc-logo-mark-square.png') }}">

        <!-- Scripts & Styles -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="font-sans antialiased bg-slate-50 text-slate-800" x-data="{ mobileSidebarOpen: false }">
        <div class="min-h-screen flex flex-col lg:flex-row">
            <!-- Left Sidebar Navigation -->
            <x-sidebar />

            <!-- Main App Area -->
            <div class="flex-1 flex flex-col min-w-0 min-h-screen">
                <!-- Top Header Navigation -->
                <x-topbar />

                <!-- Page Header (Optional) -->
                @if (isset($header))
                    <div class="bg-white/90 backdrop-blur-xs border-b border-slate-200/80 py-4 px-4 sm:px-6 shadow-2xs">
                        <div class="w-full">
                            {{ $header }}
                        </div>
                    </div>
                @endif

                <!-- Main Content Area -->
                <main class="flex-1 p-4 md:p-6">
                    <div class="w-full">
                        {{ $slot }}
                    </div>
                </main>
            </div>
        </div>

        <!-- Global Creative Toast Notifications -->
        <x-toast-notification />

        @livewireScripts
    </body>
</html>
