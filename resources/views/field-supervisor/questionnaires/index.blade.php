@extends('layouts.app')

@section('title', 'Kuisioner Tempat KP')
@section('page_title', 'Kuisioner Tempat KP')

@section('content')
<div class="space-y-5">
    <section class="rounded-3xl border border-cyan-100 bg-white p-5 shadow-sm ring-1 ring-white/70 md:p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-widest text-cyan-700">Evaluasi Tempat KP</p>
                <h2 class="mt-1 text-2xl font-black text-slate-950 md:text-3xl">Kuisioner untuk Pembimbing Lapangan</h2>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Kuisioner muncul untuk mahasiswa yang sudah terhubung sebagai bimbingan lapangan Anda. Isi satu respons untuk setiap mahasiswa/penempatan.</p>
            </div>
            <div class="rounded-2xl bg-cyan-50 px-4 py-3 text-sm text-slate-700">
                <span class="block text-xs font-black uppercase tracking-widest text-cyan-700">Daftar aktif</span>
                <strong>{{ $assignments->total() }} mahasiswa</strong>
            </div>
        </div>
    </section>

    <div class="space-y-4">
        @forelse($assignments as $assignment)
            @php
                $availableQuestionnaires = $questionnaires->filter(fn ($questionnaire) => ! $questionnaire->kp_period_id || $questionnaire->kp_period_id === $assignment->kp_period_id);
            @endphp
            <article class="rounded-3xl border border-cyan-100 bg-white p-5 shadow-sm transition hover:border-cyan-200 hover:shadow-lg hover:shadow-cyan-900/10">
                <div class="grid gap-4 xl:grid-cols-[1fr_auto] xl:items-center">
                    <div>
                        <p class="text-xs font-black uppercase tracking-widest text-cyan-700">{{ $assignment->period?->name ?? 'KP' }}</p>
                        <h3 class="mt-1 text-xl font-black text-slate-950">{{ $assignment->student?->user?->name }}</h3>
                        <div class="mt-3 grid gap-2 text-sm text-slate-600 sm:grid-cols-2 lg:grid-cols-3">
                            <div class="rounded-2xl bg-slate-50 px-4 py-3"><span class="block text-xs font-black uppercase text-slate-400">NIM</span>{{ $assignment->student?->nim ?? '-' }}</div>
                            <div class="rounded-2xl bg-slate-50 px-4 py-3"><span class="block text-xs font-black uppercase text-slate-400">Tempat KP</span>{{ $assignment->place?->name ?? '-' }}</div>
                            <div class="rounded-2xl bg-slate-50 px-4 py-3"><span class="block text-xs font-black uppercase text-slate-400">Status</span>{{ $assignment->statusLabel() }}</div>
                        </div>
                    </div>
                    <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap xl:justify-end">
                        @forelse($availableQuestionnaires as $questionnaire)
                            @php $done = ($submitted[$assignment->id] ?? collect())->contains('kp_questionnaire_id', $questionnaire->id); @endphp
                            <a href="{{ route('field-supervisor.questionnaires.show', [$assignment, $questionnaire]) }}" class="inline-flex items-center justify-center rounded-2xl px-5 py-3 text-sm font-black shadow-sm {{ $done ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : 'bg-cyan-800 text-white shadow-cyan-900/15' }}">
                                {{ $done ? 'Lihat / Perbarui' : 'Isi Sekarang' }}
                            </a>
                        @empty
                            <div class="rounded-2xl border border-dashed border-amber-200 bg-amber-50 px-4 py-3 text-sm font-bold text-amber-800">
                                Kuisioner belum tersedia untuk periode penempatan ini.
                            </div>
                        @endforelse
                    </div>
                </div>
            </article>
        @empty
            <div class="rounded-3xl border border-dashed border-sky-200 bg-white/70 p-8 text-center text-slate-500">
                <p class="text-lg font-black text-slate-900">Belum ada mahasiswa KP.</p>
                <p class="mt-2 text-sm">Kuisioner tersedia setelah koordinator menghubungkan Anda sebagai pembimbing lapangan pada penempatan KP aktif.</p>
            </div>
        @endforelse
    </div>

    {{ $assignments->links() }}
</div>
@endsection
