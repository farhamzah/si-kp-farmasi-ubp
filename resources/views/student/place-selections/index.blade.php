@extends('layouts.app')
@section('title', 'Pemilihan Tempat KP - '.config('app.name'))
@section('page_title', 'Pemilihan Tempat KP')
@section('content')
<div class="space-y-5">
    @if(session('status'))<div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>@endif

    <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <p class="text-xs font-semibold uppercase tracking-widest text-teal-700">Waktu Server: {{ $serverNow->format('d M Y H:i:s') }}</p>
        <h2 class="mt-2 text-2xl font-bold text-slate-950">Pilih Tempat KP</h2>
        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">Pilihan tempat menggunakan sistem first come first served. Setelah memilih, pilihan terkunci dan hanya Admin/Koordinator yang dapat mengubahnya.</p>
    </section>

    @if(! auth()->user()->isProfileComplete())
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">Lengkapi profil terlebih dahulu sebelum memilih tempat KP.</div>
    @endif

    @forelse($periods as $period)
        @php($periodRegistration = auth()->user()->student?->kpRegistrations()->where('kp_period_id', $period->id)->first())
        <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <h3 class="text-lg font-bold text-slate-950">{{ $period->name }}</h3>
                    <p class="mt-1 text-sm text-slate-500">Pemilihan: {{ $period->selection_start_at?->format('d M Y H:i') ?? '-' }} - {{ $period->selection_end_at?->format('d M Y H:i') ?? '-' }}</p>
                    <p class="mt-1 text-sm text-slate-500">Status: {{ $periodRegistration?->selectionStatusLabel() ?? 'Belum eligible' }}</p>
                </div>
                <a href="{{ route('student.place-selections.show', $period) }}" class="rounded-lg bg-teal-600 px-4 py-2 text-center text-sm font-semibold text-white">Lihat Tempat</a>
            </div>
        </section>
    @empty
        <section class="rounded-2xl bg-white p-10 text-center shadow-sm ring-1 ring-slate-200">
            <h3 class="text-lg font-bold text-slate-950">Belum ada periode yang bisa dipilih</h3>
            <p class="mt-2 text-sm text-slate-500">Pastikan pendaftaran KP Anda sudah terverifikasi.</p>
        </section>
    @endforelse
</div>
@endsection
