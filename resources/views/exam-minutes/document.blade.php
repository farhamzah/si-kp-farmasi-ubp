@extends('layouts.app')
@section('title','Berita Acara Sidang KP - '.config('app.name'))
@section('page_title','Berita Acara Sidang KP')
@section('content')
<style>
    .minutes-sheet { width: 210mm; min-height: 297mm; padding: 15mm 17mm 14mm; box-sizing: border-box; overflow: hidden; }
    @media (max-width: 900px) { .minutes-sheet { width: 100%; min-height: 0; padding: 24px; } }
    @media print { @page { size: A4 portrait; margin: 0; } body * { visibility: hidden !important; } #minutes-document, #minutes-document * { visibility: visible !important; } #minutes-document { position: absolute; inset: 0; width: 210mm; min-height: 297mm; margin: 0; padding: 15mm 17mm 14mm; box-shadow: none; } }
</style>
<div class="mx-auto space-y-4">
    <div class="flex flex-wrap justify-end gap-2 print:hidden">
        @if(in_array(session('active_role'), ['admin', 'koordinator_kp'], true))<a href="{{ route('management.exams.show', $minute->exam) }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-black text-slate-700">Kembali</a>@endif
        <a href="{{ route('exam-minutes.pdf', $minute) }}" class="rounded-xl bg-cyan-700 px-4 py-2 text-sm font-black text-white">Download PDF</a>
        <button onclick="window.print()" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-black text-slate-700">Print</button>
    </div>
    @if($minute->status === 'terbit' && in_array(session('active_role'), ['admin', 'koordinator_kp'], true))
        <details class="rounded-2xl border border-amber-200 bg-amber-50 p-4 print:hidden">
            <summary class="cursor-pointer text-sm font-black text-amber-800">Bangun Ulang Berita Acara</summary>
            <form method="POST" action="{{ route('management.exam-minutes.rebuild', $minute) }}" class="mt-3 flex flex-col gap-2 sm:flex-row" onsubmit="return confirm('QR dokumen dan QR penandatangan lama akan dicabut. Lanjutkan?')">
                @csrf
                <input name="reason" required maxlength="1000" placeholder="Alasan rebuild dokumen" class="min-w-0 flex-1 rounded-xl border border-amber-300 px-3 py-2 text-sm">
                <button class="rounded-xl bg-amber-700 px-4 py-2 text-sm font-black text-white">Rebuild Dokumen</button>
            </form>
        </details>
    @endif
    <article id="minutes-document" class="minutes-sheet mx-auto bg-white shadow-sm ring-1 ring-slate-200">
        @include('exam-minutes.partials.document-body')
    </article>
</div>
@endsection
