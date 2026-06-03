# PROMPT KP-22 - Staging Deployment Checklist & UAT Rehearsal

Kamu adalah Codex di workspace:

`E:\Aplikasi\farmasi-ubp-workspace`

Aplikasi:

`apps/kp-farmasi`

Konteks:
- KP-20 menambahkan manual external document linking/status lifecycle.
- KP-21 menambahkan production readiness gate dan Core Bridge provisioning guardrails.
- Runtime bridge TU/SAFA tetap belum boleh aktif.
- Tidak boleh write ke Core/TU/SAFA.

Tugas:
Kerjakan KP-22 - Staging Deployment Checklist & UAT Rehearsal.

Tujuan:
1. Tambahkan command read-only untuk staging deployment/UAT rehearsal checklist.
2. Pastikan migration lokal KP yang tertinggal dapat terdeteksi.
3. Pastikan asset build, command diagnostic, tabel penting, dan guardrails integrasi dicek.
4. Jalankan migration KP lokal bila hanya migration KP yang pending dan diperlukan untuk menutup blocker staging.
5. Dokumentasikan hasil staging rehearsal dan blocker production yang tersisa.

Command:
- `php artisan kp:staging-rehearsal-check`
- `php artisan kp:staging-rehearsal-check --report-json`

Validasi:
- lint file baru/diubah;
- `php artisan test --filter=StagingRehearsalCheckCommandTest`;
- `php artisan migrate:status`;
- `php artisan migrate` bila ada migration KP lokal yang pending;
- `php artisan kp:staging-rehearsal-check`;
- `php artisan kp:production-readiness-gate`;
- sebelum commit, jalankan full validation: integration gap check, core mapping coverage, route list, test, build, git status.

Rekomendasi KP-23:
Production environment template dan deployment runbook final.

