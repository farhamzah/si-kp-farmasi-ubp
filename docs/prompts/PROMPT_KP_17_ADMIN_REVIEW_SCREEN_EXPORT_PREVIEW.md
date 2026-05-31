# PROMPT KP-17 - Admin Review Screen & Export Preview

Tanggal: 2026-06-01

Kerjakan KP-17 pada `apps/kp-farmasi`.

## Tujuan
- Tambahkan halaman review Admin/Koordinator untuk TU dry-run payload.
- Tambahkan halaman review Admin/Koordinator untuk SAFA public-info preview.
- Tambahkan endpoint JSON preview read-only yang tetap role-limited dan tersanitasi.
- Tetap tidak membuat write bridge aktif.

## Guardrails
- Tidak ada write ke Core/TU/SAFA.
- Tidak ada HTTP request nyata ke TU/SAFA.
- Tidak ada SSO, auto-login, signed login URL, atau URL auth sementara.
- Jangan tampilkan data privat, path internal, credential, atau secret.
- Jangan commit `.env`, `vendor`, `node_modules`, upload storage, cache, log, atau build output.
- Jangan revert perubahan user.

## Validasi
- `git status --short`
- `php artisan kp:integration-gap-check`
- `php artisan kp:core-mapping-coverage`
- `php artisan kp:tu-document-payload-preview --limit=1`
- `php artisan kp:safa-public-info-preview`
- `php artisan route:list`
- `php artisan test`
- `npm run build`
- `git status --short`
