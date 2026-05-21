@extends('layouts.app')
@section('title','Mahasiswa Bimbingan - '.config('app.name'))
@section('page_title','Mahasiswa Bimbingan')
@section('content')
<section class="si-table-wrap">
    <table class="divide-y divide-slate-100">
        <thead>
            <tr>
                <th>Mahasiswa</th>
                <th>Periode</th>
                <th>Tempat</th>
                <th>Pembimbing Lapangan</th>
                <th class="text-center">Status</th>
                <th class="text-right">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($assignments as $assignment)
                <tr>
                    <td class="min-w-56">
                        <div class="font-black text-slate-950">{{ $assignment->student->user->name }}</div>
                        <div class="mt-1 text-xs font-semibold text-slate-500">{{ $assignment->student->nim ?: '-' }}</div>
                    </td>
                    <td class="whitespace-nowrap font-medium text-slate-700">{{ $assignment->period->name }}</td>
                    <td class="min-w-44 font-medium text-slate-700">{{ $assignment->place->name }}</td>
                    <td class="min-w-56 text-slate-700">{{ $assignment->fieldSupervisor?->user?->name ?? '-' }}</td>
                    <td class="text-center">
                        <span class="inline-flex rounded-full {{ $assignment->statusBadgeClass() }} px-3 py-1 text-xs font-bold">
                            {{ $assignment->statusLabel() }}
                        </span>
                    </td>
                    <td class="whitespace-nowrap text-right">
                        <a href="{{ route('internal-supervisor.assignments.show', $assignment) }}" class="inline-flex items-center justify-center rounded-xl border border-teal-200 bg-white px-3 py-2 text-xs font-black text-teal-700 transition hover:bg-teal-50">
                            Detail
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-12 text-center text-slate-500">
                        Belum ada mahasiswa bimbingan.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="border-t border-slate-100 px-4 py-3">
        {{ $assignments->links() }}
    </div>
</section>
@endsection
