@extends('layouts.app')

@section('title', 'Detail Hasil Kuisioner')
@section('page_title', 'Detail Hasil Kuisioner')

@section('content')
<div class="space-y-5">
    <a href="{{ route('management.questionnaire-results.index') }}" class="inline-flex rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-black text-slate-700">Kembali ke Hasil</a>

    @php
        $contextPlace = $response->place?->name ?? $response->assignment?->place?->name ?? '-';
        $contextPeriod = $response->period?->name ?? $response->assignment?->period?->name ?? '-';
        $contextStudent = $response->assignment?->student?->user?->name;
        $isPlaceQuestionnaire = $response->questionnaire->audience === \App\Models\KpQuestionnaire::AUDIENCE_FIELD_SUPERVISOR;
    @endphp

    <section class="rounded-3xl border border-sky-100 bg-white p-5 shadow-sm">
        <p class="text-xs font-black uppercase tracking-widest text-cyan-700">{{ $response->questionnaire->audienceLabel() }}</p>
        <h2 class="mt-1 text-2xl font-black">{{ $response->questionnaire->title }}</h2>
        <p class="mt-2 text-sm text-slate-600">Responden: <strong>{{ $response->respondent->name }}</strong> - {{ $response->submitted_at?->format('d M Y H:i') }}</p>

        <div class="mt-5 grid gap-3 md:grid-cols-3">
            <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                <p class="text-xs font-black uppercase tracking-widest text-slate-500">{{ $isPlaceQuestionnaire ? 'Tempat KP' : 'Mahasiswa' }}</p>
                <p class="mt-1 font-black text-slate-950">{{ $isPlaceQuestionnaire ? $contextPlace : ($contextStudent ?? '-') }}</p>
                <p class="text-xs text-slate-500">{{ $contextPeriod }}</p>
            </div>
            <div class="rounded-2xl bg-cyan-50 p-4 text-center">
                <p class="text-xs font-black uppercase tracking-widest text-cyan-700">Skor Respons</p>
                <p class="mt-1 text-3xl font-black text-cyan-800">{{ $score['average'] ?? '-' }}</p>
                <p class="text-xs font-bold text-cyan-700">{{ $score['label'] }}</p>
            </div>
            <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                <p class="text-xs font-black uppercase tracking-widest text-slate-500">Kesimpulan Individual</p>
                <p class="mt-2 text-sm leading-6 text-slate-700">{{ $score['conclusion'] }}</p>
            </div>
        </div>
    </section>

    <section class="space-y-3">
        @foreach($response->questionnaire->questions as $question)
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-black uppercase tracking-widest text-slate-400">{{ $question->section ?: 'Pertanyaan' }}</p>
                <p class="mt-1 font-black text-slate-950">{{ $question->question_text }}</p>
                <p class="mt-3 rounded-2xl bg-slate-50 px-4 py-3 text-slate-700">{{ $answerMap[$question->id] ?? '-' }}</p>
            </div>
        @endforeach
    </section>
</div>
@endsection
