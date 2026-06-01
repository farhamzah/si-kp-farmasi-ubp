# PROMPT KP-19 - Local External Document Reference Draft Management

Tanggal: 2026-06-01

Kerjakan KP-19 pada `apps/kp-farmasi`.

## Tujuan
- Tambahkan halaman management untuk draft reference dokumen eksternal TU.
- Admin/Koordinator dapat melihat reference lokal.
- Admin/Koordinator dapat membuat draft reference lokal dari TU payload preview.
- Semua aksi tetap lokal di database KP.

## Guardrails
- Tidak ada write ke Core/TU/SAFA.
- Tidak ada HTTP request nyata ke TU/SAFA.
- Tidak ada SSO, auto-login, signed login URL, atau token URL.
- Tidak duplicate upload dokumen.
- Tidak menyimpan token, password, secret, signed URL, atau path internal.
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
