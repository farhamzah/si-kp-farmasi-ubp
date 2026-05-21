@extends('layouts.app')

@section('title', 'Profil Akademik - '.config('app.name'))
@section('page_title', 'Profil Akademik')

@section('content')
<div class="grid gap-6 xl:grid-cols-[0.8fr_1.2fr]">
    <!-- User Card -->
    <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 hover:shadow-md transition-all">
        <div class="flex items-center gap-4 mb-6 pb-6 border-b border-slate-200">
            <div class="flex h-16 w-16 items-center justify-center rounded-xl bg-gradient-to-br from-teal-500 to-teal-600 text-xl font-bold text-white shadow-md shadow-teal-500/25">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>
            <div>
                <h2 class="text-xl font-bold text-slate-950">{{ $user->name }}</h2>
                <p class="text-xs text-slate-500 uppercase tracking-widest font-medium mt-1">{{ $user->email }}</p>
            </div>
        </div>
        
        <div class="space-y-3 text-sm mb-6">
            <div class="flex items-center justify-between px-3 py-3 rounded-lg bg-slate-50">
                <span class="text-slate-600 font-medium">Status Akun</span>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 text-emerald-800 px-3 py-1 font-semibold text-xs">
                    <span class="h-2 w-2 rounded-full bg-emerald-600"/>
                    {{ $user->status === 'active' ? 'Aktif' : 'Nonaktif' }}
                </span>
            </div>
            <div class="flex items-center justify-between px-3 py-3 rounded-lg bg-slate-50">
                <span class="text-slate-600 font-medium">Kelengkapan Data</span>
                <span class="inline-flex rounded-full {{ $user->profile_completed ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }} px-3 py-1 font-semibold text-xs">
                    {{ $user->profile_completed ? 'Lengkap' : 'Belum Lengkap' }}
                </span>
            </div>
            <div class="flex items-center justify-between px-3 py-3 rounded-lg bg-slate-50">
                <span class="text-slate-600 font-medium">Verifikasi Kata Sandi</span>
                <span class="font-semibold text-slate-700 text-xs">{{ $user->must_change_password ? 'Diperlukan' : 'Terverifikasi' }}</span>
            </div>
        </div>
        
        <a href="{{ route('profile.edit') }}" class="block w-full rounded-lg bg-gradient-to-r from-teal-600 to-teal-700 px-4 py-2.5 text-center text-sm font-bold text-white hover:from-teal-700 hover:to-teal-800 transition-all shadow-lg shadow-teal-600/30">
            <span class="flex items-center justify-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                <span>Edit Data Profil</span>
            </span>
        </a>
    </section>

    <!-- Roles Section -->
    <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 hover:shadow-md transition-all">
        <h3 class="text-lg font-bold text-slate-950 mb-2">Peran Akademik</h3>
        <p class="text-sm text-slate-500 mb-5">Peran yang terikat pada akun Anda dalam sistem akademik.</p>
        <div class="grid gap-3 md:grid-cols-2">
            @foreach($user->roles as $role)
                <div class="rounded-xl border border-slate-200 p-4 hover:border-teal-300 hover:bg-teal-50/30 transition-all">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h4 class="font-bold text-slate-950">{{ $role->label }}</h4>
                            <p class="mt-1.5 text-xs leading-5 text-slate-500">{{ $role->description }}</p>
                        </div>
                        @if(session('active_role') === $role->name)
                            <span class="inline-flex items-center gap-1 rounded-lg bg-teal-100 text-teal-700 px-2.5 py-1 text-xs font-bold whitespace-nowrap">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                Aktif
                            </span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <!-- Profile Details Section -->
    <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 hover:shadow-md transition-all xl:col-span-2">
        <h3 class="text-lg font-bold text-slate-950 mb-1">Data Profil Lengkap</h3>
        <p class="text-sm text-slate-500 mb-5">Informasi akademis {{ str_replace('_', ' ', ucwords($profileType, '_')) }} yang terdaftar dalam sistem.</p>
        
        @if($profile)
            <div class="grid gap-3 md:grid-cols-3">
                @foreach($profile->getAttributes() as $key => $value)
                    @unless(in_array($key, ['id','user_id','created_at','updated_at'], true))
                        <div class="rounded-lg border border-slate-200 p-4 hover:border-teal-300 hover:bg-teal-50/30 transition-all">
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">{{ str_replace('_', ' ', $key) }}</p>
                            <p class="text-sm font-semibold text-slate-900">{{ $value ?: '-' }}</p>
                        </div>
                    @endunless
                @endforeach
            </div>
        @else
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-5 py-4">
                <div class="flex gap-3">
                    <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <p class="text-sm text-amber-800">Profil khusus {{ str_replace('_', ' ', ucwords($profileType, '_')) }} belum terdaftar dalam sistem.</p>
                </div>
            </div>
        @endif
    </section>
</div>
@endsection
