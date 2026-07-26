<?php

namespace App\Console\Commands;

use App\Models\IntegrationOutboxEvent;
use Illuminate\Console\Command;

class KpIntegrationHealthCommand extends Command
{
    protected $signature = 'kp:integration-health';

    protected $description = 'Show safe KP to dosen-farmasi integration health.';

    public function handle(): int
    {
        $pending = IntegrationOutboxEvent::query()->where('status', IntegrationOutboxEvent::STATUS_PENDING)->count();
        $failed = IntegrationOutboxEvent::query()->where('status', IntegrationOutboxEvent::STATUS_FAILED)->count();
        $oldestPending = IntegrationOutboxEvent::query()->where('status', IntegrationOutboxEvent::STATUS_PENDING)->oldest()->value('created_at');
        $lastSent = IntegrationOutboxEvent::query()->where('status', IntegrationOutboxEvent::STATUS_SENT)->latest('sent_at')->value('sent_at');

        $this->line('Integration enabled: '.((bool) config('dosen_farmasi.integration.enabled') ? 'yes' : 'no'));
        $this->line('Base URL configured: '.(filled(config('dosen_farmasi.integration.base_url')) ? 'yes' : 'no'));
        $this->line('Token configured: '.(filled(config('dosen_farmasi.integration.token')) ? 'yes' : 'no'));
        $this->line('Pending outbox: '.$pending);
        $this->line('Failed outbox: '.$failed);
        $this->line('Oldest pending: '.($oldestPending ?: '-'));
        $this->line('Last sent: '.($lastSent ?: '-'));
        $this->line('Queue connection: '.config('queue.default'));

        return self::SUCCESS;
    }
}
