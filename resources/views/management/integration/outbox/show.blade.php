@extends('layouts.app')
@section('title', 'Detail Outbox - '.config('app.name'))
@section('page_title', 'Detail Outbox')
@section('content')
    <x-ui.page-header title="Detail Outbox" subtitle="{{ $event->event_type }} - {{ $event->event_id }}" />

    <x-ui.card>
        <dl class="grid gap-3 md:grid-cols-2 text-sm">
            <div><dt class="text-slate-500">Status</dt><dd class="font-medium">{{ $event->status }}</dd></div>
            <div><dt class="text-slate-500">Source record</dt><dd>{{ $event->source_record_id }} r{{ $event->source_revision }}</dd></div>
            <div><dt class="text-slate-500">Attempt</dt><dd>{{ $event->attempt_count }}</dd></div>
            <div><dt class="text-slate-500">HTTP</dt><dd>{{ $event->last_http_status ?: '-' }}</dd></div>
            <div class="md:col-span-2"><dt class="text-slate-500">Error</dt><dd>{{ $event->last_error_code ?: '-' }} {{ $event->last_error_message }}</dd></div>
        </dl>

        <pre class="mt-4 max-h-96 overflow-auto rounded-lg bg-slate-950 p-4 text-xs text-slate-100">{{ json_encode($event->envelope(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>

        @if ($event->status !== 'SENT')
            <div class="mt-4 flex gap-2">
                <form method="POST" action="{{ route('management.integration.outbox.retry', $event) }}">
                    @csrf
                    <x-ui.button type="submit">Retry</x-ui.button>
                </form>
                <form method="POST" action="{{ route('management.integration.outbox.cancel', $event) }}">
                    @csrf
                    <x-ui.button type="submit" variant="secondary">Cancel</x-ui.button>
                </form>
            </div>
        @endif
    </x-ui.card>
@endsection
