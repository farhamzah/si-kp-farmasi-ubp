# KP Production Deployment Runbook

Tanggal: 2026-06-03

## Tujuan
Runbook ini menjadi urutan deployment production untuk SI-KP Farmasi UBP. Dokumen ini tidak mengaktifkan runtime bridge TU/SAFA dan tidak memuat secret.

## Template Environment
Gunakan `.env.vps.example` untuk VPS staging/UAT dan `.env.production.example` untuk production final, lalu buat `.env` di server. Jangan commit `.env` server.

Wajib production:
- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://...`
- `APP_KEY` terisi dari `php artisan key:generate`
- `SESSION_SECURE_COOKIE=true`
- `SESSION_ENCRYPT=true`
- `KP_AUTH_MODE=core_bridge_with_legacy_fallback`
- `KP_MASTER_DATA_READ_MODE=core_preferred`
- `KP_CORE_VERIFY_SSL=true`
- `KP_CORE_FAIL_SILENTLY=true`

Integrasi:
- Core DB hanya untuk read/bridge sesuai guardrail.
- TU runtime endpoint tetap kosong sampai approval gate final.
- SAFA runtime endpoint tetap kosong sampai approval gate final.
- Tidak ada SSO/autologin/token URL.

## Pre-Deploy
1. Pastikan branch release sudah divalidasi dan di-commit.
2. Backup database KP production.
3. Backup file `.env` production di lokasi aman server.
4. Pastikan storage upload tidak dihapus saat deploy.
5. Pastikan rollback target commit diketahui.
6. Jalankan rehearsal di staging:

```bash
php artisan kp:staging-rehearsal-check
php artisan kp:production-readiness-gate
```

## Deploy
Urutan pada server production:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan optimize:clear
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan kp:production-readiness-gate
```

Jika memakai queue worker:

```bash
php artisan queue:restart
```

## Post-Deploy Smoke Test
Jalankan:

```bash
php artisan kp:integration-gap-check
php artisan kp:core-mapping-coverage
php artisan kp:core-mode-preflight --auth-mode=core_bridge_with_legacy_fallback --master-data-mode=core_preferred --show-samples
php artisan kp:staging-rehearsal-check
php artisan kp:production-readiness-gate
```

Uji manual:
- login Admin;
- login Koordinator KP;
- login Mahasiswa;
- buka dashboard tiap role;
- buka pendaftaran KP;
- buka review integrasi TU/SAFA;
- buka external document reference;
- pastikan upload/download file protected tetap bekerja.

## Rollback
Jika release gagal:
1. Aktifkan maintenance mode.
2. Kembalikan kode ke commit sebelumnya.
3. Restore database KP bila migration/data perlu dibalik.
4. Jalankan:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
```

5. Jalankan smoke test.
6. Matikan maintenance mode jika sehat.

## Guardrails
- Tidak ada write ke Core.
- Tidak ada write ke TU.
- Tidak ada write ke SAFA.
- Tidak ada HTTP request nyata ke TU/SAFA sebelum approval gate final.
- Tidak ada auto-sync.
- Tidak ada SSO/autologin/token URL.
- Tidak ada duplicate upload dokumen.
- Tidak menyimpan token/password/secret/signed URL/path internal.
