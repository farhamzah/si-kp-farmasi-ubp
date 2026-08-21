@extends('layouts.app')

@section('title','Surat Undangan Sidang KP - '.config('app.name'))
@section('page_title','Surat Undangan Sidang KP')

@section('content')
@php
    $exam = $invitation->exam;
    $assignment = $exam->assignment;
    $student = $assignment?->student;
@endphp

<div class="mx-auto max-w-5xl space-y-4">
    <div class="flex flex-wrap justify-end gap-2 print:hidden">
        <a href="{{ route('exam-invitations.letter.pdf', $invitation) }}" class="rounded-xl bg-cyan-700 px-4 py-2 text-sm font-black text-white">Download PDF</a>
        @if(in_array(session('active_role'), ['admin', 'koordinator_kp'], true))
            <a href="{{ route('exam-invitations.letter.word', $invitation) }}" class="rounded-xl border border-cyan-200 px-4 py-2 text-sm font-black text-cyan-700">Download Word</a>
        @endif
        <button onclick="window.print()" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-black text-slate-700">Print</button>
    </div>

    <article class="bg-white p-8 text-slate-950 shadow-sm ring-1 ring-slate-200 print:shadow-none print:ring-0">
        @include('exam-invitations.partials.letter-body', ['invitation' => $invitation, 'verificationUrl' => $verificationUrl])
    </article>
</div>
@endsection
