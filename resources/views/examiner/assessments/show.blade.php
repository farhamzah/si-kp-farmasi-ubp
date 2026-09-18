@extends('layouts.app')
@section('title','Input Nilai Sidang - '.config('app.name'))
@section('page_title','Input Nilai Sidang')
@section('content')
@if($isExaminer)
    @include('shared.assessments.score-form', [
        'assignment' => $assignment,
        'components' => $components,
        'saveRoute' => route('examiner.assessments.save', $exam),
        'submitRoute' => route('examiner.assessments.submit', $exam),
        'submitLabel' => $isChair ? 'Submit Nilai dan Isi Berita Acara' : 'Submit Nilai',
    ])
@else
    <x-ui.card>
        <p class="text-xs font-black uppercase tracking-widest text-cyan-700">Ketua Sidang</p>
        <h2 class="mt-1 text-xl font-black text-slate-950">{{ $assignment->student->user->name }}</h2>
        <p class="mt-2 text-sm leading-6 text-slate-600">Anda ditetapkan sebagai Ketua Sidang, tetapi tidak tercatat sebagai anggota tim penguji. Karena itu tidak ada nilai penguji yang perlu diisi dari akun ini.</p>
    </x-ui.card>
@endif

@if($isChair || $exam->minutes)
    <div id="berita-acara" class="mt-6 scroll-mt-6">
        @include('exam-minutes.partials.close-panel', [
            'requireChairScoreSubmitted' => $isExaminer,
            'chairScoreSubmitted' => $chairScoreSubmitted,
        ])
    </div>
@endif
@endsection
