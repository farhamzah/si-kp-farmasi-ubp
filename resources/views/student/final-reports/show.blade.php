@extends('layouts.app')
@section('title','Laporan Akhir - '.config('app.name'))
@section('page_title','Laporan Akhir')
@section('content')
<div class="space-y-5">
    @if($errors->any())<div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>@endif
    @if(! $assignment)
        <section class="rounded-2xl bg-white p-10 text-center shadow-sm ring-1 ring-slate-200"><h2 class="text-xl font-bold text-slate-950">Anda belum memiliki penempatan KP aktif.</h2><p class="mt-2 text-sm text-slate-500">Laporan akhir dapat diupload setelah penempatan KP aktif atau berjalan.</p></section>
    @else
        <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div><p class="text-xs font-semibold uppercase tracking-widest text-teal-700">Penempatan KP</p><h2 class="mt-1 text-2xl font-bold text-slate-950">{{ $assignment->place->name }}</h2><p class="mt-1 text-sm text-slate-500">Pembimbing Dalam: {{ $assignment->internalSupervisor?->user?->name ?? '-' }}</p></div>
                @if($report)<span class="rounded-full px-3 py-1 text-xs font-semibold ring-1 {{ $report->statusBadgeClass() }}">{{ $report->statusLabel() }}</span>@endif
            </div>
            @if($report)
                <div class="mt-5 grid gap-4 md:grid-cols-3">
                    <div class="rounded-xl bg-slate-50 p-4"><p class="text-xs text-slate-500">Versi Saat Ini</p><p class="mt-2 text-2xl font-bold">{{ $report->current_version }}</p></div>
                    <div class="rounded-xl bg-slate-50 p-4"><p class="text-xs text-slate-500">Progress</p><p class="mt-2 text-sm font-bold">{{ $report->progressLabel() }}</p></div>
                    <div class="rounded-xl bg-slate-50 p-4"><p class="text-xs text-slate-500">File Terakhir</p><p class="mt-2 text-sm font-bold">{{ $report->latestFile?->original_filename ?? 'Belum upload' }}</p></div>
                </div>
                @if($report->review_note)<div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">{{ $report->review_note }}</div>@endif
                @if($report->isApproved())<div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">Laporan akhir telah disetujui. Anda dapat melanjutkan ke tahap pengajuan sidang pada tahap berikutnya.</div>@endif
            @endif
        </section>
        <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h3 class="text-lg font-bold text-slate-950">Upload Laporan</h3>
            <p class="mt-1 text-sm text-slate-500">Format PDF, DOC, atau DOCX. Maksimal 10MB. Semua versi laporan disimpan untuk audit.</p>
            @if(! $report || $report->canBeEditedByStudent())
                <form method="POST" action="{{ route('student.final-reports.upload') }}" enctype="multipart/form-data" class="mt-4 grid gap-3 md:grid-cols-[1fr_1fr_auto]">@csrf<input type="file" name="report_file" class="rounded-lg border border-slate-300 px-3 py-2 text-sm"><input name="note" placeholder="Catatan upload opsional" class="rounded-lg border border-slate-300 px-3 py-2 text-sm"><button class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white">Upload</button></form>
            @else
                <p class="mt-4 rounded-lg bg-slate-50 px-4 py-3 text-sm text-slate-500">Upload tidak tersedia pada status saat ini.</p>
            @endif
            @if($report?->canBeSubmitted())
                <form method="POST" action="{{ route('student.final-reports.submit') }}" class="mt-4">@csrf<button onclick="return confirm('Submit laporan untuk review pembimbing dalam?')" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Submit untuk Review</button></form>
            @endif
        </section>
        <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h3 class="text-lg font-bold text-slate-950">Riwayat Versi</h3>
            <div class="mt-4 space-y-3">@forelse($report?->files ?? [] as $file)<div class="flex flex-col gap-2 rounded-xl border border-slate-200 p-4 md:flex-row md:items-center md:justify-between"><div><p class="font-semibold">Versi {{ $file->version }} - {{ $file->original_filename }}</p><p class="text-xs text-slate-500">{{ $file->humanFileSize() }} | {{ $file->uploaded_at->format('d M Y H:i') }}</p></div><a href="{{ route('student.final-reports.files.download',$file) }}" class="rounded-lg border border-teal-200 px-3 py-1.5 text-xs font-semibold text-teal-700">Download</a></div>@empty<p class="rounded-lg bg-slate-50 px-4 py-3 text-sm text-slate-500">Belum ada file laporan.</p>@endforelse</div>
        </section>
    @endif
</div>
@endsection
