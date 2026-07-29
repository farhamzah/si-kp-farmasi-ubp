@extends('layouts.app')

@section('title', $questionnaire->title)
@section('page_title', 'Isi Kuisioner KP')

@section('content')
<form method="POST" action="{{ route('student.questionnaires.submit', $questionnaire) }}" class="space-y-5">
    @csrf
    <a href="{{ route('student.questionnaires.index') }}" class="inline-flex rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-black text-slate-700">Kembali ke Kuisioner</a>
    <section class="rounded-3xl border border-sky-100 bg-white p-5 shadow-sm">
        <p class="text-xs font-black uppercase tracking-widest text-cyan-700">{{ $assignment->place?->name ?? 'Tempat KP' }}</p>
        <h2 class="mt-1 text-2xl font-black text-slate-950">{{ $questionnaire->title }}</h2>
        <p class="mt-2 text-sm leading-6 text-slate-600">{{ $questionnaire->description }}</p>
    </section>

    @foreach($questionnaire->activeQuestions as $question)
        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-black uppercase tracking-widest text-cyan-700">{{ $question->section ?: 'Pertanyaan' }} @if($question->is_required)<span class="text-rose-600">*</span>@endif</p>
            <h3 class="mt-2 text-lg font-black text-slate-950">{{ $question->question_text }}</h3>
            @error('answers.'.$question->id)<p class="mt-2 text-sm font-bold text-rose-600">{{ $message }}</p>@enderror

            @if($question->answer_type === 'scale')
                <div class="mt-4 grid grid-cols-5 gap-2">
                    @for($i = 1; $i <= 5; $i++)
                        <label class="cursor-pointer rounded-2xl border border-slate-200 bg-slate-50 p-3 text-center transition has-[:checked]:border-cyan-400 has-[:checked]:bg-cyan-50">
                            <input type="radio" name="answers[{{ $question->id }}]" value="{{ $i }}" class="sr-only" @checked(($answerMap[$question->id] ?? '') == $i)>
                            <span class="block text-lg font-black text-slate-950">{{ $i }}</span>
                        </label>
                    @endfor
                </div>
                <div class="mt-2 flex justify-between text-xs font-bold text-slate-400"><span>Sangat kurang</span><span>Sangat baik</span></div>
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
                <textarea name="answers[{{ $question->id }}]" rows="4" class="mt-4 w-full rounded-2xl border border-slate-200 px-4 py-3" placeholder="Tulis jawaban Anda">{{ $answerMap[$question->id] ?? '' }}</textarea>
            @endif
        </section>
    @endforeach

    <button class="sticky bottom-4 w-full rounded-2xl bg-cyan-800 px-5 py-4 text-sm font-black text-white shadow-xl shadow-cyan-900/20">Kirim Kuisioner</button>
</form>
@endsection
