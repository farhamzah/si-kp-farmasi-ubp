@extends('layouts.app')
@section('title','Detail Validasi Sidang - '.config('app.name'))
@section('page_title','Detail Validasi Sidang')
@section('content')
@php
    $assignment = $examRequest->assignment;
    $eligibility = $assignment->examEligibility();
    $report = $assignment->finalReport;
    $canReview = in_array($examRequest->status, ['diajukan', 'revisi'], true);
    $canApprove = $canReview && $eligibility['ready'];
@endphp
<div class="space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('management.exam-requests.index') }}" class="inline-flex rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 shadow-sm">Kembali ke Antrian</a>
        @if($examRequest->canBeScheduled() && $eligibility['ready'] && ! $examRequest->exam)
            <a href="{{ route('management.exam-requests.schedule', $examRequest) }}" class="inline-flex rounded-xl bg-cyan-700 px-4 py-2 text-sm font-bold text-white shadow-sm">Lanjut Jadwalkan Sidang</a>
        @endif
    </div>

    <div class="grid gap-5 xl:grid-cols-[1fr_380px]">
        <div class="space-y-5">
            <x-ui.card>
                <div class="flex flex-col gap-5 md:flex-row md:items-start md:justify-between">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full px-3 py-1 text-xs font-bold ring-1 {{ $examRequest->statusBadgeClass() }}">{{ $examRequest->statusLabel() }}</span>
                            <span class="rounded-full px-3 py-1 text-xs font-bold ring-1 {{ $eligibility['ready'] ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-amber-50 text-amber-700 ring-amber-200' }}">{{ $eligibility['ready'] ? 'Syarat lengkap' : 'Ada syarat tertahan' }}</span>
                        </div>
                        <h2 class="mt-4 text-3xl font-black text-slate-950">{{ $assignment->student->user->name }}</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ $assignment->student->nim ?: '-' }} · {{ $assignment->period->name }}</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-4 text-sm ring-1 ring-slate-200 md:min-w-64">
                        <p class="text-xs font-black uppercase tracking-widest text-slate-500">Tempat KP</p>
                        <p class="mt-2 text-lg font-black text-slate-950">{{ $assignment->place->name }}</p>
                    </div>
                </div>

                <div class="mt-6 grid gap-3 md:grid-cols-3">
                    <div class="rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-100">
                        <p class="text-xs font-black uppercase tracking-widest text-slate-500">Pembimbing Dalam</p>
                        <p class="mt-2 font-bold text-slate-950">{{ $assignment->internalSupervisor ? lecturer_display_name($assignment->internalSupervisor) : '-' }}</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-100">
                        <p class="text-xs font-black uppercase tracking-widest text-slate-500">Pembimbing Lapangan</p>
                        <p class="mt-2 font-bold text-slate-950">{{ $assignment->fieldSupervisor ? field_supervisor_display_name($assignment->fieldSupervisor) : '-' }}</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-100">
                        <p class="text-xs font-black uppercase tracking-widest text-slate-500">Tanggal Pengajuan</p>
                        <p class="mt-2 font-bold text-slate-950">{{ $examRequest->submitted_at?->format('d M Y H:i') ?: '-' }}</p>
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card>
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <p class="text-xs font-black uppercase tracking-widest text-cyan-700">Syarat masuk jadwal sidang</p>
                        <h3 class="mt-1 text-xl font-black text-slate-950">Checklist kesiapan mahasiswa</h3>
                    </div>
                    <span class="w-fit rounded-full px-3 py-1 text-xs font-bold ring-1 {{ $eligibility['ready'] ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-amber-50 text-amber-700 ring-amber-200' }}">{{ collect($eligibility['items'])->where('ready', true)->count() }}/{{ count($eligibility['items']) }} lengkap</span>
                </div>
                <div class="mt-5 grid gap-3 md:grid-cols-2">
                    @foreach($eligibility['items'] as $item)
                        <div class="rounded-2xl border p-4 {{ $item['ready'] ? 'border-emerald-200 bg-emerald-50/50' : 'border-amber-200 bg-amber-50/60' }}">
                            <div class="flex items-start gap-3">
                                <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-[11px] font-black {{ $item['ready'] ? 'bg-emerald-600 text-white' : 'bg-amber-500 text-white' }}">{{ $item['ready'] ? 'OK' : '!' }}</span>
                                <div>
                                    <p class="font-black text-slate-950">{{ $item['label'] }}</p>
                                    <p class="mt-1 text-sm leading-6 text-slate-600">{{ $item['description'] }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-ui.card>

            <x-ui.card>
                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                    <div>
                        <p class="text-xs font-black uppercase tracking-widest text-cyan-700">Laporan akhir</p>
                        <h3 class="mt-1 text-xl font-black text-slate-950">{{ $report?->final_document_label ?: $report?->latestFile?->original_filename ?: 'Dokumen final mahasiswa' }}</h3>
                        <p class="mt-1 text-sm text-slate-500">Status laporan: {{ $report?->statusLabel() ?? 'Belum tersedia' }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @if($report?->final_document_url)
                            <a href="{{ $report->final_document_url }}" target="_blank" rel="noopener" class="rounded-xl border border-cyan-200 px-4 py-2 text-sm font-bold text-cyan-700">Preview Link</a>
                        @endif
                        @if($report)
                            <a href="{{ route('management.final-reports.show', $report) }}" class="rounded-xl bg-slate-950 px-4 py-2 text-sm font-bold text-white">Detail Laporan</a>
                        @endif
                    </div>
                </div>
            </x-ui.card>
        </div>

        <aside class="space-y-5">
            <x-ui.card>
                <h3 class="text-lg font-black text-slate-950">Validasi Koordinator</h3>
                <p class="mt-1 text-sm leading-6 text-slate-600">Setujui hanya jika checklist sudah lengkap. Setelah disetujui, mahasiswa masuk tahap penjadwalan sidang.</p>

                @if($canReview)
                    <form method="POST" action="{{ route('management.exam-requests.approve', $examRequest) }}" class="mt-4">
                        @csrf
                        <textarea name="review_note" rows="2" placeholder="Catatan opsional untuk persetujuan" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm shadow-sm"></textarea>
                        <button @disabled(! $canApprove) class="mt-3 w-full rounded-xl px-4 py-3 text-sm font-bold text-white shadow-sm {{ $canApprove ? 'bg-emerald-600' : 'cursor-not-allowed bg-slate-300' }}">Setujui Masuk Jadwal</button>
                        @unless($canApprove)
                            <p class="mt-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-800">Validasi akhir terkunci sampai semua checklist kesiapan sidang berstatus OK.</p>
                        @endunless
                    </form>
                    <form method="POST" action="{{ route('management.exam-requests.revision', $examRequest) }}" class="mt-4">
                        @csrf
                        <textarea name="review_note" rows="2" required placeholder="Catatan revisi untuk mahasiswa" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm shadow-sm"></textarea>
                        <button class="mt-3 w-full rounded-xl bg-blue-600 px-4 py-3 text-sm font-bold text-white shadow-sm">Minta Revisi</button>
                    </form>
                    <form method="POST" action="{{ route('management.exam-requests.reject', $examRequest) }}" class="mt-4">
                        @csrf
                        <textarea name="review_note" rows="2" required placeholder="Alasan penolakan" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm shadow-sm"></textarea>
                        <button class="mt-3 w-full rounded-xl bg-red-600 px-4 py-3 text-sm font-bold text-white shadow-sm">Tolak Pengajuan</button>
                    </form>
                @else
                    <div class="mt-4 rounded-2xl bg-slate-50 p-4 text-sm text-slate-600 ring-1 ring-slate-200">Pengajuan ini sudah diproses. Gunakan tombol jadwal atau riwayat untuk tindak lanjut.</div>
                @endif

                @if($examRequest->exam)
                    <a href="{{ route('management.exams.show', $examRequest->exam) }}" class="mt-4 block rounded-xl bg-cyan-700 px-4 py-3 text-center text-sm font-bold text-white shadow-sm">Lihat Jadwal Sidang</a>
                @elseif($examRequest->canBeScheduled() && $eligibility['ready'])
                    <a href="{{ route('management.exam-requests.schedule', $examRequest) }}" class="mt-4 block rounded-xl border border-cyan-200 px-4 py-3 text-center text-sm font-bold text-cyan-700 shadow-sm">Buka Form Jadwal</a>
                @endif
            </x-ui.card>

            <x-ui.card>
                <h3 class="text-lg font-black text-slate-950">Riwayat</h3>
                <div class="mt-4 space-y-3">
                    @forelse($examRequest->logs as $log)
                        <div class="rounded-xl border border-slate-200 p-3 text-sm">
                            <p class="font-bold text-slate-950">{{ str_replace('_', ' ', $log->action) }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $log->user?->name ?? '-' }} · {{ $log->created_at?->format('d M Y H:i') }}</p>
                            @if($log->note)
                                <p class="mt-2 rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-600">{{ $log->note }}</p>
                            @endif
                        </div>
                    @empty
                        <p class="rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-500">Belum ada riwayat.</p>
                    @endforelse
                </div>
            </x-ui.card>
        </aside>
    </div>
</div>
@endsection
