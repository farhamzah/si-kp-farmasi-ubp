# Integration Operations

This runbook covers the KP producer outbox for `dosen-farmasi`.

## M6 Local Acceptance Snapshot

Date: 2026-07-17

Main KP -> Dosen HTTP path passed on nonproduction MySQL:

- integration enabled locally;
- base URL configured locally;
- token configured locally and not printed;
- KP outbox pending: 0;
- KP outbox failed: 0;
- 20 business-generated outbox rows delivered as `SENT`;
- Dosen returned HTTP `202` for all 20 rows;
- Dosen processed all 20 integration events;
- Dosen audit command reported all findings 0.

Failure drills also passed for consumer down, invalid token, timeout/connection failure, HTTP `5xx`, two-worker concurrency, and orphan `PROCESSING` recovery. HTTP `429` retryability is covered by automated delivery tests. Browser QA passed for KP admin dashboard/outbox pages and Dosen role/admin pages except Dosen `/admin/integration-clients`, which was blocked by the in-app browser URL policy.

Final M6 acceptance remains pending only for that blocked browser page unless the gate owner accepts the tool limitation.

## Health

```bash
php artisan kp:integration-health
```

Expected for an enabled local pilot:

- integration enabled;
- base URL configured;
- token configured;
- pending/failed outbox counts known;
- queue mode known.

Do not print or copy the token into tickets, docs, screenshots, or chat.

## Delivery

Preview eligible rows:

```bash
php artisan kp:deliver-integration-outbox --dry-run --limit=25
```

Dispatch delivery jobs:

```bash
php artisan kp:deliver-integration-outbox --limit=25
```

Retry failed rows after operator review:

```bash
php artisan kp:deliver-integration-outbox --retry-failed --limit=25
```

Retry one event:

```bash
php artisan kp:deliver-integration-outbox --event-id=<uuid>
```

## Retention And Orphan Recovery

Default preview:

```bash
php artisan kp:prune-integration-outbox --days=90 --show-rows
```

Apply terminal-row pruning only after review:

```bash
php artisan kp:prune-integration-outbox --days=90 --execute --confirm-execute
```

Recover stale `PROCESSING` rows to `PENDING` after confirming no worker is still processing them:

```bash
php artisan kp:prune-integration-outbox --recover-orphans --execute --confirm-execute
```

Policy:

- `SENT` retained for at least 90 days.
- `CANCELLED` retained for at least 90 days.
- `FAILED` is retained until manually resolved; the current schema has no resolved marker.
- `PENDING` is never pruned.
- Stale `PROCESSING` is treated as orphaned only after the configured lock age.

## Cross-App Audit

Run from `apps/dosen-farmasi`:

```bash
php artisan dosen:audit-kp-integration
```

Use `--show-rows` for safe technical references:

```bash
php artisan dosen:audit-kp-integration --show-rows
```

The audit is read-only and detects:

- KP `SENT` outbox rows missing a Dosen consumer event;
- stale `PENDING` outbox rows;
- old `FAILED` outbox rows;
- processed consumer events whose inbox/calendar objects are missing;
- completed KP events that did not create one `SYSTEM_VERIFIED` portfolio;
- duplicate portfolio source identity.

## Failure Handling

Consumer down:

1. Leave KP business flow running.
2. Confirm outbox remains `PENDING` or becomes retryable.
3. Restart Dosen.
4. Retry delivery.
5. Confirm one effective consumer event and no duplicate side effects.

Invalid token:

1. Use a temporary invalid local token.
2. Confirm failure is permanent/configuration style and not retried forever.
3. Restore valid local token.
4. Clear config cache.

5xx or rate limit:

1. Use a test endpoint or HTTP fake in automated tests.
2. Confirm retryable classification and backoff.

Concurrent delivery:

1. Run multiple workers or dispatch commands close together on MySQL.
2. Confirm row locking results in one effective send per outbox row.
3. Confirm Dosen idempotency prevents duplicate inbox, agenda, notification, and portfolio records.

M6 note: live drills passed for consumer down, invalid token, timeout/connection failure, HTTP `5xx`, two-worker concurrency, and orphan recovery. HTTP `429` is validated by automated coverage. Recovery should always be followed by `dosen:audit-kp-integration --show-rows`.

## Rollback

Set:

```env
DOSEN_FARMASI_INTEGRATION_ENABLED=false
```

Then:

```bash
php artisan optimize:clear
```

Do not delete outbox rows during rollback. Fix the root cause, then retry safely.
