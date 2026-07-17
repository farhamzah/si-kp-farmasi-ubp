@extends('layouts.app')
@section('title','Antrian Sidang - '.config('app.name'))
@section('page_title','Antrian Validasi Sidang')
@section('content')
@php
    $statusOptions = ['diajukan' => 'Menunggu Validasi', 'disetujui' => 'Siap Dijadwalkan', 'dijadwalkan' => 'Sudah Dijadwalkan', 'revisi' => 'Perlu Revisi', 'ditolak' => 'Ditolak'];
    $summaryCards = [
        ['label' => 'Menunggu validasi', 'value' => $summary['diajukan'] ?? 0, 'tone' => 'text-amber-700 ring-amber-200 bg-amber-50'],
        ['label' => 'Siap dijadwalkan', 'value' => $summary['disetujui'] ?? 0, 'tone' => 'text-emerald-700 ring-emerald-200 bg-emerald-50'],
        ['label' => 'Sudah dijadwalkan', 'value' => $summary['dijadwalkan'] ?? 0, 'tone' => 'text-cyan-700 ring-cyan-200 bg-cyan-50'],
        ['label' => 'Perlu revisi', 'value' => $summary['revisi'] ?? 0, 'tone' => 'text-blue-700 ring-blue-200 bg-blue-50'],
    ];
@endphp
<div class="space-y-5">
    <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach($summaryCards as $card)
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <p class="text-xs font-black uppercase tracking-widest text-slate-500">{{ $card['label'] }}</p>
                <div class="mt-3 flex items-end justify-between gap-3">
                    <p class="text-3xl font-black text-slate-950">{{ $card['value'] }}</p>
                    <span class="rounded-full px-3 py-1 text-xs font-bold ring-1 {{ $card['tone'] }}">Sidang KP</span>
                </div>
            </div>
        @endforeach
    </section>

    <x-ui.card>
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-widest text-cyan-700">Meja kerja koordinator</p>
                <h2 class="mt-1 text-xl font-black text-slate-950">Validasi kandidat sebelum penjadwalan</h2>
                <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-600">Mahasiswa masuk ke antrian ini setelah mengajukan sidang. Cek validasi logbook, minimal 8 bimbingan laporan, dan persetujuan laporan dari pembimbing dalam serta lapangan sebelum menjadwalkan.</p>
            </div>
            <a href="{{ route('management.exams.index') }}" class="inline-flex justify-center rounded-xl border border-cyan-200 px-4 py-3 text-sm font-bold text-cyan-700 shadow-sm">Lihat Jadwal Sidang</a>
        </div>
        <form method="GET" class="mt-5 grid gap-3 lg:grid-cols-[1fr_220px_220px_auto]">
            <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Cari nama atau NIM mahasiswa" class="rounded-xl border border-slate-300 px-4 py-3 text-sm shadow-sm">
            <select name="period" class="rounded-xl border border-slate-300 px-4 py-3 text-sm shadow-sm">
                <option value="">Semua Periode</option>
                @foreach($periods as $period)
                    <option value="{{ $period->id }}" @selected(($filters['period'] ?? '') == $period->id)>{{ $period->name }}</option>
                @endforeach
            </select>
            <select name="status" class="rounded-xl border border-slate-300 px-4 py-3 text-sm shadow-sm">
                <option value="">Semua Status</option>
                @foreach($statusOptions as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="rounded-xl bg-slate-950 px-5 py-3 text-sm font-bold text-white shadow-sm">Filter</button>
        </form>
    </x-ui.card>

    <section class="space-y-3">
        @forelse($requests as $examRequest)
            @php
                $assignment = $examRequest->assignment;
                $eligibility = $assignment->examEligibility();
                $readyCount = collect($eligibility['items'])->where('ready', true)->count();
                $totalCount = count($eligibility['items']);
                $report = $assignment->finalReport;
            @endphp
            <article class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="grid gap-0 xl:grid-cols-[1fr_280px]">
                    <div class="p-5">
                        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full px-3 py-1 text-xs font-bold ring-1 {{ $examRequest->statusBadgeClass() }}">{{ $examRequest->statusLabel() }}</span>
                                    @if($eligibility['ready'])
                                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 ring-1 ring-emerald-200">Syarat lengkap</span>
                                    @else
                                        <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700 ring-1 ring-amber-200">{{ $readyCount }}/{{ $totalCount }} syarat</span>
                                    @endif
                                </div>
                                <h3 class="mt-3 text-xl font-black text-slate-950">{{ $assignment->student->user->name }}</h3>
                                <p class="mt-1 text-sm text-slate-500">{{ $assignment->student->nim ?: '-' }} · {{ $assignment->period->name }} · {{ $assignment->place->name }}</p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('management.exam-requests.show', $examRequest) }}" class="rounded-xl border border-cyan-200 px-4 py-2 text-sm font-bold text-cyan-700">Validasi</a>
                                @if($examRequest->status === 'disetujui' && ! $examRequest->exam)
                                    <a href="{{ route('management.exam-requests.schedule', $examRequest) }}" class="rounded-xl bg-cyan-700 px-4 py-2 text-sm font-bold text-white">Jadwalkan</a>
                                @elseif($examRequest->exam)
                                    <a href="{{ route('management.exams.show', $examRequest->exam) }}" class="rounded-xl bg-slate-950 px-4 py-2 text-sm font-bold text-white">Lihat Jadwal</a>
                                @endif
                            </div>
                        </div>

                        <div class="mt-5 grid gap-3 md:grid-cols-3">
                            <div class="rounded-xl bg-slate-50 p-4">
                                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Pembimbing Dalam</p>
                                <p class="mt-1 text-sm font-bold text-slate-950">{{ $assignment->internalSupervisor ? lecturer_display_name($assignment->internalSupervisor) : '-' }}</p>
                            </div>
                            <div class="rounded-xl bg-slate-50 p-4">
                                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Pembimbing Lapangan</p>
                                <p class="mt-1 text-sm font-bold text-slate-950">{{ $assignment->fieldSupervisor ? field_supervisor_display_name($assignment->fieldSupervisor) : '-' }}</p>
                            </div>
                            <div class="rounded-xl bg-slate-50 p-4">
                                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Laporan Final</p>
                                <p class="mt-1 text-sm font-bold text-slate-950">{{ $report?->statusLabel() ?? 'Belum tersedia' }}</p>
                            </div>
                        </div>
                    </div>

                    <aside class="border-t border-slate-100 bg-slate-50/70 p-5 xl:border-l xl:border-t-0">
                        <p class="text-xs font-black uppercase tracking-widest text-slate-500">Checklist eligible</p>
                        <div class="mt-3 space-y-2">
                            @foreach($eligibility['items'] as $item)
                                <div class="flex items-start gap-2 rounded-xl bg-white px-3 py-2 ring-1 ring-slate-200">
                                    <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-[10px] font-black {{ $item['ready'] ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">{{ $item['ready'] ? 'OK' : '!' }}</span>
                                    <div>
                                        <p class="text-xs font-bold text-slate-950">{{ $item['label'] }}</p>
                                        <p class="text-[11px] leading-4 text-slate-500">{{ $item['description'] }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </aside>
                </div>
            </article>
        @empty
            <x-ui.card>
                <div class="py-10 text-center">
                    <p class="text-lg font-black text-slate-950">Belum ada kandidat sidang.</p>
                    <p class="mt-2 text-sm text-slate-500">Mahasiswa akan muncul setelah mengajukan sidang dari menu Sidang.</p>
                </div>
            </x-ui.card>
        @endforelse
    </section>

    <div>{{ $requests->links() }}</div>
</div>
@endsection
