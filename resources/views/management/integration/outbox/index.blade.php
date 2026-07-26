@extends('layouts.app')
@section('title', 'Outbox Dosen Farmasi - '.config('app.name'))
@section('page_title', 'Outbox Dosen Farmasi')
@section('content')
    <x-ui.page-header title="Outbox Dosen Farmasi" subtitle="Monitoring pengiriman event KP ke aplikasi dosen." />

    <x-ui.card>
        <form method="GET" class="grid gap-3 md:grid-cols-3">
            <select name="status" class="rounded-lg border-slate-300">
                <option value="">Semua status</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $status }}</option>
                @endforeach
            </select>
            <input name="event_type" value="{{ $filters['event_type'] ?? '' }}" class="rounded-lg border-slate-300" placeholder="Event type">
            <x-ui.button type="submit">Filter</x-ui.button>
        </form>
    </x-ui.card>

    <x-ui.card class="mt-4 overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead>
                <tr class="text-left text-slate-600">
                    <th class="px-3 py-2">Event</th>
                    <th class="px-3 py-2">Record</th>
                    <th class="px-3 py-2">Status</th>
                    <th class="px-3 py-2">Attempt</th>
                    <th class="px-3 py-2">Last error</th>
                    <th class="px-3 py-2">Dibuat</th>
                    <th class="px-3 py-2"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($events as $event)
                    <tr>
                        <td class="px-3 py-2">
                            <div class="font-medium">{{ $event->event_type }}</div>
                            <div class="text-xs text-slate-500">{{ $event->event_id }}</div>
                        </td>
                        <td class="px-3 py-2">{{ $event->source_record_id }} r{{ $event->source_revision }}</td>
                        <td class="px-3 py-2">{{ $event->status }}</td>
                        <td class="px-3 py-2">{{ $event->attempt_count }}</td>
                        <td class="px-3 py-2">{{ $event->last_error_code ?: '-' }}</td>
                        <td class="px-3 py-2">{{ $event->created_at?->format('d M Y H:i') }}</td>
                        <td class="px-3 py-2 text-right">
                            <a href="{{ route('management.integration.outbox.show', $event) }}" class="text-cyan-700">Detail</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-3 py-8 text-center text-slate-500">Belum ada outbox event.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-4">{{ $events->links() }}</div>
    </x-ui.card>
@endsection
