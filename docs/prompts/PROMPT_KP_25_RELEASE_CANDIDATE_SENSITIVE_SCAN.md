# PROMPT KP-25 - Release Candidate Sensitive Scan

Kamu adalah Codex di workspace:

`E:\Aplikasi\farmasi-ubp-workspace`

Aplikasi:

`apps/kp-farmasi`

Konteks:
- KP-24 sudah membuat checkpoint production readiness dan deployment runbooks.
- Branch `main` sudah dipush sampai commit `2016574`.
- Next step menuju production adalah memastikan kandidat release tidak membawa file sensitif atau generated output.

Tugas:
Kerjakan KP-25 - Release Candidate Sensitive Scan.

Tujuan:
1. Tambahkan command read-only `php artisan kp:release-sensitive-scan`.
2. Scan file tracked dan untracked non-ignored.
3. Blok file sensitif/generated seperti `.env`, `.env.production`, `vendor`, `node_modules`, `public/build`, storage upload/cache/log.
4. Blok private key dan assignment secret yang jelas.
5. Pastikan `.env.production.example` aman tetap boleh.
6. Tambahkan test.
7. Dokumentasikan report KP-25.

Guardrails:
- Jangan aktifkan runtime bridge TU/SAFA.
- Jangan write ke Core/TU/SAFA.
- Jangan commit `.env`.
- Jangan commit `.env.production`.
- Jangan commit secret nyata.

Validasi:
- `php artisan test --filter=ReleaseSensitiveScanCommandTest`
- `php artisan kp:release-sensitive-scan`
- validasi penuh sebelum checkpoint commit.

