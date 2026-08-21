@extends('layouts.app')

@section('title','Jadwal Sidang - '.config('app.name'))
@section('page_title','Jadwal Sidang')

@section('content')
<div class="space-y-5">
    @if(session('status'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">{{ $errors->first() }}</div>
    @endif

    <x-ui.card>
        <form method="GET" class="grid gap-3 md:grid-cols-[220px_220px_auto]">
            <select name="period" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="">Semua Periode</option>
                @foreach($periods as $period)
                    <option value="{{ $period->id }}" @selected(($filters['period'] ?? '') == $period->id)>{{ $period->name }}</option>
                @endforeach
            </select>
            <select name="status" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="">Semua Status</option>
                @foreach(['dijadwalkan'=>'Dijadwalkan','selesai'=>'Selesai','ditunda'=>'Ditunda','dibatalkan'=>'Dibatalkan'] as $value=>$label)
                    <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Filter</button>
        </form>
    </x-ui.card>

    <section class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-cyan-100">
        <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-widest text-cyan-700">Surat undangan sidang</p>
                <h2 class="mt-1 text-xl font-black text-slate-950">Kelola jadwal dan surat resmi</h2>
                <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-600">Pejabat penandatangan diatur sekali dan berlaku untuk semua surat selama masa jabatan. Dari daftar jadwal ini koordinator dapat menerbitkan undangan satu per satu atau sekaligus.</p>
            </div>
        </div>

        <div class="mt-5 rounded-2xl border border-cyan-100 bg-cyan-50/40 p-4">
            <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-widest text-cyan-700">Pejabat penandatangan aktif</p>
                    <p class="mt-1 text-sm text-slate-600">Digunakan otomatis saat undangan sidang diterbitkan. Surat yang sudah terbit menyimpan salinan pejabat pada saat surat dibuat.</p>
                </div>
                @if($signatory)
                    <span class="w-fit rounded-full bg-emerald-50 px-3 py-1 text-xs font-black text-emerald-700 ring-1 ring-emerald-200">Sudah diatur</span>
                @else
                    <span class="w-fit rounded-full bg-amber-50 px-3 py-1 text-xs font-black text-amber-700 ring-1 ring-amber-200">Belum diatur</span>
                @endif
            </div>

            <details class="mt-4 rounded-2xl border border-white/80 bg-white p-4" @if(! $signatory || $errors->has('signatory')) open @endif>
                <summary class="cursor-pointer text-sm font-black text-cyan-700">{{ $signatory ? 'Ubah pejabat aktif' : 'Isi pejabat penandatangan' }}</summary>
                <form method="POST" action="{{ route('management.exams.invitations.signatory.update') }}" class="mt-4 grid gap-3 lg:grid-cols-3">
                    @csrf
                    <label class="block">
                        <span class="text-xs font-black uppercase tracking-widest text-slate-500">Koordinator Sidang</span>
                        <input name="coordinator_name" value="{{ old('coordinator_name', $signatory?->coordinator_name ?? auth()->user()->name) }}" class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" required>
                    </label>
                    <label class="block">
                        <span class="text-xs font-black uppercase tracking-widest text-slate-500">NUPTK Koordinator</span>
                        <input name="coordinator_nuptk" value="{{ old('coordinator_nuptk', $signatory?->coordinator_nuptk) }}" class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                    </label>
                    <label class="block">
                        <span class="text-xs font-black uppercase tracking-widest text-slate-500">Mulai Berlaku</span>
                        <input type="date" name="effective_start_date" value="{{ old('effective_start_date', $signatory?->effective_start_date?->toDateString() ?? now()->toDateString()) }}" class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                    </label>

                    <label class="block">
                        <span class="text-xs font-black uppercase tracking-widest text-slate-500">Kaprodi</span>
                        <input name="head_program_name" value="{{ old('head_program_name', $signatory?->head_program_name) }}" class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" required>
                    </label>
                    <label class="block">
                        <span class="text-xs font-black uppercase tracking-widest text-slate-500">NUPTK Kaprodi</span>
                        <input name="head_program_nuptk" value="{{ old('head_program_nuptk', $signatory?->head_program_nuptk) }}" class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                    </label>
                    <div class="hidden lg:block"></div>

                    <label class="block">
                        <span class="text-xs font-black uppercase tracking-widest text-slate-500">Dekan</span>
                        <input name="dean_name" value="{{ old('dean_name', $signatory?->dean_name) }}" class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" required>
                    </label>
                    <label class="block">
                        <span class="text-xs font-black uppercase tracking-widest text-slate-500">NUPTK Dekan</span>
                        <input name="dean_nuptk" value="{{ old('dean_nuptk', $signatory?->dean_nuptk) }}" class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                    </label>
                    <div class="flex items-end">
                        <button class="w-full rounded-xl bg-slate-950 px-4 py-3 text-sm font-black text-white">Simpan Pejabat Aktif</button>
                    </div>
                </form>
            </details>
        </div>

        @php
            $unpublishedExamIds = $exams->getCollection()->filter(fn ($exam) => ! $exam->invitation)->pluck('id');
        @endphp

        @if($unpublishedExamIds->isNotEmpty())
            <form method="POST" action="{{ route('management.exams.invitations.bulk-store') }}" class="mt-4 flex flex-col gap-3 rounded-2xl border border-emerald-100 bg-emerald-50/60 p-4 md:flex-row md:items-center md:justify-between">
                @csrf
                @foreach($unpublishedExamIds as $examId)
                    <input type="hidden" name="exam_ids[]" value="{{ $examId }}">
                @endforeach
                <div>
                    <p class="text-sm font-black text-emerald-800">Kirim semua undangan yang belum terbit di daftar ini</p>
                    <p class="mt-1 text-xs leading-5 text-emerald-700">{{ $unpublishedExamIds->count() }} jadwal sidang akan dibuatkan surat memakai pejabat aktif.</p>
                </div>
                <button class="rounded-xl bg-emerald-700 px-4 py-3 text-sm font-black text-white shadow-sm shadow-emerald-700/20" @disabled(! $signatory)>Kirim Semua</button>
            </form>
        @endif

        <div class="mt-5 space-y-3">
            @forelse($exams as $exam)
                @php
                    $isUpcoming = $exam->exam_date && $exam->exam_date->toDateString() >= now()->toDateString() && in_array($exam->status, ['dijadwalkan', 'ditunda'], true);
                    $invitation = $exam->invitation;
                @endphp
                <article class="rounded-2xl border border-slate-200 bg-slate-50/60 p-4">
                    <div class="grid gap-4 xl:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)_auto] xl:items-start">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full px-2.5 py-1 text-[11px] font-black ring-1 {{ $exam->statusBadgeClass() }}">{{ $exam->statusLabel() }}</span>
                                <span class="rounded-full px-2.5 py-1 text-[11px] font-black {{ $isUpcoming ? 'bg-emerald-50 text-emerald-700 ring-emerald-100' : 'bg-slate-100 text-slate-600 ring-slate-200' }}">{{ $isUpcoming ? 'Akan Dilaksanakan' : 'Riwayat' }}</span>
                                @if($invitation)
                                    <span class="rounded-full bg-cyan-50 px-2.5 py-1 text-[11px] font-black text-cyan-700 ring-1 ring-cyan-100">Surat terbit</span>
                                @else
                                    <span class="rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-black text-amber-700 ring-1 ring-amber-100">Belum ada surat</span>
                                @endif
                            </div>
                            <h3 class="mt-3 text-lg font-black text-slate-950">{{ $exam->assignment->student->user->name }}</h3>
                            <p class="mt-1 text-sm text-slate-500">{{ $exam->assignment->student->nim ?: '-' }} · {{ $exam->assignment->period?->name ?? '-' }}</p>
                            <p class="mt-2 text-sm font-semibold text-slate-700">{{ $exam->assignment->place?->name ?? '-' }}</p>
                        </div>

                        <div class="grid gap-2 text-sm text-slate-700">
                            <div class="rounded-xl bg-white px-3 py-2 ring-1 ring-slate-200">
                                <p class="text-[11px] font-black uppercase tracking-widest text-slate-500">Jadwal</p>
                                <p class="mt-1 font-bold">{{ $exam->scheduleLabel() }}</p>
                                <p class="text-xs text-slate-500">{{ $exam->room ?: $exam->meeting_link ?: '-' }}</p>
                            </div>
                            <div class="rounded-xl bg-white px-3 py-2 ring-1 ring-slate-200">
                                <p class="text-[11px] font-black uppercase tracking-widest text-slate-500">Penguji dan pembimbing</p>
                                <p class="mt-1 text-xs leading-5"><strong>Pembimbing:</strong> {{ $exam->supervisor ? lecturer_display_name($exam->supervisor) : '-' }}</p>
                                <p class="text-xs leading-5"><strong>Penguji:</strong> {{ $exam->examinerNamesLabel() }}</p>
                            </div>
                        </div>

                        <div class="flex flex-col gap-2 xl:min-w-60">
                            <a href="{{ route('management.exams.show',$exam) }}" class="rounded-xl bg-cyan-700 px-4 py-2 text-center text-sm font-black text-white">Detail Sidang</a>
                            @if($invitation)
                                <a href="{{ route('exam-invitations.letter.preview', $invitation) }}" class="rounded-xl border border-cyan-200 bg-white px-4 py-2 text-center text-xs font-black text-cyan-700">Preview Surat</a>
                                <a href="{{ route('exam-invitations.letter.pdf', $invitation) }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-center text-xs font-black text-slate-700">PDF</a>
                                <a href="{{ route('exam-invitations.letter.word', $invitation) }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-center text-xs font-black text-slate-700">Word</a>
                            @else
                                <form method="POST" action="{{ route('management.exams.invitation.store', $exam) }}">
                                    @csrf
                                    <button class="w-full rounded-xl border border-emerald-200 bg-emerald-600 px-4 py-2 text-center text-xs font-black text-white" @disabled(! $signatory)>Kirim Undangan</button>
                                </form>
                                @unless($signatory)
                                    <span class="rounded-xl bg-amber-50 px-3 py-2 text-center text-xs font-bold text-amber-700 ring-1 ring-amber-100">Isi pejabat aktif dulu</span>
                                @endunless
                            @endif
                        </div>
                    </div>
                </article>
            @empty
                <x-ui.empty-state title="Belum ada jadwal sidang." description="Jadwal sidang akan muncul setelah pengajuan sidang disetujui dan dijadwalkan." />
            @endforelse
        </div>

        <div class="mt-5 border-t border-slate-100 pt-4">{{ $exams->links() }}</div>
    </section>
</div>
@endsection
