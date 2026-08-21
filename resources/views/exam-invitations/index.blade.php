@extends('layouts.app')

@section('title','Undangan Sidang - '.config('app.name'))
@section('page_title','Undangan Sidang')

@section('content')
<div class="space-y-5">
    <section class="grid gap-3 sm:grid-cols-4">
        <x-ui.card>
            <p class="text-xs font-black uppercase tracking-widest text-slate-500">Total undangan</p>
            <p class="mt-2 text-3xl font-black text-slate-950">{{ $summary['total'] }}</p>
        </x-ui.card>
        <x-ui.card>
            <p class="text-xs font-black uppercase tracking-widest text-slate-500">Sidang hari ini</p>
            <p class="mt-2 text-3xl font-black text-cyan-700">{{ $summary['today'] }}</p>
        </x-ui.card>
        <x-ui.card>
            <p class="text-xs font-black uppercase tracking-widest text-slate-500">Akan datang</p>
            <p class="mt-2 text-3xl font-black text-emerald-700">{{ $summary['upcoming'] }}</p>
        </x-ui.card>
        <x-ui.card>
            <p class="text-xs font-black uppercase tracking-widest text-slate-500">Riwayat</p>
            <p class="mt-2 text-3xl font-black text-slate-700">{{ $summary['history'] }}</p>
        </x-ui.card>
    </section>

    <section class="rounded-3xl border border-cyan-100 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-widest text-cyan-700">Inbox Jadwal Sidang</p>
                <h2 class="mt-1 text-2xl font-black text-slate-950">Undangan sidang</h2>
                <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-600">Daftar ini dibaca dari jadwal sidang KP. Surat resmi muncul jika koordinator sudah menerbitkan undangan, lengkap dengan preview, PDF, dan QR verifikasi.</p>
            </div>
            <span class="w-fit rounded-full bg-emerald-50 px-3 py-1 text-xs font-black text-emerald-700 ring-1 ring-emerald-200">Read-only</span>
        </div>

        @php
            $upcomingExams = $exams->getCollection()->filter(fn ($exam) => $exam->exam_date && $exam->exam_date->toDateString() >= now()->toDateString() && in_array($exam->status, ['dijadwalkan', 'ditunda'], true));
            $historyExams = $exams->getCollection()->reject(fn ($exam) => $upcomingExams->contains('id', $exam->id));
        @endphp

        <div class="mt-5 space-y-6">
            @foreach([
                'Akan Dilaksanakan' => $upcomingExams,
                'Riwayat / Sudah Lewat' => $historyExams,
            ] as $groupTitle => $groupExams)
                <div>
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <h3 class="text-sm font-black uppercase tracking-widest text-slate-600">{{ $groupTitle }}</h3>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600">{{ $groupExams->count() }} undangan</span>
                    </div>

            @forelse($groupExams as $exam)
                @php
                    $assignment = $exam->assignment;
                    $student = $assignment?->student;
                    $detailUrl = match ($activeRole) {
                        'mahasiswa' => route('student.exams.index'),
                        'pembimbing_dalam' => route('internal-supervisor.exams.show', $exam),
                        'pembimbing_lapangan' => route('field-supervisor.assignments.show', $assignment),
                        'penguji' => route('examiner.exams.show', $exam),
                        default => route('management.exams.show', $exam),
                    };
                @endphp
                <article class="rounded-2xl border border-slate-200 bg-slate-50/60 p-4 transition hover:border-cyan-200 hover:bg-white hover:shadow-sm">
                    <div class="grid gap-4 lg:grid-cols-[1.2fr_1fr_auto] lg:items-center">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full px-2.5 py-1 text-[11px] font-black ring-1 {{ $exam->statusBadgeClass() }}">{{ $exam->statusLabel() }}</span>
                                <span class="rounded-full bg-cyan-50 px-2.5 py-1 text-[11px] font-black text-cyan-700 ring-1 ring-cyan-100">{{ $exam->modeLabel() }}</span>
                            </div>
                            <h3 class="mt-3 text-xl font-black text-slate-950">{{ $student?->user?->name ?? '-' }}</h3>
                            <p class="mt-1 text-sm text-slate-500">{{ $student?->nim ?: '-' }} · {{ $assignment?->period?->name ?? '-' }}</p>
                            <p class="mt-2 text-sm font-semibold text-slate-700">{{ $assignment?->place?->name ?? '-' }}</p>
                        </div>

                        <div class="grid gap-2 text-sm text-slate-700 sm:grid-cols-2 lg:grid-cols-1">
                            <div class="rounded-xl bg-white px-3 py-2 ring-1 ring-slate-200">
                                <p class="text-[11px] font-black uppercase tracking-widest text-slate-500">Waktu</p>
                                <p class="mt-1 font-bold">{{ $exam->scheduleLabel() }}</p>
                            </div>
                            <div class="rounded-xl bg-white px-3 py-2 ring-1 ring-slate-200">
                                <p class="text-[11px] font-black uppercase tracking-widest text-slate-500">Lokasi / Link</p>
                                <p class="mt-1 font-bold">{{ $exam->room ?: '-' }}</p>
                                @if($exam->meeting_link)
                                    <a href="{{ $exam->meeting_link }}" target="_blank" rel="noopener" class="mt-1 inline-flex text-xs font-bold text-cyan-700 hover:text-cyan-900">Buka link sidang</a>
                                @endif
                            </div>
                        </div>

                        <div class="flex flex-col gap-2 lg:min-w-52">
                            <a href="{{ $detailUrl }}" class="rounded-xl bg-cyan-700 px-4 py-3 text-center text-sm font-black text-white shadow-sm shadow-cyan-700/20 hover:bg-cyan-800">Buka Detail</a>
                            @if($exam->invitation)
                                <a href="{{ route('exam-invitations.letter.preview', $exam->invitation) }}" class="rounded-xl border border-cyan-200 bg-white px-4 py-2 text-center text-xs font-black text-cyan-700">Preview Surat</a>
                                <a href="{{ route('exam-invitations.letter.pdf', $exam->invitation) }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-center text-xs font-black text-slate-700">Download PDF</a>
                            @else
                                <span class="rounded-xl bg-amber-50 px-3 py-2 text-center text-xs font-bold text-amber-700 ring-1 ring-amber-100">Surat belum diterbitkan koordinator</span>
                            @endif
                            <div class="rounded-xl bg-white px-3 py-2 text-xs leading-5 text-slate-600 ring-1 ring-slate-200">
                                <div><span class="font-black text-slate-700">Pembimbing:</span> {{ $exam->supervisor ? lecturer_display_name($exam->supervisor) : '-' }}</div>
                                <div><span class="font-black text-slate-700">Penguji:</span> {{ $exam->examinerNamesLabel() }}</div>
                                <div><span class="font-black text-slate-700">Lapangan:</span> {{ $assignment?->fieldSupervisor?->user?->name ?? '-' }}</div>
                            </div>
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">Belum ada data pada bagian ini.</div>
            @endforelse
                </div>
            @endforeach
        </div>

        <div class="mt-5 border-t border-slate-100 pt-4">{{ $exams->links() }}</div>
    </section>
</div>
@endsection
