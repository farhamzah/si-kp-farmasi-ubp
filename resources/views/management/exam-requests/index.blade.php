@extends('layouts.app')
@section('title','Antrian Sidang - '.config('app.name'))
@section('page_title','Antrian Validasi Sidang')
@section('content')
@php
    $statusOptions = ['siap_diajukan' => 'Siap Diajukan', 'diajukan' => 'Menunggu Validasi', 'disetujui' => 'Siap Dijadwalkan', 'dijadwalkan' => 'Sudah Dijadwalkan', 'revisi' => 'Perlu Revisi', 'ditolak' => 'Ditolak'];
    $summaryCards = [
        ['label' => 'Menunggu validasi', 'value' => $summary['diajukan'] ?? 0, 'tone' => 'text-amber-700 ring-amber-200 bg-amber-50'],
        ['label' => 'Siap dijadwalkan', 'value' => $summary['disetujui'] ?? 0, 'tone' => 'text-emerald-700 ring-emerald-200 bg-emerald-50'],
        ['label' => 'Sudah dijadwalkan', 'value' => $summary['dijadwalkan'] ?? 0, 'tone' => 'text-cyan-700 ring-cyan-200 bg-cyan-50'],
        ['label' => 'Perlu revisi', 'value' => $summary['revisi'] ?? 0, 'tone' => 'text-blue-700 ring-blue-200 bg-blue-50'],
    ];
@endphp
<div class="space-y-5">
    @if(session('status'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('status') }}</div>
    @endif
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
                <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-600">Mahasiswa dengan laporan final tersedia dapat diajukan oleh mahasiswa atau koordinator. Untuk sementara, persetujuan pembimbing dan bukti pembayaran tidak menghambat antrean maupun penjadwalan; status review tetap tercatat.</p>
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

    @if((! ($filters['status'] ?? null) || $filters['status'] === 'siap_diajukan') && $candidates->isNotEmpty())
        <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-emerald-200">
            <div class="flex flex-wrap items-baseline justify-between gap-2">
                <h2 class="text-lg font-black text-slate-950">Siap diajukan: {{ $candidates->count() }} mahasiswa</h2>
                <p class="text-sm text-slate-600">Laporan final tersedia; persetujuan pembimbing dan bukti pembayaran dapat menyusul.</p>
            </div>
            <div class="mt-4 divide-y divide-slate-100">
                @foreach($candidates as $candidate)
                    <div class="flex flex-col gap-3 py-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <p class="font-bold text-slate-950">{{ $candidate->student?->user?->name ?? '-' }} <span class="font-normal text-slate-500">· {{ $candidate->student?->nim ?? '-' }}</span></p>
                            <p class="mt-1 text-xs text-slate-600">{{ $candidate->period?->name ?? '-' }} · {{ $candidate->place?->name ?? '-' }}</p>
                        </div>
                        <form method="POST" action="{{ route('management.exam-requests.candidates.enqueue', $candidate) }}" class="shrink-0">
                            @csrf
                            <button type="submit" class="rounded-lg bg-cyan-700 px-4 py-2 text-sm font-bold text-white shadow-sm">Masukkan Antrean</button>
                        </form>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <section class="space-y-3">
        @forelse($requests as $examRequest)
            @php
                $assignment = $examRequest->assignment;
                $eligibility = $assignment->examEligibility();
                $checklistItems = collect($eligibility['items']);
                $readyCount = $checklistItems->where('ready', true)->count();
                $totalCount = $checklistItems->count();
                $allReady = $eligibility['ready'];
                $report = $assignment->finalReport;
            @endphp
            <article class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200 sm:p-5">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-base font-black text-slate-950">{{ $assignment->student->user->name }}</h3>
                            <span class="rounded-full px-2 py-1 text-xs font-bold ring-1 {{ $examRequest->statusBadgeClass() }}">{{ $examRequest->statusLabel() }}</span>
                            <span class="rounded-full px-2 py-1 text-xs font-bold {{ $allReady ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">{{ $allReady ? 'Siap dijadwalkan' : 'Belum siap dijadwalkan' }}</span>
                        </div>
                        <p class="mt-1 text-xs text-slate-600">{{ $assignment->student->nim ?: '-' }} · {{ $assignment->period->name }} · {{ $assignment->place->name }}</p>
                        <p class="mt-2 text-xs text-slate-600">Pembayaran KP: {{ $examRequest->paymentProofStatusLabel() }} · Tidak menghambat jadwal sidang</p>
                    </div>
                    <div class="flex shrink-0 flex-wrap gap-2">
                        <a href="{{ route('management.exam-requests.show', $examRequest) }}" class="rounded-lg border border-cyan-200 px-4 py-2 text-sm font-bold text-cyan-700">Validasi</a>
                        @if($examRequest->status === 'disetujui' && $allReady && ! $examRequest->exam)
                            <a href="{{ route('management.exam-requests.schedule', $examRequest) }}" class="rounded-lg bg-cyan-700 px-4 py-2 text-sm font-bold text-white">Jadwalkan</a>
                        @elseif($examRequest->exam)
                            <a href="{{ route('management.exams.show', $examRequest->exam) }}" class="rounded-lg bg-slate-950 px-4 py-2 text-sm font-bold text-white">Lihat Jadwal</a>
                        @endif
                    </div>
                </div>
                <details class="mt-3 border-t border-slate-100 pt-3">
                    <summary class="cursor-pointer text-xs font-bold text-cyan-700">Detail pembimbing dan progres review ({{ $readyCount }}/{{ $totalCount }})</summary>
                    <div class="mt-3 grid gap-3 text-xs sm:grid-cols-2 lg:grid-cols-3">
                        <p><span class="font-bold text-slate-500">Pembimbing Dalam</span><br>{{ $assignment->internalSupervisor ? lecturer_display_name($assignment->internalSupervisor) : '-' }}</p>
                        <p><span class="font-bold text-slate-500">Pembimbing Lapangan</span><br>{{ $assignment->fieldSupervisor ? field_supervisor_display_name($assignment->fieldSupervisor) : '-' }}</p>
                        <p><span class="font-bold text-slate-500">Laporan Final</span><br>{{ $report?->statusLabel() ?? 'Belum tersedia' }}</p>
                    </div>
                    <ul class="mt-3 grid gap-x-6 gap-y-2 border-t border-slate-100 pt-3 sm:grid-cols-2">
                        @foreach($checklistItems as $item)
                            <li class="flex items-start gap-2 text-xs">
                                <span class="font-bold {{ $item['ready'] ? 'text-emerald-700' : 'text-amber-700' }}">{{ $item['ready'] ? 'OK' : '!' }}</span>
                                <span><strong class="text-slate-950">{{ $item['label'] }}</strong><br><span class="text-slate-500">{{ $item['description'] }}</span></span>
                            </li>
                        @endforeach
                    </ul>
                </details>
            </article>
        @empty
            <x-ui.card>
                <div class="py-10 text-center">
                    <p class="text-lg font-black text-slate-950">Belum ada kandidat sidang.</p>
                    <p class="mt-2 text-sm text-slate-500">{{ ($filters['status'] ?? null) === 'siap_diajukan' ? 'Tidak ada mahasiswa lain dengan syarat lengkap yang belum masuk antrean.' : 'Mahasiswa dapat mengajukan sendiri dari menu Sidang, atau koordinator memasukkan kandidat siap dari daftar di atas.' }}</p>
                </div>
            </x-ui.card>
        @endforelse
    </section>

    <div>{{ $requests->links() }}</div>
</div>
@endsection
