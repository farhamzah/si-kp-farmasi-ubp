@extends('layouts.app')

@section('title', 'Hasil Kuisioner KP')
@section('page_title', 'Hasil Kuisioner KP')

@section('content')
<div class="space-y-5">
    <form method="GET" action="{{ route('management.questionnaire-results.index') }}" class="grid gap-3 rounded-3xl border border-sky-100 bg-white p-5 shadow-sm lg:grid-cols-[1fr_260px_140px]">
        <input name="q" value="{{ $filters['q'] ?? '' }}" class="rounded-2xl border border-slate-200 px-4 py-3" placeholder="Cari responden, mahasiswa, email, atau tempat KP">
        <select name="audience" class="rounded-2xl border border-slate-200 px-4 py-3">
            <option value="">Semua jenis kuisioner</option>
            @foreach($audiences as $value => $label)
                <option value="{{ $value }}" @selected(($filters['audience'] ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <button class="rounded-2xl bg-slate-950 px-5 py-3 text-sm font-black text-white">Filter</button>
    </form>

    <section class="grid gap-4 xl:grid-cols-2">
        @forelse($summaries as $summary)
            @php
                $questionnaire = $summary['questionnaire'];
                $percentage = $summary['percentage'] ?? 0;
                $distributionTotal = array_sum($summary['distribution']);
            @endphp
            <article class="rounded-3xl border border-cyan-100 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <p class="text-xs font-black uppercase tracking-widest text-cyan-700">{{ $questionnaire->audienceLabel() }}</p>
                        <h2 class="mt-1 text-xl font-black text-slate-950">{{ $questionnaire->title }}</h2>
                        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">{{ $summary['conclusion'] }}</p>
                    </div>
                    <div class="rounded-3xl bg-slate-50 px-5 py-4 text-center">
                        <p class="text-xs font-black uppercase tracking-widest text-slate-500">Rata-rata</p>
                        <p class="mt-1 text-3xl font-black text-cyan-700">{{ $summary['average'] ?? '-' }}</p>
                        <p class="text-xs font-bold text-slate-500">{{ $summary['label'] }}</p>
                    </div>
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-3">
                    <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                        <p class="text-xs font-black uppercase tracking-widest text-slate-500">Respons</p>
                        <p class="mt-1 text-2xl font-black text-slate-950">{{ $summary['response_count'] }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                        <p class="text-xs font-black uppercase tracking-widest text-slate-500">Pertanyaan</p>
                        <p class="mt-1 text-2xl font-black text-slate-950">{{ $summary['question_count'] }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                        <p class="text-xs font-black uppercase tracking-widest text-slate-500">Capaian</p>
                        <p class="mt-1 text-2xl font-black text-slate-950">{{ $summary['percentage'] === null ? '-' : $summary['percentage'].'%' }}</p>
                    </div>
                </div>

                <div class="mt-5 rounded-2xl border border-slate-100 p-4">
                    <div class="mb-3 flex items-center justify-between">
                        <p class="text-sm font-black text-slate-900">Distribusi Skor</p>
                        <p class="text-xs text-slate-500">{{ $distributionTotal }} jawaban skala</p>
                    </div>
                    <div class="space-y-2">
                        @foreach([5, 4, 3, 2, 1] as $scale)
                            @php
                                $count = $summary['distribution'][$scale] ?? 0;
                                $width = $distributionTotal > 0 ? round(($count / $distributionTotal) * 100) : 0;
                            @endphp
                            <div class="grid grid-cols-[32px_1fr_42px] items-center gap-3 text-xs">
                                <span class="font-black text-slate-600">{{ $scale }}</span>
                                <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                                    <div class="h-full rounded-full bg-cyan-600" style="width: {{ $width }}%"></div>
                                </div>
                                <span class="text-right font-bold text-slate-500">{{ $count }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="mt-5 grid gap-3 lg:grid-cols-2">
                    <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4">
                        <p class="text-xs font-black uppercase tracking-widest text-emerald-700">Aspek terkuat</p>
                        <p class="mt-1 font-black text-slate-950">{{ $summary['strongest']['section'] ?? '-' }}</p>
                        <p class="text-sm text-slate-600">{{ $summary['strongest']['average'] ?? '-' }} / 5</p>
                    </div>
                    <div class="rounded-2xl border border-amber-100 bg-amber-50 p-4">
                        <p class="text-xs font-black uppercase tracking-widest text-amber-700">Perlu perhatian</p>
                        <p class="mt-1 font-black text-slate-950">{{ $summary['weakest']['section'] ?? '-' }}</p>
                        <p class="text-sm text-slate-600">{{ $summary['weakest']['average'] ?? '-' }} / 5</p>
                    </div>
                </div>

                <div class="mt-5">
                    <p class="mb-3 text-sm font-black text-slate-900">Skor per Aspek</p>
                    <div class="grid gap-3 md:grid-cols-2">
                        @foreach($summary['sections'] as $section)
                            <div class="rounded-2xl border border-slate-100 p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <p class="font-black text-slate-900">{{ $section['section'] }}</p>
                                    <span class="rounded-full bg-cyan-50 px-3 py-1 text-xs font-black text-cyan-700">{{ $section['average'] ?? '-' }}</span>
                                </div>
                                <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100">
                                    <div class="h-full rounded-full bg-cyan-600" style="width: {{ $section['percentage'] ?? 0 }}%"></div>
                                </div>
                                <p class="mt-2 text-xs text-slate-500">{{ $section['answer_count'] }} jawaban dari {{ $section['question_count'] }} pertanyaan skala</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                @if(count($summary['open_feedback']) > 0)
                    <div class="mt-5 rounded-2xl border border-slate-100 bg-slate-50 p-4">
                        <p class="mb-3 text-sm font-black text-slate-900">Cuplikan Masukan</p>
                        <div class="space-y-3">
                            @foreach($summary['open_feedback'] as $feedback)
                                <div>
                                    <p class="text-xs font-bold text-slate-500">{{ $feedback['question'] }}</p>
                                    <p class="text-sm text-slate-700">{{ $feedback['answer'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </article>
        @empty
            <article class="rounded-3xl border border-dashed border-sky-200 bg-white p-8 text-center text-slate-500 xl:col-span-2">
                Belum ada kuisioner atau respons yang bisa diolah.
            </article>
        @endforelse
    </section>

    <section class="overflow-hidden rounded-3xl border border-sky-100 bg-white shadow-sm">
        <div class="border-b border-slate-100 p-5">
            <p class="text-xs font-black uppercase tracking-widest text-cyan-700">Data Mentah</p>
            <h2 class="mt-1 text-xl font-black text-slate-950">Daftar Respons Kuisioner</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs font-black uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-4">Kuisioner</th>
                        <th class="px-5 py-4">Responden</th>
                        <th class="px-5 py-4">Konteks KP</th>
                        <th class="px-5 py-4">Submit</th>
                        <th class="px-5 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($responses as $response)
                        <tr>
                            <td class="px-5 py-4">
                                <p class="font-black text-slate-900">{{ $response->questionnaire->title }}</p>
                                <p class="text-xs text-cyan-700">{{ $response->questionnaire->audienceLabel() }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <p class="font-bold text-slate-900">{{ $response->respondent->name }}</p>
                                <p class="text-xs text-slate-500">{{ $response->respondent->email }}</p>
                            </td>
                            <td class="px-5 py-4 text-slate-600">
                                <p>{{ $response->assignment?->student?->user?->name ?? '-' }}</p>
                                <p class="text-xs">{{ $response->assignment?->place?->name ?? '-' }} · {{ $response->assignment?->period?->name ?? '-' }}</p>
                            </td>
                            <td class="px-5 py-4">{{ $response->submitted_at?->format('d M Y H:i') }}</td>
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('management.questionnaire-results.show', $response) }}" class="rounded-xl border border-cyan-200 px-4 py-2 text-xs font-black text-cyan-700">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-8 text-center text-slate-500">Belum ada respons sesuai filter.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-5 py-4">{{ $responses->links() }}</div>
    </section>
</div>
@endsection
