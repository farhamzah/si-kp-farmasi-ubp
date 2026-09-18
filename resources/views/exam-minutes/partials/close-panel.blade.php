@php
    $isChair = (int) $exam->chair_lecturer_id === (int) (auth()->user()->lecturer?->id ?: 0);
    $closeRoute = session('active_role') === 'penguji' ? route('examiner.exams.close', $exam) : route('internal-supervisor.exams.close', $exam);
@endphp
<x-ui.card>
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div><p class="text-xs font-black uppercase tracking-widest text-cyan-700">Berita Acara</p><h3 class="mt-1 text-lg font-black text-slate-950">{{ $exam->minutes?->statusLabel() ?? 'Belum dibuat' }}</h3></div>
        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700">{{ $exam->minutes_number ?: 'Nomor belum tersedia' }}</span>
    </div>
    <p class="mt-3 text-sm text-slate-600">Ketua Sidang: <strong>{{ $exam->chair ? lecturer_display_name($exam->chair) : 'Belum ditentukan' }}</strong></p>

    @if($exam->minutes)
        <div class="mt-4 flex flex-wrap gap-2">
            <a href="{{ route('exam-minutes.preview', $exam->minutes) }}" class="rounded-xl bg-cyan-700 px-4 py-2 text-sm font-bold text-white">Preview Berita Acara</a>
            <a href="{{ route('exam-minutes.pdf', $exam->minutes) }}" class="rounded-xl border border-cyan-200 px-4 py-2 text-sm font-bold text-cyan-700">Download PDF</a>
        </div>
    @elseif($isChair && ($requireChairScoreSubmitted ?? false) && ! ($chairScoreSubmitted ?? false))
        <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
            <p class="font-black">Submit nilai terlebih dahulu</p>
            <p class="mt-1 leading-6 text-amber-800">Setelah seluruh nilai wajib Ketua Sidang disubmit, formulir hasil sidang dan berita acara akan terbuka di bagian ini.</p>
        </div>
    @elseif($isChair)
        <form method="POST" action="{{ $closeRoute }}" class="mt-5 space-y-4" onsubmit="return confirm('Tutup sidang dan buat berita acara?')">
            @csrf
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label class="text-sm font-bold">Mulai Aktual</label><input type="time" name="actual_start_time" value="{{ old('actual_start_time', substr((string) $exam->start_time, 0, 5)) }}" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2"></div>
                <div><label class="text-sm font-bold">Selesai Aktual</label><input type="time" name="actual_end_time" value="{{ old('actual_end_time', substr((string) $exam->end_time, 0, 5)) }}" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2"></div>
            </div>
            <div><label class="text-sm font-bold">Keputusan Sidang</label><select name="result" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2"><option value="lulus">Lulus</option><option value="lulus_revisi">Lulus dengan Revisi</option><option value="belum_lulus">Belum Lulus</option></select></div>
            <div><label class="text-sm font-bold">Batas Revisi</label><input type="date" name="revision_deadline" value="{{ old('revision_deadline') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2"><p class="mt-1 text-xs text-slate-500">Wajib diisi untuk keputusan lulus dengan revisi.</p></div>
            <fieldset><legend class="text-sm font-bold">Kehadiran</legend><div class="mt-2 flex flex-wrap gap-3">@foreach(['mahasiswa'=>'Mahasiswa','ketua_sidang'=>'Ketua Sidang','tim_penguji'=>'Tim Penguji'] as $value=>$label)<label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="attendance[]" value="{{ $value }}" checked class="rounded border-slate-300 text-cyan-700">{{ $label }}</label>@endforeach</div></fieldset>
            <div><label class="text-sm font-bold">Catatan Sidang</label><textarea name="notes" rows="4" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" placeholder="Catatan keputusan, revisi, atau kejadian selama sidang.">{{ old('notes') }}</textarea></div>
            @if($errors->any())<div class="rounded-xl bg-red-50 p-3 text-sm text-red-700">{{ $errors->first() }}</div>@endif
            <button class="w-full rounded-xl bg-emerald-600 px-4 py-3 text-sm font-black text-white">Tutup Sidang dan Buat Berita Acara</button>
        </form>
    @else
        <p class="mt-4 rounded-xl bg-slate-50 p-3 text-sm text-slate-600">Penutupan sidang hanya dapat dilakukan oleh Ketua Sidang yang tercantum pada jadwal.</p>
    @endif
</x-ui.card>
