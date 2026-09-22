@extends('layouts.app')

@section('title','Sidang KP - '.config('app.name'))
@section('page_title','Sidang KP')

@section('content')
<div class="space-y-5">
    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">{{ $errors->first() }}</div>
    @endif

    @if(! $assignment)
        <x-ui.empty-state title="Anda belum memiliki penempatan KP aktif." description="Pengajuan sidang tersedia setelah penempatan dan laporan akhir selesai." />
    @else
        @php($isReady = $examEligibility['ready'] ?? false)
        <x-ui.status-stepper :steps="[
            ['label' => 'Syarat Sidang', 'state' => $isReady ? 'done' : 'warning', 'description' => $isReady ? 'Lengkap' : 'Belum lengkap'],
            ['label' => 'Pengajuan Sidang', 'state' => $examRequest ? 'done' : 'pending', 'description' => $examRequest?->statusLabel() ?? 'Belum diajukan'],
            ['label' => 'Dijadwalkan', 'state' => $exam ? 'done' : 'pending', 'description' => $exam?->scheduleLabel() ?? 'Menunggu jadwal'],
            ['label' => 'Selesai', 'state' => $exam?->status === 'selesai' ? 'done' : 'pending', 'description' => $exam?->statusLabel() ?? 'Belum selesai'],
        ]" />

        <x-ui.card>
            <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-widest text-cyan-700">Kesiapan Sidang</p>
                    <h2 class="mt-1 text-2xl font-black text-slate-950">{{ $assignment->place->name }}</h2>
                    <p class="mt-1 text-sm text-slate-500">Pembimbing Dalam: {{ $assignment->internalSupervisor ? lecturer_display_name($assignment->internalSupervisor) : '-' }}</p>
                </div>
                @if($examRequest)
                    <span class="rounded-full px-3 py-1 text-xs font-bold ring-1 {{ $examRequest->statusBadgeClass() }}">{{ $examRequest->statusLabel() }}</span>
                @endif
            </div>

            @if($isReady && ($examEligibility['provisional'] ?? false) && collect($examEligibility['items'])->contains(fn ($item) => ! $item['ready']))
                <p class="mt-5 rounded-lg border border-cyan-100 bg-cyan-50 px-4 py-3 text-sm text-cyan-800">Laporan final tersedia. Pengajuan sidang sementara dapat diproses meski review pembimbing atau bimbingan masih berjalan; statusnya tetap terlihat di bawah.</p>
            @endif
            <div class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                @foreach(($examEligibility['items'] ?? []) as $item)
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

            @if($examRequest)
                <div class="mt-5 rounded-2xl border {{ $examRequest->paymentProofApproved() ? 'border-emerald-200 bg-emerald-50/60' : 'border-blue-200 bg-blue-50/50' }} p-4">
                    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-xs font-black uppercase tracking-widest text-blue-700">Bukti pembayaran KP</p>
                                <span class="rounded-full bg-white px-2.5 py-1 text-[11px] font-black text-blue-700 ring-1 ring-blue-200">Syarat membuka nilai</span>
                            </div>
                            <p class="mt-1 text-sm font-bold text-slate-950">{{ $examRequest->paymentProofLabel() }}</p>
                            <p class="mt-1 text-xs leading-5 text-slate-600">Belum upload atau belum disetujui tidak menghambat pengajuan dan jadwal sidang.</p>
                            <span class="mt-3 inline-flex rounded-full px-3 py-1 text-xs font-bold ring-1 {{ $examRequest->paymentProofBadgeClass() }}">{{ $examRequest->paymentProofStatusLabel() }}</span>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            @if($examRequest->payment_proof_path)
                                <a href="{{ route('student.exams.payment-proof.preview') }}" target="_blank" rel="noopener" class="rounded-xl border border-cyan-200 bg-white px-4 py-2 text-sm font-bold text-cyan-700">Preview File</a>
                                <a href="{{ route('student.exams.payment-proof.download') }}" class="rounded-xl bg-slate-950 px-4 py-2 text-sm font-bold text-white">Download</a>
                            @endif
                            @if($examRequest->payment_proof_url)
                                <a href="{{ $examRequest->payment_proof_url }}" target="_blank" rel="noopener" class="rounded-xl border border-emerald-200 bg-white px-4 py-2 text-sm font-bold text-emerald-700">Buka Link Drive</a>
                            @endif
                        </div>
                    </div>
                    @if($examRequest->payment_proof_review_note)
                        <div class="mt-4 rounded-xl border border-blue-200 bg-white px-4 py-3 text-sm text-blue-800">{{ $examRequest->payment_proof_review_note }}</div>
                    @endif
                </div>
            @endif

            @if(! $isReady)
                <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    @if($examEligibility['provisional'] ?? false)
                        Pengajuan sidang dibuka setelah dokumen laporan final tersedia dan penempatan KP valid. Persetujuan pembimbing, progres bimbingan, serta bukti pembayaran tetap tercatat, tetapi untuk sementara tidak mengunci pengajuan atau jadwal sidang.
                    @else
                        Pengajuan sidang dibuka setelah logbook dan bimbingan selesai serta laporan final disetujui kedua pembimbing. Bukti pembayaran tidak mengunci pengajuan sidang.
                    @endif
                </div>
            @elseif(! $examRequest)
                <form method="POST" action="{{ route('student.exams.submit') }}" enctype="multipart/form-data" class="mt-5 space-y-3">
                    @csrf
                    <div class="rounded-2xl border border-cyan-100 bg-cyan-50/40 p-4">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="text-xs font-black uppercase tracking-widest text-cyan-700">Bukti pembayaran KP</p>
                            <span class="rounded-full bg-white px-2.5 py-1 text-[11px] font-black text-cyan-700 ring-1 ring-cyan-200">Opsional saat mengajukan sidang</span>
                        </div>
                        <p class="mt-1 text-sm leading-6 text-slate-600">Boleh dikosongkan agar tetap masuk antrian sidang. Bukti yang disetujui koordinator wajib sebelum nilai dapat dilihat.</p>
                        <div class="mt-3 grid gap-3 md:grid-cols-2">
                            <label class="block">
                                <span class="text-xs font-black uppercase tracking-widest text-slate-500">Upload file</span>
                                <input type="file" name="payment_proof" accept=".pdf,.jpg,.jpeg,.png" class="mt-2 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                            </label>
                            <label class="block">
                                <span class="text-xs font-black uppercase tracking-widest text-slate-500">Link Drive</span>
                                <input name="payment_proof_url" value="{{ old('payment_proof_url') }}" placeholder="https://drive.google.com/file/d/..." class="mt-2 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                            </label>
                        </div>
                        <label class="mt-3 block">
                            <span class="text-xs font-black uppercase tracking-widest text-slate-500">Label bukti</span>
                            <input name="payment_proof_label" value="{{ old('payment_proof_label') }}" placeholder="Contoh: Bukti pembayaran KP semester ini" class="mt-2 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                        </label>
                    </div>
                    <textarea name="request_note" rows="3" placeholder="Catatan pengajuan opsional" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea>
                    <button class="rounded-lg bg-cyan-700 px-4 py-2 text-sm font-bold text-white">Ajukan Sidang</button>
                </form>
            @elseif($examRequest->canReplacePaymentProof())
                <form method="POST" action="{{ route('student.exams.payment-proof.update') }}" enctype="multipart/form-data" class="mt-5 space-y-3">
                    @csrf
                    <div class="rounded-2xl border border-cyan-100 bg-cyan-50/40 p-4">
                        <p class="text-xs font-black uppercase tracking-widest text-cyan-700">{{ $examRequest->hasPaymentProof() ? 'Ganti bukti pembayaran KP' : 'Unggah bukti pembayaran KP' }}</p>
                        <p class="mt-1 text-sm leading-6 text-slate-600">Bukti dapat diunggah atau diganti meskipun sidang sudah dijadwalkan. Setelah disimpan, bukti menunggu validasi koordinator untuk membuka akses nilai.</p>
                        <div class="mt-3 grid gap-3 md:grid-cols-2">
                            <label class="block">
                                <span class="text-xs font-black uppercase tracking-widest text-slate-500">Upload file pengganti</span>
                                <input type="file" name="payment_proof" accept=".pdf,.jpg,.jpeg,.png" class="mt-2 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                            </label>
                            <label class="block">
                                <span class="text-xs font-black uppercase tracking-widest text-slate-500">Link Drive pengganti</span>
                                <input name="payment_proof_url" value="{{ old('payment_proof_url') }}" placeholder="https://drive.google.com/file/d/..." class="mt-2 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                            </label>
                        </div>
                        <label class="mt-3 block">
                            <span class="text-xs font-black uppercase tracking-widest text-slate-500">Label bukti</span>
                            <input name="payment_proof_label" value="{{ old('payment_proof_label') }}" placeholder="Contoh: Bukti pembayaran KP yang benar" class="mt-2 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                        </label>
                    </div>
                    <button class="rounded-lg bg-cyan-700 px-4 py-2 text-sm font-bold text-white">{{ $examRequest->hasPaymentProof() ? 'Ganti Bukti Pembayaran' : 'Unggah Bukti Pembayaran' }}</button>
                </form>
            @elseif($examRequest)
                <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">Bukti pembayaran tidak dapat diubah karena pengajuan sidang sudah tidak aktif.</div>
            @endif

            @if($examRequest?->review_note)
                <div class="mt-5 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">{{ $examRequest->review_note }}</div>
            @endif
        </x-ui.card>

        @if($exam)
            <x-ui.card>
                <h3 class="text-lg font-black text-slate-950">Jadwal Sidang</h3>
                <div class="mt-4 grid gap-4 md:grid-cols-3">
                    <div class="rounded-xl bg-slate-50 p-4"><p class="text-xs text-slate-500">Tanggal & Jam</p><p class="mt-1 font-bold">{{ $exam->scheduleLabel() }}</p></div>
                    <div class="rounded-xl bg-slate-50 p-4"><p class="text-xs text-slate-500">Mode</p><p class="mt-1 font-bold">{{ $exam->modeLabel() }}</p></div>
                    <div class="rounded-xl bg-slate-50 p-4"><p class="text-xs text-slate-500">Penguji</p><p class="mt-1 font-bold">{{ $exam->examinerNamesLabel() }}</p></div>
                </div>
                <p class="mt-4 text-sm text-slate-600">Lokasi: {{ $exam->room ?: '-' }} | Link: {{ $exam->meeting_link ?: '-' }}</p>
            </x-ui.card>
        @endif
    @endif
</div>
@endsection
