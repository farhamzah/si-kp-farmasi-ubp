@extends('layouts.app')
@section('title','Penilaian Sidang - '.config('app.name'))
@section('page_title','Penilaian Sidang')
@section('content')
<div class="space-y-4">
    <nav class="grid gap-2 rounded-lg bg-white p-2 shadow-sm ring-1 ring-slate-100 sm:grid-cols-3" aria-label="Tampilan penilaian sidang">
        @foreach(['queue' => 'Perlu Dinilai', 'minutes' => 'Berita Acara', 'completed' => 'Riwayat'] as $key => $label)
            <a href="{{ route('examiner.assessments.index', ['tab' => $key]) }}" @if($tab === $key) aria-current="page" @endif class="flex items-center justify-between gap-3 rounded-lg px-4 py-3 text-sm font-bold {{ $tab === $key ? 'bg-cyan-700 text-white' : 'text-slate-600 hover:bg-slate-50' }}">
                <span>{{ $label }}</span><span class="rounded-full px-2 py-0.5 text-xs {{ $tab === $key ? 'bg-white/20' : 'bg-slate-100' }}">{{ $counts[$key] }}</span>
            </a>
        @endforeach
    </nav>
    <section class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-slate-100">
        <div class="border-b border-slate-100 p-5"><h2 class="text-lg font-black text-slate-950">{{ ['queue' => 'Perlu Dinilai', 'minutes' => 'Berita Acara Ketua Sidang', 'completed' => 'Riwayat Penilaian'][$tab] }}</h2></div>
        <div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-100 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-bold uppercase text-slate-500"><tr><th class="px-5 py-3">Mahasiswa</th><th class="px-5 py-3">Tempat KP</th><th class="px-5 py-3">Tugas</th><th class="px-5 py-3 text-right">Aksi</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($exams as $exam)
                    @php
                        $isChair = (int) $exam->chair_lecturer_id === (int) (auth()->user()->lecturer?->id ?: 0);
                        $submitted = $exam->assignment->areRequiredScoresSubmittedBy(auth()->id(), 'penguji');
                    @endphp
                    <tr>
                        <td class="px-5 py-4"><p class="font-bold text-slate-950">{{ $exam->assignment->student->user->name }}</p><p class="text-xs text-slate-500">{{ $exam->assignment->student->nim }} · {{ $exam->scheduleLabel() }}</p></td>
                        <td class="px-5 py-4">{{ $exam->assignment->place->name }}</td>
                        <td class="px-5 py-4"><span class="font-bold text-slate-700">{{ $isChair ? 'Ketua Sidang' : 'Penguji' }}</span>
                            @if($tab === 'minutes')<p class="mt-1 text-xs text-amber-700">{{ $exam->minutes?->statusLabel() ?? 'Berita acara belum diisi' }}</p>
                            @elseif($tab === 'completed')<p class="mt-1 text-xs text-emerald-700">{{ $submitted ? 'Sudah dinilai' : 'Berita acara terbit' }}</p>@endif
                        </td>
                        <td class="px-5 py-4 text-right"><a href="{{ route('examiner.assessments.show', $exam) }}{{ $tab === 'minutes' ? '#berita-acara' : '' }}" class="font-bold text-cyan-700">{{ $tab === 'queue' ? 'Input Nilai' : ($tab === 'minutes' ? 'Buka Berita Acara' : ($submitted ? 'Lihat Nilai' : 'Lihat Berita Acara')) }}</a></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-5 py-10 text-center text-slate-500">{{ ['queue' => 'Tidak ada nilai sidang yang menunggu Anda.', 'minutes' => 'Tidak ada berita acara yang perlu ditindaklanjuti.', 'completed' => 'Belum ada riwayat penilaian.'][$tab] }}</td></tr>
                @endforelse
            </tbody>
        </table></div>
        @if($exams->hasPages())<div class="p-5">{{ $exams->links() }}</div>@endif
    </section>
</div>
@endsection
