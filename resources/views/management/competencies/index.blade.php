@extends('layouts.app')
@section('title','Panduan Kompetensi KP - '.config('app.name'))
@section('page_title','Panduan Kompetensi KP')
@section('content')
<div class="space-y-5">
    @if($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>
    @endif

    <section class="grid gap-5 xl:grid-cols-[0.8fr_1.2fr]">
        <form method="POST" action="{{ route('management.competencies.store') }}" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            @csrf
            <h2 class="text-lg font-black text-slate-950">Tambah Kompetensi</h2>
            <p class="mt-1 text-sm text-slate-500">Kompetensi dapat dibuat sebanyak kebutuhan periode KP.</p>
            <div class="mt-4 grid gap-3">
                <label class="text-sm font-semibold text-slate-700">Periode
                    <select name="kp_period_id" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Umum untuk semua periode</option>
                        @foreach($periods as $period)
                            <option value="{{ $period->id }}" @selected(($filters['period'] ?? '') == $period->id)>{{ $period->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="text-sm font-semibold text-slate-700">Tipe Tempat KP
                    <select name="place_type" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Semua tipe tempat</option>
                        @foreach($placeTypes as $type)
                            <option value="{{ $type }}" @selected(old('place_type') === $type)>{{ (new \App\Models\KpPlace(['type' => $type]))->typeLabel() }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="text-sm font-semibold text-slate-700">Judul Kompetensi
                    <input name="title" value="{{ old('title') }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Contoh: Mampu melakukan pelayanan resep">
                </label>
                <label class="text-sm font-semibold text-slate-700">Panduan Pembimbing Luar
                    <textarea name="description" rows="4" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Tuliskan indikator kompetensi yang harus dicapai mahasiswa.">{{ old('description') }}</textarea>
                </label>
                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="text-sm font-semibold text-slate-700">Urutan
                        <input name="sort_order" type="number" min="0" value="{{ old('sort_order', 0) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    </label>
                    <label class="text-sm font-semibold text-slate-700">Status
                        <select name="status" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            <option value="aktif">Aktif</option>
                            <option value="nonaktif">Nonaktif</option>
                        </select>
                    </label>
                </div>
                <button class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-bold text-white">Simpan Kompetensi</button>
            </div>
        </form>

        <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 class="text-lg font-black text-slate-950">Daftar Kompetensi</h2>
                    <p class="mt-1 text-sm text-slate-500">Koordinator dapat mengubah teks, urutan, dan status kompetensi.</p>
                </div>
                <form method="GET" class="flex gap-2">
                    <select name="period" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Semua periode</option>
                        @foreach($periods as $period)
                            <option value="{{ $period->id }}" @selected(($filters['period'] ?? '') == $period->id)>{{ $period->name }}</option>
                        @endforeach
                    </select>
                    <select name="place_type" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Semua tipe</option>
                        @foreach($placeTypes as $type)
                            <option value="{{ $type }}" @selected(($filters['place_type'] ?? '') === $type)>{{ (new \App\Models\KpPlace(['type' => $type]))->typeLabel() }}</option>
                        @endforeach
                    </select>
                    <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-bold text-white">Filter</button>
                </form>
            </div>

            <div class="mt-4 space-y-3">
                @forelse($competencies as $competency)
                    <form method="POST" action="{{ route('management.competencies.update', $competency) }}" class="rounded-xl border border-slate-200 p-4">
                        @csrf
                        @method('PUT')
                        <div class="grid gap-3 lg:grid-cols-[1fr_150px_110px_130px_auto]">
                            <div>
                                <input name="title" value="{{ old('title', $competency->title) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold">
                                <textarea name="description" rows="2" class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">{{ old('description', $competency->description) }}</textarea>
                                <input type="hidden" name="kp_period_id" value="{{ $competency->kp_period_id }}">
                                <p class="mt-1 text-xs text-slate-500">{{ $competency->period?->name ?? 'Umum semua periode' }} · {{ $competency->placeTypeLabel() }} · {{ $competency->achievements_count ?? $competency->achievements->count() }} checklist</p>
                            </div>
                            <select name="place_type" class="h-10 rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                <option value="">Semua tipe</option>
                                @foreach($placeTypes as $type)
                                    <option value="{{ $type }}" @selected($competency->place_type === $type)>{{ (new \App\Models\KpPlace(['type' => $type]))->typeLabel() }}</option>
                                @endforeach
                            </select>
                            <input name="sort_order" type="number" min="0" value="{{ $competency->sort_order }}" class="h-10 rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            <select name="status" class="h-10 rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                <option value="aktif" @selected($competency->status === 'aktif')>Aktif</option>
                                <option value="nonaktif" @selected($competency->status === 'nonaktif')>Nonaktif</option>
                            </select>
                            <button class="h-10 rounded-lg border border-teal-200 px-3 py-2 text-xs font-bold text-teal-700">Update</button>
                        </div>
                    </form>
                @empty
                    <p class="rounded-lg bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">Belum ada kompetensi KP.</p>
                @endforelse
            </div>
        </section>
    </section>

    <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="border-b border-slate-100 p-5">
            <h2 class="text-lg font-black text-slate-950">Monitoring Capaian Mahasiswa</h2>
            <p class="mt-1 text-sm text-slate-500">Admin dan koordinator melihat semua mahasiswa, pembimbing luar yang mencentang capaian.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr><th class="px-4 py-3">Mahasiswa</th><th class="px-4 py-3">Tempat</th><th class="px-4 py-3">Pembimbing</th><th class="px-4 py-3">Capaian</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($assignments as $assignment)
                        @php($applicableCompetencies = $competencies->filter(fn ($competency) => $competency->status === 'aktif' && in_array($competency->kp_period_id, [null, $assignment->kp_period_id], true) && (blank($competency->place_type) || $competency->place_type === $assignment->place?->type)))
                        @php($total = $applicableCompetencies->count())
                        @php($done = $assignment->competencyAchievements->whereIn('kp_competency_id', $applicableCompetencies->pluck('id'))->count())
                        <tr>
                            <td class="px-4 py-4"><div class="font-semibold text-slate-950">{{ $assignment->student->user->name }}</div><div class="text-xs text-slate-500">{{ $assignment->student->nim ?: '-' }}</div></td>
                            <td class="px-4 py-4"><div>{{ $assignment->place->name }}</div><div class="text-xs text-slate-500">{{ $assignment->place->typeLabel() }}</div></td>
                            <td class="px-4 py-4"><div>{{ $assignment->internalSupervisor ? lecturer_display_name($assignment->internalSupervisor) : '-' }}</div><div class="text-xs text-slate-500">{{ $assignment->fieldSupervisor?->user?->name ?? '-' }}</div></td>
                            <td class="px-4 py-4 font-bold text-cyan-700">{{ $done }} / {{ $total }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-10 text-center text-slate-500">Belum ada penempatan KP.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-200 px-4 py-3">{{ $assignments->links() }}</div>
    </section>
</div>
@endsection
