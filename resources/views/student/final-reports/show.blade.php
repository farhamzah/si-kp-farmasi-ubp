@extends('layouts.app')

@section('title','Laporan Akhir - '.config('app.name'))
@section('page_title','Laporan Akhir')

@section('content')
<div class="space-y-5">
    @if($errors->any())
        <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">{{ $errors->first() }}</div>
    @endif

    @if(! $assignment)
        <x-ui.empty-state title="Anda belum memiliki penempatan KP aktif." description="Laporan akhir dapat diupload setelah penempatan KP aktif atau berjalan." />
    @else
        @php
            $guidanceLogs = $assignment->reportGuidanceLogs->sortByDesc('guidance_date');
            $approvedGuidance = $assignment->reportGuidanceLogs->where('status', 'disetujui')->count();
            $guidanceProgress = min(100, (int) round(($approvedGuidance / 8) * 100));
            $eligibilityItems = $examEligibility['items'] ?? [];
        @endphp

        <section class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-cyan-100">
            <div class="border-b border-cyan-100 bg-gradient-to-r from-cyan-50 via-white to-emerald-50 px-5 py-5 md:px-6">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                    <div class="min-w-0">
                        <p class="text-xs font-black uppercase tracking-widest text-cyan-700">Ruang kerja laporan akhir</p>
                        <h2 class="mt-1 text-2xl font-black text-slate-950">{{ $assignment->place->name }}</h2>
                        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Kerjakan laporan di Google Docs/Drive, catat setiap sesi bimbingan, lalu submit link final setelah pembimbing dalam dan pembimbing lapangan sepakat dokumen siap dinilai.</p>
                    </div>
                    @if($report)
                        <span class="inline-flex w-fit rounded-full px-3 py-1 text-xs font-bold ring-1 {{ $report->statusBadgeClass() }}">{{ $report->statusLabel() }}</span>
                    @endif
                </div>
            </div>
            <div class="grid gap-4 p-5 md:grid-cols-3 md:p-6">
                <div class="min-w-0">
                    <p class="text-xs font-black uppercase tracking-widest text-slate-500">Pembimbing Dalam</p>
                    <p class="mt-1 font-black text-slate-950">{{ $assignment->internalSupervisor ? lecturer_display_name($assignment->internalSupervisor) : '-' }}</p>
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-black uppercase tracking-widest text-slate-500">Pembimbing Lapangan</p>
                    <p class="mt-1 font-black text-slate-950">{{ $assignment->fieldSupervisor ? field_supervisor_display_name($assignment->fieldSupervisor) : '-' }}</p>
                </div>
                <div>
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-xs font-black uppercase tracking-widest text-slate-500">Bimbingan Disetujui</p>
                        <p class="font-black text-cyan-700">{{ $approvedGuidance }}/8</p>
                    </div>
                    <div class="mt-2 h-2.5 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-gradient-to-r from-cyan-600 to-emerald-500" style="width: {{ $guidanceProgress }}%"></div>
                    </div>
                </div>
            </div>

            <div class="px-5 pb-5 md:px-6 md:pb-6">
                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                    @foreach($eligibilityItems as $item)
                        <div class="rounded-2xl border {{ $item['ready'] ? 'border-emerald-200 bg-emerald-50/60' : 'border-amber-200 bg-amber-50/60' }} p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-black text-slate-950">{{ $item['label'] }}</p>
                                    <p class="mt-1 text-xs text-slate-600">{{ $item['description'] }}</p>
                                </div>
                                <span class="rounded-full px-2.5 py-1 text-[11px] font-black {{ $item['ready'] ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">{{ $item['ready'] ? 'OK' : 'Belum' }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="grid gap-5 xl:grid-cols-[1fr_380px]">
            <div class="space-y-5">
                <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 md:p-6">
                    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                        <div>
                            <h3 class="text-lg font-black text-slate-950">Link Laporan Final</h3>
                            <p class="mt-1 text-sm text-slate-500">Simpan link final setelah proses bimbingan selesai. Untuk proses revisi harian, isi link dokumen pada log bimbingan di samping.</p>
                        </div>
                        @if($report?->final_document_url)
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ $report->final_document_url }}" target="_blank" rel="noopener" class="rounded-xl border border-cyan-200 px-4 py-2 text-sm font-bold text-cyan-700">Preview</a>
                                <a href="{{ $report->final_document_url }}" target="_blank" rel="noopener" class="rounded-xl bg-cyan-700 px-4 py-2 text-sm font-bold text-white">Buka Dokumen</a>
                            </div>
                        @endif
                    </div>

                    @if(! $report || $report->canBeEditedByStudent())
                        <form method="POST" action="{{ route('student.final-reports.final-link') }}" class="mt-4 grid gap-3 md:grid-cols-[minmax(0,1fr)_260px_auto]">
                            @csrf
                            <input name="final_document_url" value="{{ old('final_document_url', $report?->final_document_url) }}" placeholder="https://docs.google.com/..." class="rounded-xl border border-slate-300 px-3 py-2 text-sm shadow-sm">
                            <input name="final_document_label" value="{{ old('final_document_label', $report?->final_document_label) }}" placeholder="Judul/link opsional" class="rounded-xl border border-slate-300 px-3 py-2 text-sm shadow-sm">
                            <button class="rounded-xl bg-cyan-700 px-4 py-2 text-sm font-bold text-white shadow-lg shadow-cyan-700/15">Simpan Link</button>
                        </form>
                    @else
                        <p class="mt-4 rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-500">Link final terkunci saat laporan sedang direview atau sudah disetujui.</p>
                    @endif
                </section>

                <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 md:p-6">
                    <h3 class="text-lg font-black text-slate-950">Upload File Laporan</h3>
                    <p class="mt-1 text-sm text-slate-500">Opsional jika laporan final memakai Google Docs/Drive. Format PDF, DOC, atau DOCX. Maksimal 10MB.</p>
                    @if(! $report || $report->canBeEditedByStudent())
                        <form method="POST" action="{{ route('student.final-reports.upload') }}" enctype="multipart/form-data" class="mt-4 grid gap-3 md:grid-cols-[1fr_1fr_auto]">
                            @csrf
                            <input type="file" name="report_file" class="rounded-xl border border-slate-300 px-3 py-2 text-sm shadow-sm">
                            <input name="note" placeholder="Catatan upload opsional" class="rounded-xl border border-slate-300 px-3 py-2 text-sm shadow-sm">
                            <button class="rounded-xl bg-teal-600 px-4 py-2 text-sm font-bold text-white shadow-lg shadow-teal-700/15">Upload</button>
                        </form>
                    @else
                        <p class="mt-4 rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-500">Upload tidak tersedia pada status saat ini.</p>
                    @endif

                    <div class="mt-4 space-y-3">
                        @forelse($report?->files ?? [] as $file)
                            <div class="flex flex-col gap-2 rounded-xl border border-slate-200 p-4 md:flex-row md:items-center md:justify-between">
                                <div>
                                    <p class="font-bold text-slate-950">Versi {{ $file->version }} - {{ $file->original_filename }}</p>
                                    <p class="text-xs text-slate-500">{{ $file->humanFileSize() }} | {{ $file->uploaded_at->format('d M Y H:i') }}</p>
                                </div>
                                <a href="{{ route('student.final-reports.files.download',$file) }}" class="rounded-lg border border-cyan-200 px-3 py-1.5 text-xs font-bold text-cyan-700">Download</a>
                            </div>
                        @empty
                            <p class="rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-500">Belum ada file laporan.</p>
                        @endforelse
                    </div>
                </section>
            </div>

            <aside class="space-y-5">
                <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <h3 class="font-black text-slate-950">Status Persetujuan</h3>
                    <div class="mt-4 space-y-3 text-sm">
                        <div class="rounded-xl bg-slate-50 p-4">
                            <p class="text-xs font-black uppercase tracking-widest text-slate-500">Pembimbing Dalam</p>
                            <p class="mt-1 font-black text-slate-950">{{ $report?->internalReviewStatusLabel() ?? 'Belum Review' }}</p>
                            @if($report?->internal_review_note)<p class="mt-2 text-xs text-slate-600">{{ $report->internal_review_note }}</p>@endif
                        </div>
                        <div class="rounded-xl bg-slate-50 p-4">
                            <p class="text-xs font-black uppercase tracking-widest text-slate-500">Pembimbing Lapangan</p>
                            <p class="mt-1 font-black text-slate-950">{{ $report?->fieldReviewStatusLabel() ?? 'Belum Review' }}</p>
                            @if($report?->field_review_note)<p class="mt-2 text-xs text-slate-600">{{ $report->field_review_note }}</p>@endif
                        </div>
                    </div>
                    @if($report?->review_note)
                        <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">{{ $report->review_note }}</div>
                    @endif
                    @if($report?->canBeSubmitted())
                        <form method="POST" action="{{ route('student.final-reports.submit') }}" class="mt-4">
                            @csrf
                            <button onclick="return confirm('Submit laporan final untuk review kedua pembimbing?')" class="w-full rounded-xl bg-slate-950 px-4 py-3 text-sm font-bold text-white">Submit Final untuk Review</button>
                        </form>
                    @endif
                </section>

                <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="font-black text-slate-950">Log Bimbingan Laporan</h3>
                            <p class="mt-1 text-sm text-slate-500">Catat topik, hasil diskusi, dan link dokumen kerja yang sedang diperiksa.</p>
                        </div>
                        <span class="rounded-full bg-cyan-50 px-3 py-1 text-xs font-black text-cyan-700">{{ $approvedGuidance }}/8 OK</span>
                    </div>
                    <form method="POST" action="{{ route('student.final-reports.guidance.store') }}" class="mt-4 space-y-3">
                        @csrf
                        <input type="date" name="guidance_date" value="{{ old('guidance_date', now()->toDateString()) }}" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm shadow-sm">
                        <input name="topic" value="{{ old('topic') }}" placeholder="Topik bimbingan laporan" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm shadow-sm">
                        <input name="document_url" value="{{ old('document_url', $report?->final_document_url) }}" placeholder="Link Google Docs/Drive yang dibahas" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm shadow-sm">
                        <input name="document_label" value="{{ old('document_label') }}" placeholder="Label link opsional, contoh: Draft Bab 3" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm shadow-sm">
                        <p class="rounded-xl bg-cyan-50 px-3 py-2 text-xs leading-5 text-cyan-800">Gunakan link Google Docs/Drive yang aksesnya sudah dibagikan ke pembimbing. Link ini akan tampil saat pembimbing memvalidasi sesi bimbingan.</p>
                        <textarea name="student_note" rows="3" placeholder="Catatan mahasiswa opsional" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm shadow-sm">{{ old('student_note') }}</textarea>
                        <button class="w-full rounded-xl bg-cyan-700 px-4 py-2 text-sm font-bold text-white">Kirim Log Bimbingan</button>
                    </form>
                    <div class="mt-4 max-h-96 space-y-2 overflow-y-auto pr-1">
                        @forelse($guidanceLogs as $guidance)
                            <div class="rounded-xl border border-slate-200 p-3 text-sm">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <p class="font-bold text-slate-950">{{ $guidance->topic }}</p>
                                        <p class="text-xs text-slate-500">{{ $guidance->guidance_date->format('d M Y') }}</p>
                                    </div>
                                    <span class="rounded-full px-2.5 py-1 text-[11px] font-bold ring-1 {{ $guidance->statusBadgeClass() }}">{{ $guidance->statusLabel() }}</span>
                                </div>
                                @if($guidance->validation_note)<p class="mt-2 text-xs text-slate-600">{{ $guidance->validation_note }}</p>@endif
                                @if($guidance->document_url)
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        <a href="{{ $guidance->document_url }}" target="_blank" rel="noopener" class="rounded-lg border border-cyan-200 px-3 py-1.5 text-xs font-bold text-cyan-700">Preview</a>
                                        <a href="{{ $guidance->document_url }}" target="_blank" rel="noopener" class="rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-bold text-white">{{ $guidance->document_label ?: 'Buka Dokumen' }}</a>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <p class="rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-500">Belum ada log bimbingan laporan.</p>
                        @endforelse
                    </div>
                </section>
            </aside>
        </section>
    @endif
</div>
@endsection
