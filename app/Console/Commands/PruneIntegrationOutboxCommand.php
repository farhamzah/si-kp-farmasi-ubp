<?php

namespace App\Console\Commands;

use App\Models\IntegrationOutboxEvent;
use Illuminate\Console\Command;

class PruneIntegrationOutboxCommand extends Command
{
    protected $signature = 'kp:prune-integration-outbox
        {--days=90 : Minimum retention age for terminal events}
        {--orphan-minutes=30 : PROCESSING lock age before it is treated as orphaned}
        {--execute : Apply delete/recovery changes}
        {--confirm-execute : Required with --execute}
        {--recover-orphans : Reset stale PROCESSING rows to PENDING}
        {--show-rows : Show affected event IDs}';

    protected $description = 'Preview or apply safe retention and orphan recovery for KP integration outbox events.';

    public function handle(): int
    {
        $days = max(90, (int) $this->option('days'));
        $orphanMinutes = max(5, (int) $this->option('orphan-minutes'));
        $execute = (bool) $this->option('execute');
        $confirmed = (bool) $this->option('confirm-execute');
        $recoverOrphans = (bool) $this->option('recover-orphans');

        if ($execute && ! $confirmed) {
            $this->error('Execute refused: missing --confirm-execute.');

            return self::FAILURE;
        }

        $cutoff = now()->subDays($days);
        $orphanCutoff = now()->subMinutes($orphanMinutes);

        $prunable = IntegrationOutboxEvent::query()
            ->whereIn('status', [IntegrationOutboxEvent::STATUS_SENT, IntegrationOutboxEvent::STATUS_CANCELLED])
            ->where('updated_at', '<=', $cutoff);

        $staleProcessing = IntegrationOutboxEvent::query()
            ->where('status', IntegrationOutboxEvent::STATUS_PROCESSING)
            ->whereNotNull('locked_at')
            ->where('locked_at', '<=', $orphanCutoff);

        $pendingCount = IntegrationOutboxEvent::query()->where('status', IntegrationOutboxEvent::STATUS_PENDING)->count();
        $failedCount = IntegrationOutboxEvent::query()->where('status', IntegrationOutboxEvent::STATUS_FAILED)->count();
        $prunableCount = (clone $prunable)->count();
        $orphanCount = (clone $staleProcessing)->count();

        $this->line('KP integration outbox retention');
        $this->line('Mode: '.($execute ? 'execute' : 'dry-run'));
        $this->line('Retention days: '.$days);
        $this->line('PENDING retained: '.$pendingCount);
        $this->line('FAILED retained: '.$failedCount);
        $this->line('Terminal rows eligible for prune: '.$prunableCount);
        $this->line('Stale PROCESSING rows detected: '.$orphanCount);

        if ($this->option('show-rows')) {
            (clone $prunable)->orderBy('id')->limit(25)->get(['event_id', 'status'])
                ->each(fn (IntegrationOutboxEvent $event) => $this->line('prune '.$event->event_id.' '.$event->status));
            (clone $staleProcessing)->orderBy('id')->limit(25)->get(['event_id', 'locked_at'])
                ->each(fn (IntegrationOutboxEvent $event) => $this->line('orphan '.$event->event_id.' locked_at='.$event->locked_at));
        }

        if (! $execute) {
            $this->line('No changes applied.');

            return self::SUCCESS;
        }

        $deleted = (clone $prunable)->delete();
        $recovered = 0;

        if ($recoverOrphans) {
            $recovered = (clone $staleProcessing)->update([
                'status' => IntegrationOutboxEvent::STATUS_PENDING,
                'available_at' => now(),
                'locked_at' => null,
                'last_error_code' => 'ORPHAN_RECOVERED',
                'last_error_message' => 'Recovered by kp:prune-integration-outbox.',
                'updated_at' => now(),
            ]);
        }

        $this->info('Terminal rows pruned: '.$deleted);
        $this->info('Orphan rows recovered: '.$recovered);

        return self::SUCCESS;
    }
}
