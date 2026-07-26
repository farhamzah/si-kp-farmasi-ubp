<?php

namespace App\Services;

use App\Models\IntegrationOutboxEvent;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class DosenFarmasiIntegrationClient
{
    public function configured(): bool
    {
        return filled(config('dosen_farmasi.integration.base_url'))
            && filled(config('dosen_farmasi.integration.token'));
    }

    public function send(IntegrationOutboxEvent $event): IntegrationDeliveryResult
    {
        if (! $this->configured()) {
            return IntegrationDeliveryResult::permanent(null, 'CONFIGURATION_ERROR', 'Base URL atau token integrasi belum dikonfigurasi.');
        }

        try {
            $response = $this->request()->post($this->endpoint(), $event->envelope());
        } catch (ConnectionException $exception) {
            return IntegrationDeliveryResult::retryable(null, 'CONNECTION_FAILURE', $this->safeMessage($exception->getMessage()));
        }

        return $this->classify($response);
    }

    private function request()
    {
        return Http::acceptJson()
            ->asJson()
            ->withToken((string) config('dosen_farmasi.integration.token'))
            ->timeout((int) config('dosen_farmasi.integration.timeout_seconds', 10))
            ->connectTimeout((int) config('dosen_farmasi.integration.connect_timeout_seconds', 3))
            ->withOptions(['verify' => (bool) config('dosen_farmasi.integration.verify_tls', true)]);
    }

    private function endpoint(): string
    {
        return rtrim((string) config('dosen_farmasi.integration.base_url'), '/').'/api/internal/v1/events';
    }

    private function classify(Response $response): IntegrationDeliveryResult
    {
        $status = $response->status();
        $message = $this->safeMessage((string) ($response->json('message') ?: $response->body()));

        if (in_array($status, [200, 202], true)) {
            return IntegrationDeliveryResult::sent($status, $message ?: 'Accepted.');
        }

        if ($status === 429 || $status >= 500) {
            return IntegrationDeliveryResult::retryable($status, 'TEMPORARY_HTTP_'.$status, $message ?: 'Temporary integration failure.');
        }

        if (in_array($status, [401, 403], true)) {
            return IntegrationDeliveryResult::permanent($status, 'AUTHORIZATION_FAILED', $message ?: 'Token atau client integrasi ditolak.');
        }

        if ($status === 422) {
            return IntegrationDeliveryResult::permanent($status, 'VALIDATION_FAILED', $message ?: 'Payload tidak sesuai kontrak.');
        }

        return IntegrationDeliveryResult::permanent($status, 'HTTP_'.$status, $message ?: 'HTTP response tidak didukung.');
    }

    private function safeMessage(string $message): string
    {
        return Str::limit(preg_replace('/Bearer\s+[A-Za-z0-9\-\._~\+\/]+=*/', 'Bearer [redacted]', $message) ?: '', 900);
    }
}
