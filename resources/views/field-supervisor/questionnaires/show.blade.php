@extends('layouts.app')

@section('title', $questionnaire->title)
@section('page_title', 'Isi Kuisioner Tempat KP')

@section('content')
<form method="POST" action="{{ route('field-supervisor.questionnaires.submit', [$assignment, $questionnaire]) }}" class="space-y-5">
    @csrf
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <a href="{{ route('field-supervisor.questionnaires.index') }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-black text-slate-700">Kembali ke Daftar</a>
        <span class="rounded-full bg-cyan-50 px-4 py-2 text-xs font-black uppercase tracking-widest text-cyan-700">{{ $questionnaire->activeQuestions->count() }} pertanyaan</span>
    </div>

    <section class="rounded-3xl border border-cyan-100 bg-white p-5 shadow-sm md:p-6">
        <p class="text-xs font-black uppercase tracking-widest text-cyan-700">{{ $assignment->place?->name ?? 'Tempat KP' }}</p>
        <h2 class="mt-1 text-2xl font-black text-slate-950 md:text-3xl">{{ $questionnaire->title }}</h2>
        <p class="mt-2 text-sm leading-6 text-slate-600">Mahasiswa: <strong>{{ $assignment->student?->user?->name }}</strong> &middot; {{ $assignment->student?->nim }}</p>
    </section>

    @foreach($questionnaire->activeQuestions as $question)
        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm md:p-6">
            <p class="text-xs font-black uppercase tracking-widest text-cyan-700">{{ $question->section ?: 'Pertanyaan' }} @if($question->is_required)<span class="text-rose-600">*</span>@endif</p>
            <h3 class="mt-2 text-lg font-black text-slate-950">{{ $question->question_text }}</h3>
            @error('answers.'.$question->id)<p class="mt-2 text-sm font-bold text-rose-600">{{ $message }}</p>@enderror

            @if($question->answer_type === 'scale')
                @php $scaleLabels = [1 => 'Sangat kurang', 2 => 'Kurang', 3 => 'Cukup', 4 => 'Baik', 5 => 'Sangat baik']; @endphp
                <div class="mt-4 grid gap-2 sm:grid-cols-5">
                    @for($i = 1; $i <= 5; $i++)
                        <label class="flex cursor-pointer items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-3 transition has-[:checked]:border-cyan-400 has-[:checked]:bg-cyan-50 has-[:checked]:shadow-sm sm:flex-col sm:justify-center sm:text-center">
                            <input type="radio" name="answers[{{ $question->id }}]" value="{{ $i }}" class="sr-only" @checked(($answerMap[$question->id] ?? '') == $i)>
                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-white text-lg font-black text-cyan-800 ring-1 ring-slate-200">{{ $i }}</span>
                            <span class="text-xs font-bold leading-4 text-slate-600">{{ $scaleLabels[$i] }}</span>
                        </label>
                    @endfor
                </div>
            @elseif($question->answer_type === 'choice')
                <select name="answers[{{ $question->id }}]" class="mt-4 w-full rounded-2xl border border-slate-200 px-4 py-3">
                    <option value="">Pilih jawaban</option>
                    @foreach($question->optionList() as $option)
                        <option value="{{ $option }}" @selected(($answerMap[$question->id] ?? '') === $option)>{{ $option }}</option>
                    @endforeach
                </select>
            @elseif($question->answer_type === 'number')
                <input type="number" min="0" step="1" name="answers[{{ $question->id }}]" value="{{ $answerMap[$question->id] ?? '' }}" class="mt-4 w-full rounded-2xl border border-slate-200 px-4 py-3">
            @else
                <textarea name="answers[{{ $question->id }}]" rows="4" class="mt-4 w-full rounded-2xl border border-slate-200 px-4 py-3" placeholder="Tulis jawaban">{{ $answerMap[$question->id] ?? '' }}</textarea>
            @endif
        </section>
    @endforeach

    <button class="sticky bottom-4 w-full rounded-2xl bg-cyan-800 px-5 py-4 text-sm font-black text-white shadow-xl shadow-cyan-900/20">Kirim Kuisioner</button>
</form>
@endsection
