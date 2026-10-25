@extends('layouts.app')
@section('title','Detail Laporan Akhir - '.config('app.name'))
@section('page_title','Detail Laporan Akhir')
@section('content')
@php($isCoordinator = session('active_role') === 'koordinator_kp' && auth()->user()->hasRole('koordinator_kp'))
<div class="si-page space-y-5">
    <a href="{{ route('management.final-reports.index') }}" class="si-btn si-btn-secondary">Kembali ke Monitoring</a>
    @if($errors->any())<div class="rounded border border-red-200 bg-red-50 p-4 text-sm text-red-800">{{ $errors->first() }}</div>@endif
    <section class="si-card p-5">
        <p class="text-sm text-slate-500">{{ $report->assignment->student->user->name }} | {{ $report->assignment->student->nim ?: '-' }}</p>
        <h2 class="mt-1 text-xl font-bold text-slate-950">{{ $report->assignment->place->name }}</h2>
        <span class="mt-2 inline-flex rounded px-2 py-1 text-xs font-semibold ring-1 {{ $report->statusBadgeClass() }}">{{ $report->statusLabel() }}</span>
        <div class="mt-4 grid gap-4 md:grid-cols-2">
            @foreach(['internal' => 'Pembimbing Dalam', 'field' => 'Pembimbing Lapangan'] as $role => $label)
                <div class="rounded border border-slate-200 p-4 text-sm">
                    <h3 class="font-bold text-slate-950">{{ $label }}</h3>
                    <p class="mt-1 text-slate-600">{{ $role === 'internal' ? ($report->assignment->internalSupervisor ? lecturer_display_name($report->assignment->internalSupervisor) : '-') : ($report->assignment->fieldSupervisor?->user?->name ?? '-') }}</p>
                    <p class="mt-2">Bimbingan: {{ $report->{$role.'_guidance_completed_at'} ? 'Selesai' : 'Belum selesai' }} | Laporan: {{ $role === 'internal' ? $report->internalReviewStatusLabel() : $report->fieldReviewStatusLabel() }}</p>
                    @if($isCoordinator && !$report->{$role.'_guidance_completed_at'})
                        <form class="mt-3 space-y-2" method="POST" action="{{ route('management.final-reports.guidance.complete-on-behalf', [$report, $role]) }}">
                            @csrf
                            <label class="block text-xs font-semibold" for="complete-{{ $role }}">Alasan mewakili {{ $label }}</label>
                            <input id="complete-{{ $role }}" name="reason" required minlength="10" maxlength="1000" class="si-input mt-0" placeholder="Alasan dan bukti pemeriksaan">
                            <button class="si-btn si-btn-secondary">Tandai Bimbingan Selesai</button>
                        </form>
                    @endif
                    @if($isCoordinator && $report->status === 'menunggu_review' && in_array($report->{$role.'_review_status'}, [null, 'pending'], true) && ($report->files->isNotEmpty() || filled($report->final_document_url)))
                        <form class="mt-3 space-y-2" method="POST" action="{{ route('management.final-reports.approve-on-behalf', [$report, $role]) }}">
                            @csrf
                            <label class="block text-xs font-semibold" for="approve-{{ $role }}">Alasan persetujuan mewakili {{ $label }}</label>
                            <input id="approve-{{ $role }}" name="reason" required minlength="10" maxlength="1000" class="si-input mt-0" placeholder="Alasan dan bukti pemeriksaan">
                            <button class="si-btn si-btn-primary" onclick="return confirm('Laporan sudah Anda periksa dan layak disetujui?')">Setujui Laporan</button>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>
        @if(filled($report->final_document_url))<a class="mt-4 inline-block text-sm font-semibold text-cyan-700 underline" href="{{ $report->final_document_url }}" target="_blank" rel="noopener noreferrer">Buka Laporan di Drive</a>@endif
        <h3 class="mt-6 font-bold">File Laporan</h3>
        <div class="mt-2 space-y-2">
            @forelse($report->files as $file)
                <div class="flex flex-wrap items-center justify-between gap-2 rounded border border-slate-200 p-3 text-sm"><span>Versi {{ $file->version }} - {{ $file->original_filename }}</span><a href="{{ route('management.final-reports.files.download',$file) }}" class="si-btn si-btn-secondary">Download</a></div>
            @empty<p class="text-sm text-slate-500">Tidak ada file lokal.</p>@endforelse
        </div>
    </section>
    <section class="si-card p-5">
        <h3 class="font-bold text-slate-950">Sesi Bimbingan Laporan</h3>
        <div class="mt-3 space-y-3">
            @forelse($report->assignment->reportGuidanceLogs->sortBy([['guidance_date', 'asc'], ['id', 'asc']]) as $item)
                <div class="grid gap-3 rounded border border-slate-200 p-3 text-sm md:grid-cols-[1fr_auto]">
                    <div><p class="font-semibold">{{ $item->reviewerTypeLabel() }} | {{ $item->guidance_date?->format('d/m/Y') }} | {{ $item->statusLabel() }}</p><p class="mt-1 text-slate-600">{{ $item->topic }}</p>@if($item->validatedBy)<p class="mt-1 text-xs text-slate-500">Divalidasi: {{ $item->validatedBy->name }} | {{ $item->validation_note }}</p>@endif</div>
                    @if($isCoordinator && $item->status === 'menunggu_validasi')
                        <form method="POST" action="{{ route('management.final-reports.guidance.approve-on-behalf', [$report, $item]) }}" class="flex flex-wrap items-end gap-2">
                            @csrf
                            <label class="text-xs font-semibold">Alasan mewakili pembimbing <input name="reason" required minlength="10" maxlength="1000" class="si-input mt-1" placeholder="Alasan dan bukti pemeriksaan"></label>
                            <button class="si-btn si-btn-secondary" onclick="return confirm('Sesi ini sudah Anda periksa?')">Validasi Sesi</button>
                        </form>
                    @endif
                </div>
            @empty<p class="text-sm text-slate-500">Belum ada sesi bimbingan.</p>@endforelse
        </div>
    </section>
    <section class="si-card p-5">
        <h3 class="font-bold text-slate-950">Log Aktivitas</h3>
        <div class="mt-3 space-y-2">@foreach($report->logs->sortByDesc('created_at') as $log)<p class="border-b border-slate-100 pb-2 text-sm text-slate-600">{{ $log->created_at->format('d M Y H:i') }} - {{ str_replace('_',' ', $log->action) }} oleh {{ $log->user?->name ?? '-' }}@if($log->note) | {{ $log->note }}@endif</p>@endforeach</div>
    </section>
</div>
@endsection
