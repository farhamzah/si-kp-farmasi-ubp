@extends('layouts.app')
@section('title','Nilai KP - '.config('app.name'))
@section('page_title','Nilai KP')
@section('content')
<div class="space-y-6">
    @if(! $assignment)
        <section class="rounded-3xl bg-white p-8 text-center shadow-sm ring-1 ring-slate-100">
            <h2 class="text-lg font-black text-slate-950">Nilai akhir belum tersedia</h2>
            <p class="mt-2 text-sm text-slate-500">Anda belum memiliki penempatan KP aktif.</p>
        </section>
    @elseif(! ($scoreVisibility['visible'] ?? false))
        <section class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-100">
            <div class="border-b border-slate-100 p-6">
                <p class="text-xs font-black uppercase tracking-widest text-cyan-700">Status akses nilai</p>
                <h2 class="mt-2 text-2xl font-black text-slate-950">Nilai belum dapat dibuka</h2>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">{{ $scoreVisibility['message'] ?? 'Nilai belum tersedia.' }}</p>
            </div>

            <div class="grid gap-4 p-6 lg:grid-cols-[1fr_320px]">
                <div class="space-y-3">
                    <h3 class="text-base font-black text-slate-950">Syarat Pembukaan Nilai</h3>
                    @foreach(($scoreVisibility['requirements'] ?? []) as $requirement)
                        <div class="rounded-2xl border {{ $requirement['ready'] ? 'border-emerald-200 bg-emerald-50/50' : 'border-amber-200 bg-amber-50/50' }} p-4">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <p class="font-black text-slate-950">{{ $requirement['label'] }}</p>
                                    <p class="mt-1 text-sm leading-6 text-slate-600">{{ $requirement['description'] }}</p>
                                </div>
                                <span class="inline-flex w-fit rounded-full {{ $requirement['ready'] ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }} px-3 py-1 text-xs font-black">
                                    {{ $requirement['ready'] ? 'OK' : 'Belum' }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>

                <aside class="rounded-2xl bg-slate-50 p-5">
                    <p class="text-xs font-black uppercase tracking-widest text-slate-500">Informasi</p>
                    <p class="mt-3 text-sm leading-6 text-slate-600">Nilai hanya tampil setelah dipublish, akses periode dibuka oleh Koordinator KP, dan syarat akhir Anda lengkap.</p>
                    <p class="mt-4 text-sm font-bold text-slate-950">{{ $assignment->period?->name }}</p>
                    <p class="text-sm text-slate-500">{{ $assignment->place?->name }}</p>
                </aside>
            </div>
        </section>
    @else
        <section class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-100">
            <div class="bg-slate-950 p-8 text-center text-white">
                <p class="text-sm font-black uppercase tracking-widest text-cyan-200">Nilai Akhir KP</p>
                <p class="mt-4 text-6xl font-black">{{ $finalScore->final_score }}</p>
                <span class="mt-4 inline-flex rounded-full bg-white px-5 py-2 text-lg font-black text-slate-950">{{ $finalScore->final_grade }}</span>
                <p class="mt-4 text-sm text-slate-300">{{ $finalScore->note }}</p>
            </div>

            @if($breakdown)
                <div class="grid gap-3 p-6 md:grid-cols-4">
                    @foreach($breakdown['sections'] as $section)
                        <div class="rounded-2xl border border-slate-100 bg-slate-50 p-5">
                            <p class="text-xs font-black uppercase tracking-widest text-slate-500">{{ $section['label'] }}</p>
                            <p class="mt-3 text-3xl font-black text-slate-950">{{ number_format($section['score'], 2) }}</p>
                            <p class="mt-1 text-sm text-slate-500">Bobot {{ $section['weight'] }}% - kontribusi {{ number_format($section['contribution'], 2) }}</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
            <h3 class="text-base font-black text-slate-950">Syarat Pembukaan Nilai</h3>
            <div class="mt-4 grid gap-3 md:grid-cols-2">
                @foreach(($scoreVisibility['requirements'] ?? []) as $requirement)
                    <div class="rounded-2xl border border-emerald-100 bg-emerald-50/50 p-4">
                        <p class="font-black text-slate-950">{{ $requirement['label'] }}</p>
                        <p class="mt-1 text-sm text-slate-600">{{ $requirement['description'] }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</div>
@endsection
