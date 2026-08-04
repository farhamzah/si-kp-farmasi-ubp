@extends('layouts.app')
@section('title','Monitoring Nilai - '.config('app.name'))
@section('page_title','Monitoring Nilai')
@section('content')
<div class="space-y-6">
    <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-widest text-cyan-700">Kontrol nilai mahasiswa</p>
                <h2 class="mt-2 text-2xl font-black text-slate-950">Monitoring Nilai KP</h2>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Koordinator dapat menghitung, publish, serta membuka atau menutup akses nilai mahasiswa per periode. Akses tetap memeriksa syarat laporan final dan kuisioner mahasiswa.</p>
            </div>

            @if($selectedPeriod)
                <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4 lg:min-w-80">
                    <p class="text-xs font-black uppercase tracking-widest text-slate-500">Periode dipantau</p>
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <p class="font-black text-slate-950">{{ $selectedPeriod->name }}</p>
                        <span class="rounded-full {{ $selectedPeriod->scoreVisibilityBadgeClass() }} px-3 py-1 text-xs font-black">{{ $selectedPeriod->scoreVisibilityLabel() }}</span>
                    </div>
                    <p class="mt-2 text-xs leading-5 text-slate-500">Jika dibuka, mahasiswa tetap harus punya nilai published, laporan final disetujui, dan kuisioner sudah diisi.</p>
                </div>
            @endif
        </div>
    </section>

    @if($selectedPeriod)
        <section class="grid gap-3 rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-100 lg:grid-cols-[1fr_auto_auto] lg:items-center">
            <div>
                <h3 class="text-lg font-black text-slate-950">Akses Nilai Periode</h3>
                <p class="mt-1 text-sm text-slate-600">Atur apakah mahasiswa pada periode ini boleh membuka nilai akhir setelah semua syarat terpenuhi.</p>
            </div>
            <form method="POST" action="{{ route('management.scores.period-visibility.update', $selectedPeriod) }}">
                @csrf
                @method('PATCH')
                <input type="hidden" name="score_visible_to_students" value="1">
                <button class="w-full rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-black text-white shadow-sm shadow-emerald-100 lg:w-auto">Buka Akses</button>
            </form>
            <form method="POST" action="{{ route('management.scores.period-visibility.update', $selectedPeriod) }}">
                @csrf
                @method('PATCH')
                <input type="hidden" name="score_visible_to_students" value="0">
                <button class="w-full rounded-2xl border border-slate-200 px-5 py-3 text-sm font-black text-slate-700 lg:w-auto">Tutup Akses</button>
            </form>
        </section>
    @endif

    <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
        <form class="grid gap-3 md:grid-cols-[1fr_240px_auto]">
            <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Cari nama/NIM" class="rounded-2xl border-slate-200 text-sm">
            <select name="period" class="rounded-2xl border-slate-200 text-sm">
                <option value="">Semua periode</option>
                @foreach($periods as $period)
                    <option value="{{ $period->id }}" @selected(($filters['period'] ?? '') == $period->id)>{{ $period->name }}</option>
                @endforeach
            </select>
            <button class="rounded-2xl bg-slate-950 px-5 py-3 text-sm font-black text-white">Filter</button>
        </form>
    </section>

    <section class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-100">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-black uppercase tracking-widest text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Mahasiswa</th>
                        <th class="px-5 py-3">Tempat</th>
                        <th class="px-5 py-3">Kelengkapan</th>
                        <th class="px-5 py-3">Nilai Akhir</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($assignments as $assignment)
                        <tr>
                            <td class="px-5 py-4">
                                <p class="font-bold text-slate-950">{{ $assignment->student->user->name }}</p>
                                <p class="text-xs text-slate-500">{{ $assignment->student->nim }}</p>
                            </td>
                            <td class="px-5 py-4">{{ $assignment->place->name }}</td>
                            <td class="px-5 py-4">{{ $assignment->scoresCompletionPercentage() }}%</td>
                            <td class="px-5 py-4 font-bold text-slate-950">{{ $assignment->finalScore?->final_score ?? '-' }}</td>
                            <td class="px-5 py-4">
                                @if($assignment->finalScore)
                                    <span class="rounded-full {{ $assignment->finalScore->statusBadgeClass() }} px-3 py-1 text-xs font-bold">{{ $assignment->finalScore->statusLabel() }}</span>
                                @else
                                    <span class="text-slate-400">Belum dihitung</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <a class="inline-flex rounded-xl border border-cyan-200 px-3 py-2 text-xs font-black text-cyan-700" href="{{ route('management.scores.show',$assignment) }}">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-10 text-center text-slate-500">Belum ada penempatan KP.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-5">{{ $assignments->links() }}</div>
    </section>
</div>
@endsection
