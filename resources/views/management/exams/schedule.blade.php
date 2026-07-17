@extends('layouts.app')
@section('title','Jadwal Sidang - '.config('app.name'))
@section('page_title', $exam ? 'Edit Jadwal Sidang' : 'Jadwalkan Sidang')
@section('content')
@php
    $assignment = $examRequest->assignment;
    $eligibility = $assignment->examEligibility();
    $report = $assignment->finalReport;
    $selectedExaminerIds = collect(old('examiner_ids', $exam?->examinerIds() ?? []))->map(fn ($id) => (int) $id)->all();
@endphp
<div class="space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('management.exam-requests.show', $examRequest) }}" class="inline-flex rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 shadow-sm">Kembali ke Validasi</a>
        <a href="{{ route('management.exams.index') }}" class="inline-flex rounded-xl border border-cyan-200 bg-white px-4 py-2 text-sm font-bold text-cyan-700 shadow-sm">Daftar Jadwal</a>
    </div>

    <div class="grid gap-5 xl:grid-cols-[380px_1fr]">
        <aside class="space-y-5">
            <x-ui.card>
                <p class="text-xs font-black uppercase tracking-widest text-cyan-700">Kandidat sidang</p>
                <h2 class="mt-2 text-2xl font-black text-slate-950">{{ $assignment->student->user->name }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $assignment->student->nim ?: '-' }} · {{ $assignment->period->name }}</p>

                <div class="mt-5 space-y-3">
                    <div class="rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-100">
                        <p class="text-xs font-black uppercase tracking-widest text-slate-500">Tempat KP</p>
                        <p class="mt-1 font-bold text-slate-950">{{ $assignment->place->name }}</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-100">
                        <p class="text-xs font-black uppercase tracking-widest text-slate-500">Pembimbing Dalam</p>
                        <p class="mt-1 font-bold text-slate-950">{{ $assignment->internalSupervisor ? lecturer_display_name($assignment->internalSupervisor) : '-' }}</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-100">
                        <p class="text-xs font-black uppercase tracking-widest text-slate-500">Pembimbing Lapangan</p>
                        <p class="mt-1 font-bold text-slate-950">{{ $assignment->fieldSupervisor ? field_supervisor_display_name($assignment->fieldSupervisor) : '-' }}</p>
                    </div>
                </div>

                @if($report?->final_document_url)
                    <a href="{{ $report->final_document_url }}" target="_blank" rel="noopener" class="mt-4 block rounded-xl bg-slate-950 px-4 py-3 text-center text-sm font-bold text-white shadow-sm">Preview Laporan Final</a>
                @endif
            </x-ui.card>

            <x-ui.card>
                <div class="flex items-center justify-between gap-3">
                    <h3 class="text-lg font-black text-slate-950">Checklist</h3>
                    <span class="rounded-full px-3 py-1 text-xs font-bold ring-1 {{ $eligibility['ready'] ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-amber-50 text-amber-700 ring-amber-200' }}">{{ collect($eligibility['items'])->where('ready', true)->count() }}/{{ count($eligibility['items']) }}</span>
                </div>
                <div class="mt-4 space-y-2">
                    @foreach($eligibility['items'] as $item)
                        <div class="flex items-start gap-2 rounded-xl bg-slate-50 px-3 py-2 ring-1 ring-slate-100">
                            <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-[10px] font-black {{ $item['ready'] ? 'bg-emerald-600 text-white' : 'bg-amber-500 text-white' }}">{{ $item['ready'] ? 'OK' : '!' }}</span>
                            <div>
                                <p class="text-xs font-bold text-slate-950">{{ $item['label'] }}</p>
                                <p class="text-[11px] leading-4 text-slate-500">{{ $item['description'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-ui.card>
        </aside>

        <x-ui.card>
            <form method="POST" action="{{ $exam ? route('management.exams.update', $exam) : route('management.exam-requests.schedule.store', $examRequest) }}" class="space-y-6">
                @csrf
                @if($exam) @method('PUT') @endif

                <section>
                    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                        <div>
                            <p class="text-xs font-black uppercase tracking-widest text-cyan-700">Penguji sidang</p>
                            <h3 class="mt-1 text-xl font-black text-slate-950">Pilih 2 sampai 3 penguji</h3>
                            <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-600">Pembimbing dalam boleh menjadi penguji bila dosen tersebut juga memiliki role Penguji. Urutan pilihan pertama akan disimpan sebagai penguji utama untuk kompatibilitas data lama.</p>
                        </div>
                        <span class="w-fit rounded-full bg-cyan-50 px-3 py-1 text-xs font-black text-cyan-700 ring-1 ring-cyan-200">Minimal 2 penguji</span>
                    </div>

                    <div class="mt-4 grid gap-3 md:grid-cols-2 2xl:grid-cols-3">
                        @foreach($examiners as $examiner)
                            <label class="group flex min-h-24 cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-cyan-300 hover:bg-cyan-50/40">
                                <input type="checkbox" name="examiner_ids[]" value="{{ $examiner->id }}" @checked(in_array($examiner->id, $selectedExaminerIds, true)) class="mt-1 rounded border-slate-300 text-cyan-700 focus:ring-cyan-500">
                                <span>
                                    <span class="block text-sm font-black text-slate-950">{{ lecturer_display_name($examiner) }}</span>
                                    <span class="mt-1 block text-xs text-slate-500">{{ $examiner->nidn_nip ?: 'Nomor dosen belum tersedia' }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    @error('examiner_ids')<p class="mt-2 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                    @error('examiner_ids.*')<p class="mt-2 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                </section>

                <section class="rounded-3xl bg-slate-50 p-4 ring-1 ring-slate-100 md:p-5">
                    <p class="text-xs font-black uppercase tracking-widest text-cyan-700">Waktu dan tempat</p>
                    <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <div>
                            <label class="text-sm font-bold text-slate-800">Tanggal Sidang</label>
                            <input type="date" name="exam_date" value="{{ old('exam_date', $exam?->exam_date?->format('Y-m-d')) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-3 text-sm shadow-sm">
                            @error('exam_date')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="text-sm font-bold text-slate-800">Jam Mulai</label>
                            <input type="time" name="start_time" value="{{ old('start_time', $exam?->start_time ? substr($exam->start_time, 0, 5) : '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-3 text-sm shadow-sm">
                            @error('start_time')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="text-sm font-bold text-slate-800">Jam Selesai</label>
                            <input type="time" name="end_time" value="{{ old('end_time', $exam?->end_time ? substr($exam->end_time, 0, 5) : '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-3 text-sm shadow-sm">
                            @error('end_time')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="text-sm font-bold text-slate-800">Mode Sidang</label>
                            <select name="mode" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-3 text-sm shadow-sm">
                                @foreach(['offline' => 'Offline', 'online' => 'Online', 'hybrid' => 'Hybrid'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('mode', $exam?->mode) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('mode')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="text-sm font-bold text-slate-800">Ruangan</label>
                            <input name="room" value="{{ old('room', $exam?->room) }}" placeholder="Contoh: Ruang Sidang 1" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-3 text-sm shadow-sm">
                            @error('room')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="text-sm font-bold text-slate-800">Link Meeting</label>
                            <input name="meeting_link" value="{{ old('meeting_link', $exam?->meeting_link) }}" placeholder="https://meet.google.com/..." class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-3 text-sm shadow-sm">
                            @error('meeting_link')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div class="mt-4">
                        <label class="text-sm font-bold text-slate-800">Catatan Jadwal</label>
                        <textarea name="note" rows="3" placeholder="Catatan untuk peserta sidang, penguji, atau admin." class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-3 text-sm shadow-sm">{{ old('note', $exam?->note) }}</textarea>
                        @error('note')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                    </div>
                </section>

                <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:items-center sm:justify-end">
                    <a href="{{ route('management.exam-requests.show', $examRequest) }}" class="inline-flex justify-center rounded-xl border border-slate-200 px-5 py-3 text-sm font-bold text-slate-700">Batal</a>
                    <button class="rounded-xl bg-cyan-700 px-6 py-3 text-sm font-black text-white shadow-sm">{{ $exam ? 'Update Jadwal' : 'Simpan Jadwal Sidang' }}</button>
                </div>
            </form>
        </x-ui.card>
    </div>
</div>
@endsection
