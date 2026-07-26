# Integrasi Dosen Farmasi

Producer KP mengirim event ke `dosen-farmasi` melalui outbox lokal `integration_outbox_events`.

## Konfigurasi

```env
DOSEN_FARMASI_INTEGRATION_ENABLED=false
DOSEN_FARMASI_BASE_URL=
DOSEN_FARMASI_INTEGRATION_TOKEN=
DOSEN_FARMASI_TIMEOUT_SECONDS=10
DOSEN_FARMASI_CONNECT_TIMEOUT_SECONDS=3
DOSEN_FARMASI_VERIFY_TLS=true
DOSEN_FARMASI_MAX_ATTEMPTS=5
```

Token dibuat dari `dosen-farmasi`:

```bash
php artisan dosen:integration-client-token kp-farmasi
```

Plaintext token hanya tampil sekali dan harus disimpan di environment runtime, bukan repository.

## Event

- `kp.supervisor.assigned`
- `kp.supervisor.changed`
- `kp.examiner.assigned`
- `kp.examiner.changed`
- `kp.exam.scheduled`
- `kp.exam.rescheduled`
- `kp.exam.completed`
- `kp.exam.cancelled`

## Operations

Runbook lengkap tersedia di `docs/INTEGRATION_OPERATIONS.md`.

Useful commands:

```bash
php artisan kp:integration-health
php artisan kp:deliver-integration-outbox --dry-run --limit=25
php artisan kp:deliver-integration-outbox --retry-failed --limit=25
php artisan kp:prune-integration-outbox --days=90 --show-rows
```

Cross-app audit dijalankan dari `dosen-farmasi`:

```bash
php artisan dosen:audit-kp-integration
```

Satu event dikirim per dosen. Satu ujian dengan satu pembimbing dan dua penguji menghasilkan tiga event jadwal.

## Trigger

- `KpAssignmentService` menulis event pembimbing.
- `KpExamService` menulis event penguji, jadwal, reschedule, selesai, dan batal.
- Semua outbox ditulis dalam transaction bisnis yang sama.
- HTTP delivery dilakukan setelah commit melalui job/command, bukan di dalam transaction.

## Revision

`kp_assignments.integration_revision` dan `kp_exams.integration_revision` naik saat perubahan bisnis integrasi terjadi. Retry tidak menaikkan revision.

## Security

Payload tidak membawa password, token, credential, file binary, atau base64. URL aksi memakai path internal relatif.

## M6 Acceptance Note

Pada acceptance 2026-07-17, KP producer -> Dosen consumer lulus main path, consumer-down retry, invalid-token classification, timeout/connection failure recovery, HTTP `5xx` retry, two-worker concurrency, orphan recovery, MySQL regression, build, route list, retention dry-run, dan audit lintas aplikasi. HTTP `429` retryability divalidasi oleh automated test.

Status gate tetap `technical integration complete / acceptance pending` karena satu halaman Dosen admin (`/admin/integration-clients`) terblokir oleh kebijakan URL in-app browser saat QA. M7 belum dimulai.
