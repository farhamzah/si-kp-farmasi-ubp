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
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Kuisioner muncul untuk tempat KP yang sudah terhubung dengan Anda. Isi satu respons per tempat dan periode, walaupun jumlah mahasiswa bimbingan lebih dari satu.</p>
            </div>
            <div class="rounded-2xl bg-cyan-50 px-4 py-3 text-sm text-slate-700">
                <span class="block text-xs font-black uppercase tracking-widest text-cyan-700">Daftar aktif</span>
                <strong>{{ $contexts->count() }} tempat</strong>
            </div>
        </div>
    </section>

    <div class="space-y-4">
        @forelse($contexts as $context)
            @php
                $assignment = $context['assignment'];
                $place = $context['place'];
                $period = $context['period'];
                $availableQuestionnaires = $questionnaires->filter(fn ($questionnaire) => ! $questionnaire->kp_period_id || $questionnaire->kp_period_id === $assignment->kp_period_id);
                $submittedForContext = $submitted[$context['key']] ?? collect();
            @endphp
            <article class="rounded-3xl border border-cyan-100 bg-white p-5 shadow-sm transition hover:border-cyan-200 hover:shadow-lg hover:shadow-cyan-900/10">
                <div class="grid gap-4 xl:grid-cols-[1fr_auto] xl:items-center">
                    <div>
                        <p class="text-xs font-black uppercase tracking-widest text-cyan-700">{{ $period?->name ?? 'KP' }}</p>
                        <h3 class="mt-1 text-xl font-black text-slate-950">{{ $place?->name ?? 'Tempat KP' }}</h3>
                        <div class="mt-3 grid gap-2 text-sm text-slate-600 sm:grid-cols-2 lg:grid-cols-3">
                            <div class="rounded-2xl bg-slate-50 px-4 py-3"><span class="block text-xs font-black uppercase text-slate-400">Tipe Tempat</span>{{ $place?->typeLabel() ?? '-' }}</div>
                            <div class="rounded-2xl bg-slate-50 px-4 py-3"><span class="block text-xs font-black uppercase text-slate-400">Mahasiswa Aktif</span>{{ $context['student_count'] }} mahasiswa</div>
                            <div class="rounded-2xl bg-slate-50 px-4 py-3"><span class="block text-xs font-black uppercase text-slate-400">Status</span>{{ $assignment->statusLabel() }}</div>
                        </div>
                        @if($context['students']->isNotEmpty())
                            <p class="mt-3 text-xs leading-5 text-slate-500">
                                Mahasiswa terkait: {{ $context['students']->take(4)->implode(', ') }}{{ $context['students']->count() > 4 ? ', dan lainnya' : '' }}.
                            </p>
                        @endif
                    </div>
                    <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap xl:justify-end">
                        @forelse($availableQuestionnaires as $questionnaire)
                            @php $done = $submittedForContext->contains('kp_questionnaire_id', $questionnaire->id); @endphp
                            @if($done)
                                <a href="{{ route('field-supervisor.questionnaires.show', [$assignment, $questionnaire]) }}" class="inline-flex min-w-36 items-center justify-center rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-3 text-sm font-black text-emerald-700 shadow-sm transition hover:bg-emerald-100" style="background-color: #ecfdf5 !important; border-color: #a7f3d0 !important; color: #047857 !important; text-decoration: none;">
                                    Lihat / Perbarui
                                </a>
                            @else
                                <a href="{{ route('field-supervisor.questionnaires.show', [$assignment, $questionnaire]) }}" class="inline-flex min-w-36 items-center justify-center rounded-2xl border border-cyan-700 bg-cyan-800 px-5 py-3 text-sm font-black text-white shadow-lg shadow-cyan-900/15 transition hover:bg-cyan-700" style="background-color: #155e75 !important; border-color: #0e7490 !important; color: #ffffff !important; text-decoration: none;">
                                    Isi Sekarang
                                </a>
                            @endif
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
                <p class="text-lg font-black text-slate-900">Belum ada tempat KP aktif.</p>
                <p class="mt-2 text-sm">Kuisioner tersedia setelah koordinator menghubungkan Anda sebagai pembimbing lapangan pada penempatan KP aktif.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection
