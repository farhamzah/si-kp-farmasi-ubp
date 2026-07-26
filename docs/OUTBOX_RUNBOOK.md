# Outbox Runbook

## M6 Acceptance Snapshot

Date: 2026-07-17

Local nonproduction MySQL main path passed:

- 20 KP business-generated events were delivered to Dosen.
- 20 outbox rows reached `SENT`.
- Dosen returned HTTP `202` for all rows.
- Dosen processed all 20 consumer events.
- Cross-app audit findings were all 0.
- Retention dry-run completed with no rows pruned.

Additional M6 closure checks:

- consumer-down live drill: PASS;
- invalid-token live drill: PASS;
- timeout/connection failure drill: PASS;
- HTTP `5xx` live drill: PASS;
- HTTP `429` automated retryability coverage: PASS;
- two-worker concurrency drill: PASS;
- orphan `PROCESSING` dry-run/recovery/retry drill: PASS;
- KP admin outbox list/detail browser QA: PASS;
- responsive checks: PASS.

Final strict acceptance remains pending because Dosen `/admin/integration-clients` was blocked by the in-app browser URL policy.

## Health

```bash
php artisan kp:integration-health
```

## Delivery

```bash
php artisan kp:deliver-integration-outbox --dry-run --limit=25
php artisan kp:deliver-integration-outbox --limit=25
php artisan kp:deliver-integration-outbox --retry-failed --limit=25
php artisan kp:deliver-integration-outbox --event-id=<uuid>
```

## Status

- `PENDING`: menunggu delivery.
- `PROCESSING`: dikunci worker.
- `SENT`: diterima consumer; duplicate `200` dianggap sukses.
- `FAILED`: gagal permanen atau melewati batas retry.
- `CANCELLED`: dibatalkan operator.

## Retry

Retryable: timeout, connection failure, `429`, `5xx`.

Permanent: `401`, `403`, `422`, payload/config invalid.

M6 observed classifications:

- connection failure without Dosen: `PENDING` with `CONNECTION_FAILURE`;
- invalid token: `FAILED` with `AUTHORIZATION_FAILED`;
- HTTP `500`: `PENDING` with `TEMPORARY_HTTP_500`;
- HTTP `429`: `PENDING` with `TEMPORARY_HTTP_429` in automated coverage;
- stale `PROCESSING` recovery: `PENDING` with `ORPHAN_RECOVERED`.

## Monitoring

UI monitoring:

```text
/management/integration/dosen-farmasi-outbox
```

Admin/koordinator dapat inspect payload, retry, dan cancel pending/failed. Event `SENT` tidak diubah.

## Audit

Audit lintas aplikasi dijalankan dari `dosen-farmasi`:

```bash
php artisan dosen:audit-kp-integration
php artisan dosen:audit-kp-integration --show-rows
```

Command tersebut read-only dan membandingkan KP outbox dengan `integration_events`, inbox, agenda, dan portfolio di Dosen.

## Retention

Preview aman:

```bash
php artisan kp:prune-integration-outbox --days=90 --show-rows
```

Eksekusi setelah review operator:

```bash
php artisan kp:prune-integration-outbox --days=90 --execute --confirm-execute
```

Recovery orphan `PROCESSING` harus eksplisit:

```bash
php artisan kp:prune-integration-outbox --recover-orphans --execute --confirm-execute
```

Kebijakan awal:

- `SENT` minimal 90 hari.
- `CANCELLED` minimal 90 hari.
- `FAILED` tidak dipurge otomatis karena belum ada marker resolved.
- `PENDING` tidak dipurge.

## Deployment

1. Backup database.
2. Deploy `dosen-farmasi` consumer dan migration.
3. Buat/rotate token `kp-farmasi`.
4. Deploy migration outbox KP.
5. Deploy producer KP dengan integration disabled.
6. Jalankan test dan health.
7. Set token/base URL.
8. Jalankan dry-run.
9. Aktifkan integration.
10. Kirim satu pilot event dan verifikasi consumer.
11. Aktifkan scheduler/queue.

Rollback: set `DOSEN_FARMASI_INTEGRATION_ENABLED=false`, hentikan scheduler/worker, jangan hapus outbox, perbaiki konfigurasi, lalu retry.
