# KP-23 - Production Environment Template & Deployment Runbook

Tanggal: 2026-06-03

## Ringkasan
KP-23 menambahkan template environment production yang aman dan runbook deployment production. Tahap ini tidak mengubah runtime bridge, tidak mengaktifkan auto-sync TU/SAFA, dan tidak menambahkan secret.

## File Dibuat/Diubah
Dibuat:
- `.env.production.example`
- `docs/integration/KP-PRODUCTION-DEPLOYMENT-RUNBOOK.md`
- `docs/reports/KP-23-PRODUCTION-ENV-TEMPLATE-DEPLOYMENT-RUNBOOK.md`
- `docs/prompts/PROMPT_KP_23_PRODUCTION_ENV_TEMPLATE_DEPLOYMENT_RUNBOOK.md`

Diubah:
- `docs/integration/KP-STAGING-DEPLOYMENT-UAT-CHECKLIST.md`
- `tests/Feature/ProductionReadinessTest.php`

## Template Production
`.env.production.example` berisi placeholder aman:
- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://kp-farmasi.example.ac.id`
- `SESSION_SECURE_COOKIE=true`
- `SESSION_ENCRYPT=true`
- `KP_CORE_VERIFY_SSL=true`
- `QUEUE_CONNECTION=database`
- `CACHE_STORE=database`
- `MAIL_MAILER=smtp`

Secret tetap kosong dan harus diisi di server production:
- `APP_KEY`
- `DB_PASSWORD`
- `CORE_DB_PASSWORD`
- `KP_CORE_CLIENT_SECRET`
- `MAIL_PASSWORD`
- `AWS_SECRET_ACCESS_KEY`

## Runbook Production
Runbook production mencakup:
- pre-deploy backup;
- deploy steps;
- migration;
- cache optimization;
- queue restart;
- post-deploy smoke test;
- rollback sequence;
- guardrails integrasi.

## Guardrails
- Tidak ada `.env` production yang dibuat/di-commit.
- Tidak ada password/token/secret nyata.
- Tidak ada write ke Core/TU/SAFA.
- Tidak ada HTTP request nyata ke TU/SAFA.
- Tidak ada SSO/autologin/token URL.
- Tidak ada duplicate upload dokumen.
- Runtime bridge TU/SAFA tetap tertutup sampai approval gate final.

## Validasi
Validasi KP-23:
- `php artisan test --filter=ProductionReadinessTest`: memastikan template production aman dan sesuai readiness requirements.

Validasi penuh tetap perlu dijalankan sebelum checkpoint commit:
- `php artisan kp:integration-gap-check`
- `php artisan kp:core-mapping-coverage`
- `php artisan kp:staging-rehearsal-check`
- `php artisan kp:production-readiness-gate`
- `php artisan route:list`
- `php artisan test`
- `npm run build`
- `git status --short`

## Rekomendasi KP-24
KP-24 sebaiknya fokus pada checkpoint commit aman untuk KP-20 sampai KP-23, lalu push setelah validasi penuh dan review file sensitif.

