@extends('layouts.app')
@section('title', 'Pemilihan Tempat KP - '.config('app.name'))
@section('page_title', 'Pemilihan Tempat KP')
@section('content')
<div class="space-y-6">
    @if(session('status'))<div class="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-800 shadow-sm">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-medium text-red-700 shadow-sm">{{ $errors->first() }}</div>@endif

    <section class="relative overflow-hidden rounded-3xl bg-slate-950 p-7 text-white shadow-2xl shadow-slate-900/20 ring-1 ring-slate-800 md:p-8">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(45,212,191,0.26),transparent_34%),linear-gradient(135deg,rgba(15,118,110,0.3),transparent_46%)]"></div>
        <div class="relative grid gap-6 lg:grid-cols-[minmax(0,1fr)_280px] lg:items-center">
            <div>
                <p class="text-xs font-black uppercase tracking-widest text-teal-200">Waktu Server: {{ $serverNow->format('d M Y H:i:s') }}</p>
                <h2 class="mt-3 text-3xl font-black tracking-tight md:text-4xl">Pilih Tempat KP</h2>
                <p class="mt-4 max-w-3xl text-sm leading-7 text-slate-200">Pilih tempat kerja praktek dengan sistem first come first served. Setelah memilih, pilihan terkunci dan hanya Admin/Koordinator yang dapat mengubahnya.</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-white/10 p-4 backdrop-blur">
                <p class="text-xs font-bold uppercase tracking-widest text-teal-100">Alur pemilihan</p>
                <div class="mt-4 space-y-3">
                    <div class="flex items-center gap-3 text-sm font-semibold"><span class="flex h-7 w-7 items-center justify-center rounded-lg bg-teal-300 text-slate-950">1</span>Profil lengkap</div>
                    <div class="flex items-center gap-3 text-sm font-semibold"><span class="flex h-7 w-7 items-center justify-center rounded-lg bg-teal-300 text-slate-950">2</span>Pendaftaran terverifikasi</div>
                    <div class="flex items-center gap-3 text-sm font-semibold"><span class="flex h-7 w-7 items-center justify-center rounded-lg bg-teal-300 text-slate-950">3</span>Pilih kuota tersedia</div>
                </div>
            </div>
        </div>
    </section>

    @if(! auth()->user()->isProfileComplete())
        <div class="rounded-2xl border border-amber-300 bg-amber-50 px-5 py-4 text-sm font-semibold text-amber-900 shadow-sm">
            Lengkapi profil terlebih dahulu sebelum memilih tempat KP.
        </div>
    @endif

    @forelse($periods as $period)
        @php($periodRegistration = auth()->user()->student?->kpRegistrations()->where('kp_period_id', $period->id)->first())
        <section class="rounded-2xl bg-white p-6 shadow-lg shadow-slate-200/70 ring-1 ring-slate-200">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <h3 class="text-lg font-bold text-slate-950">{{ $period->name }}</h3>
                    <p class="mt-1 text-sm text-slate-500">Pemilihan: {{ $period->selection_start_at?->format('d M Y H:i') ?? '-' }} - {{ $period->selection_end_at?->format('d M Y H:i') ?? '-' }}</p>
                    <p class="mt-1 text-sm text-slate-500">Status: {{ $periodRegistration?->selectionStatusLabel() ?? 'Belum eligible' }}</p>
                </div>
                <a href="{{ route('student.place-selections.show', $period) }}" class="rounded-xl bg-teal-700 px-5 py-3 text-center text-sm font-bold text-white shadow-lg shadow-teal-700/20 transition hover:bg-teal-800">Lihat Tempat</a>
            </div>
        </section>
    @empty
        <section class="rounded-3xl border border-slate-200 bg-white p-10 text-center shadow-xl shadow-slate-200/70">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 text-teal-700 ring-1 ring-slate-200">
                <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M5 11h14M6 21h12a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z"/>
                </svg>
            </div>
            <h3 class="mt-5 text-xl font-black text-slate-950">Belum ada periode yang bisa dipilih</h3>
            <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">Pastikan profil dan pendaftaran KP Anda sudah terverifikasi. Periode pemilihan akan tampil otomatis saat dibuka oleh program.</p>
        </section>
    @endforelse
</div>
@endsection
