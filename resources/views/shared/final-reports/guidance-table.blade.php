@php
    $logs = collect($guidanceLogs ?? [])->values();
    $minimumSessions = (int) ($minimumSessions ?? 0);
    $rowCount = max($logs->count(), $minimumSessions);
    $showReviewer = (bool) ($showReviewer ?? false);
    $actions = $actions ?? null;
    $emptyText = $emptyText ?? 'Belum ada log bimbingan laporan.';
    $approvedCount = $logs->where('status', 'disetujui')->count();
    $openCount = $logs->whereIn('status', ['menunggu_validasi', 'revisi', 'ditolak'])->count();
    $targetText = $minimumSessions > 0 ? $approvedCount.'/'.$minimumSessions.' sesi disetujui' : $approvedCount.' sesi disetujui';
@endphp

@if($rowCount > 0)
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="grid gap-3 border-b border-slate-100 bg-slate-50/80 px-4 py-4 md:grid-cols-3">
            <div>
                <p class="text-[11px] font-black uppercase tracking-widest text-slate-500">Progress</p>
                <p class="mt-1 text-sm font-black text-slate-950">{{ $targetText }}</p>
            </div>
            <div>
                <p class="text-[11px] font-black uppercase tracking-widest text-slate-500">Perlu Tindak Lanjut</p>
                <p class="mt-1 text-sm font-black text-slate-950">{{ $openCount }} sesi</p>
            </div>
            <div>
                <p class="text-[11px] font-black uppercase tracking-widest text-slate-500">Total Log</p>
                <p class="mt-1 text-sm font-black text-slate-950">{{ $logs->count() }} catatan</p>
            </div>
        </div>

        <div class="lg:hidden">
            <div class="divide-y divide-slate-100">
                @for($index = 0; $index < $rowCount; $index++)
                    @php($guidance = $logs->get($index))
                    <article class="p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-[11px] font-black uppercase tracking-widest text-cyan-700">Sesi {{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</p>
                                @if($guidance)
                                    <h4 class="mt-1 text-base font-black leading-6 text-slate-950">{{ $guidance->topic }}</h4>
                                    <p class="mt-1 text-xs font-semibold text-slate-500">{{ optional($guidance->guidance_date)->format('d M Y') ?: '-' }}@if($showReviewer) | {{ $guidance->reviewerTypeLabel() }}@endif</p>
                                @else
                                    <h4 class="mt-1 text-base font-black leading-6 text-slate-400">Belum ada sesi bimbingan</h4>
                                    <p class="mt-1 text-xs font-semibold text-slate-400">Slot ini belum dikirim mahasiswa.</p>
                                @endif
                            </div>
                            @if($guidance)
                                <span class="inline-flex shrink-0 rounded-full px-2.5 py-1 text-[11px] font-bold ring-1 {{ $guidance->statusBadgeClass() }}">{{ $guidance->statusLabel() }}</span>
                            @else
                                <span class="inline-flex shrink-0 rounded-full bg-slate-50 px-2.5 py-1 text-[11px] font-bold text-slate-400 ring-1 ring-slate-200">Kosong</span>
                            @endif
                        </div>

                        @if($guidance)
                            @if($guidance->student_note)
                                <div class="mt-3 rounded-xl bg-slate-50 px-3 py-2">
                                    <p class="text-[11px] font-black uppercase tracking-widest text-slate-400">Catatan Mahasiswa</p>
                                    <p class="mt-1 text-sm leading-6 text-slate-700">{{ $guidance->student_note }}</p>
                                </div>
                            @endif
                            @if($guidance->validation_note)
                                <div class="mt-3 rounded-xl border border-emerald-100 bg-emerald-50 px-3 py-2">
                                    <p class="text-[11px] font-black uppercase tracking-widest text-emerald-600">Catatan Validasi</p>
                                    <p class="mt-1 text-sm leading-6 text-slate-700">{{ $guidance->validation_note }}</p>
                                </div>
                            @endif
                            <div class="mt-3 flex flex-wrap gap-2">
                                @if($guidance->document_url)
                                    <a href="{{ $guidance->document_url }}" target="_blank" rel="noopener" class="inline-flex rounded-xl border border-cyan-200 bg-white px-3 py-2 text-xs font-black text-cyan-700">Preview Dokumen</a>
                                    <a href="{{ $guidance->document_url }}" target="_blank" rel="noopener" class="inline-flex rounded-xl bg-slate-900 px-3 py-2 text-xs font-black text-white">{{ $guidance->document_label ?: 'Buka Link' }}</a>
                                @else
                                    <span class="rounded-xl bg-slate-50 px-3 py-2 text-xs font-bold text-slate-400">Tidak ada link dokumen</span>
                                @endif
                            </div>

                            @if($actions)
                                <div class="mt-4 rounded-2xl border border-slate-100 bg-slate-50 p-3">
                                    @if($guidance->status === 'disetujui')
                                        <p class="rounded-xl bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700">Sesi ini sudah disetujui.</p>
                                    @else
                                        @php($approveRoute = $actions === 'internal' ? route('internal-supervisor.final-reports.guidance.approve', [$report, $guidance]) : route('field-supervisor.final-reports.guidance.approve', [$report, $guidance]))
                                        @php($revisionRoute = $actions === 'internal' ? route('internal-supervisor.final-reports.guidance.revision', [$report, $guidance]) : route('field-supervisor.final-reports.guidance.revision', [$report, $guidance]))
                                        <form method="POST" action="{{ $approveRoute }}">
                                            @csrf
                                            <label class="block text-xs font-black uppercase tracking-widest text-slate-500">Catatan persetujuan</label>
                                            <textarea name="review_note" rows="5" placeholder="Tuliskan ringkasan hasil bimbingan atau catatan opsional." class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-3 py-3 text-sm leading-6 shadow-sm"></textarea>
                                            <button class="mt-2 w-full rounded-xl bg-emerald-600 px-4 py-3 text-sm font-black text-white">Setujui Sesi</button>
                                        </form>
                                        <form method="POST" action="{{ $revisionRoute }}" class="mt-3">
                                            @csrf
                                            <label class="block text-xs font-black uppercase tracking-widest text-slate-500">Catatan revisi</label>
                                            <textarea name="review_note" rows="5" required placeholder="Tuliskan perbaikan yang harus dilakukan mahasiswa." class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-3 py-3 text-sm leading-6 shadow-sm"></textarea>
                                            <button class="mt-2 w-full rounded-xl bg-amber-500 px-4 py-3 text-sm font-black text-white">Minta Revisi</button>
                                        </form>
                                    @endif
                                </div>
                            @endif
                        @endif
                    </article>
                @endfor
            </div>
        </div>

        <div class="hidden overflow-x-auto lg:block">
            <table class="min-w-[1120px] w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs font-black uppercase tracking-widest text-slate-500">
                    <tr>
                        <th class="w-16 px-4 py-3">Sesi</th>
                        <th class="w-36 px-4 py-3">Tanggal</th>
                        @if($showReviewer)
                            <th class="w-44 px-4 py-3">Untuk</th>
                        @endif
                        <th class="px-4 py-3">Topik dan Catatan</th>
                        <th class="w-56 px-4 py-3">Dokumen</th>
                        <th class="w-40 px-4 py-3">Status</th>
                        @if($actions)
                            <th class="w-[420px] px-4 py-3">Aksi Validasi</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @for($index = 0; $index < $rowCount; $index++)
                        @php($guidance = $logs->get($index))
                        <tr class="align-top">
                            <td class="px-4 py-4 font-black text-cyan-700">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</td>
                            @if($guidance)
                                <td class="px-4 py-4 text-slate-700">{{ optional($guidance->guidance_date)->format('d M Y') ?: '-' }}</td>
                                @if($showReviewer)
                                    <td class="px-4 py-4 font-semibold text-slate-700">{{ $guidance->reviewerTypeLabel() }}</td>
                                @endif
                                <td class="px-4 py-4">
                                    <p class="font-black leading-6 text-slate-950">{{ $guidance->topic }}</p>
                                    @if($guidance->student_note)
                                        <div class="mt-3 rounded-xl bg-slate-50 px-3 py-2">
                                            <p class="text-[11px] font-black uppercase tracking-widest text-slate-400">Catatan Mahasiswa</p>
                                            <p class="mt-1 text-sm leading-6 text-slate-700">{{ $guidance->student_note }}</p>
                                        </div>
                                    @endif
                                    @if($guidance->validation_note)
                                        <div class="mt-3 rounded-xl border border-emerald-100 bg-emerald-50 px-3 py-2">
                                            <p class="text-[11px] font-black uppercase tracking-widest text-emerald-600">Catatan Validasi</p>
                                            <p class="mt-1 text-sm leading-6 text-slate-700">{{ $guidance->validation_note }}</p>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-4">
                                    @if($guidance->document_url)
                                        <div class="flex flex-col gap-2">
                                            <a href="{{ $guidance->document_url }}" target="_blank" rel="noopener" class="inline-flex w-fit rounded-lg border border-cyan-200 px-3 py-1.5 text-xs font-bold text-cyan-700">Preview Dokumen</a>
                                            <a href="{{ $guidance->document_url }}" target="_blank" rel="noopener" class="inline-flex w-fit rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-bold text-white">{{ $guidance->document_label ?: 'Buka Link Bimbingan' }}</a>
                                        </div>
                                    @else
                                        <span class="text-xs font-semibold text-slate-400">Tidak ada link</span>
                                    @endif
                                </td>
                                <td class="px-4 py-4">
                                    <span class="inline-flex w-fit rounded-full px-2.5 py-1 text-[11px] font-bold ring-1 {{ $guidance->statusBadgeClass() }}">{{ $guidance->statusLabel() }}</span>
                                </td>
                                @if($actions)
                                    <td class="px-4 py-4">
                                        @if($guidance->status === 'disetujui')
                                            <p class="rounded-xl bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700">Sesi ini sudah disetujui.</p>
                                        @else
                                            @php($approveRoute = $actions === 'internal' ? route('internal-supervisor.final-reports.guidance.approve', [$report, $guidance]) : route('field-supervisor.final-reports.guidance.approve', [$report, $guidance]))
                                            @php($revisionRoute = $actions === 'internal' ? route('internal-supervisor.final-reports.guidance.revision', [$report, $guidance]) : route('field-supervisor.final-reports.guidance.revision', [$report, $guidance]))
                                            <div class="grid gap-3 xl:grid-cols-2">
                                                <form method="POST" action="{{ $approveRoute }}">
                                                    @csrf
                                                    <label class="block text-xs font-black uppercase tracking-widest text-slate-500">Catatan persetujuan</label>
                                                    <textarea name="review_note" rows="5" placeholder="Tuliskan ringkasan hasil bimbingan atau catatan opsional." class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-3 py-3 text-sm leading-6 shadow-sm"></textarea>
                                                    <button class="mt-2 w-full rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-black text-white">Setujui Sesi</button>
                                                </form>
                                                <form method="POST" action="{{ $revisionRoute }}">
                                                    @csrf
                                                    <label class="block text-xs font-black uppercase tracking-widest text-slate-500">Catatan revisi</label>
                                                    <textarea name="review_note" rows="5" required placeholder="Tuliskan perbaikan yang harus dilakukan mahasiswa." class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-3 py-3 text-sm leading-6 shadow-sm"></textarea>
                                                    <button class="mt-2 w-full rounded-xl bg-amber-500 px-4 py-2.5 text-sm font-black text-white">Minta Revisi</button>
                                                </form>
                                            </div>
                                        @endif
                                    </td>
                                @endif
                            @else
                                <td class="px-4 py-4 text-slate-400">Belum ada</td>
                                @if($showReviewer)
                                    <td class="px-4 py-4 text-slate-400">-</td>
                                @endif
                                <td class="px-4 py-4">
                                    <p class="font-bold text-slate-400">Belum ada sesi bimbingan</p>
                                    <p class="mt-1 text-xs leading-5 text-slate-400">Slot ini membantu melihat kekurangan sesi minimal.</p>
                                </td>
                                <td class="px-4 py-4 text-xs font-semibold text-slate-400">-</td>
                                <td class="px-4 py-4">
                                    <span class="inline-flex rounded-full bg-slate-50 px-2.5 py-1 text-[11px] font-bold text-slate-400 ring-1 ring-slate-200">Menunggu sesi</span>
                                </td>
                                @if($actions)
                                    <td class="px-4 py-4 text-xs font-semibold text-slate-400">Aksi muncul setelah mahasiswa mengirim log.</td>
                                @endif
                            @endif
                        </tr>
                    @endfor
                </tbody>
            </table>
        </div>
    </div>
@else
    <p class="rounded-xl bg-slate-50 px-4 py-4 text-sm leading-6 text-slate-500">{{ $emptyText }}</p>
@endif
