@extends('layouts.app')
@section('title','Penempatan KP - '.config('app.name'))
@section('page_title','Penempatan KP')
@section('content')
<div class="space-y-5">
<div class="flex justify-end"><a href="{{ route('management.kp-assignments.create') }}" class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white">Buat Penempatan</a></div>
<section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
    <form method="GET" class="grid gap-3 lg:grid-cols-12">
        <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Cari nama/NIM mahasiswa" class="rounded-lg border border-slate-300 px-3 py-2 text-sm lg:col-span-4">
        <input name="place" value="{{ $filters['place'] ?? '' }}" placeholder="Cari tempat KP" class="rounded-lg border border-slate-300 px-3 py-2 text-sm lg:col-span-3">
        <select name="period" class="rounded-lg border border-slate-300 px-3 py-2 text-sm lg:col-span-3">
            <option value="">Semua Periode</option>
            @foreach($periods as $period)
                <option value="{{ $period->id }}" @selected(($filters['period'] ?? '') == $period->id)>{{ $period->name }}</option>
            @endforeach
        </select>
        <select name="status" class="rounded-lg border border-slate-300 px-3 py-2 text-sm lg:col-span-2">
            <option value="">Semua Status</option>
            @foreach(['menunggu_pembimbing'=>'Menunggu Pembimbing','aktif'=>'Aktif','berjalan'=>'Berjalan','selesai'=>'Selesai','dibatalkan'=>'Dibatalkan'] as $value=>$label)
                <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <input name="internal_supervisor" value="{{ $filters['internal_supervisor'] ?? '' }}" placeholder="Cari pembimbing dalam" class="rounded-lg border border-slate-300 px-3 py-2 text-sm lg:col-span-4">
        <input name="field_supervisor" value="{{ $filters['field_supervisor'] ?? '' }}" placeholder="Cari pembimbing lapangan" class="rounded-lg border border-slate-300 px-3 py-2 text-sm lg:col-span-4">
        <div class="flex gap-2 lg:col-span-4">
            <button class="flex-1 rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Filter</button>
            <a href="{{ route('management.kp-assignments.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Reset</a>
        </div>
    </form>
</section>
<section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200"><div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-sm"><thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-3">Mahasiswa</th><th class="px-4 py-3">Periode</th><th class="px-4 py-3">Tempat</th><th class="px-4 py-3">Pembimbing Dalam</th><th class="px-4 py-3">Pembimbing Lapangan</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Aksi</th></tr></thead><tbody class="divide-y divide-slate-100">@forelse($assignments as $assignment)@php($studentDisplay = app(\App\Services\KpMasterDataReadService::class)->getStudentDisplayData($assignment->student))@php($internalSupervisorDisplay = $assignment->internalSupervisor ? app(\App\Services\KpMasterDataReadService::class)->getLecturerDisplayData($assignment->internalSupervisor) : null)<tr><td class="px-4 py-4"><div class="font-semibold">{{ $studentDisplay->name }}</div><div class="text-xs text-slate-500">{{ $studentDisplay->studentNumber ?: '-' }}</div></td><td class="px-4 py-4">{{ $assignment->period->name }}</td><td class="px-4 py-4">{{ $assignment->place->name }}</td><td class="px-4 py-4">{{ $internalSupervisorDisplay?->name ?? 'Belum ada' }}</td><td class="px-4 py-4">{{ $assignment->fieldSupervisor?->user?->name ?? 'Belum ada' }}</td><td class="px-4 py-4"><span class="rounded-full {{ $assignment->statusBadgeClass() }} px-2 py-1 text-xs font-semibold">{{ $assignment->statusLabel() }}</span></td><td class="px-4 py-4 text-right"><a href="{{ route('management.kp-assignments.show',$assignment) }}" class="rounded-lg border border-teal-200 px-3 py-1.5 text-xs font-semibold text-teal-700">Detail</a></td></tr>@empty<tr><td colspan="7" class="px-4 py-10 text-center text-slate-500">Belum ada penempatan KP.</td></tr>@endforelse</tbody></table></div><div class="border-t px-4 py-3">{{ $assignments->links() }}</div></section>
</div>
@endsection
