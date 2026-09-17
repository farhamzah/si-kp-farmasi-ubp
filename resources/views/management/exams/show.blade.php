@extends('layouts.app')
@section('title','Detail Jadwal Sidang - '.config('app.name'))
@section('page_title','Detail Jadwal Sidang')
@section('content')
<div class="grid gap-5 lg:grid-cols-[1fr_360px]">
    <x-ui.card>
        <p class="text-sm text-slate-500">{{ $exam->assignment->student->user->name }} | {{ $exam->assignment->student->nim ?: '-' }}</p>
        <h2 class="mt-1 text-2xl font-bold text-slate-950">{{ $exam->assignment->place->name }}</h2>
        <span class="mt-3 inline-flex rounded-full px-3 py-1 text-xs font-semibold ring-1 {{ $exam->statusBadgeClass() }}">{{ $exam->statusLabel() }}</span>
        <div class="mt-5 grid gap-4 md:grid-cols-2"><div class="rounded-xl bg-slate-50 p-4"><p class="text-xs text-slate-500">Jadwal</p><p class="font-bold">{{ $exam->scheduleLabel() }}</p></div><div class="rounded-xl bg-slate-50 p-4"><p class="text-xs text-slate-500">Mode</p><p class="font-bold">{{ $exam->modeLabel() }}</p></div><div class="rounded-xl bg-slate-50 p-4"><p class="text-xs text-slate-500">Pembimbing</p><p class="font-bold">{{ $exam->supervisor ? lecturer_display_name($exam->supervisor) : '-' }}</p></div><div class="rounded-xl bg-slate-50 p-4"><p class="text-xs text-slate-500">Penguji</p><p class="font-bold">{{ $exam->examinerNamesLabel() }}</p></div><div class="rounded-xl bg-slate-50 p-4"><p class="text-xs text-slate-500">Ketua Sidang</p><p class="font-bold">{{ $exam->chair ? lecturer_display_name($exam->chair) : '-' }}</p></div><div class="rounded-xl bg-slate-50 p-4"><p class="text-xs text-slate-500">Nomor Berita Acara</p><p class="font-bold">{{ $exam->minutes_number ?: '-' }}</p></div></div>
        <p class="mt-4 text-sm text-slate-600">Ruangan: {{ $exam->room ?: '-' }} | Link: {{ $exam->meeting_link ?: '-' }}</p>
    </x-ui.card>
    <aside class="space-y-5">
        <x-ui.card><a href="{{ route('management.exams.edit',$exam) }}" class="block rounded-lg bg-cyan-700 px-4 py-2 text-center text-sm font-semibold text-white">Edit Jadwal</a>@if($exam->canBeCancelled())<form method="POST" action="{{ route('management.exams.cancel',$exam) }}" class="mt-4">@csrf<input name="reason" required placeholder="Alasan pembatalan" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"><button class="mt-2 w-full rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white">Batalkan Sidang</button></form>@endif</x-ui.card>
        <x-ui.card>
            <p class="text-xs font-black uppercase tracking-widest text-cyan-700">Berita Acara</p>
            <h3 class="mt-1 text-lg font-black">{{ $exam->minutes?->statusLabel() ?? 'Belum dibuat Ketua Sidang' }}</h3>
            @if($exam->minutes)
                <p class="mt-2 text-sm text-slate-600">Keputusan: <strong>{{ $exam->minutes->resultLabel() }}</strong></p>
                <div class="mt-4 grid gap-2"><a href="{{ route('exam-minutes.preview',$exam->minutes) }}" class="rounded-xl border border-cyan-200 px-4 py-2 text-center text-sm font-bold text-cyan-700">Preview</a><a href="{{ route('exam-minutes.pdf',$exam->minutes) }}" class="rounded-xl border border-slate-200 px-4 py-2 text-center text-sm font-bold text-slate-700">Download PDF</a></div>
                @if($exam->minutes->status === 'siap_terbit')<form method="POST" action="{{ route('management.exam-minutes.publish',$exam->minutes) }}" class="mt-3" onsubmit="return confirm('Terbitkan berita acara resmi?')">@csrf<button class="w-full rounded-xl bg-emerald-600 px-4 py-2 text-sm font-black text-white">Validasi dan Terbitkan</button></form>@elseif($exam->minutes->status === 'menunggu_nilai')<p class="mt-3 rounded-xl bg-amber-50 p-3 text-xs text-amber-800">Dokumen sudah tercatat, tetapi penerbitan menunggu seluruh nilai wajib disubmit.</p>@endif
            @else<p class="mt-3 text-sm text-slate-600">Ketua Sidang menutup sidang dari menu Jadwal Sidang pada akunnya.</p>@endif
        </x-ui.card>
    </aside>
</div>
@endsection
