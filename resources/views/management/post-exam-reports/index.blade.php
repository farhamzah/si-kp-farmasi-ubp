@extends('layouts.app')

@section('title','Dokumen Pascasidang - '.config('app.name'))
@section('page_title','Dokumen Final Pascasidang')

@section('content')
<div class="space-y-5">
    <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyan-100">
        <p class="text-xs font-black uppercase tracking-widest text-cyan-700">Validasi Koordinator</p>
        <h2 class="mt-1 text-2xl font-black text-slate-950">Dokumen hasil revisi dan pengesahan</h2>
        <p class="mt-2 text-sm text-slate-600">Setujui PDF final yang sudah direvisi, ditandatangani, dan disahkan. Persetujuan membuka salah satu syarat melihat nilai serta menjadi syarat awal pendaftaran TA.</p>
        <form method="GET" class="mt-5 grid gap-3 md:grid-cols-[1fr_260px_auto]">
            <input name="q" value="{{ request('q') }}" placeholder="Cari nama atau NIM mahasiswa" class="rounded-xl border border-slate-300 px-3 py-2 text-sm">
            <select name="status" class="rounded-xl border border-slate-300 px-3 py-2 text-sm">
                <option value="">Semua status</option>
                @foreach(['menunggu_validasi' => 'Menunggu Validasi', 'revisi' => 'Perlu Perbaikan', 'disetujui' => 'Disetujui'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="rounded-xl bg-slate-950 px-5 py-2 text-sm font-bold text-white">Filter</button>
        </form>
    </section>

    @forelse($reports as $report)
        <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="text-lg font-black text-slate-950">{{ $report->assignment->student->user->name }}</h3>
                        <span class="rounded-full px-3 py-1 text-xs font-bold ring-1 {{ $report->statusBadgeClass() }}">{{ $report->statusLabel() }}</span>
                    </div>
                    <p class="mt-1 text-sm text-slate-500">{{ $report->assignment->student->nim }} · {{ $report->assignment->place?->name }} · Versi {{ $report->version }}</p>
                    <p class="mt-2 text-sm font-semibold text-slate-700">{{ $report->documentLabel() }}</p>
                    @if($report->review_note)<p class="mt-3 rounded-xl bg-blue-50 px-4 py-3 text-sm text-blue-800">{{ $report->review_note }}</p>@endif
                </div>
                <div class="flex flex-wrap gap-2">
                    @if($report->file_path)
                        <a target="_blank" rel="noopener" href="{{ route('management.post-exam-reports.preview', $report) }}" class="rounded-xl border border-cyan-200 px-4 py-2 text-sm font-bold text-cyan-700">Preview</a>
                        <a href="{{ route('management.post-exam-reports.download', $report) }}" class="rounded-xl bg-slate-950 px-4 py-2 text-sm font-bold text-white">Download</a>
                    @endif
                    @if($report->document_url)<a target="_blank" rel="noopener" href="{{ $report->document_url }}" class="rounded-xl border border-emerald-200 px-4 py-2 text-sm font-bold text-emerald-700">Buka Link</a>@endif
                </div>
            </div>
            @if($report->status !== 'disetujui')
                <div class="mt-5 grid gap-4 lg:grid-cols-2">
                    <form method="POST" action="{{ route('management.post-exam-reports.approve', $report) }}" class="rounded-xl border border-emerald-200 bg-emerald-50/60 p-4">
                        @csrf
                        <textarea name="review_note" rows="2" placeholder="Catatan persetujuan opsional" class="w-full rounded-xl border border-emerald-200 px-3 py-2 text-sm"></textarea>
                        <button class="mt-3 w-full rounded-xl bg-emerald-600 px-4 py-2 text-sm font-bold text-white">Setujui Dokumen</button>
                    </form>
                    <form method="POST" action="{{ route('management.post-exam-reports.revision', $report) }}" class="rounded-xl border border-blue-200 bg-blue-50/60 p-4">
                        @csrf
                        <textarea name="review_note" rows="2" required placeholder="Tuliskan bagian yang harus diperbaiki" class="w-full rounded-xl border border-blue-200 px-3 py-2 text-sm"></textarea>
                        <button class="mt-3 w-full rounded-xl bg-blue-700 px-4 py-2 text-sm font-bold text-white">Kembalikan untuk Perbaikan</button>
                    </form>
                </div>
            @endif
        </section>
    @empty
        <x-ui.empty-state title="Belum ada dokumen pascasidang." description="Dokumen akan muncul setelah mahasiswa selesai sidang dan mengirim PDF final." />
    @endforelse

    {{ $reports->links() }}
</div>
@endsection
