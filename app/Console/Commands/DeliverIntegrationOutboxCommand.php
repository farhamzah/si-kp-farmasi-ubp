<?php

namespace App\Console\Commands;

use App\Jobs\DeliverIntegrationOutboxEvent;
use App\Models\IntegrationOutboxEvent;
use Illuminate\Console\Command;

class DeliverIntegrationOutboxCommand extends Command
{
    protected $signature = 'kp:deliver-integration-outbox {--limit=25} {--event-id=} {--retry-failed} {--dry-run}';

    protected $description = 'Deliver pending KP integration outbox events to dosen-farmasi.';

    public function handle(): int
    {
        $query = IntegrationOutboxEvent::query()
            ->where('destination_app', 'dosen-farmasi')
            ->where(fn ($query) => $query->whereNull('available_at')->orWhere('available_at', '<=', now()))
            ->orderBy('id');

        if ($this->option('event-id')) {
            $query->where('event_id', $this->option('event-id'));
        } else {
            $statuses = [IntegrationOutboxEvent::STATUS_PENDING];
            if ($this->option('retry-failed')) {
                $statuses[] = IntegrationOutboxEvent::STATUS_FAILED;
            }
            $query->whereIn('status', $statuses)->limit(max(1, (int) $this->option('limit')));
        }

        $events = $query->get();
        $this->line('Eligible events: '.$events->count());

        if ($this->option('dry-run')) {
            $events->each(fn (IntegrationOutboxEvent $event) => $this->line($event->event_id.' '.$event->event_type.' '.$event->status));

            return self::SUCCESS;
        }

        $events->each(fn (IntegrationOutboxEvent $event) => DeliverIntegrationOutboxEvent::dispatch($event->id)->afterCommit());
        $this->info('Delivery jobs dispatched: '.$events->count());

        return self::SUCCESS;
    }
}
