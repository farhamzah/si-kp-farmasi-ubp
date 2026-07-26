<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\IntegrationOutboxEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IntegrationOutboxController extends Controller
{
    public function index(Request $request): View
    {
        $events = IntegrationOutboxEvent::query()
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('event_type'), fn ($query) => $query->where('event_type', $request->event_type))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('management.integration.outbox.index', [
            'events' => $events,
            'filters' => $request->only(['status', 'event_type']),
            'statuses' => [
                IntegrationOutboxEvent::STATUS_PENDING,
                IntegrationOutboxEvent::STATUS_PROCESSING,
                IntegrationOutboxEvent::STATUS_SENT,
                IntegrationOutboxEvent::STATUS_FAILED,
                IntegrationOutboxEvent::STATUS_CANCELLED,
            ],
        ]);
    }

    public function show(IntegrationOutboxEvent $event): View
    {
        return view('management.integration.outbox.show', ['event' => $event]);
    }

    public function retry(IntegrationOutboxEvent $event): RedirectResponse
    {
        abort_if($event->status === IntegrationOutboxEvent::STATUS_SENT, 422);

        $event->update([
            'status' => IntegrationOutboxEvent::STATUS_PENDING,
            'available_at' => now(),
            'locked_at' => null,
            'last_error_code' => null,
            'last_error_message' => null,
        ]);

        return back()->with('status', 'Outbox event dijadwalkan retry.');
    }

    public function cancel(IntegrationOutboxEvent $event): RedirectResponse
    {
        abort_if($event->status === IntegrationOutboxEvent::STATUS_SENT, 422);

        $event->update([
            'status' => IntegrationOutboxEvent::STATUS_CANCELLED,
            'locked_at' => null,
        ]);

        return back()->with('status', 'Outbox event dibatalkan.');
    }
}
