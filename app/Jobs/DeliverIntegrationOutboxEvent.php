<?php

namespace App\Jobs;

use App\Models\IntegrationOutboxEvent;
use App\Services\DosenFarmasiIntegrationClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DeliverIntegrationOutboxEvent implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public readonly int $outboxEventId) {}

    public function handle(DosenFarmasiIntegrationClient $client): void
    {
        $event = $this->lockEligibleEvent();
        if (! $event) {
            return;
        }

        $result = $client->send($event);

        DB::transaction(function () use ($event, $result): void {
            $locked = IntegrationOutboxEvent::query()->lockForUpdate()->find($event->id);
            if (! $locked || $locked->status !== IntegrationOutboxEvent::STATUS_PROCESSING) {
                return;
            }

            if ($result->sentSuccessfully()) {
                $locked->update([
                    'status' => IntegrationOutboxEvent::STATUS_SENT,
                    'sent_at' => now(),
                    'locked_at' => null,
                    'last_http_status' => $result->httpStatus,
                    'last_error_code' => null,
                    'last_error_message' => null,
                ]);

                return;
            }

            $maxAttempts = (int) config('dosen_farmasi.integration.max_attempts', 5);
            $retryable = $result->retryableFailure() && $locked->attempt_count < $maxAttempts;

            $locked->update([
                'status' => $retryable ? IntegrationOutboxEvent::STATUS_PENDING : IntegrationOutboxEvent::STATUS_FAILED,
                'available_at' => $retryable ? now()->addSeconds($this->backoffSeconds($locked->attempt_count)) : null,
                'locked_at' => null,
                'last_http_status' => $result->httpStatus,
                'last_error_code' => $result->errorCode,
                'last_error_message' => Str::limit((string) $result->message, 900),
            ]);
        });
    }

    private function lockEligibleEvent(): ?IntegrationOutboxEvent
    {
        return DB::transaction(function (): ?IntegrationOutboxEvent {
            $event = IntegrationOutboxEvent::query()
                ->whereKey($this->outboxEventId)
                ->whereIn('status', [IntegrationOutboxEvent::STATUS_PENDING, IntegrationOutboxEvent::STATUS_FAILED])
                ->where(fn ($query) => $query->whereNull('available_at')->orWhere('available_at', '<=', now()))
                ->lockForUpdate()
                ->first();

            if (! $event) {
                return null;
            }

            $event->update([
                'status' => IntegrationOutboxEvent::STATUS_PROCESSING,
                'attempt_count' => $event->attempt_count + 1,
                'locked_at' => now(),
                'last_attempted_at' => now(),
            ]);

            return $event->fresh();
        });
    }

    private function backoffSeconds(int $attemptCount): int
    {
        return match (true) {
            $attemptCount <= 1 => 60,
            $attemptCount === 2 => 300,
            $attemptCount === 3 => 900,
            default => 3600,
        };
    }
}
