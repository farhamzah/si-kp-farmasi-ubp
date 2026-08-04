@extends('layouts.app')
@section('title','Detail Nilai - '.config('app.name'))
@section('page_title','Detail Nilai')
@section('content')
@php
    $componentWeightTotals = $assignment->period->assessmentComponents
        ->where('status', 'aktif')
        ->groupBy('assessor_type')
        ->map(fn ($items) => max(0.01, (float) $items->sum('weight')));
@endphp
<div class="space-y-6">
    <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-widest text-cyan-700">Rekap nilai KP</p>
                <h2 class="mt-2 text-2xl font-black text-slate-950">{{ $assignment->student->user->name }}</h2>
                <p class="text-sm text-slate-500">{{ $assignment->student->nim }} - {{ $assignment->place->name }}</p>
            </div>
            <div class="rounded-3xl bg-slate-950 px-6 py-4 text-right text-white">
                <p class="text-xs font-black uppercase tracking-widest text-cyan-200">Nilai akhir</p>
                <p class="mt-1 text-4xl font-black">{{ $assignment->finalScore?->final_score ?? $breakdown['final_score'] }}</p>
                <p class="text-sm font-bold text-slate-300">Grade {{ $assignment->finalScore?->final_grade ?? '-' }}</p>
            </div>
        </div>
    </section>

    <section class="grid gap-3 md:grid-cols-4">
        @foreach($breakdown['sections'] as $section)
            <div class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
                <p class="text-xs font-black uppercase tracking-widest text-slate-500">{{ $section['label'] }}</p>
                <p class="mt-3 text-3xl font-black text-slate-950">{{ number_format($section['score'], 2) }}</p>
                <p class="mt-1 text-sm text-slate-500">Bobot akhir {{ $section['weight'] }}% - kontribusi {{ number_format($section['contribution'], 2) }}</p>
                @if(($section['meta']['source'] ?? null) === 'logbook')
                    <p class="mt-2 text-xs font-semibold text-slate-500">{{ $section['meta']['approved_logbook_days'] }} logbook disetujui / {{ $section['meta']['workdays'] }} hari kerja</p>
                @elseif(($section['meta']['source'] ?? null) === 'override')
                    <p class="mt-2 text-xs font-semibold text-amber-700">Override koordinator</p>
                @endif
            </div>
        @endforeach
    </section>

    <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
        <div class="grid gap-6 lg:grid-cols-[1fr_360px]">
            <div>
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-xs font-black uppercase tracking-widest text-cyan-700">Akses nilai mahasiswa</p>
                        <h3 class="mt-2 text-xl font-black text-slate-950">{{ ($scoreVisibility['visible'] ?? false) ? 'Nilai dapat dilihat mahasiswa' : 'Nilai belum dapat dilihat mahasiswa' }}</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ $scoreVisibility['message'] ?? 'Status akses nilai belum tersedia.' }}</p>
                    </div>
                    <span class="inline-flex w-fit rounded-full {{ ($scoreVisibility['visible'] ?? false) ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }} px-4 py-2 text-xs font-black">
                        {{ ($scoreVisibility['visible'] ?? false) ? 'Terbuka' : 'Tertahan' }}
                    </span>
                </div>

                <div class="mt-5 grid gap-3 md:grid-cols-2">
                    @foreach(($scoreVisibility['requirements'] ?? []) as $requirement)
                        <div class="rounded-2xl border {{ $requirement['ready'] ? 'border-emerald-200 bg-emerald-50/50' : 'border-amber-200 bg-amber-50/50' }} p-4">
                            <p class="font-black text-slate-950">{{ $requirement['label'] }}</p>
                            <p class="mt-1 text-sm leading-6 text-slate-600">{{ $requirement['description'] }}</p>
                            <span class="mt-3 inline-flex rounded-full {{ $requirement['ready'] ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }} px-3 py-1 text-xs font-black">
                                {{ $requirement['ready'] ? 'OK' : 'Belum lengkap' }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>

            <form method="POST" action="{{ route('management.scores.visibility-override.update', $assignment) }}" class="rounded-2xl border border-slate-100 bg-slate-50 p-5">
                @csrf
                @method('PATCH')
                @php($override = $scoreVisibility['override'] ?? null)
                <p class="text-xs font-black uppercase tracking-widest text-slate-500">Override mahasiswa</p>
                <p class="mt-2 text-sm leading-6 text-slate-600">Default mengikuti pengaturan periode: <span class="font-bold text-slate-950">{{ $assignment->period?->scoreVisibilityLabel() }}</span>.</p>
                <select name="visibility_override" class="mt-4 w-full rounded-2xl border-slate-200 text-sm">
                    <option value="inherit" @selected(! $override)>Ikuti pengaturan periode</option>
                    <option value="allow" @selected($override?->can_view === true)>Boleh lihat khusus</option>
                    <option value="deny" @selected($override?->can_view === false)>Tahan khusus</option>
                </select>
                <textarea name="visibility_note" rows="3" class="mt-3 w-full rounded-2xl border-slate-200 text-sm" placeholder="Catatan opsional untuk audit">{{ old('visibility_note', $override?->note) }}</textarea>
                <button class="mt-3 w-full rounded-2xl bg-slate-950 px-5 py-3 text-sm font-black text-white">Simpan Akses Nilai</button>
            </form>
        </div>
    </section>

    <form method="POST" action="{{ route('management.scores.override', $assignment) }}" class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
        @csrf
        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
            <div>
                <h3 class="text-xl font-black text-slate-950">Koreksi Nilai Koordinator</h3>
                <p class="text-sm text-slate-500">Nilai dapat dikoreksi sebelum finalisasi. Perubahan akan dihitung ulang otomatis.</p>
            </div>
            <button class="rounded-2xl bg-cyan-700 px-5 py-3 text-sm font-bold text-white" @disabled($assignment->finalScore?->isLocked())>Simpan Koreksi</button>
        </div>

        <div class="mt-5 rounded-2xl border border-slate-100 p-4">
            <div class="grid gap-3 md:grid-cols-[220px_180px_1fr] md:items-center">
                <div>
                    <p class="font-black text-slate-950">Kehadiran / Logbook</p>
                    <p class="text-xs text-slate-500">Bobot akhir 15%</p>
                </div>
                <input type="number" name="attendance_score" min="0" max="100" step="0.01" value="{{ old('attendance_score', $assignment->finalScore?->attendance_score_override) }}" class="rounded-2xl border-slate-200 text-sm font-bold" placeholder="{{ number_format($breakdown['sections']['kehadiran']['score'], 2) }}" @disabled($assignment->finalScore?->isLocked())>
                <input name="attendance_note" value="{{ old('attendance_note', $assignment->finalScore?->attendance_note) }}" class="rounded-2xl border-slate-200 text-sm" placeholder="Catatan override kehadiran bila ada" @disabled($assignment->finalScore?->isLocked())>
            </div>
        </div>

        <div class="mt-5 overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-black uppercase tracking-widest text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Penilai</th>
                        <th class="px-4 py-3">Komponen</th>
                        <th class="px-4 py-3">Bobot Internal</th>
                        <th class="px-4 py-3">Nilai</th>
                        <th class="px-4 py-3">Catatan</th>
                        <th class="px-4 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($assignment->period->assessmentComponents->where('status','aktif')->sortBy([['assessor_type','asc'], ['sort_order','asc']])->values() as $index => $component)
                        @php
                            $score = $assignment->scores->where('kp_assessment_component_id', $component->id)->first();
                            $total = $componentWeightTotals[$component->assessor_type] ?? 100;
                            $normalizedWeight = ((float) $component->weight / $total) * 100;
                        @endphp
                        <tr>
                            <td class="px-4 py-4 align-top font-semibold text-slate-700">{{ $component->assessorTypeLabel() }}</td>
                            <td class="px-4 py-4 align-top">
                                <input type="hidden" name="scores[{{ $index }}][component_id]" value="{{ $component->id }}">
                                <p class="font-black text-slate-950">{{ $component->component_name }}</p>
                                <p class="text-xs text-slate-500">{{ $component->description }}</p>
                            </td>
                            <td class="px-4 py-4 align-top">
                                <p class="font-bold">{{ number_format((float) $component->weight, 2) }}%</p>
                                <p class="text-xs text-slate-500">Normalisasi {{ number_format($normalizedWeight, 2) }}%</p>
                            </td>
                            <td class="px-4 py-4 align-top">
                                <input type="number" name="scores[{{ $index }}][score]" min="0" max="100" step="0.01" value="{{ old("scores.$index.score", $score?->score) }}" class="w-28 rounded-2xl border-slate-200 text-sm font-bold" @disabled($assignment->finalScore?->isLocked())>
                            </td>
                            <td class="px-4 py-4 align-top">
                                <input name="scores[{{ $index }}][note]" value="{{ old("scores.$index.note", $score?->note) }}" class="w-72 rounded-2xl border-slate-200 text-sm" placeholder="Catatan koreksi" @disabled($assignment->finalScore?->isLocked())>
                            </td>
                            <td class="px-4 py-4 align-top">
                                <span class="rounded-full {{ $score?->statusBadgeClass() ?? 'bg-slate-100 text-slate-700' }} px-3 py-1 text-xs font-bold">{{ $score?->statusLabel() ?? 'Belum diisi' }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($errors->any())<div class="mt-4 rounded-2xl bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">{{ $errors->first() }}</div>@endif
    </form>

    <section class="flex flex-wrap gap-2 rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
        <form method="POST" action="{{ route('management.scores.calculate',$assignment) }}">@csrf<button class="rounded-2xl border border-cyan-200 px-4 py-2 text-sm font-bold text-cyan-700">Hitung Ulang</button></form>
        <form method="POST" action="{{ route('management.scores.finalize',$assignment) }}" onsubmit="return confirm('Finalisasi dan kunci nilai?')">@csrf<button class="rounded-2xl bg-cyan-700 px-4 py-2 text-sm font-bold text-white">Finalisasi</button></form>
        @if($assignment->finalScore)<form method="POST" action="{{ route('management.final-scores.publish',$assignment->finalScore) }}" onsubmit="return confirm('Publish nilai ke mahasiswa?')">@csrf<button class="rounded-2xl bg-emerald-600 px-4 py-2 text-sm font-bold text-white">Publish</button></form><form method="POST" action="{{ route('management.final-scores.unlock',$assignment->finalScore) }}" onsubmit="return confirm('Buka kunci nilai?')">@csrf<input type="hidden" name="reason" value="Dibuka ulang oleh Koordinator/Admin"><button class="rounded-2xl border border-amber-200 px-4 py-2 text-sm font-bold text-amber-700">Unlock</button></form>@endif
    </section>
</div>
@endsection
