<div class="space-y-6">
    <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-100"><h2 class="text-xl font-black text-slate-950">{{ $assignment->student->user->name }}</h2><p class="text-sm text-slate-500">{{ $assignment->student->nim }} · {{ $assignment->place->name }}</p>@if($assignment->finalScore?->isLocked())<div class="mt-4 rounded-2xl bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800">Nilai sudah dikunci/dipublish dan tidak dapat diubah.</div>@endif</section>
    <form method="POST" action="{{ $saveRoute }}" class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-100">@csrf
        <div class="space-y-4">
            @forelse($components as $index => $component)
                @php($score = $assignment->scores->firstWhere('kp_assessment_component_id', $component->id))
                <div class="rounded-2xl border border-slate-100 p-4">
                    <input type="hidden" name="scores[{{ $index }}][component_id]" value="{{ $component->id }}">
                    <div class="grid gap-3 md:grid-cols-[1fr_140px] md:items-start">
                        <div><p class="font-black text-slate-950">{{ $component->component_name }} @if($component->is_required)<span class="text-rose-600">*</span>@endif</p><p class="text-xs text-slate-500">Bobot {{ $component->weight }}% · Max {{ $component->max_score }}</p><textarea name="scores[{{ $index }}][note]" rows="2" class="mt-3 w-full rounded-2xl border-slate-200 text-sm" placeholder="Catatan opsional">{{ old("scores.$index.note", $score?->note) }}</textarea></div>
                        <div><input type="number" step="0.01" min="0" max="{{ $component->max_score }}" name="scores[{{ $index }}][score]" value="{{ old("scores.$index.score", $score?->score) }}" class="w-full rounded-2xl border-slate-200 text-sm" @disabled($assignment->finalScore?->isLocked())><p class="mt-2 text-xs font-bold text-slate-500">{{ $score?->statusLabel() ?? 'Belum diisi' }}</p></div>
                    </div>
                </div>
            @empty
                <div class="rounded-2xl bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">Komponen penilaian belum diatur untuk periode ini.</div>
            @endforelse
        </div>
        @if($errors->any())<div class="mt-4 rounded-2xl bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">{{ $errors->first() }}</div>@endif
        <div class="mt-6 flex justify-end gap-2"><button class="rounded-2xl border border-cyan-200 px-4 py-2 text-sm font-bold text-cyan-700" @disabled($assignment->finalScore?->isLocked())>Simpan Draft</button></div>
    </form>
    <form method="POST" action="{{ $submitRoute }}" onsubmit="return confirm('Submit nilai? Nilai tidak dapat diubah setelah nilai akhir dikunci.')" class="flex justify-end">@csrf<button class="rounded-2xl bg-cyan-700 px-5 py-3 text-sm font-bold text-white" @disabled($assignment->finalScore?->isLocked())>Submit Nilai</button></form>
</div>
