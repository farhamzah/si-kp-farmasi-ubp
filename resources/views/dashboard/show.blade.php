@extends('layouts.app')

@section('title', 'Dashboard '.$roleData['label'].' - '.config('app.name'))
@section('page_title', 'Dashboard '.$roleData['label'])

@section('content')
<div class="space-y-6">
    <section class="rounded-2xl bg-gradient-to-br from-teal-700 to-slate-900 p-6 text-white shadow-sm md:p-8">
        <p class="text-sm font-semibold text-teal-100">{{ $roleData['label'] }}</p>
        <h2 class="mt-2 text-2xl font-bold md:text-3xl">Selamat datang, {{ auth()->user()->name }}</h2>
        <p class="mt-3 max-w-3xl text-sm leading-6 text-teal-50">Fondasi dashboard sudah disiapkan. Modul utama akan diaktifkan bertahap sesuai kebutuhan proses Kerja Praktek Farmasi UBP.</p>
    </section>

    @if(! auth()->user()->profile_completed)
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            Profil Anda belum lengkap. Silakan <a href="{{ route('profile.edit') }}" class="font-semibold underline">lengkapi profil</a> sebelum menggunakan fitur utama.
        </div>
    @endif

    @if(auth()->user()->must_change_password)
        <div class="rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900">
            Password awal masih perlu diganti. Fitur ubah password akan disempurnakan pada tahap berikutnya.
        </div>
    @endif

    @if($adminStats)
        <section class="grid gap-4 md:grid-cols-5">
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total user</p>
                <p class="mt-3 text-2xl font-bold text-slate-950">{{ $adminStats['total_users'] }}</p>
            </div>
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Aktif</p>
                <p class="mt-3 text-2xl font-bold text-emerald-700">{{ $adminStats['active_users'] }}</p>
            </div>
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nonaktif</p>
                <p class="mt-3 text-2xl font-bold text-rose-700">{{ $adminStats['inactive_users'] }}</p>
            </div>
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Profil belum lengkap</p>
                <p class="mt-3 text-2xl font-bold text-amber-700">{{ $adminStats['incomplete_profiles'] }}</p>
            </div>
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Import terakhir</p>
                <p class="mt-3 text-sm font-bold text-slate-950">{{ $adminStats['last_import']?->created_at?->format('d M Y') ?? 'Belum ada' }}</p>
            </div>
        </section>
    @endif

    <section class="grid gap-4 md:grid-cols-3">
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <p class="text-sm font-semibold text-slate-500">Status Profil</p>
            <div class="mt-3 flex items-center justify-between">
                <span class="text-lg font-bold text-slate-950">{{ auth()->user()->profile_completed ? 'Lengkap' : 'Belum Lengkap' }}</span>
                <span class="rounded-full {{ auth()->user()->profile_completed ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }} px-3 py-1 text-xs font-semibold">{{ auth()->user()->profile_completed ? 'Aktif' : 'Perlu update' }}</span>
            </div>
        </div>
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <p class="text-sm font-semibold text-slate-500">Role Aktif</p>
            <p class="mt-3 text-lg font-bold text-slate-950">{{ $roleData['label'] }}</p>
        </div>
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <p class="text-sm font-semibold text-slate-500">Status Akun</p>
            <p class="mt-3 text-lg font-bold text-slate-950">Aktif</p>
        </div>
    </section>

    <section>
        <div class="mb-4 flex items-end justify-between gap-3">
            <div>
                <h3 class="text-lg font-bold text-slate-950">Ringkasan Fitur</h3>
                <p class="text-sm text-slate-500">Placeholder modul yang akan dikembangkan pada tahap berikutnya.</p>
            </div>
        </div>
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach($features as $feature)
                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-lg bg-teal-50 text-sm font-bold text-teal-700">{{ sprintf('%02d', $loop->iteration) }}</div>
                    <h4 class="font-bold text-slate-950">{{ $feature }}</h4>
                    <p class="mt-2 text-sm leading-6 text-slate-500">Modul ini akan tersedia pada tahap pengembangan berikutnya.</p>
                    <span class="mt-4 inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">Segera tersedia</span>
                </div>
            @endforeach
        </div>
    </section>
</div>
@endsection
