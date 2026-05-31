# PROMPT KP-18 - External Document Reference Design for TU Bridge

Tanggal: 2026-06-01

Kerjakan KP-18 pada `apps/kp-farmasi`.

## Tujuan
- Buat desain dan fondasi lokal KP untuk menyimpan referensi dokumen eksternal TU.
- Gunakan prinsip reference/link/status, bukan duplicate upload.
- Tambahkan migration, model, service/helper, command preview, test, dan dokumentasi.
- Command default harus preview/read-only.

## Guardrails
- Tidak ada write ke Core/TU/SAFA.
- Tidak ada HTTP request nyata ke TU/SAFA.
- Tidak ada SSO, auto-login, signed login URL, atau token URL.
- Tidak mengekspos path internal/file privat.
- Jangan commit `.env`, `vendor`, `node_modules`, upload storage, cache, log, atau build output.
- Jangan revert perubahan user.

## Validasi
- `git status --short`
- `php artisan kp:integration-gap-check`
- `php artisan kp:core-mapping-coverage`
- `php artisan kp:tu-document-payload-preview --limit=1`
- `php artisan kp:safa-public-info-preview`
- `php artisan kp:external-document-reference-preview`
- `php artisan route:list`
- `php artisan test`
- `npm run build`
- `git status --short`
