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

    <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyan-100">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-widest text-cyan-700">Agenda Sidang KP</p>
                <h2 class="mt-1 text-xl font-black text-slate-950">Daftar jadwal koordinator</h2>
                <p class="mt-1 text-sm text-slate-600">Jadwal terdekat otomatis tampil paling atas. Gunakan preview atau PDF untuk membagikan daftar resmi ke grup WhatsApp.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('management.exams.report.preview', $filters) }}" target="_blank" class="rounded-xl border border-cyan-200 bg-white px-4 py-2 text-sm font-black text-cyan-700">Preview Daftar</a>
                <a href="{{ route('management.exams.report.pdf', $filters) }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-black text-slate-700">Download PDF</a>
                <a href="{{ route('management.exams.report.preview', array_merge($filters, ['print' => 1])) }}" target="_blank" class="rounded-xl bg-slate-950 px-4 py-2 text-sm font-black text-white">Print</a>
            </div>
        </div>

        <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach([['label' => 'Laporan Final Tersedia', 'value' => $stats['reports_available'], 'class' => 'text-slate-950'], ['label' => 'Sudah Dijadwalkan', 'value' => $stats['scheduled'], 'class' => 'text-cyan-700'], ['label' => 'Layak, Belum Dijadwalkan', 'value' => $stats['ready_unscheduled'], 'class' => 'text-emerald-700'], ['label' => 'Perlu Tindak Lanjut', 'value' => $stats['blocked'], 'class' => 'text-amber-700']] as $stat)
                <div class="rounded-xl bg-slate-50 px-4 py-3 ring-1 ring-slate-100">
                    <p class="text-[11px] font-black uppercase tracking-widest text-slate-500">{{ $stat['label'] }}</p>
                    <p class="mt-1 text-2xl font-black {{ $stat['class'] }}">{{ $stat['value'] }}</p>
                </div>
            @endforeach
        </div>

        <form method="GET" class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-[minmax(220px,1fr)_170px_150px_150px_150px_auto]">
            <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Cari nama, NIM, judul, tempat, atau ruang" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <select name="period" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="">Semua Periode</option>
                @foreach($periods as $period)
                    <option value="{{ $period->id }}" @selected(($filters['period'] ?? '') == $period->id)>{{ $period->name }}</option>
                @endforeach
            </select>
            <select name="status" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="">Semua Status</option>
                <option value="belum_dijadwalkan" @selected(($filters['status'] ?? '') === 'belum_dijadwalkan')>Belum Dijadwalkan</option>
                @foreach(['dijadwalkan'=>'Dijadwalkan','selesai'=>'Selesai','ditunda'=>'Ditunda','dibatalkan'=>'Dibatalkan'] as $value=>$label)
                    <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" title="Tanggal mulai" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" title="Tanggal akhir" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <div class="flex gap-2">
                <button class="flex-1 rounded-lg bg-cyan-700 px-4 py-2 text-sm font-semibold text-white">Terapkan</button>
                <a href="{{ route('management.exams.index') }}" class="rounded-lg border border-slate-200 px-4 py-2 text-center text-sm font-semibold text-slate-600">Reset</a>
            </div>
        </form>
    </section>

    @if(! ($filters['status'] ?? null) || $filters['status'] === 'belum_dijadwalkan')
        <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-emerald-200">
            <div class="flex flex-wrap items-baseline justify-between gap-2">
                <h2 class="text-lg font-black text-slate-950">Layak sidang, belum dijadwalkan</h2>
                <span class="text-sm font-bold text-emerald-700">{{ $readyCandidates->count() }} mahasiswa</span>
            </div>
            <div class="mt-4 divide-y divide-slate-100">
                @forelse($readyCandidates as $candidate)
                    @php
                        $requestStatus = $candidate->examRequest?->status;
                    @endphp
                    <div class="flex flex-col gap-3 py-3 lg:flex-row lg:items-center lg:justify-between">
                        <div class="min-w-0">
                            <p class="font-bold text-slate-950">{{ $candidate->student?->user?->name ?? '-' }} <span class="font-normal text-slate-500">· {{ $candidate->student?->nim ?? '-' }}</span></p>
                            <p class="mt-1 text-xs text-slate-600">{{ $candidate->period?->name ?? '-' }} · {{ $candidate->place?->name ?? '-' }}</p>
                            <p class="mt-1 text-xs text-emerald-700">Laporan final tersedia · pembimbing dalam: {{ $candidate->finalReport?->internalReviewStatusLabel() ?? 'Belum review' }} · pembimbing lapangan: {{ $candidate->finalReport?->fieldReviewStatusLabel() ?? 'Belum review' }} · {{ $candidate->examRequest?->paymentProofStatusLabel() ?? 'Bukti pembayaran belum diunggah' }}</p>
                        </div>
                        <div class="flex shrink-0 flex-wrap items-center gap-2">
                            @if(! $candidate->examRequest)
                                <span class="rounded-full bg-emerald-50 px-2 py-1 text-xs font-bold text-emerald-700">Belum diajukan</span>
                                <form method="POST" action="{{ route('management.exam-requests.candidates.enqueue', $candidate) }}">
                                    @csrf
                                    <button type="submit" class="rounded-lg bg-cyan-700 px-4 py-2 text-sm font-bold text-white shadow-sm">Masukkan Antrean</button>
                                </form>
                            @elseif($requestStatus === 'disetujui')
                                <a href="{{ route('management.exam-requests.schedule', $candidate->examRequest) }}" class="rounded-lg bg-cyan-700 px-4 py-2 text-sm font-bold text-white">Jadwalkan</a>
                            @else
                                <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-bold text-slate-700">{{ $candidate->examRequest->statusLabel() }}</span>
                                <a href="{{ route('management.exam-requests.show', $candidate->examRequest) }}" class="rounded-lg border border-cyan-200 px-4 py-2 text-sm font-bold text-cyan-700">{{ $requestStatus === 'diajukan' ? 'Validasi' : 'Detail Pengajuan' }}</a>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="py-6 text-sm text-slate-500">Tidak ada mahasiswa layak sidang yang menunggu jadwal pada filter ini.</p>
                @endforelse
            </div>
        </section>

        @if($blockedCandidates->isNotEmpty())
            <details class="rounded-2xl border border-amber-200 bg-white p-5">
                <summary class="cursor-pointer text-sm font-bold text-amber-800">{{ $blockedCandidates->count() }} laporan final masih perlu tindak lanjut</summary>
                <div class="mt-4 divide-y divide-slate-100">
                    @foreach($blockedCandidates as $candidate)
                        @php
                            $pending = collect($candidate->examEligibility()['items'])
                                ->filter(fn ($item) => $item['required_for_scheduling'] && ! $item['ready'])
                                ->pluck('label')
                                ->join(', ');
                        @endphp
                        <div class="py-3">
                            <p class="font-bold text-slate-950">{{ $candidate->student?->user?->name ?? '-' }} <span class="font-normal text-slate-500">· {{ $candidate->student?->nim ?? '-' }}</span></p>
                            <p class="mt-1 text-xs text-amber-800">Perlu: {{ $pending }}</p>
                        </div>
                    @endforeach
                </div>
            </details>
        @endif
    @endif

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
                <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="grid xl:grid-cols-[140px_minmax(220px,1.15fr)_minmax(250px,1fr)_minmax(250px,1fr)_auto]">
                        <div class="flex flex-row items-center gap-4 border-b border-slate-100 bg-cyan-50/70 p-4 xl:flex-col xl:items-start xl:border-b-0 xl:border-r">
                            <div class="flex h-14 w-14 flex-none items-center justify-center rounded-xl bg-white text-center ring-1 ring-cyan-100">
                                <span><strong class="block text-xl font-black text-cyan-800">{{ $exam->exam_date?->format('d') }}</strong><small class="block text-[10px] font-black uppercase text-cyan-600">{{ $exam->exam_date?->translatedFormat('M') }}</small></span>
                            </div>
                            <div>
                                <p class="text-xs font-black text-slate-950">{{ $exam->exam_date?->translatedFormat('l') }}</p>
                                <p class="mt-1 text-sm font-black text-cyan-800">{{ substr((string) $exam->start_time, 0, 5) }}–{{ substr((string) $exam->end_time, 0, 5) }}</p>
                                <p class="text-[11px] text-slate-500">WIB</p>
                            </div>
                        </div>

                        <div class="min-w-0 border-b border-slate-100 p-4 xl:border-b-0 xl:border-r">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full px-2.5 py-1 text-[11px] font-black ring-1 {{ $exam->statusBadgeClass() }}">{{ $exam->statusLabel() }}</span>
                                <span class="rounded-full px-2.5 py-1 text-[11px] font-black {{ $isUpcoming ? 'bg-emerald-50 text-emerald-700 ring-emerald-100' : 'bg-slate-100 text-slate-600 ring-slate-200' }}">{{ $isUpcoming ? 'Akan Dilaksanakan' : 'Riwayat' }}</span>
                                @if($invitation)
                                    <span class="rounded-full bg-cyan-50 px-2.5 py-1 text-[11px] font-black text-cyan-700 ring-1 ring-cyan-100">Surat terbit</span>
                                @else
                                    <span class="rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-black text-amber-700 ring-1 ring-amber-100">Belum ada surat</span>
                                @endif
                                @if($exam->minutes)
                                    <span class="rounded-full bg-white px-2.5 py-1 text-[11px] font-black text-cyan-700 ring-1 ring-cyan-200">BA: {{ $exam->minutes->statusLabel() }}</span>
                                @endif
                                @if($exam->backdate_reason)
                                    <span class="rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-black text-amber-700 ring-1 ring-amber-200">Kasus backdate</span>
                                @endif
                            </div>
                            <h3 class="mt-3 text-base font-black text-slate-950">{{ $exam->assignment->student->user->name }}</h3>
                            <p class="mt-1 text-xs font-semibold text-cyan-700">{{ $exam->assignment->student->nim ?: '-' }} · {{ $exam->assignment->period?->name ?? '-' }}</p>
                            <p class="mt-3 text-[11px] font-black uppercase tracking-widest text-slate-400">Judul Laporan</p>
                            <p class="mt-1 text-sm font-semibold leading-5 text-slate-800">{{ $exam->assignment->finalReport?->report_title ?: $exam->assignment->finalReport?->final_document_label ?: 'Judul belum diisi' }}</p>
                            @if($exam->backdate_reason)
                                <p class="mt-2 rounded-xl bg-amber-50 px-3 py-2 text-xs leading-5 text-amber-800 ring-1 ring-amber-100"><strong>Alasan tanggal sebelumnya:</strong> {{ $exam->backdate_reason }}</p>
                            @endif
                        </div>

                        <div class="border-b border-slate-100 p-4 text-sm xl:border-b-0 xl:border-r">
                            <p class="text-[11px] font-black uppercase tracking-widest text-slate-400">Lokasi Pelaksanaan</p>
                            <p class="mt-2 font-black text-slate-900">{{ $exam->room ?: ($exam->mode === 'online' ? 'Daring' : '-') }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $exam->modeLabel() }}@if($exam->meeting_link) · Link rapat tersedia @endif</p>
                            <p class="mt-4 text-[11px] font-black uppercase tracking-widest text-slate-400">Tempat KP</p>
                            <p class="mt-1 text-sm font-semibold text-slate-700">{{ $exam->assignment->place?->name ?? '-' }}</p>
                        </div>

                        <div class="border-b border-slate-100 p-4 text-xs leading-5 text-slate-700 xl:border-b-0 xl:border-r">
                            <p class="text-[11px] font-black uppercase tracking-widest text-slate-400">Tim Sidang</p>
                            <p class="mt-2"><strong class="text-slate-950">Ketua:</strong><br>{{ $exam->chair ? lecturer_display_name($exam->chair) : '-' }}</p>
                            <p class="mt-2"><strong class="text-slate-950">Pembimbing:</strong><br>{{ $exam->supervisor ? lecturer_display_name($exam->supervisor) : '-' }}</p>
                            <p class="mt-2"><strong class="text-slate-950">Penguji:</strong><br>{{ $exam->examinerNamesLabel() }}</p>
                        </div>

                        <div class="flex flex-wrap content-start gap-2 p-4 xl:w-44 xl:flex-col">
                            <a href="{{ route('management.exams.show',$exam) }}" class="rounded-lg bg-cyan-700 px-3 py-2 text-center text-xs font-black text-white">Detail</a>
                            @if($exam->minutes)<a href="{{ route('exam-minutes.preview',$exam->minutes) }}" class="rounded-lg border border-emerald-200 px-3 py-2 text-center text-xs font-black text-emerald-700">Berita Acara</a>@endif
                            @if($invitation)
                                <a href="{{ route('exam-invitations.letter.preview', $invitation) }}" class="rounded-lg border border-cyan-200 px-3 py-2 text-center text-xs font-black text-cyan-700">Undangan</a>
                                <div class="flex gap-2"><a href="{{ route('exam-invitations.letter.pdf', $invitation) }}" class="flex-1 rounded-lg border border-slate-200 px-3 py-2 text-center text-xs font-black text-slate-700">PDF</a><a href="{{ route('exam-invitations.letter.word', $invitation) }}" class="flex-1 rounded-lg border border-slate-200 px-3 py-2 text-center text-xs font-black text-slate-700">Word</a></div>
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
