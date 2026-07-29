@extends('layouts.app')

@section('title', 'Kuisioner KP')
@section('page_title', 'Kuisioner KP')

@section('content')
<div class="space-y-5">
    <section class="rounded-3xl border border-sky-100 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-widest text-cyan-700">Builder Kuisioner</p>
                <h2 class="mt-1 text-2xl font-black text-slate-950">Default bisa dipakai langsung, tetap bisa direvisi</h2>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Kuisioner disimpan di KP dan tidak menulis ke Core/TU/SAFA. Koordinator dapat mengubah judul, status, dan pertanyaan sesuai kebutuhan periode.</p>
            </div>
            <a href="{{ route('management.questionnaire-results.index') }}" class="inline-flex items-center justify-center rounded-2xl bg-cyan-800 px-5 py-3 text-sm font-black text-white shadow-lg shadow-cyan-900/15">Lihat Hasil</a>
        </div>
    </section>

    <div class="grid gap-5 xl:grid-cols-[420px_1fr]">
        <section class="rounded-3xl border border-sky-100 bg-white p-5 shadow-sm">
            <h3 class="text-lg font-black text-slate-950">Daftar Kuisioner</h3>
            <form method="GET" action="{{ route('management.questionnaires.index') }}" class="mt-4 grid gap-3 sm:grid-cols-[1fr_auto] xl:grid-cols-1">
                <select name="audience" class="rounded-2xl border border-slate-200 px-4 py-3">
                    <option value="">Semua responden</option>
                    @foreach($audiences as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['audience'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <button class="rounded-2xl bg-slate-950 px-5 py-3 text-sm font-black text-white">Filter</button>
            </form>

            <div class="mt-4 space-y-3">
                @foreach($questionnaires as $questionnaire)
                    <a href="{{ route('management.questionnaires.index', ['questionnaire' => $questionnaire->id, 'audience' => $filters['audience'] ?? null]) }}" class="block rounded-2xl border p-4 transition {{ $selected?->id === $questionnaire->id ? 'border-cyan-300 bg-cyan-50/70 shadow-sm' : 'border-slate-200 hover:border-cyan-200' }}">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-black text-slate-950">{{ $questionnaire->title }}</p>
                                <p class="mt-1 text-xs font-bold text-cyan-700">{{ $questionnaire->audienceLabel() }}</p>
                            </div>
                            <span class="rounded-full px-3 py-1 text-xs font-black {{ $questionnaire->statusBadgeClass() }}">{{ $questionnaire->statusLabel() }}</span>
                        </div>
                        <p class="mt-2 text-xs text-slate-500">{{ $questionnaire->questions->count() }} pertanyaan · {{ $questionnaire->responses->count() }} respons</p>
                    </a>
                @endforeach
            </div>

            <form method="POST" action="{{ route('management.questionnaires.store') }}" class="mt-6 space-y-3 rounded-2xl border border-dashed border-cyan-200 bg-cyan-50/40 p-4">
                @csrf
                <p class="text-sm font-black text-slate-900">Tambah Paket Kuisioner</p>
                <input name="title" class="w-full rounded-2xl border border-slate-200 px-4 py-3" placeholder="Judul kuisioner" required>
                <select name="audience" class="w-full rounded-2xl border border-slate-200 px-4 py-3" required>
                    @foreach($audiences as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                <select name="kp_period_id" class="w-full rounded-2xl border border-slate-200 px-4 py-3">
                    <option value="">Umum untuk semua periode</option>
                    @foreach($periods as $period)
                        <option value="{{ $period->id }}">{{ $period->name }}</option>
                    @endforeach
                </select>
                <textarea name="description" class="w-full rounded-2xl border border-slate-200 px-4 py-3" rows="3" placeholder="Deskripsi singkat"></textarea>
                <input type="hidden" name="status" value="aktif">
                <button class="w-full rounded-2xl bg-cyan-700 px-5 py-3 text-sm font-black text-white">Simpan Kuisioner</button>
            </form>
        </section>

        <section class="rounded-3xl border border-sky-100 bg-white p-5 shadow-sm">
            @if($selected)
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <p class="text-xs font-black uppercase tracking-widest text-cyan-700">{{ $selected->audienceLabel() }}</p>
                        <h3 class="mt-1 text-2xl font-black text-slate-950">{{ $selected->title }}</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ $selected->description ?: 'Belum ada deskripsi.' }}</p>
                    </div>
                    <span class="w-fit rounded-full px-3 py-1 text-xs font-black {{ $selected->statusBadgeClass() }}">{{ $selected->statusLabel() }}</span>
                </div>

                <form method="POST" action="{{ route('management.questionnaires.update', $selected) }}" class="mt-5 grid gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 lg:grid-cols-2">
                    @csrf
                    @method('PATCH')
                    <input name="title" value="{{ $selected->title }}" class="rounded-2xl border border-slate-200 px-4 py-3" required>
                    <select name="status" class="rounded-2xl border border-slate-200 px-4 py-3">
                        <option value="aktif" @selected($selected->status === 'aktif')>Aktif</option>
                        <option value="nonaktif" @selected($selected->status === 'nonaktif')>Nonaktif</option>
                    </select>
                    <select name="audience" class="rounded-2xl border border-slate-200 px-4 py-3">
                        @foreach($audiences as $value => $label)
                            <option value="{{ $value }}" @selected($selected->audience === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <select name="kp_period_id" class="rounded-2xl border border-slate-200 px-4 py-3">
                        <option value="">Umum untuk semua periode</option>
                        @foreach($periods as $period)
                            <option value="{{ $period->id }}" @selected($selected->kp_period_id === $period->id)>{{ $period->name }}</option>
                        @endforeach
                    </select>
                    <textarea name="description" class="rounded-2xl border border-slate-200 px-4 py-3 lg:col-span-2" rows="3">{{ $selected->description }}</textarea>
                    <button class="rounded-2xl bg-slate-950 px-5 py-3 text-sm font-black text-white lg:col-span-2">Update Paket</button>
                </form>

                <form method="POST" action="{{ route('management.questionnaires.questions.store', $selected) }}" class="mt-5 grid gap-3 rounded-2xl border border-cyan-200 bg-cyan-50/40 p-4 lg:grid-cols-2">
                    @csrf
                    <input name="section" class="rounded-2xl border border-slate-200 px-4 py-3" placeholder="Bagian/section">
                    <input name="sort_order" type="number" min="0" class="rounded-2xl border border-slate-200 px-4 py-3" placeholder="Urutan">
                    <textarea name="question_text" class="rounded-2xl border border-slate-200 px-4 py-3 lg:col-span-2" rows="3" placeholder="Tulis pertanyaan" required></textarea>
                    <select name="answer_type" class="rounded-2xl border border-slate-200 px-4 py-3">
                        @foreach($types as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <select name="status" class="rounded-2xl border border-slate-200 px-4 py-3">
                        <option value="aktif">Aktif</option>
                        <option value="nonaktif">Nonaktif</option>
                    </select>
                    <textarea name="options_text" class="rounded-2xl border border-slate-200 px-4 py-3 lg:col-span-2" rows="2" placeholder="Opsi pilihan, satu baris satu opsi bila tipe Pilihan"></textarea>
                    <label class="flex items-center gap-2 text-sm font-bold text-slate-700"><input type="checkbox" name="is_required" value="1" checked> Wajib diisi</label>
                    <button class="rounded-2xl bg-cyan-700 px-5 py-3 text-sm font-black text-white">Tambah Pertanyaan</button>
                </form>

                <div class="mt-5 space-y-4">
                    @foreach($selected->questions as $question)
                        <form method="POST" action="{{ route('management.questionnaire-questions.update', $question) }}" class="rounded-2xl border border-slate-200 p-4">
                            @csrf
                            @method('PATCH')
                            <div class="grid gap-3 lg:grid-cols-[1fr_150px_150px]">
                                <input name="section" value="{{ $question->section }}" class="rounded-2xl border border-slate-200 px-4 py-3" placeholder="Section">
                                <input name="sort_order" type="number" value="{{ $question->sort_order }}" class="rounded-2xl border border-slate-200 px-4 py-3">
                                <select name="status" class="rounded-2xl border border-slate-200 px-4 py-3">
                                    <option value="aktif" @selected($question->status === 'aktif')>Aktif</option>
                                    <option value="nonaktif" @selected($question->status === 'nonaktif')>Nonaktif</option>
                                </select>
                            </div>
                            <textarea name="question_text" class="mt-3 w-full rounded-2xl border border-slate-200 px-4 py-3" rows="2" required>{{ $question->question_text }}</textarea>
                            <div class="mt-3 grid gap-3 lg:grid-cols-[220px_1fr_160px]">
                                <select name="answer_type" class="rounded-2xl border border-slate-200 px-4 py-3">
                                    @foreach($types as $value => $label)
                                        <option value="{{ $value }}" @selected($question->answer_type === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <textarea name="options_text" class="rounded-2xl border border-slate-200 px-4 py-3" rows="1" placeholder="Opsi pilihan">{{ implode("\n", $question->optionList()) }}</textarea>
                                <label class="flex items-center gap-2 rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold"><input type="checkbox" name="is_required" value="1" @checked($question->is_required)> Wajib</label>
                            </div>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <button class="rounded-2xl border border-cyan-200 px-4 py-2 text-sm font-black text-cyan-700">Update</button>
                            </div>
                        </form>
                    @endforeach
                </div>
            @else
                <div class="rounded-2xl border border-dashed border-sky-200 p-8 text-center text-slate-500">Belum ada kuisioner.</div>
            @endif
        </section>
    </div>
</div>
@endsection
