# PROMPT KP-28 - UI/UX Production Polish

Kamu adalah Codex di workspace:

`E:\Aplikasi\farmasi-ubp-workspace`

Aplikasi:

`apps/kp-farmasi`

Konteks:
- KP-27 sudah membuat release candidate manifest dan remote sudah sinkron.
- User ingin memastikan aplikasi KP-Farmasi berjalan baik, bukan hanya koneksi Core.
- Validasi backend sudah lulus: test, build, staging rehearsal, sensitive scan.
- Visual browser runtime lokal bisa saja tidak tersedia di sandbox, sehingga audit kode dan command readiness tetap diperlukan.

Tugas:
Kerjakan KP-28 - UI/UX Production Polish & Visual QA.

Tujuan:
1. Audit UI/UX production readiness.
2. Hilangkan placeholder menu production yang diketahui.
3. Tambahkan command read-only `php artisan kp:ui-readiness-check`.
4. Tambahkan test untuk readiness UI.
5. Pastikan dashboard semua role tidak menampilkan badge `Segera`.
6. Dokumentasikan hasil dan batasan visual screenshot.

Guardrails:
- Jangan membuat fitur besar baru.
- Jangan write ke Core/TU/SAFA.
- Jangan aktifkan runtime bridge TU/SAFA.
- Jangan membuat SSO/autologin/token URL.
- Jangan commit `.env`, `.env.production`, `vendor`, `node_modules`, build output, upload storage, cache, atau log.

Validasi:
- `php artisan kp:ui-readiness-check`
- `php artisan test --filter=UiReadinessCheckCommandTest`
- `php artisan test --filter=KpRecapExportAndDashboardTest`
- `php artisan test`
- `npm run build`
- `git status --short`
