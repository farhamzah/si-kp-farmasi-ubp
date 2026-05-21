@extends('layouts.app')

@section('title', 'Detail Import - '.config('app.name'))
@section('page_title', 'Detail Import')

@section('content')
<div class="space-y-5">
    <section class="grid gap-4 md:grid-cols-4">
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200"><p class="text-xs text-slate-500">Total</p><p class="text-2xl font-bold">{{ $batch->total_rows }}</p></div>
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200"><p class="text-xs text-slate-500">Sukses</p><p class="text-2xl font-bold text-emerald-700">{{ $batch->success_rows }}</p></div>
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200"><p class="text-xs text-slate-500">Gagal</p><p class="text-2xl font-bold text-rose-700">{{ $batch->failed_rows }}</p></div>
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200"><p class="text-xs text-slate-500">Status</p><p class="text-sm font-bold">{{ $batch->status }}</p></div>
    </section>

    <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
        <h2 class="text-lg font-bold text-slate-950">Error Per Baris</h2>
        @if($batch->errors->isEmpty())
            <p class="mt-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700">Tidak ada error pada import ini.</p>
        @else
            <div class="mt-4 overflow-x-auto rounded-xl border border-slate-200">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr><th class="px-3 py-2">Baris</th><th class="px-3 py-2">Identifier</th><th class="px-3 py-2">Pesan</th><th class="px-3 py-2">Data</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($batch->errors as $error)
                            <tr class="align-top">
                                <td class="px-3 py-2">{{ $error->row_number }}</td>
                                <td class="px-3 py-2">{{ $error->identifier ?: '-' }}</td>
                                <td class="px-3 py-2 text-rose-700">{{ $error->error_message }}</td>
                                <td class="px-3 py-2"><code class="text-xs text-slate-600">{{ json_encode($error->row_data) }}</code></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</div>
@endsection
