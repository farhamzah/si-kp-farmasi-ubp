# KP-32 - VPS Predeploy Hardening

Tanggal: 2026-06-09

## Tujuan
Menutup gap kecil sebelum KP Farmasi dinaikkan ke VPS staging/UAT.

## Perubahan
- Menambahkan `.env.vps.example` sebagai template aman untuk server VPS.
- Memperketat `php artisan kp:staging-rehearsal-check`:
  - validasi `KP_AUTH_MODE`;
  - validasi `KP_MASTER_DATA_READ_MODE`;
  - blocker bila auth memakai Core bridge tetapi master data masih `legacy`;
  - validasi template `.env.vps.example` tersedia.
- Menambahkan test untuk template VPS dan staging rehearsal guardrail.
- Memperbarui checklist/runbook deployment.

## Env VPS Target
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://...
SESSION_SECURE_COOKIE=true
SESSION_ENCRYPT=true
KP_AUTH_MODE=core_bridge_with_legacy_fallback
KP_MASTER_DATA_READ_MODE=core_preferred
KP_CORE_HTTP_ENABLED=false
```

`KP_CORE_HTTP_ENABLED=false` adalah default aman sampai Core HTTP app-client credential staging tersedia.

## Catatan Core
Preflight `core_preferred` saat ini masih `WARN` di local karena profil student/lecturer Core untuk skenario UAT belum lengkap. Ini tidak memblokir naik ke VPS staging, tetapi harus dibereskan sebelum production final.

## Guardrails
- Tidak ada `.env` server yang dicommit.
- Tidak ada secret/password/token pada template.
- Tidak ada write ke Core/TU/SAFA.
- TU/SAFA runtime endpoint tetap kosong sampai approval gate final.

## Validasi Wajib Setelah Deploy VPS
```bash
php artisan optimize:clear
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan kp:staging-rehearsal-check
php artisan kp:core-mode-preflight --auth-mode=core_bridge_with_legacy_fallback --master-data-mode=core_preferred --show-samples
php artisan kp:core-mapping-coverage
php artisan kp:production-readiness-gate
```

## Validasi Lokal
- `php -l app/Console/Commands/StagingRehearsalCheckCommand.php`: lulus
- `php artisan test --filter=StagingRehearsalCheckCommandTest`: 3 passed, 13 assertions
- `php artisan test --filter=ProductionReadinessTest`: 5 passed, 53 assertions
- `php artisan kp:staging-rehearsal-check`: gagal expected di local karena `.env` lokal masih `KP_MASTER_DATA_READ_MODE=legacy`
- `php artisan test`: 181 passed, 1030 assertions
- `npm run build`: berhasil
- `php artisan route:list`: berhasil, 213 routes
- `php artisan kp:release-sensitive-scan`: findings 0
