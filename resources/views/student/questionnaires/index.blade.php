@extends('layouts.app')

@section('title', 'Kuisioner KP')
@section('page_title', 'Kuisioner KP')

@section('content')
<div class="space-y-5">
    <section class="rounded-3xl border border-sky-100 bg-white p-5 shadow-sm">
        <p class="text-xs font-black uppercase tracking-widest text-cyan-700">Evaluasi Mahasiswa</p>
        <h2 class="mt-1 text-2xl font-black text-slate-950">Kuisioner Kepuasan KP</h2>
        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Isi kuisioner berdasarkan pengalaman kerja praktek Anda. Jawaban membantu koordinator memperbaiki pelaksanaan KP berikutnya.</p>
    </section>

    @unless($assignment)
        <div class="rounded-3xl border border-dashed border-sky-200 bg-white/70 p-8 text-center">
            <p class="text-lg font-black text-slate-900">Belum ada penempatan KP aktif.</p>
            <p class="mt-2 text-sm text-slate-600">Kuisioner tersedia setelah Anda memiliki penempatan KP.</p>
        </div>
    @else
        <div class="grid gap-4 lg:grid-cols-2">
            @forelse($questionnaires as $questionnaire)
                @php $response = $questionnaire->responses->first(); @endphp
                <article class="rounded-3xl border border-sky-100 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-black uppercase tracking-widest text-cyan-700">{{ $assignment->period?->name ?? 'KP' }}</p>
                            <h3 class="mt-1 text-xl font-black text-slate-950">{{ $questionnaire->title }}</h3>
                        </div>
                        <span class="rounded-full px-3 py-1 text-xs font-black {{ $response?->isSubmitted() ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">{{ $response?->isSubmitted() ? 'Sudah isi' : 'Belum isi' }}</span>
                    </div>
                    <p class="mt-3 text-sm leading-6 text-slate-600">{{ $questionnaire->description }}</p>
                    <div class="mt-4 grid gap-3 rounded-2xl bg-slate-50 p-4 text-sm text-slate-600 sm:grid-cols-2">
                        <div><span class="block text-xs font-black uppercase text-slate-400">Tempat KP</span>{{ $assignment->place?->name ?? '-' }}</div>
                        <div><span class="block text-xs font-black uppercase text-slate-400">Pertanyaan</span>{{ $questionnaire->active_questions_count }} item</div>
                    </div>
                    <a href="{{ route('student.questionnaires.show', $questionnaire) }}" class="mt-4 inline-flex w-full items-center justify-center rounded-2xl bg-cyan-800 px-5 py-3 text-sm font-black text-white">{{ $response?->isSubmitted() ? 'Lihat / Perbarui Jawaban' : 'Isi Kuisioner' }}</a>
                </article>
            @empty
                <div class="rounded-3xl border border-dashed border-sky-200 bg-white/70 p-8 text-center text-slate-500 lg:col-span-2">Belum ada kuisioner aktif.</div>
            @endforelse
        </div>
    @endunless
</div>
@endsection
