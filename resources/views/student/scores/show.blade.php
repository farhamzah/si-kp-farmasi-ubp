@extends('layouts.app')
@section('title','Nilai KP - '.config('app.name'))
@section('page_title','Nilai KP')
@section('content')
<div class="space-y-6">
    @if(! $assignment)
        <section class="rounded-3xl bg-white p-8 text-center shadow-sm ring-1 ring-slate-100"><h2 class="text-lg font-black text-slate-950">Nilai akhir belum tersedia</h2><p class="mt-2 text-sm text-slate-500">Anda belum memiliki penempatan KP aktif.</p></section>
    @elseif(! $finalScore)
        <section class="rounded-3xl bg-white p-8 text-center shadow-sm ring-1 ring-slate-100"><h2 class="text-lg font-black text-slate-950">Nilai akhir belum tersedia</h2><p class="mt-2 text-sm text-slate-500">Nilai sedang diproses oleh Koordinator KP.</p></section>
    @elseif(! $finalScore->isVisibleToStudent())
        <section class="rounded-3xl bg-white p-8 text-center shadow-sm ring-1 ring-slate-100"><h2 class="text-lg font-black text-slate-950">Nilai sedang diproses</h2><p class="mt-2 text-sm text-slate-500">Nilai akhir belum dipublish oleh Koordinator KP.</p></section>
    @else
        <section class="rounded-3xl bg-white p-8 text-center shadow-sm ring-1 ring-slate-100"><p class="text-sm font-black uppercase tracking-widest text-cyan-700">Nilai Akhir KP</p><p class="mt-4 text-6xl font-black text-slate-950">{{ $finalScore->final_score }}</p><span class="mt-4 inline-flex rounded-full {{ $finalScore->gradeBadgeClass() }} px-5 py-2 text-lg font-black">{{ $finalScore->final_grade }}</span><p class="mt-4 text-sm text-slate-500">{{ $finalScore->note }}</p></section>
    @endif
</div>
@endsection
