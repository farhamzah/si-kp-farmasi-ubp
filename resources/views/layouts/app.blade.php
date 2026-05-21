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
    <aside class="border-b border-slate-200 bg-white lg:fixed lg:inset-y-0 lg:left-0 lg:w-72 lg:border-b-0 lg:border-r">
        <div class="flex items-center gap-3 px-5 py-5">
            <div class="flex h-12 w-12 items-center justify-center overflow-hidden rounded-xl border border-slate-200 bg-white p-1 shadow-sm">
                <img src="{{ asset('images/logo-fakultas-farmasi-ubp.png') }}" alt="Logo Fakultas Farmasi UBP" class="h-full w-full object-contain">
            </div>
            <div>
                <p class="text-sm font-semibold tracking-wide text-teal-700">SI-KP Farmasi UBP</p>
                <p class="text-xs text-slate-500">Kerja Praktek Farmasi</p>
            </div>
        </div>
        <nav class="flex gap-2 overflow-x-auto px-4 pb-4 lg:block lg:space-y-1 lg:overflow-visible">
            @foreach(($roleData['menu'] ?? ['Dashboard', 'Profil Saya']) as $item)
                @php
                    $isDashboard = $item === 'Dashboard';
                    $isProfile = $item === 'Profil Saya';
                    $routeMap = [
                        'Manajemen User' => 'admin.users.index',
                        'Import User' => 'admin.import-users.index',
                        'Riwayat Import' => 'admin.import-users.history',
                    ];
                    $mappedRoute = $routeMap[$item] ?? null;
                    $href = $isDashboard ? route($roleData['route'] ?? 'dashboard') : ($isProfile ? route('profile.show') : ($mappedRoute && Route::has($mappedRoute) ? route($mappedRoute) : '#'));
                    $isActive = ($isDashboard && request()->routeIs($roleData['route'] ?? 'dashboard')) || ($isProfile && request()->routeIs('profile.show', 'profile.edit')) || ($mappedRoute && request()->routeIs($mappedRoute, $mappedRoute.'.*'));
                @endphp
                <a href="{{ $href }}" class="flex min-w-max items-center justify-between rounded-lg px-3 py-2 text-sm font-medium {{ $isActive ? 'bg-teal-50 text-teal-800' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950' }}">
                    <span>{{ $item }}</span>
                    @unless($isDashboard || $isProfile || $mappedRoute)
                        <span class="ml-3 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] uppercase tracking-wide text-slate-500">Soon</span>
                    @endunless
                </a>
            @endforeach
        </nav>
    </aside>

    <div class="flex min-h-screen flex-1 flex-col lg:pl-72">
        <header class="sticky top-0 z-20 border-b border-slate-200 bg-white/95 backdrop-blur">
            <div class="flex flex-col gap-3 px-5 py-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-teal-700">{{ config('app.name') }}</p>
                    <h1 class="text-lg font-semibold text-slate-950">@yield('page_title', 'Dashboard')</h1>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">{{ $roleLabel }}</span>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700">{{ auth()->user()->name }}</span>
                    @if($ownedRoles->count() > 1)
                        <a href="{{ route('role.select') }}" class="rounded-lg border border-teal-200 px-3 py-2 text-sm font-semibold text-teal-700 hover:bg-teal-50">Ganti Role</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-700">Logout</button>
                    </form>
                </div>
            </div>
        </header>

        <main class="flex-1 px-5 py-6">
            @if(session('status'))
                <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
            @endif
            @yield('content')
        </main>

        <footer class="border-t border-slate-200 px-5 py-4 text-xs text-slate-500">
            SI-KP Farmasi UBP - Sistem Informasi Kerja Praktek Farmasi
        </footer>
    </div>
</div>
</body>
</html>
