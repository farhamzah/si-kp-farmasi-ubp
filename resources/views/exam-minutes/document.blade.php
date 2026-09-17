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
        <a href="{{ route('exam-minutes.pdf', $minute) }}" class="rounded-xl bg-cyan-700 px-4 py-2 text-sm font-black text-white">Download PDF</a>
        <button onclick="window.print()" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-black text-slate-700">Print</button>
    </div>
    <article id="minutes-document" class="minutes-sheet mx-auto bg-white shadow-sm ring-1 ring-slate-200">
        @include('exam-minutes.partials.document-body')
    </article>
</div>
@endsection
