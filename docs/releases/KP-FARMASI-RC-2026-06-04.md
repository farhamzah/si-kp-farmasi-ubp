# KP Farmasi Release Candidate - 2026-06-04

## Identitas Release
- Aplikasi: SI-KP Farmasi UBP
- Branch: `main`
- Remote: `origin/main`
- Commit release candidate: `6df48d2 Add KP release candidate gate`
- Tanggal manifest: 2026-06-04
- Status: release candidate siap untuk staging/production rehearsal, belum boleh go-live sebelum environment production memenuhi gate.

## Ringkasan Cakupan
Release candidate ini mencakup MVP end-to-end KP Farmasi dan rangkaian readiness lintas aplikasi:
- Core bridge read-only, fallback legacy, mapping coverage, provisioning dry-run/controlled.
- TU document dry-run preview dan external document reference manual lifecycle.
- SAFA public-info whitelist/preview.
- Admin integration review screens.
- Production readiness gate, staging rehearsal gate, sensitive scan, dan release candidate gate.

## Commit Penting
- `6df48d2` Add KP release candidate gate
- `22912ae` Add KP release sensitive scan
- `2016574` Add KP production readiness gates and deployment runbooks
- `6d8609c` Add KP external document reference draft management
- `29df47d` Add KP external document reference design
- `4f139b4` Add KP integration review screens
- `642c068` Add KP cross-app integration contracts and dry-run previews

## Gate Wajib Sebelum Tag/Deploy
Jalankan dari root aplikasi `apps/kp-farmasi`:

```bash
php artisan kp:release-sensitive-scan
php artisan kp:release-candidate-gate --strict-git
php artisan kp:integration-gap-check
php artisan kp:core-mapping-coverage
php artisan kp:staging-rehearsal-check
php artisan kp:production-readiness-gate
php artisan route:list
php artisan test
npm run build
git status --short --branch
```

## Status Validasi Lokal Terakhir
- `php artisan kp:release-sensitive-scan`: sukses, findings `0`.
- `php artisan kp:release-candidate-gate`: berjalan read-only, gagal expected pada local env karena production env belum aktif.
- `php artisan kp:integration-gap-check`: sukses.
- `php artisan kp:core-mapping-coverage`: sukses, `9/9` mapped.
- `php artisan kp:staging-rehearsal-check`: sukses, blocker `0`.
- `php artisan kp:production-readiness-gate`: gagal expected pada local env.
- `php artisan route:list`: sukses, `213` routes.
- `php artisan test`: sukses, `169` passed, `967` assertions.
- `npm run build`: sukses.
- `git status --short --branch`: clean dan sinkron dengan `origin/main` setelah push commit `6df48d2`.

## Blocker Go-Live Yang Masih Harus Diselesaikan Di Server
- `APP_ENV=production`.
- `APP_DEBUG=false`.
- `APP_URL=https://domain-resmi`.
- `SESSION_SECURE_COOKIE=true`.
- Mail service production bukan `log`.
- Backup database dan storage diverifikasi.
- Akun demo dinonaktifkan atau password dirotasi.
- Approval UAT/go-live diberikan.

## Guardrails Release
- KP tidak menulis ke database Core.
- Runtime write bridge TU tetap tertutup.
- Runtime write bridge SAFA tetap tertutup.
- Tidak ada SSO/autologin/token URL.
- Tidak ada duplicate upload dokumen ke TU.
- Tidak commit `.env`, `.env.production`, `vendor`, `node_modules`, `public/build`, upload storage, cache, atau log.
- Tidak menyimpan token/password/secret/signed URL/path internal pada repository.

## Keputusan Release
Release candidate ini boleh dipakai untuk rehearsal dan deployment preparation. Tag production final baru boleh dibuat setelah command berikut lulus di environment production atau staging final yang disamakan:

```bash
php artisan kp:release-candidate-gate --strict-git
php artisan kp:production-readiness-gate
```
