@extends('layouts.app')

@section('title', 'Riwayat Import - '.config('app.name'))
@section('page_title', 'Riwayat Import')

@section('content')
<div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Tanggal</th>
                    <th class="px-4 py-3">Tipe</th>
                    <th class="px-4 py-3">File</th>
                    <th class="px-4 py-3">Ringkasan</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($batches as $batch)
                    <tr>
                        <td class="px-4 py-4">{{ $batch->created_at->format('d M Y H:i') }}</td>
                        <td class="px-4 py-4">{{ str_replace('_', ' ', $batch->import_type) }}</td>
                        <td class="px-4 py-4">{{ $batch->original_filename ?: '-' }}</td>
                        <td class="px-4 py-4">Total {{ $batch->total_rows }}, sukses {{ $batch->success_rows }}, gagal {{ $batch->failed_rows }}</td>
                        <td class="px-4 py-4"><span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700">{{ $batch->status }}</span></td>
                        <td class="px-4 py-4 text-right"><a href="{{ route('admin.import-users.history.show', $batch) }}" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700">Detail</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-slate-500">Belum ada riwayat import.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="border-t border-slate-200 px-4 py-3">{{ $batches->links() }}</div>
</div>
@endsection
