@extends('layouts.app')

@section('title','Laporan Sidang KP - '.config('app.name'))
@section('page_title','Laporan Sidang KP')

@section('content')
<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('exam-invitations.index') }}" class="si-btn si-btn-secondary">Kembali ke Undangan</a>
        <div class="flex flex-wrap gap-2">
            @if($documentUrl)
                <a href="{{ $documentUrl }}" target="_blank" rel="noopener noreferrer" class="si-btn si-btn-primary">Buka Laporan di Drive</a>
            @endif
            @if($file)
                <a href="{{ route('exam-invitations.report.file', ['exam' => $exam, 'download' => 1]) }}" class="si-btn si-btn-secondary">Download {{ $documentUrl ? 'Berkas Lokal' : 'Laporan' }}</a>
            @endif
        </div>
    </div>

    <section class="si-card p-5">
        <p class="text-xs font-bold uppercase text-cyan-700">Dokumen untuk sidang KP</p>
        <h2 class="mt-1 text-xl font-bold text-slate-950">{{ $report->report_title ?: ($report->final_document_label ?: ($file?->original_filename ?: 'Laporan KP')) }}</h2>
        <p class="mt-2 text-sm text-slate-600">{{ $exam->assignment->student->user->name }} · {{ $exam->assignment->student->nim ?: '-' }} · {{ $exam->assignment->place?->name ?? '-' }}</p>
        <p class="mt-1 text-sm text-slate-500">Status: {{ $report->statusLabel() }} · Versi {{ $report->current_version }}</p>
    </section>

    @if($documentUrl)
        <section class="si-card p-5 text-sm text-slate-600">
            Laporan final tersimpan di Google Drive. Gunakan tombol <strong>Buka Laporan di Drive</strong> untuk melihatnya. Jika akses ditolak, minta mahasiswa memberikan izin kepada akun penguji.
        </section>
    @elseif($isPdf)
        <iframe src="{{ route('exam-invitations.report.file', $exam) }}" title="Preview laporan KP" class="h-[75vh] min-h-[520px] w-full border border-slate-200 bg-white"></iframe>
    @elseif($file)
        <section class="si-card p-5 text-sm text-slate-600">
            Berkas {{ $file->original_filename }} tidak dapat ditampilkan langsung di browser. Gunakan tombol <strong>Download Laporan</strong>.
        </section>
    @else
        <section class="si-card p-5 text-sm text-amber-800">Dokumen laporan belum tersedia. Hubungi koordinator atau mahasiswa untuk melengkapinya.</section>
    @endif
</div>
@endsection
