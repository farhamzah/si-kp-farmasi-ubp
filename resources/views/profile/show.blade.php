@extends('layouts.app')

@section('title', 'Profil Saya - '.config('app.name'))
@section('page_title', 'Profil Saya')

@section('content')
<div class="grid gap-6 xl:grid-cols-[0.8fr_1.2fr]">
    <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div class="flex items-center gap-4">
            <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-teal-600 text-xl font-bold text-white">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
            <div>
                <h2 class="text-xl font-bold text-slate-950">{{ $user->name }}</h2>
                <p class="text-sm text-slate-500">{{ $user->email }}</p>
            </div>
        </div>
        <div class="mt-6 space-y-3 text-sm">
            <div class="flex items-center justify-between border-t border-slate-100 pt-3">
                <span class="text-slate-500">Status akun</span>
                <span class="rounded-full bg-emerald-50 px-3 py-1 font-semibold text-emerald-700">{{ $user->status === 'active' ? 'Aktif' : 'Tidak Aktif' }}</span>
            </div>
            <div class="flex items-center justify-between border-t border-slate-100 pt-3">
                <span class="text-slate-500">Kelengkapan profil</span>
                <span class="rounded-full {{ $user->profile_completed ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }} px-3 py-1 font-semibold">{{ $user->profile_completed ? 'Lengkap' : 'Belum lengkap' }}</span>
            </div>
            <div class="flex items-center justify-between border-t border-slate-100 pt-3">
                <span class="text-slate-500">Wajib ganti password</span>
                <span class="font-semibold text-slate-800">{{ $user->must_change_password ? 'Ya' : 'Tidak' }}</span>
            </div>
        </div>
        <a href="{{ route('profile.edit') }}" class="mt-6 block w-full rounded-lg bg-teal-600 px-4 py-2.5 text-center text-sm font-semibold text-white hover:bg-teal-700">Edit Profil</a>
    </section>

    <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <h3 class="text-lg font-bold text-slate-950">Role yang Dimiliki</h3>
        <p class="mt-1 text-sm text-slate-500">Akses berikut melekat pada akun Anda dan divalidasi oleh sistem.</p>
        <div class="mt-5 grid gap-3 md:grid-cols-2">
            @foreach($user->roles as $role)
                <div class="rounded-xl border border-slate-200 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h4 class="font-bold text-slate-950">{{ $role->label }}</h4>
                            <p class="mt-1 text-sm leading-6 text-slate-500">{{ $role->description }}</p>
                        </div>
                        @if(session('active_role') === $role->name)
                            <span class="rounded-full bg-teal-50 px-3 py-1 text-xs font-semibold text-teal-700">Aktif</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 xl:col-span-2">
        <h3 class="text-lg font-bold text-slate-950">Data Profil {{ str_replace('_', ' ', ucwords($profileType, '_')) }}</h3>
        @if($profile)
            <div class="mt-5 grid gap-3 md:grid-cols-3">
                @foreach($profile->getAttributes() as $key => $value)
                    @unless(in_array($key, ['id','user_id','created_at','updated_at'], true))
                        <div class="rounded-lg bg-slate-50 px-3 py-2">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ str_replace('_', ' ', $key) }}</p>
                            <p class="mt-1 text-sm font-medium text-slate-800">{{ $value ?: '-' }}</p>
                        </div>
                    @endunless
                @endforeach
            </div>
        @else
            <p class="mt-4 rounded-lg bg-slate-50 px-4 py-3 text-sm text-slate-500">Akun ini belum memiliki data profil khusus.</p>
        @endif
    </section>
</div>
@endsection
