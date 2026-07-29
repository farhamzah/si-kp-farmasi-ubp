@extends('layouts.app')

@section('title', 'Kuisioner Tempat KP')
@section('page_title', 'Kuisioner Tempat KP')

@section('content')
<div class="space-y-5">
    <section class="rounded-3xl border border-sky-100 bg-white p-5 shadow-sm">
        <p class="text-xs font-black uppercase tracking-widest text-cyan-700">Evaluasi Tempat KP</p>
        <h2 class="mt-1 text-2xl font-black text-slate-950">Kuisioner untuk Pembimbing Lapangan</h2>
        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Isi kuisioner berdasarkan pengalaman membimbing mahasiswa pada penempatan terkait. Bila membimbing beberapa mahasiswa, setiap respons akan tersimpan per mahasiswa/penempatan.</p>
    </section>

    <div class="space-y-4">
        @forelse($assignments as $assignment)
            <article class="rounded-3xl border border-sky-100 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="text-xs font-black uppercase tracking-widest text-cyan-700">{{ $assignment->period?->name ?? 'KP' }}</p>
                        <h3 class="mt-1 text-xl font-black text-slate-950">{{ $assignment->student?->user?->name }}</h3>
                        <p class="mt-1 text-sm text-slate-600">{{ $assignment->student?->nim }} · {{ $assignment->place?->name ?? '-' }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @foreach($questionnaires as $questionnaire)
                            @php $done = ($submitted[$assignment->id] ?? collect())->contains('kp_questionnaire_id', $questionnaire->id); @endphp
                            @if(! $questionnaire->kp_period_id || $questionnaire->kp_period_id === $assignment->kp_period_id)
                                <a href="{{ route('field-supervisor.questionnaires.show', [$assignment, $questionnaire]) }}" class="rounded-2xl px-4 py-2 text-sm font-black {{ $done ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : 'bg-cyan-800 text-white' }}">{{ $done ? 'Lihat / Perbarui' : 'Isi Kuisioner' }}</a>
                            @endif
                        @endforeach
                    </div>
                </div>
            </article>
        @empty
            <div class="rounded-3xl border border-dashed border-sky-200 bg-white/70 p-8 text-center text-slate-500">Belum ada mahasiswa KP yang terhubung dengan akun pembimbing lapangan ini.</div>
        @endforelse
    </div>

    {{ $assignments->links() }}
</div>
@endsection
