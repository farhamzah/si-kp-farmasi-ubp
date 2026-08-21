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
            $guidanceLogs = $assignment->reportGuidanceLogs
                ->sortBy(fn ($guidance) => (optional($guidance->guidance_date)->format('Y-m-d') ?: '9999-12-31').'-'.str_pad((string) $guidance->id, 10, '0', STR_PAD_LEFT))
                ->values();
            $internalGuidanceLogs = $guidanceLogs->filter(fn ($guidance) => $guidance->isForInternalSupervisor())->values();
            $fieldGuidanceLogs = $guidanceLogs->filter(fn ($guidance) => $guidance->isForFieldSupervisor())->values();
            $pendingGuidanceLogs = $guidanceLogs->where('status', 'menunggu_validasi')->values();
            $guidanceSubmissionLocked = $pendingGuidanceLogs->isNotEmpty();
            $reviewedInternalGuidance = $internalGuidanceLogs->whereIn('status', ['disetujui', 'revisi'])->count();
            $reviewedFieldGuidance = $fieldGuidanceLogs->whereIn('status', ['disetujui', 'revisi'])->count();
            $pendingInternalGuidance = $internalGuidanceLogs->where('status', 'menunggu_validasi')->count();
            $pendingFieldGuidance = $fieldGuidanceLogs->where('status', 'menunggu_validasi')->count();
            $internalGuidanceCompleted = $report?->isInternalGuidanceCompleted() ?? false;
            $fieldGuidanceCompleted = $report?->isFieldGuidanceCompleted() ?? false;
            $internalGuidanceLabel = $internalGuidanceCompleted ? 'Selesai' : $reviewedInternalGuidance.'/8 sesi direview';
            $fieldGuidanceLabel = $fieldGuidanceCompleted ? 'Selesai' : $reviewedFieldGuidance.' sesi direview';
            $internalGuidanceProgress = $internalGuidanceCompleted ? 100 : min(100, (int) round(($reviewedInternalGuidance / 8) * 100));
            $fieldGuidanceProgress = $fieldGuidanceCompleted ? 100 : ($reviewedFieldGuidance > 0 ? 60 : 0);
            $eligibilityItems = $examEligibility['items'] ?? [];
        @endphp

        <section class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-cyan-100">
            <div class="border-b border-cyan-100 bg-gradient-to-r from-cyan-50 via-white to-emerald-50 px-5 py-5 md:px-6">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                    <div class="min-w-0">
                        <p class="text-xs font-black uppercase tracking-widest text-cyan-700">Ruang kerja laporan akhir</p>
                        <h2 class="mt-1 text-2xl font-black text-slate-950">{{ $assignment->place->name }}</h2>
                        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Kerjakan draft di Google Docs/Drive, catat bimbingan dengan pembimbing dalam dan pembimbing lapangan, lalu submit link laporan final setelah dokumen siap direview.</p>
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
                <div class="space-y-4">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-xs font-black uppercase tracking-widest text-slate-500">Bimbingan Dalam</p>
                        <p class="font-black text-cyan-700">{{ $internalGuidanceLabel }}</p>
                    </div>
                    <div class="mt-2 h-2.5 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-gradient-to-r from-cyan-600 to-emerald-500" style="width: {{ $internalGuidanceProgress }}%"></div>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-xs font-black uppercase tracking-widest text-slate-500">Bimbingan Lapangan</p>
                        <p class="font-black text-cyan-700">{{ $fieldGuidanceLabel }}</p>
                    </div>
                    <div class="mt-2 h-2.5 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-gradient-to-r from-sky-500 to-teal-500" style="width: {{ $fieldGuidanceProgress }}%"></div>
                    </div>
                </div>
            </div>

            <div class="px-5 pb-5 md:px-6 md:pb-6">
                <div class="mb-5 rounded-2xl border border-cyan-100 bg-cyan-50/40 p-4">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <p class="text-xs font-black uppercase tracking-widest text-cyan-700">Alur Laporan Akhir</p>
                            <h3 class="mt-1 text-lg font-black text-slate-950">Kerjakan berurutan supaya tidak bingung</h3>
                        </div>
                        <p class="max-w-2xl text-sm leading-6 text-slate-600">Bimbingan laporan dicatat terpisah dari logbook KP harian. Nilai dibuka setelah laporan final disetujui pembimbing dan kuisioner selesai.</p>
                    </div>
                    <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-5">
                        @foreach([
                            ['no' => '01', 'title' => 'Draft di Drive', 'desc' => 'Gunakan Google Docs/Drive agar pembimbing mudah memberi catatan.'],
                            ['no' => '02', 'title' => 'Catat Bimbingan', 'desc' => 'Kirim satu sesi, tunggu disetujui atau direvisi, lalu ajukan sesi berikutnya.'],
                            ['no' => '03', 'title' => 'Validasi Bimbingan', 'desc' => 'Sesi disetujui maupun revisi tetap tercatat. Pembimbing dalam minimal 8 sesi, lapangan minimal 1 sesi, lalu ditandai selesai.'],
                            ['no' => '04', 'title' => 'Upload Final', 'desc' => 'Simpan PDF final bertanda tangan di folder Drive resmi.'],
                            ['no' => '05', 'title' => 'Submit Review', 'desc' => 'Tempel link file final, lalu kirim untuk review kedua pembimbing.'],
                        ] as $step)
                            <div class="rounded-2xl border border-white bg-white p-4 shadow-sm">
                                <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-cyan-700 text-xs font-black text-white">{{ $step['no'] }}</span>
                                <p class="mt-3 text-sm font-black text-slate-950">{{ $step['title'] }}</p>
                                <p class="mt-1 text-xs leading-5 text-slate-500">{{ $step['desc'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

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

        <section class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_420px]">
            <div class="space-y-5">
                <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyan-100 md:p-6">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <p class="text-xs font-black uppercase tracking-widest text-cyan-700">Bimbingan laporan</p>
                            <h3 class="mt-1 text-xl font-black text-slate-950">Catat hasil bimbingan laporan</h3>
                            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Setiap catatan menjadi record resmi proses bimbingan. Mahasiswa hanya dapat mengirim satu sesi yang menunggu validasi; setelah pembimbing menyetujui atau meminta revisi, form akan terbuka lagi.</p>
                        </div>
                        <span class="inline-flex w-fit rounded-full bg-cyan-50 px-3 py-1 text-xs font-black text-cyan-700">Dalam {{ $internalGuidanceLabel }} | Lapangan {{ $fieldGuidanceLabel }}</span>
                    </div>

                    @if($pendingGuidanceLogs->isNotEmpty())
                        <div class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm leading-6 text-amber-800">
                            <p class="font-black">Masih ada {{ $pendingGuidanceLogs->count() }} sesi menunggu keputusan pembimbing</p>
                            <p class="mt-1">Mahasiswa belum bisa mengajukan bimbingan baru sampai sesi yang menunggu validasi diputuskan sebagai disetujui atau revisi.</p>
                            <div class="mt-3 grid gap-2 md:grid-cols-2">
                                @foreach($pendingGuidanceLogs->take(4) as $pendingItem)
                                    @php
                                        $sectionLogs = $pendingItem->isForFieldSupervisor() ? $fieldGuidanceLogs : $internalGuidanceLogs;
                                        $sectionIndex = $sectionLogs->search(fn ($item) => $item->id === $pendingItem->id);
                                    @endphp
                                    <div class="rounded-xl bg-white/70 px-3 py-2 ring-1 ring-amber-100">
                                        <p class="text-xs font-black uppercase tracking-widest text-amber-700">{{ $pendingItem->reviewerTypeLabel() }} sesi {{ str_pad((string) ($sectionIndex + 1), 2, '0', STR_PAD_LEFT) }}</p>
                                        <p class="mt-1 line-clamp-2 font-semibold text-amber-900">{{ $pendingItem->topic }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('student.final-reports.guidance.store') }}" class="mt-5 grid gap-3">
                        @csrf
                        <div class="grid gap-3 md:grid-cols-2">
                            <label class="block">
                                <span class="text-xs font-black uppercase tracking-widest text-slate-500">Divalidasi oleh</span>
                                <select name="reviewer_type" class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-3 text-sm font-semibold shadow-sm" @disabled($guidanceSubmissionLocked)>
                                    <option value="internal" @selected(old('reviewer_type') === 'internal')>Pembimbing Dalam</option>
                                    <option value="field" @selected(old('reviewer_type') === 'field')>Pembimbing Lapangan</option>
                                </select>
                            </label>
                            <label class="block">
                                <span class="text-xs font-black uppercase tracking-widest text-slate-500">Tanggal bimbingan</span>
                                <input type="date" name="guidance_date" value="{{ old('guidance_date', now()->toDateString()) }}" class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-3 text-sm shadow-sm" @disabled($guidanceSubmissionLocked)>
                            </label>
                        </div>
                        <label class="block">
                            <span class="text-xs font-black uppercase tracking-widest text-slate-500">Topik</span>
                            <input name="topic" value="{{ old('topic') }}" placeholder="Contoh: Revisi Bab 3 dan pembahasan" class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-3 text-sm shadow-sm" @disabled($guidanceSubmissionLocked)>
                        </label>
                        <div class="grid gap-3 md:grid-cols-[minmax(0,1fr)_280px]">
                            <label class="block">
                                <span class="text-xs font-black uppercase tracking-widest text-slate-500">Link dokumen kerja</span>
                                <input name="document_url" value="{{ old('document_url', $report?->final_document_url) }}" placeholder="https://docs.google.com/document/d/..." class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-3 text-sm shadow-sm" @disabled($guidanceSubmissionLocked)>
                            </label>
                            <label class="block">
                                <span class="text-xs font-black uppercase tracking-widest text-slate-500">Label link</span>
                                <input name="document_label" value="{{ old('document_label') }}" placeholder="Draft Bab 3" class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-3 text-sm shadow-sm" @disabled($guidanceSubmissionLocked)>
                            </label>
                        </div>
                        <p class="rounded-xl bg-cyan-50 px-4 py-3 text-xs font-semibold leading-5 text-cyan-800">Bagikan akses Google Docs/Drive ke pembimbing sebelum submit log. Pembimbing akan melihat tombol preview dan memvalidasi sesi ini dari akun mereka.</p>
                        <label class="block">
                            <span class="text-xs font-black uppercase tracking-widest text-slate-500">Catatan hasil bimbingan dari mahasiswa <span class="text-rose-600">*</span></span>
                            <textarea name="student_note" rows="6" required placeholder="Tuliskan ringkasan diskusi, arahan pembimbing, bagian yang direvisi, dan tindak lanjut yang akan Anda kerjakan." class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm leading-6 shadow-sm focus:border-cyan-400 focus:outline-none focus:ring-2 focus:ring-cyan-100" @disabled($guidanceSubmissionLocked)>{{ old('student_note') }}</textarea>
                            <span class="mt-2 block text-xs leading-5 text-slate-500">Catatan ini wajib diisi agar riwayat bimbingan berisi konteks mahasiswa dan mudah divalidasi pembimbing.</span>
                        </label>
                        <button @disabled($guidanceSubmissionLocked) class="w-full rounded-xl px-4 py-3 text-sm font-black text-white shadow-lg {{ $guidanceSubmissionLocked ? 'cursor-not-allowed bg-slate-400 shadow-slate-400/10' : 'bg-cyan-700 shadow-cyan-700/15' }}">{{ $guidanceSubmissionLocked ? 'Menunggu Keputusan Pembimbing' : 'Kirim Log Bimbingan' }}</button>
                    </form>
                </section>

                <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 md:p-6">
                    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                        <div>
                            <h3 class="text-lg font-black text-slate-950">Riwayat Bimbingan Laporan</h3>
                            <p class="mt-1 text-sm text-slate-500">Riwayat dipisahkan supaya catatan Pembimbing Dalam dan Pembimbing Lapangan tidak bercampur.</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-black text-emerald-700">Dalam {{ $internalGuidanceLabel }}</span>
                            <span class="rounded-full bg-sky-50 px-3 py-1 text-xs font-black text-sky-700">Lapangan {{ $fieldGuidanceLabel }}</span>
                        </div>
                    </div>

                    <div class="mt-5 grid gap-3 md:grid-cols-2">
                        <div class="rounded-2xl border border-emerald-100 bg-emerald-50/50 p-4">
                            <p class="text-xs font-black uppercase tracking-widest text-emerald-700">1. Pembimbing Dalam</p>
                            <p class="mt-1 text-sm font-semibold leading-6 text-slate-600">Minimal 8 sesi direview. Sesi disetujui maupun revisi tetap dihitung sebagai bimbingan.</p>
                        </div>
                        <div class="rounded-2xl border border-sky-100 bg-sky-50/50 p-4">
                            <p class="text-xs font-black uppercase tracking-widest text-sky-700">2. Pembimbing Lapangan</p>
                            <p class="mt-1 text-sm font-semibold leading-6 text-slate-600">Minimal 1 sesi direview, lalu Pembimbing Lapangan menandai bimbingan selesai.</p>
                        </div>
                    </div>
                </section>

                <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-emerald-100 md:p-6">
                    <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                        <div>
                            <p class="text-xs font-black uppercase tracking-widest text-emerald-700">1. Pembimbing Dalam</p>
                            <h3 class="mt-1 text-lg font-black text-slate-950">Riwayat Bimbingan Pembimbing Dalam</h3>
                            <p class="mt-1 text-sm text-slate-500">Nomor sesi di bagian ini hanya menghitung bimbingan dengan Pembimbing Dalam.</p>
                        </div>
                        <span class="inline-flex w-fit rounded-full bg-emerald-50 px-3 py-1 text-xs font-black text-emerald-700">{{ $reviewedInternalGuidance }} direview, {{ $pendingInternalGuidance }} menunggu</span>
                    </div>
                    <div class="mt-4">
                        @include('shared.final-reports.guidance-table', [
                            'guidanceLogs' => $internalGuidanceLogs,
                            'emptyText' => 'Belum ada log bimbingan Pembimbing Dalam. Pilih Pembimbing Dalam pada form bimbingan saat ingin mengajukan sesi internal.',
                            'showReviewer' => false,
                        ])
                    </div>
                </section>

                <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-sky-100 md:p-6">
                    <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                        <div>
                            <p class="text-xs font-black uppercase tracking-widest text-sky-700">2. Pembimbing Lapangan</p>
                            <h3 class="mt-1 text-lg font-black text-slate-950">Riwayat Bimbingan Pembimbing Lapangan</h3>
                            <p class="mt-1 text-sm text-slate-500">Nomor sesi di bagian ini hanya menghitung bimbingan dengan Pembimbing Lapangan.</p>
                        </div>
                        <span class="inline-flex w-fit rounded-full bg-sky-50 px-3 py-1 text-xs font-black text-sky-700">{{ $reviewedFieldGuidance }} direview, {{ $pendingFieldGuidance }} menunggu</span>
                    </div>
                    <div class="mt-4">
                        @include('shared.final-reports.guidance-table', [
                            'guidanceLogs' => $fieldGuidanceLogs,
                            'emptyText' => 'Belum ada log bimbingan Pembimbing Lapangan. Pilih Pembimbing Lapangan pada form bimbingan saat ingin mengajukan sesi lapangan.',
                            'showReviewer' => false,
                        ])
                    </div>
                </section>

                <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-cyan-100">
                    <div class="grid gap-5 p-5 md:p-6 xl:grid-cols-[minmax(0,1fr)_320px]">
                        <div class="min-w-0">
                            <p class="text-xs font-black uppercase tracking-widest text-cyan-700">Folder resmi laporan final</p>
                            <h3 class="mt-1 text-xl font-black text-slate-950">Upload PDF final ke Google Drive prodi</h3>
                            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Gunakan folder Drive resmi untuk menyimpan laporan akhir yang sudah final dan sudah ditandatangani lengkap. Setelah file berhasil diupload, buka file di Drive, salin link berbagi file, lalu tempel di form Link Laporan Final.</p>
                            <div class="mt-4 grid gap-3 md:grid-cols-3">
                                <div class="rounded-2xl border border-cyan-100 bg-cyan-50/60 p-4">
                                    <p class="text-xs font-black uppercase tracking-widest text-cyan-700">1. Nama File</p>
                                    <p class="mt-2 text-sm font-semibold leading-6 text-slate-700">Ubah nama PDF sesuai format resmi.</p>
                                </div>
                                <div class="rounded-2xl border border-cyan-100 bg-white p-4">
                                    <p class="text-xs font-black uppercase tracking-widest text-cyan-700">2. Upload</p>
                                    <p class="mt-2 text-sm font-semibold leading-6 text-slate-700">Masukkan file ke folder Drive laporan final.</p>
                                </div>
                                <div class="rounded-2xl border border-cyan-100 bg-white p-4">
                                    <p class="text-xs font-black uppercase tracking-widest text-cyan-700">3. Submit Link</p>
                                    <p class="mt-2 text-sm font-semibold leading-6 text-slate-700">Tempel link file, bukan link folder.</p>
                                </div>
                            </div>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs font-black uppercase tracking-widest text-slate-500">Contoh nama file</p>
                            <p class="mt-2 break-all rounded-xl bg-white px-3 py-3 text-sm font-black text-slate-950 ring-1 ring-slate-100">{{ $suggestedFinalFilename }}</p>
                            <p class="mt-3 text-xs leading-5 text-slate-500">Best practice: gunakan PDF final, nama file berisi NIM dan nama, tanpa karakter khusus, dan pastikan akses link dapat dibuka pembimbing/koordinator.</p>
                            @if($driveFolderUrl)
                                <a href="{{ $driveFolderUrl }}" target="_blank" rel="noopener" class="mt-4 inline-flex w-full items-center justify-center rounded-xl bg-cyan-700 px-4 py-3 text-sm font-black text-white shadow-lg shadow-cyan-700/15">Buka Folder Drive Resmi</a>
                            @endif
                        </div>
                    </div>
                </section>

                <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 md:p-6">
                    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                        <div>
                            <h3 class="text-lg font-black text-slate-950">Link Laporan Final</h3>
                            <p class="mt-1 text-sm text-slate-500">Tempel link file laporan final dari Google Drive resmi. Link ini menjadi bukti pengumpulan final dan akan diperiksa pembimbing sebelum nilai dapat dibuka.</p>
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
                            <input name="final_document_url" value="{{ old('final_document_url', $report?->final_document_url) }}" placeholder="https://drive.google.com/file/d/..." class="rounded-xl border border-slate-300 px-3 py-2 text-sm shadow-sm">
                            <input name="final_document_label" value="{{ old('final_document_label', $report?->final_document_label ?: $suggestedFinalFilename) }}" placeholder="Judul/link opsional" class="rounded-xl border border-slate-300 px-3 py-2 text-sm shadow-sm">
                            <button class="rounded-xl bg-cyan-700 px-4 py-2 text-sm font-bold text-white shadow-lg shadow-cyan-700/15">Simpan Link</button>
                        </form>
                        <p class="mt-3 rounded-xl bg-amber-50 px-4 py-3 text-xs font-semibold leading-5 text-amber-800">Pastikan yang ditempel adalah link file laporan final, bukan link folder. Jika pembimbing tidak bisa membuka, ubah akses Drive menjadi dapat dilihat oleh akun yang memiliki link atau bagikan langsung ke pembimbing.</p>
                    @else
                        <p class="mt-4 rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-500">Link final terkunci saat laporan sedang direview atau sudah disetujui.</p>
                    @endif
                </section>

                <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 md:p-6">
                    <h3 class="text-lg font-black text-slate-950">Alternatif Upload ke Aplikasi</h3>
                    <p class="mt-1 text-sm text-slate-500">Gunakan hanya jika diminta admin/koordinator. Alur utama laporan final adalah upload PDF ke Google Drive resmi lalu submit link file di aplikasi.</p>
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

            <aside class="space-y-5 xl:sticky xl:top-24 xl:self-start">
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

                <section class="rounded-2xl border border-cyan-100 bg-cyan-50/50 p-5">
                    <h3 class="font-black text-slate-950">Ringkasan Syarat Sidang</h3>
                    <div class="mt-4 space-y-3 text-sm">
                        <div>
                            <div class="flex items-center justify-between gap-3">
                                <span class="font-bold text-slate-700">Bimbingan Dalam</span>
                                <span class="font-black text-cyan-700">{{ $internalGuidanceLabel }}</span>
                            </div>
                            <div class="mt-2 h-2 overflow-hidden rounded-full bg-white">
                                <div class="h-full rounded-full bg-cyan-700" style="width: {{ $internalGuidanceProgress }}%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex items-center justify-between gap-3">
                                <span class="font-bold text-slate-700">Bimbingan Lapangan</span>
                                <span class="font-black text-cyan-700">{{ $fieldGuidanceLabel }}</span>
                            </div>
                            <div class="mt-2 h-2 overflow-hidden rounded-full bg-white">
                                <div class="h-full rounded-full bg-emerald-500" style="width: {{ $fieldGuidanceProgress }}%"></div>
                            </div>
                        </div>
                    </div>
                    <p class="mt-4 text-xs leading-5 text-slate-600">Setelah link/file final disetujui pembimbing dalam dan pembimbing lapangan, koordinator dapat memvalidasi akhir untuk penjadwalan sidang.</p>
                </section>
            </aside>
        </section>
    @endif
</div>
@endsection
