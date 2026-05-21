@extends('layouts.app')
@section('title','Sidang KP - '.config('app.name'))
@section('page_title','Sidang KP')
@section('content')
<div class="space-y-5">
    @if($errors->any())<div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>@endif
    @if(! $assignment)
        <x-ui.empty-state title="Anda belum memiliki penempatan KP aktif." description="Pengajuan sidang tersedia setelah penempatan dan laporan akhir selesai." />
    @else
        <x-ui.status-stepper :steps="[
            ['label' => 'Laporan Disetujui', 'state' => $assignment->finalReport?->isApproved() ? 'done' : 'warning', 'description' => $assignment->finalReport?->statusLabel() ?? 'Belum ada laporan'],
            ['label' => 'Pengajuan Sidang', 'state' => $examRequest ? 'done' : 'pending', 'description' => $examRequest?->statusLabel() ?? 'Belum diajukan'],
            ['label' => 'Dijadwalkan', 'state' => $exam ? 'done' : 'pending', 'description' => $exam?->scheduleLabel() ?? 'Menunggu jadwal'],
            ['label' => 'Selesai', 'state' => $exam?->status === 'selesai' ? 'done' : 'pending', 'description' => $exam?->statusLabel() ?? 'Belum selesai'],
        ]" />
        <x-ui.card>
            <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-widest text-cyan-700">Status Sidang</p>
                    <h2 class="mt-1 text-2xl font-bold text-slate-950">{{ $assignment->place->name }}</h2>
                    <p class="mt-1 text-sm text-slate-500">Pembimbing Dalam: {{ $assignment->internalSupervisor?->user?->name ?? '-' }}</p>
                </div>
                @if($examRequest)<span class="rounded-full px-3 py-1 text-xs font-semibold ring-1 {{ $examRequest->statusBadgeClass() }}">{{ $examRequest->statusLabel() }}</span>@endif
            </div>
            @if(! $assignment->finalReport?->isApproved())
                <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">Pengajuan sidang dapat dilakukan setelah laporan akhir disetujui oleh Pembimbing Dalam.</div>
            @elseif(! $examRequest)
                <form method="POST" action="{{ route('student.exams.submit') }}" class="mt-5 space-y-3">@csrf<textarea name="request_note" rows="3" placeholder="Catatan pengajuan opsional" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea><button class="rounded-lg bg-cyan-700 px-4 py-2 text-sm font-semibold text-white">Ajukan Sidang</button></form>
            @endif
            @if($examRequest?->review_note)<div class="mt-5 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">{{ $examRequest->review_note }}</div>@endif
        </x-ui.card>
        @if($exam)
            <x-ui.card>
                <h3 class="text-lg font-bold text-slate-950">Jadwal Sidang</h3>
                <div class="mt-4 grid gap-4 md:grid-cols-3">
                    <div class="rounded-xl bg-slate-50 p-4"><p class="text-xs text-slate-500">Tanggal & Jam</p><p class="mt-1 font-bold">{{ $exam->scheduleLabel() }}</p></div>
                    <div class="rounded-xl bg-slate-50 p-4"><p class="text-xs text-slate-500">Mode</p><p class="mt-1 font-bold">{{ $exam->modeLabel() }}</p></div>
                    <div class="rounded-xl bg-slate-50 p-4"><p class="text-xs text-slate-500">Penguji</p><p class="mt-1 font-bold">{{ $exam->examiner?->user?->name ?? '-' }}</p></div>
                </div>
                <p class="mt-4 text-sm text-slate-600">Lokasi: {{ $exam->room ?: '-' }} | Link: {{ $exam->meeting_link ?: '-' }}</p>
            </x-ui.card>
        @endif
    @endif
</div>
@endsection
