<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'SI-KP Farmasi UBP'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 font-sans text-slate-900">
@php
    $activeRole = session('active_role');
    $roleData = $activeRole ? \App\Support\RoleDashboard::dataFor($activeRole) : null;
    $roleLabel = \App\Support\RoleDashboard::labelFor($activeRole);
    $ownedRoles = auth()->user()?->roles ?? collect();
@endphp
<div class="min-h-screen lg:flex">
    <!-- Sidebar Navigation -->
    <aside class="border-b border-slate-200 bg-white shadow-sm lg:fixed lg:inset-y-0 lg:left-0 lg:w-72 lg:border-b-0 lg:border-r lg:shadow-none">
        <!-- Branding -->
        <div class="flex items-center justify-between gap-3 px-5 py-6 lg:py-8 border-b border-slate-200/50">
            <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 items-center justify-center overflow-hidden rounded-lg bg-gradient-to-br from-teal-400 to-teal-600 p-1.5 shadow-md shadow-teal-500/25">
                    <img src="{{ asset('images/logo-fakultas-farmasi-ubp.png') }}" alt="Logo Fakultas Farmasi UBP" class="h-full w-full object-contain">
                </div>
                <div class="hidden lg:block">
                    <p class="text-xs font-bold tracking-widest uppercase text-slate-900">SI-KP</p>
                    <p class="text-[10px] text-slate-500 font-medium">Farmasi UBP</p>
                </div>
            </div>
            <a href="/" class="hidden lg:block text-slate-400 hover:text-slate-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
        </div>
        
        <!-- Navigation Menu -->
        <nav class="flex gap-2 overflow-x-auto px-4 py-4 lg:block lg:space-y-1 lg:overflow-visible lg:p-4">
            @foreach(($roleData['menu'] ?? ['Dashboard', 'Profil Saya']) as $item)
                @php
                    $isDashboard = $item === 'Dashboard';
                    $isProfile = $item === 'Profil Saya';
                    $routeMap = [
                        'Manajemen User' => 'admin.users.index',
                        'Import User' => 'admin.import-users.index',
                        'Riwayat Import' => 'admin.import-users.history',
                        'Periode KP' => 'management.kp-periods.index',
                        'Tempat KP' => 'management.kp-places.index',
                        'Kuota Tempat KP' => 'management.kp-place-quotas.index',
                        'Log Kuota' => 'management.kp-quota-logs.index',
                    ];
                    $mappedRoute = $routeMap[$item] ?? null;
                    $href = $isDashboard ? route($roleData['route'] ?? 'dashboard') : ($isProfile ? route('profile.show') : ($mappedRoute && Route::has($mappedRoute) ? route($mappedRoute) : '#'));
                    $isActive = ($isDashboard && request()->routeIs($roleData['route'] ?? 'dashboard')) || ($isProfile && request()->routeIs('profile.show', 'profile.edit')) || ($mappedRoute && request()->routeIs($mappedRoute, $mappedRoute.'.*'));
                @endphp
                <a href="{{ $href }}" class="flex min-w-max items-center justify-between rounded-lg px-4 py-2.5 text-sm font-medium transition-all {{ $isActive ? 'bg-teal-50 text-teal-700 shadow-sm ring-1 ring-teal-100' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                    <span>{{ $item }}</span>
                    @unless($isDashboard || $isProfile || $mappedRoute)
                        <span class="ml-2 rounded-md bg-slate-200 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-widest text-slate-600">Segera</span>
                    @endunless
                </a>
            @endforeach
        </nav>

        <!-- User Info (Mobile) -->
        <div class="border-t border-slate-200/50 px-4 py-4 lg:hidden">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Pengguna</p>
            <p class="text-sm font-semibold text-slate-900 truncate">{{ auth()->user()->name }}</p>
            <p class="text-xs text-slate-500 mt-1">{{ $roleLabel }}</p>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="flex min-h-screen flex-1 flex-col lg:pl-72">
        <!-- Header -->
        <header class="sticky top-0 z-20 border-b border-slate-200 bg-white/80 backdrop-blur-md">
            <div class="flex flex-col gap-4 px-5 py-4 md:flex-row md:items-center md:justify-between md:gap-3">
                <!-- Page Title -->
                <div>
                    <p class="text-xs font-semibold uppercase tracking-widest text-teal-700 mb-1">{{ config('app.name') }}</p>
                    <h1 class="text-xl font-bold text-slate-950">@yield('page_title', 'Dashboard')</h1>
                </div>
                
                <!-- Header Actions -->
                <div class="flex flex-wrap items-center gap-2 md:gap-3">
                    <!-- Role Badge -->
                    <span class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-teal-50 to-emerald-50 px-3 py-1.5 text-xs font-semibold text-teal-700 ring-1 ring-teal-100">
                        <span class="h-2 w-2 rounded-full bg-teal-500"/>
                        {{ $roleLabel }}
                    </span>
                    
                    <!-- User Badge -->
                    <span class="hidden md:inline-flex items-center rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-700 ring-1 ring-slate-200">
                        {{ auth()->user()->name }}
                    </span>
                    
                    <!-- Role Switcher -->
                    @if($ownedRoles->count() > 1)
                        <a href="{{ route('role.select') }}" class="flex items-center gap-2 rounded-lg border border-teal-300 bg-white px-3 py-1.5 text-xs font-semibold text-teal-700 hover:bg-teal-50 transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            <span class="hidden sm:inline">Ganti Peran</span>
                        </a>
                    @endif
                    
                    <!-- Logout Button -->
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="flex items-center gap-2 rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-800 transition-all ring-1 ring-slate-700">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                            <span class="hidden sm:inline">Keluar</span>
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 px-5 py-6 md:px-8">
            <!-- Status Message -->
            @if(session('status'))
                <div class="mb-6 rounded-lg border border-emerald-200 bg-gradient-to-r from-emerald-50 to-teal-50 px-5 py-4 text-sm text-emerald-800 shadow-sm">
                    <div class="flex gap-3">
                        <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span>{{ session('status') }}</span>
                    </div>
                </div>
            @endif
            
            <!-- Page Content -->
            @yield('content')
        </main>

        <!-- Footer -->
        <footer class="border-t border-slate-200 px-5 py-5 text-center text-xs text-slate-500 md:px-8">
            <p class="font-medium">SI-KP Farmasi UBP</p>
            <p class="mt-1">Sistem Informasi Portal Akademik Kerja Praktek Farmasi Universitas Bhakti Pensada</p>
        </footer>
    </div>
</div>
</body>
</html>
