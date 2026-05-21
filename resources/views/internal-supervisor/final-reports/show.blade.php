@extends('layouts.app')
@section('title','Detail Review Laporan - '.config('app.name'))
@section('page_title','Detail Review Laporan')
@section('content')
<div class="grid gap-5 lg:grid-cols-[1fr_360px]">
    <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <p class="text-sm text-slate-500">{{ $report->assignment->student->user->name }} | {{ $report->assignment->student->nim ?: '-' }}</p>
        <h2 class="mt-1 text-2xl font-bold text-slate-950">{{ $report->assignment->place->name }}</h2>
        <span class="mt-3 inline-flex rounded-full px-3 py-1 text-xs font-semibold ring-1 {{ $report->statusBadgeClass() }}">{{ $report->statusLabel() }}</span>
        @if($report->review_note)<div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">{{ $report->review_note }}</div>@endif
        <h3 class="mt-6 font-bold text-slate-950">Riwayat Versi</h3>
        <div class="mt-3 space-y-3">@foreach($report->files as $file)<div class="flex items-center justify-between rounded-xl border border-slate-200 p-4 text-sm"><div><p class="font-semibold">Versi {{ $file->version }} - {{ $file->original_filename }}</p><p class="text-xs text-slate-500">{{ $file->humanFileSize() }} | {{ $file->uploaded_at->format('d M Y H:i') }}</p></div><a href="{{ route('internal-supervisor.final-reports.files.download',$file) }}" class="rounded-lg border border-teal-200 px-3 py-1.5 text-xs font-semibold text-teal-700">Download</a></div>@endforeach</div>
    </section>
    <aside class="space-y-5">
        <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <h3 class="font-bold text-slate-950">Aksi Review</h3>
            <form method="POST" action="{{ route('internal-supervisor.final-reports.approve',$report) }}" class="mt-4">@csrf<textarea name="review_note" rows="3" placeholder="Catatan opsional" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea><button class="mt-3 w-full rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white">Setujui</button></form>
            <form method="POST" action="{{ route('internal-supervisor.final-reports.revision',$report) }}" class="mt-4">@csrf<textarea name="review_note" rows="3" required placeholder="Catatan revisi wajib" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea><button class="mt-3 w-full rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Minta Revisi</button></form>
            <form method="POST" action="{{ route('internal-supervisor.final-reports.reject',$report) }}" class="mt-4">@csrf<textarea name="review_note" rows="3" required placeholder="Alasan penolakan wajib" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea><button onclick="return confirm('Tolak laporan ini?')" class="mt-3 w-full rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white">Tolak</button></form>
        </section>
        <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200"><h3 class="font-bold text-slate-950">Log Aktivitas</h3><div class="mt-3 space-y-2">@foreach($report->logs as $log)<p class="text-xs text-slate-500">{{ $log->created_at->format('d M Y H:i') }} - {{ str_replace('_',' ', $log->action) }}</p>@endforeach</div></section>
    </aside>
</div>
@endsection
