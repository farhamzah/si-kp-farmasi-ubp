@extends('layouts.app')

@section('title','Detail Review Laporan - '.config('app.name'))
@section('page_title','Detail Review Laporan')

@section('content')
@php
    $approvedLogbooks = $report->assignment->logbooks()->where('status', 'disetujui')->count();
    $pendingLogbooks = $report->assignment->logbooks()->where('status', 'menunggu_validasi')->count();
    $reviewedUnapprovedLogbooks = $report->assignment->logbooks()->whereIn('status', ['revisi', 'ditolak'])->count();
    $guidanceLogs = $report->assignment->reportGuidanceLogs
        ->filter(fn ($guidance) => $guidance->isForFieldSupervisor())
        ->sortBy('guidance_date');
    $reviewedGuidance = $guidanceLogs->whereIn('status', ['disetujui', 'revisi'])->count();
    $pendingGuidance = $guidanceLogs->where('status', 'menunggu_validasi')->count();
    $fieldGuidanceCompleted = $report->isFieldGuidanceCompleted();
    $canCompleteFieldGuidance = ! $fieldGuidanceCompleted && $reviewedGuidance > 0 && $pendingGuidance === 0;
    $guidanceLabel = $fieldGuidanceCompleted ? 'Selesai' : $reviewedGuidance.' sesi direview';
    $guidanceProgress = $fieldGuidanceCompleted ? 100 : ($reviewedGuidance > 0 ? 60 : 0);
    $hasFinalDocument = filled($report->final_document_url) || $report->files->isNotEmpty();
@endphp
<div class="space-y-5">
    @if($errors->any())
        <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">{{ $errors->first() }}</div>
    @endif
    <a href="{{ route('field-supervisor.final-reports.index', request()->query()) }}" class="inline-flex rounded-xl border border-sky-200 bg-white px-4 py-2 text-sm font-bold text-cyan-700 shadow-sm">Kembali ke daftar review</a>

    <div class="grid gap-5 xl:grid-cols-[1fr_380px]">
        <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 md:p-6">
            <p class="text-xs font-black uppercase tracking-widest text-cyan-700">Ruang review pembimbing lapangan</p>
            <p class="text-sm text-slate-500">{{ $report->assignment->student->user->name }} | {{ $report->assignment->student->nim ?: '-' }}</p>
            <div class="mt-1 flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                <div>
                    <h2 class="text-2xl font-black text-slate-950">{{ $report->assignment->place->name }}</h2>
                    <p class="mt-1 text-sm text-slate-500">Pembimbing Dalam: {{ $report->assignment->internalSupervisor ? lecturer_display_name($report->assignment->internalSupervisor) : '-' }}</p>
                </div>
                <span class="inline-flex w-fit rounded-full px-3 py-1 text-xs font-bold ring-1 {{ $report->statusBadgeClass() }}">{{ $report->statusLabel() }}</span>
            </div>

            <div class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl bg-slate-50 p-4">
                    <p class="text-xs font-black uppercase tracking-widest text-slate-500">Logbook KP</p>
                    <p class="mt-1 font-black text-slate-950">{{ $approvedLogbooks }} disetujui</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $pendingLogbooks }} menunggu validasi, {{ $reviewedUnapprovedLogbooks }} direview tidak dihitung absen</p>
                </div>
                <div class="rounded-2xl bg-cyan-50/70 p-4">
                    <p class="text-xs font-black uppercase tracking-widest text-cyan-700">Bimbingan Anda</p>
                    <p class="mt-1 font-black text-slate-950">{{ $guidanceLabel }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $pendingGuidance }} menunggu validasi</p>
                </div>
                <div class="rounded-2xl bg-slate-50 p-4">
                    <p class="text-xs font-black uppercase tracking-widest text-slate-500">Review Pembimbing Dalam</p>
                    <p class="mt-1 font-black text-slate-950">{{ $report->internalReviewStatusLabel() }}</p>
                    @if($report->internal_review_note)<p class="mt-2 text-sm text-slate-600">{{ $report->internal_review_note }}</p>@endif
                </div>
                <div class="rounded-2xl bg-slate-50 p-4">
                    <p class="text-xs font-black uppercase tracking-widest text-slate-500">Review Anda</p>
                    <p class="mt-1 font-black text-slate-950">{{ $report->fieldReviewStatusLabel() }}</p>
                    @if($report->field_review_note)<p class="mt-2 text-sm text-slate-600">{{ $report->field_review_note }}</p>@endif
                </div>
            </div>

            <h3 class="mt-6 font-black text-slate-950">Dokumen Final</h3>
            @if($report->final_document_url)
                <div class="mt-3 rounded-2xl border border-cyan-200 bg-cyan-50/50 p-4">
                    <p class="text-sm font-bold text-slate-950">{{ $report->final_document_label ?: 'Link laporan final' }}</p>
                    <p class="mt-1 text-xs leading-5 text-slate-500">Buka dokumen untuk membaca naskah final mahasiswa. Jika belum final, minta mahasiswa revisi dari aksi review.</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <a href="{{ $report->final_document_url }}" target="_blank" rel="noopener" class="inline-flex rounded-xl border border-cyan-200 bg-white px-4 py-2 text-sm font-bold text-cyan-700">Preview Dokumen</a>
                        <a href="{{ $report->final_document_url }}" target="_blank" rel="noopener" class="inline-flex rounded-xl bg-cyan-700 px-4 py-2 text-sm font-bold text-white">Buka Google Drive</a>
                    </div>
                </div>
            @endif
            <div class="mt-3 space-y-3">
                @forelse($report->files as $file)
                    <div class="flex flex-col gap-2 rounded-xl border border-slate-200 p-4 text-sm md:flex-row md:items-center md:justify-between">
                        <div>
                            <p class="font-bold text-slate-950">Versi {{ $file->version }} - {{ $file->original_filename }}</p>
                            <p class="text-xs text-slate-500">{{ $file->humanFileSize() }} | {{ $file->uploaded_at->format('d M Y H:i') }}</p>
                        </div>
                        <a href="{{ route('field-supervisor.final-reports.files.download',$file) }}" class="rounded-lg border border-cyan-200 px-3 py-1.5 text-xs font-bold text-cyan-700">Download</a>
                    </div>
                @empty
                    @unless($report->final_document_url)
                        <p class="rounded-xl bg-slate-50 px-4 py-4 text-sm leading-6 text-slate-500">Belum ada dokumen final. Mahasiswa perlu menempel link file Google Drive final atau mengunggah file sebelum laporan dapat direview.</p>
                    @endunless
                @endforelse
            </div>

            <div class="mt-6 rounded-2xl border border-cyan-100 bg-cyan-50/40 p-4">
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h3 class="font-black text-slate-950">Log Bimbingan Laporan</h3>
                        <p class="mt-1 text-sm text-slate-500">Review minimal 1 sesi bimbingan laporan lapangan. Sesi disetujui maupun revisi tetap dihitung, lalu tandai bimbingan lapangan selesai.</p>
                    </div>
                    <span class="inline-flex w-fit rounded-full bg-white px-3 py-1 text-xs font-black text-cyan-700 ring-1 ring-cyan-200">{{ $guidanceLabel }}</span>
                </div>
                <div class="mt-3 h-2.5 overflow-hidden rounded-full bg-white">
                    <div class="h-full rounded-full bg-gradient-to-r from-cyan-600 to-emerald-500" style="width: {{ $guidanceProgress }}%"></div>
                </div>
                <div class="mt-4">
                    @include('shared.final-reports.guidance-table', [
                        'guidanceLogs' => $guidanceLogs,
                        'emptyText' => 'Belum ada bimbingan laporan untuk pembimbing lapangan. Minta mahasiswa membuat log bimbingan dan memilih Pembimbing Lapangan saat mengirim log.',
                        'actions' => 'field',
                        'report' => $report,
                    ])
                </div>

                @if($fieldGuidanceCompleted)
                    <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm leading-6 text-emerald-800">
                        Bimbingan laporan lapangan sudah ditandai selesai
                        @if($report->field_guidance_completed_at) pada {{ $report->field_guidance_completed_at->format('d M Y H:i') }} @endif.
                        @if($report->field_guidance_completion_note)
                            <p class="mt-2 text-xs leading-5">{{ $report->field_guidance_completion_note }}</p>
                        @endif
                    </div>
                @elseif($canCompleteFieldGuidance)
                    <form method="POST" action="{{ route('field-supervisor.final-reports.guidance.complete', $report) }}" class="mt-4 rounded-2xl border border-emerald-200 bg-white p-4">
                        @csrf
                        <label class="block">
                            <span class="text-xs font-black uppercase tracking-widest text-emerald-700">Catatan penyelesaian bimbingan lapangan</span>
                            <textarea name="review_note" rows="3" placeholder="Contoh: Draft final sudah sesuai masukan lapangan dan siap masuk validasi akhir." class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-3 text-sm leading-6 shadow-sm"></textarea>
                        </label>
                        <button onclick="return confirm('Tandai bimbingan laporan lapangan selesai?')" class="mt-3 w-full rounded-xl bg-emerald-600 px-4 py-3 text-sm font-black text-white">Tandai Bimbingan Lapangan Selesai</button>
                    </form>
                @else
                    <p class="mt-4 rounded-xl bg-white px-4 py-3 text-xs font-semibold leading-5 text-slate-600 ring-1 ring-cyan-100">Tombol selesai muncul setelah minimal 1 sesi bimbingan lapangan sudah direview dan tidak ada log yang masih menunggu validasi.</p>
                @endif
            </div>
        </section>

        <aside class="space-y-5">
            <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 xl:sticky xl:top-24">
                <h3 class="font-black text-slate-950">Aksi Review Laporan</h3>
                @if($report->status === 'menunggu_review' && $hasFinalDocument)
                    <p class="mt-2 rounded-xl bg-cyan-50 px-4 py-3 text-xs font-semibold leading-5 text-cyan-800">Gunakan aksi ini untuk keputusan laporan final. Validasi sesi bimbingan laporan dan logbook tetap dilakukan pada modul masing-masing.</p>
                    <form method="POST" action="{{ route('field-supervisor.final-reports.approve',$report) }}" class="mt-4">
                        @csrf
                        <textarea name="review_note" rows="4" placeholder="Catatan persetujuan opsional untuk mahasiswa" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm leading-6"></textarea>
                        <button class="mt-3 w-full rounded-lg bg-emerald-600 px-4 py-2 text-sm font-bold text-white">Setujui Laporan</button>
                    </form>
                    <form method="POST" action="{{ route('field-supervisor.final-reports.revision',$report) }}" class="mt-4">
                        @csrf
                        <textarea name="review_note" rows="4" required placeholder="Tuliskan poin revisi laporan yang perlu diperbaiki mahasiswa" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm leading-6"></textarea>
                        <button class="mt-3 w-full rounded-lg bg-blue-600 px-4 py-2 text-sm font-bold text-white">Minta Revisi</button>
                    </form>
                    <form method="POST" action="{{ route('field-supervisor.final-reports.reject',$report) }}" class="mt-4">
                        @csrf
                        <textarea name="review_note" rows="4" required placeholder="Tuliskan alasan penolakan dengan jelas" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm leading-6"></textarea>
                        <button onclick="return confirm('Tolak laporan ini?')" class="mt-3 w-full rounded-lg bg-red-600 px-4 py-2 text-sm font-bold text-white">Tolak</button>
                    </form>
                @else
                    <p class="mt-4 rounded-xl bg-slate-50 px-4 py-4 text-sm leading-6 text-slate-500">Aksi review tersedia setelah mahasiswa submit link/file laporan final. Selama proses bimbingan, validasi log bimbingan dan logbook yang masuk terlebih dahulu.</p>
                @endif
            </section>
            <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <h3 class="font-black text-slate-950">Catatan</h3>
                <p class="mt-2 text-sm text-slate-600">Validasi laporan final dilakukan setelah mahasiswa mengunggah link/file final. Validasi logbook KP tetap dilakukan dari menu Validasi Logbook.</p>
            </section>
        </aside>
    </div>
</div>
@endsection
