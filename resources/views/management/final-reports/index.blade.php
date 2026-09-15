@extends('layouts.app')

@section('title','Monitoring Laporan - '.config('app.name'))
@section('page_title','Monitoring Laporan')

@section('content')
<div class="si-page">
    <section class="grid gap-3 md:grid-cols-5">
        @foreach(['total'=>'Total Laporan','follow_up'=>'Perlu Tindak Lanjut','internal_complete'=>'Pembimbing Dalam Lengkap','field_complete'=>'Pembimbing Lapangan Lengkap','complete'=>'Keduanya Lengkap'] as $key=>$label)
            <x-ui.stat-card :label="$label" :value="$stats[$key] ?? 0" tone="cyan" />
        @endforeach
    </section>

    <section class="si-card p-5">
        <form method="GET" class="grid gap-3 md:grid-cols-2 xl:grid-cols-6">
            <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Cari mahasiswa/tempat" class="si-input mt-0 xl:col-span-2">
            <select name="period" class="si-input mt-0">
                <option value="">Semua Periode</option>
                @foreach($periods as $period)
                    <option value="{{ $period->id }}" @selected(($filters['period'] ?? '') == $period->id)>{{ $period->name }}</option>
                @endforeach
            </select>
            <select name="status" class="si-input mt-0">
                <option value="">Semua Status</option>
                @foreach(['draft'=>'Draft','menunggu_review'=>'Menunggu Review','revisi'=>'Revisi','disetujui'=>'Disetujui','ditolak'=>'Ditolak'] as $value=>$label)
                    <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="internal_status" class="si-input mt-0">
                <option value="">Semua Status Pembimbing Dalam</option>
                <option value="guidance_pending" @selected(($filters['internal_status'] ?? '') === 'guidance_pending')>Bimbingan Dalam Belum Selesai</option>
                <option value="guidance_completed" @selected(($filters['internal_status'] ?? '') === 'guidance_completed')>Bimbingan Dalam Selesai</option>
                <option value="report_pending" @selected(($filters['internal_status'] ?? '') === 'report_pending')>Laporan Belum Disetujui Dalam</option>
                <option value="report_approved" @selected(($filters['internal_status'] ?? '') === 'report_approved')>Laporan Disetujui Dalam</option>
                <option value="completed" @selected(($filters['internal_status'] ?? '') === 'completed')>Dalam Lengkap</option>
            </select>
            <select name="field_status" class="si-input mt-0">
                <option value="">Semua Status Pembimbing Lapangan</option>
                <option value="guidance_pending" @selected(($filters['field_status'] ?? '') === 'guidance_pending')>Bimbingan Lapangan Belum Selesai</option>
                <option value="guidance_completed" @selected(($filters['field_status'] ?? '') === 'guidance_completed')>Bimbingan Lapangan Selesai</option>
                <option value="report_pending" @selected(($filters['field_status'] ?? '') === 'report_pending')>Laporan Belum Disetujui Lapangan</option>
                <option value="report_approved" @selected(($filters['field_status'] ?? '') === 'report_approved')>Laporan Disetujui Lapangan</option>
                <option value="completed" @selected(($filters['field_status'] ?? '') === 'completed')>Lapangan Lengkap</option>
            </select>
            <select name="sort" class="si-input mt-0 xl:col-span-2">
                <option value="follow_up" @selected(($filters['sort'] ?? 'follow_up') === 'follow_up')>Urutkan: Perlu tindak lanjut lebih dulu</option>
                <option value="latest" @selected(($filters['sort'] ?? '') === 'latest')>Urutkan: Laporan terbaru</option>
                <option value="student" @selected(($filters['sort'] ?? '') === 'student')>Urutkan: Nama mahasiswa</option>
            </select>
            <div class="flex gap-2 xl:col-span-4 xl:justify-end">
                <a href="{{ route('management.final-reports.index') }}" class="si-btn si-btn-secondary">Reset</a>
                <button class="si-btn si-btn-primary">Terapkan Filter</button>
            </div>
        </form>
    </section>

    <section class="si-table-wrap">
        <div class="overflow-x-auto">
            <table class="si-data-table">
                <colgroup>
                    <col class="w-[20%]">
                    <col class="w-[18%]">
                    <col class="w-[23%]">
                    <col class="w-[23%]">
                    <col class="w-[10%]">
                    <col class="w-[6%]">
                </colgroup>
                <thead>
                    <tr>
                        <th>Mahasiswa</th>
                        <th>Tempat</th>
                        <th>Pembimbing Dalam</th>
                        <th>Pembimbing Lapangan</th>
                        <th>Status Laporan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reports as $report)
                        <tr>
                            <td>
                                <div class="font-semibold text-slate-950">{{ $report->assignment->student->user->name }}</div>
                                <div class="text-xs text-slate-500">{{ $report->assignment->student->nim ?: '-' }}</div>
                            </td>
                            <td>{{ $report->assignment->place->name }}</td>
                            <td>
                                <div class="font-semibold text-slate-950">{{ $report->assignment->internalSupervisor ? lecturer_display_name($report->assignment->internalSupervisor) : '-' }}</div>
                                <div class="mt-2 flex flex-wrap gap-1.5">
                                    <span class="rounded-full px-2 py-1 text-[11px] font-semibold ring-1 {{ $report->isInternalGuidanceCompleted() ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-amber-50 text-amber-800 ring-amber-200' }}">Bimbingan: {{ $report->isInternalGuidanceCompleted() ? 'Selesai' : 'Belum' }}</span>
                                    <span class="rounded-full px-2 py-1 text-[11px] font-semibold ring-1 {{ $report->internal_review_status === 'disetujui' ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-slate-50 text-slate-700 ring-slate-200' }}">Laporan: {{ $report->internalReviewStatusLabel() }}</span>
                                </div>
                            </td>
                            <td>
                                <div class="font-semibold text-slate-950">{{ $report->assignment->fieldSupervisor?->user?->name ?? '-' }}</div>
                                <div class="mt-2 flex flex-wrap gap-1.5">
                                    <span class="rounded-full px-2 py-1 text-[11px] font-semibold ring-1 {{ $report->isFieldGuidanceCompleted() ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-amber-50 text-amber-800 ring-amber-200' }}">Bimbingan: {{ $report->isFieldGuidanceCompleted() ? 'Selesai' : 'Belum' }}</span>
                                    <span class="rounded-full px-2 py-1 text-[11px] font-semibold ring-1 {{ $report->field_review_status === 'disetujui' ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-slate-50 text-slate-700 ring-slate-200' }}">Laporan: {{ $report->fieldReviewStatusLabel() }}</span>
                                </div>
                            </td>
                            <td>
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $report->statusBadgeClass() }}">{{ $report->statusLabel() }}</span>
                                <div class="mt-2 text-xs text-slate-500">Versi {{ $report->current_version }}</div>
                            </td>
                            <td><a href="{{ route('management.final-reports.show',$report) }}" class="si-btn si-btn-secondary min-h-9 px-3 py-1.5 text-xs text-cyan-700">Detail</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-10 text-center text-slate-500">Belum ada laporan akhir.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-sky-100 px-4 py-3">{{ $reports->links() }}</div>
    </section>
</div>
@endsection
