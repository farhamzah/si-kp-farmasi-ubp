@php
    $logs = collect($guidanceLogs ?? [])->values();
    $minimumSessions = (int) ($minimumSessions ?? 0);
    $rowCount = max($logs->count(), $minimumSessions);
    $showReviewer = (bool) ($showReviewer ?? false);
    $actions = $actions ?? null;
    $hasActions = in_array($actions, ['internal', 'field'], true);
    $emptyText = $emptyText ?? 'Belum ada log bimbingan laporan.';
    $reviewedCount = $logs->whereIn('status', ['disetujui', 'revisi'])->count();
    $pendingCount = $logs->where('status', 'menunggu_validasi')->count();
    $studentNoteCount = $logs->filter(fn ($guidance) => filled($guidance->student_note))->count();
    $supervisorNoteCount = $logs->filter(fn ($guidance) => filled($guidance->validation_note))->count();
    $targetText = $minimumSessions > 0 ? $reviewedCount.'/'.$minimumSessions.' sesi direview' : $reviewedCount.' sesi direview';
@endphp

@if($rowCount > 0)
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="grid gap-3 border-b border-slate-100 bg-slate-50/80 px-4 py-4 md:grid-cols-2 lg:grid-cols-4">
            <div>
                <p class="text-[11px] font-black uppercase tracking-widest text-slate-500">Bimbingan Tercatat</p>
                <p class="mt-1 text-sm font-black text-slate-950">{{ $targetText }}</p>
            </div>
            <div>
                <p class="text-[11px] font-black uppercase tracking-widest text-slate-500">Menunggu Validasi</p>
                <p class="mt-1 text-sm font-black text-slate-950">{{ $pendingCount }} sesi</p>
            </div>
            <div>
                <p class="text-[11px] font-black uppercase tracking-widest text-slate-500">Total Log</p>
                <p class="mt-1 text-sm font-black text-slate-950">{{ $logs->count() }} catatan</p>
            </div>
            <div>
                <p class="text-[11px] font-black uppercase tracking-widest text-slate-500">Record Catatan</p>
                <p class="mt-1 text-sm font-black text-slate-950">{{ $studentNoteCount }} mhs / {{ $supervisorNoteCount }} pembimbing</p>
            </div>
        </div>

        <div class="space-y-3 p-3 md:p-4">
            @for($index = 0; $index < $rowCount; $index++)
                @php
                    $guidance = $logs->get($index);
                    $status = $guidance?->status;
                    $cardClass = match ($status) {
                        'disetujui' => 'border-emerald-200 bg-emerald-50/30',
                        'revisi' => 'border-blue-200 bg-blue-50/30',
                        'ditolak' => 'border-red-200 bg-red-50/30',
                        'menunggu_validasi' => 'border-amber-200 bg-amber-50/30',
                        default => 'border-slate-200 bg-slate-50/60',
                    };
                    $supervisorBoxClass = match ($status) {
                        'disetujui' => 'border-emerald-200 bg-emerald-50',
                        'revisi' => 'border-blue-200 bg-blue-50',
                        'ditolak' => 'border-red-200 bg-red-50',
                        'menunggu_validasi' => 'border-amber-200 bg-amber-50',
                        default => 'border-slate-200 bg-slate-50',
                    };
                    $supervisorTitleClass = match ($status) {
                        'disetujui' => 'text-emerald-700',
                        'revisi' => 'text-blue-700',
                        'ditolak' => 'text-red-700',
                        'menunggu_validasi' => 'text-amber-700',
                        default => 'text-slate-500',
                    };
                    $supervisorEmptyText = match ($status) {
                        'disetujui' => 'Sesi disetujui tanpa catatan tambahan.',
                        'revisi' => 'Belum ada detail revisi tersimpan.',
                        'ditolak' => 'Belum ada alasan penolakan tersimpan.',
                        'menunggu_validasi' => 'Menunggu '.$guidance?->reviewerTypeLabel().' membaca catatan mahasiswa dan memberi keputusan.',
                        default => 'Belum ada catatan pembimbing.',
                    };
                @endphp

                @if($guidance)
                    <article class="rounded-2xl border {{ $cardClass }} p-4 shadow-sm">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex rounded-full bg-white px-2.5 py-1 text-[11px] font-black text-cyan-700 ring-1 ring-cyan-100">Sesi {{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                                    <span class="text-xs font-semibold text-slate-500">{{ optional($guidance->guidance_date)->format('d M Y') ?: 'Tanggal belum diisi' }}</span>
                                    @if($showReviewer)
                                        <span class="inline-flex rounded-full bg-white px-2.5 py-1 text-[11px] font-black text-slate-600 ring-1 ring-slate-200">{{ $guidance->reviewerTypeLabel() }}</span>
                                    @endif
                                </div>
                                <h4 class="mt-3 break-words text-base font-black leading-6 text-slate-950">{{ $guidance->topic }}</h4>
                            </div>
                            <span class="inline-flex w-fit shrink-0 rounded-full px-2.5 py-1 text-[11px] font-bold ring-1 {{ $guidance->statusBadgeClass() }}">{{ $guidance->statusLabel() }}</span>
                        </div>

                        <div class="mt-4 grid gap-3 xl:grid-cols-2">
                            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                <p class="text-[11px] font-black uppercase tracking-widest text-slate-500">Yang diajukan mahasiswa</p>
                                <p class="mt-2 whitespace-pre-line break-words text-sm leading-6 text-slate-700">{{ $guidance->student_note ?: 'Mahasiswa belum menulis catatan tambahan. Gunakan topik dan dokumen sebagai konteks sesi.' }}</p>

                                <div class="mt-4 flex flex-wrap gap-2">
                                    @if($guidance->document_url)
                                        <a href="{{ $guidance->document_url }}" target="_blank" rel="noopener" class="inline-flex rounded-xl border border-cyan-200 bg-white px-3 py-2 text-xs font-black text-cyan-700 shadow-sm">Preview Dokumen</a>
                                        <a href="{{ $guidance->document_url }}" target="_blank" rel="noopener" class="inline-flex max-w-full rounded-xl bg-slate-900 px-3 py-2 text-xs font-black text-white shadow-sm">
                                            <span class="truncate">{{ $guidance->document_label ?: 'Buka Link Bimbingan' }}</span>
                                        </a>
                                    @else
                                        <span class="rounded-xl bg-slate-50 px-3 py-2 text-xs font-bold text-slate-400 ring-1 ring-slate-100">Tidak ada link dokumen</span>
                                    @endif
                                </div>
                            </div>

                            <div class="rounded-2xl border {{ $supervisorBoxClass }} p-4">
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                    <p class="text-[11px] font-black uppercase tracking-widest {{ $supervisorTitleClass }}">Catatan {{ $guidance->reviewerTypeLabel() }}</p>
                                    @if($guidance->validated_at)
                                        <span class="text-xs font-semibold text-slate-500">{{ $guidance->validated_at->format('d M Y H:i') }}</span>
                                    @endif
                                </div>
                                <p class="mt-2 whitespace-pre-line break-words text-sm leading-6 text-slate-700">{{ $guidance->validation_note ?: $supervisorEmptyText }}</p>
                                @if($guidance->validatedBy)
                                    <p class="mt-3 text-xs font-semibold text-slate-500">Direview oleh {{ $guidance->validatedBy->name }}</p>
                                @endif
                            </div>
                        </div>

                        @if($hasActions)
                            @if($guidance->status !== 'menunggu_validasi')
                                <p class="mt-4 rounded-xl bg-white px-4 py-3 text-xs font-bold text-slate-600 ring-1 ring-slate-100">Sesi ini sudah direview sebagai {{ strtolower($guidance->statusLabel()) }} dan tetap dihitung sebagai record bimbingan.</p>
                            @else
                                @php($approveRoute = $actions === 'internal' ? route('internal-supervisor.final-reports.guidance.approve', [$report, $guidance]) : route('field-supervisor.final-reports.guidance.approve', [$report, $guidance]))
                                @php($revisionRoute = $actions === 'internal' ? route('internal-supervisor.final-reports.guidance.revision', [$report, $guidance]) : route('field-supervisor.final-reports.guidance.revision', [$report, $guidance]))
                                <div class="mt-4 rounded-2xl border border-cyan-100 bg-white p-4">
                                    <div class="flex flex-col gap-1 md:flex-row md:items-start md:justify-between">
                                        <div>
                                            <p class="text-[11px] font-black uppercase tracking-widest text-cyan-700">Keputusan {{ $guidance->reviewerTypeLabel() }} sesi {{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</p>
                                            <p class="mt-1 text-xs leading-5 text-slate-500">Isi catatan agar mahasiswa dan koordinator dapat membaca hasil bimbingan ini kembali.</p>
                                        </div>
                                        <span class="inline-flex w-fit rounded-full bg-cyan-50 px-3 py-1 text-xs font-black text-cyan-700">{{ $guidance->reviewerTypeLabel() }}</span>
                                    </div>

                                    <div class="mt-4 grid gap-4 xl:grid-cols-2">
                                        <form method="POST" action="{{ $approveRoute }}" class="rounded-2xl border border-emerald-100 bg-emerald-50/40 p-4">
                                            @csrf
                                            <label class="block text-xs font-black uppercase tracking-widest text-emerald-700">Catatan {{ $guidance->reviewerTypeLabel() }} / hasil bimbingan</label>
                                            <textarea name="review_note" rows="5" placeholder="Contoh: Substansi sudah sesuai arahan, revisi sudah ditindaklanjuti, dan sesi ini disetujui." class="mt-3 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm leading-6 shadow-sm focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100"></textarea>
                                            <button class="mt-3 w-full rounded-xl bg-emerald-600 px-4 py-3 text-sm font-black text-white shadow-sm hover:bg-emerald-700">Setujui Sesi</button>
                                        </form>

                                        <form method="POST" action="{{ $revisionRoute }}" class="rounded-2xl border border-amber-100 bg-amber-50/40 p-4">
                                            @csrf
                                            <label class="block text-xs font-black uppercase tracking-widest text-amber-700">Instruksi revisi untuk mahasiswa</label>
                                            <textarea name="review_note" rows="5" required placeholder="Tuliskan bagian yang perlu diperbaiki mahasiswa, misalnya Bab 2 kurang referensi atau format tabel belum sesuai panduan." class="mt-3 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm leading-6 shadow-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100"></textarea>
                                            <button class="mt-3 w-full rounded-xl bg-amber-500 px-4 py-3 text-sm font-black text-white shadow-sm hover:bg-amber-600">Minta Revisi</button>
                                        </form>
                                    </div>
                                </div>
                            @endif
                        @endif
                    </article>
                @else
                    <div class="flex flex-col gap-2 rounded-2xl border border-dashed border-slate-200 bg-slate-50/70 px-4 py-3 md:flex-row md:items-center md:justify-between">
                        <div class="flex min-w-0 items-start gap-3">
                            <span class="mt-0.5 inline-flex rounded-full bg-white px-2.5 py-1 text-[11px] font-black text-slate-400 ring-1 ring-slate-200">Sesi {{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                            <div class="min-w-0">
                                <p class="font-bold text-slate-400">Belum ada sesi bimbingan</p>
                                <p class="mt-1 text-xs leading-5 text-slate-400">Slot ini membantu melihat kekurangan sesi minimal.</p>
                            </div>
                        </div>
                        <span class="inline-flex w-fit rounded-full bg-white px-2.5 py-1 text-[11px] font-bold text-slate-400 ring-1 ring-slate-200">Menunggu sesi</span>
                    </div>
                @endif
            @endfor
        </div>
    </div>
@else
    <p class="rounded-xl bg-slate-50 px-4 py-4 text-sm leading-6 text-slate-500">{{ $emptyText }}</p>
@endif
