@extends('layouts.app')
@section('title','Report Pembimbing Dalam - '.config('app.name'))
@section('page_title','Report Pembimbing Dalam')
@section('content')
@php
    $reportQuery = array_filter([
        'q' => $filters['q'] ?? null,
        'period' => $filters['period'] ?? null,
        'status' => $filters['status'] ?? null,
    ], fn ($value) => filled($value));
@endphp
<div class="space-y-5">
    <section class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-widest text-cyan-700">Beban Pembimbing Dalam</p>
                <h2 class="mt-1 text-2xl font-black text-slate-950">Jumlah mahasiswa per tipe tempat KP</h2>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">Default report mengecualikan penempatan yang dibatalkan. Gunakan filter status bila ingin audit data batal secara khusus.</p>
            </div>
            <div class="grid gap-2 sm:grid-cols-2 lg:flex lg:flex-wrap">
                <a href="{{ route('management.internal-supervisor-workload.preview', $reportQuery) }}" target="_blank" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm">Preview</a>
                <a href="{{ route('management.internal-supervisor-workload.preview', $reportQuery + ['print' => 1]) }}" target="_blank" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm">Print</a>
                <a href="{{ route('management.internal-supervisor-workload.download', ['format' => 'word'] + $reportQuery) }}" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm">Word</a>
                <a href="{{ route('management.internal-supervisor-workload.download', ['format' => 'excel'] + $reportQuery) }}" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-emerald-200 bg-white px-4 py-2 text-sm font-semibold text-emerald-700 shadow-sm">Excel</a>
                <a href="{{ route('management.internal-supervisor-workload.download', ['format' => 'pdf'] + $reportQuery) }}" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-rose-200 bg-white px-4 py-2 text-sm font-semibold text-rose-700 shadow-sm">PDF</a>
            </div>
        </div>
    </section>

    <section class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
        <form method="GET" class="grid gap-3 lg:grid-cols-[1fr_240px_240px_140px_100px]">
            <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Cari nama/NIDN pembimbing" class="min-h-11 rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <select name="period" class="min-h-11 rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="">Semua Periode</option>
                @foreach($periods as $period)
                    <option value="{{ $period->id }}" @selected(($filters['period'] ?? '') == $period->id)>{{ $period->name }}</option>
                @endforeach
            </select>
            <select name="status" class="min-h-11 rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="">Semua aktif/non-batal</option>
                @foreach($statusOptions as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="min-h-11 rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Filter</button>
            <a href="{{ route('management.internal-supervisor-workload.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Reset</a>
        </form>
    </section>

    <section class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">No</th>
                        <th class="px-4 py-3">Nama Dosen Pembimbing</th>
                        <th class="px-4 py-3 text-center">RS</th>
                        <th class="px-4 py-3 text-center">Apotek</th>
                        <th class="px-4 py-3 text-center">Industri</th>
                        <th class="px-4 py-3 text-center">Lainnya</th>
                        <th class="px-4 py-3 text-center">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($rows as $row)
                        <tr class="{{ $row['Nama Dosen Pembimbing'] === 'TOTAL' ? 'bg-cyan-50 font-black text-cyan-900' : '' }}">
                            <td class="px-4 py-4">{{ $row['No'] }}</td>
                            <td class="px-4 py-4 font-semibold">{{ $row['Nama Dosen Pembimbing'] }}</td>
                            <td class="px-4 py-4 text-center">{{ $row['RS'] }}</td>
                            <td class="px-4 py-4 text-center">{{ $row['Apotek'] }}</td>
                            <td class="px-4 py-4 text-center">{{ $row['Industri'] }}</td>
                            <td class="px-4 py-4 text-center">{{ $row['Lainnya'] }}</td>
                            <td class="px-4 py-4 text-center font-black">{{ $row['Total'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-10 text-center text-slate-500">Belum ada data pembimbing sesuai filter.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
