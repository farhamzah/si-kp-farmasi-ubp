@extends('layouts.app')

@section('title','Surat Undangan Sidang KP - '.config('app.name'))
@section('page_title','Surat Undangan Sidang KP')

@section('content')
@php
    $exam = $invitation->exam;
    $assignment = $exam->assignment;
    $student = $assignment?->student;
@endphp

<style>
    .invitation-sheet { width: 210mm; min-height: 297mm; padding: 15mm 17mm 14mm; box-sizing: border-box; overflow: hidden; }
    @media (max-width: 900px) {
        .invitation-sheet { width: 100%; min-height: 0; padding: 24px; }
    }
    @media print {
        @page { size: A4 portrait; margin: 0; }
        body * { visibility: hidden !important; }
        #invitation-letter, #invitation-letter * { visibility: visible !important; }
        #invitation-letter { position: absolute; top: 0; left: 0; width: 210mm; min-height: 297mm; margin: 0; padding: 15mm 17mm 14mm; box-shadow: none; }
    }
</style>

<div class="mx-auto space-y-4">
    <div class="flex flex-wrap justify-end gap-2 print:hidden">
        <a href="{{ route('management.exams.show', $invitation->exam) }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-black text-slate-700">Kembali</a>
        <a href="{{ route('exam-invitations.letter.pdf', $invitation) }}" class="rounded-xl bg-cyan-700 px-4 py-2 text-sm font-black text-white">Download PDF</a>
        @if(in_array(session('active_role'), ['admin', 'koordinator_kp'], true))
            <a href="{{ route('exam-invitations.letter.word', $invitation) }}" class="rounded-xl border border-cyan-200 px-4 py-2 text-sm font-black text-cyan-700">Download Word</a>
        @endif
        <button onclick="window.print()" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-black text-slate-700">Print</button>
    </div>

    @if(in_array(session('active_role'), ['admin', 'koordinator_kp'], true))
        <details class="rounded-2xl border border-amber-200 bg-amber-50 p-4 print:hidden">
            <summary class="cursor-pointer text-sm font-black text-amber-800">Bangun Ulang Undangan</summary>
            <form method="POST" action="{{ route('management.exam-invitations.rebuild', $invitation) }}" class="mt-3 flex flex-col gap-2 sm:flex-row" onsubmit="return confirm('QR dokumen dan QR penandatangan lama akan dicabut. Lanjutkan?')">
                @csrf
                <input name="reason" required maxlength="1000" placeholder="Alasan rebuild, misalnya menambahkan QR atau memperbaiki pejabat" class="min-w-0 flex-1 rounded-xl border border-amber-300 px-3 py-2 text-sm">
                <button class="rounded-xl bg-amber-700 px-4 py-2 text-sm font-black text-white">Rebuild Dokumen</button>
            </form>
        </details>
    @endif

    <article id="invitation-letter" class="invitation-sheet mx-auto bg-white text-slate-950 shadow-sm ring-1 ring-slate-200">
        @include('exam-invitations.partials.letter-body', ['invitation' => $invitation, 'verificationUrl' => $verificationUrl])
    </article>
</div>
@endsection
