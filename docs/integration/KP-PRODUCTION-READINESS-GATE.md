# KP Production Readiness Gate

Tanggal: 2026-06-03

## Tujuan
Readiness gate adalah pemeriksaan read-only sebelum KP Farmasi dipromosikan ke staging/production. Gate ini tidak mengaktifkan integrasi otomatis dan tidak mengirim request ke Core/TU/SAFA.

## Command

```bash
php artisan kp:production-readiness-gate
```

Report JSON opsional:

```bash
php artisan kp:production-readiness-gate --report-json
```

## Boundary
Allowed:
- membaca config aplikasi;
- membaca count tabel lokal KP;
- menulis JSON report lokal hanya bila `--report-json`.

Not allowed:
- write ke Core;
- write ke TU;
- write ke SAFA;
- HTTP request eksternal;
- auto-sync;
- SSO/autologin/token URL;
- upload/duplicate document.

## Status Runtime Bridge
Gate production boleh lulus untuk kesiapan aplikasi KP, tetapi runtime bridge TU tetap harus `no` sampai approval gate final selesai.

Syarat runtime bridge TU:
- endpoint contract final;
- auth contract final;
- audit event final;
- retry/rollback policy final;
- idempotency key final;
- approval gate manual sebelum auto-sync;
- dry-run staging dengan payload nyata;
- sign-off admin akademik/TU.

## Core Bridge Provisioning
Provisioning user Core ke KP hanya boleh dipakai sebagai local bridge support:

```bash
php artisan kp:provision-core-bridge-user --email=user@domain.test
php artisan kp:provision-core-bridge-user --email=user@domain.test --execute --confirm-execute
```

Default adalah dry-run. Execute hanya menulis ke database lokal KP untuk:
- user bridge legacy;
- role lokal KP;
- profil lecturer lokal bila Core lecturer tersedia.

Core user yang masih `must_change_password` wajib menyelesaikan perubahan password di Core Profile Portal sebelum dipakai login/provisioning KP.

## Production Checklist
- `APP_ENV=production`.
- `APP_DEBUG=false`.
- `APP_URL=https://...`.
- `APP_KEY` terisi dan aman.
- `SESSION_SECURE_COOKIE=true`.
- Queue worker production aktif.
- Cache production tidak memakai `file` untuk traffic nyata.
- Mailer production tidak memakai `log` bila notifikasi aktif.
- Migration production selesai.
- `php artisan kp:production-readiness-gate` tanpa blocker.
- `php artisan test` dan `npm run build` berhasil.

