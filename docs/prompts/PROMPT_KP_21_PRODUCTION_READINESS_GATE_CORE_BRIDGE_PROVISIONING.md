# PROMPT KP-21 - Production Readiness Gate & Core Bridge Provisioning

Kamu adalah Codex di workspace:

`E:\Aplikasi\farmasi-ubp-workspace`

Aplikasi:

`apps/kp-farmasi`

Konteks:
- KP-20 sudah menambahkan manual external document linking/status lifecycle.
- Working tree dapat berisi perubahan KP-20 dan provisioning Core Bridge yang belum di-commit.
- Guardrails tetap berlaku: tidak ada write ke Core/TU/SAFA, tidak ada HTTP request ke TU/SAFA, tidak ada SSO/autologin/token URL, tidak duplicate upload dokumen, tidak menyimpan token/password/secret/signed URL/path internal.

Tugas:
Kerjakan KP-21 - Production Readiness Gate & Core Bridge Provisioning.

Tujuan:
1. Tambahkan command read-only untuk mengecek readiness production.
2. Pastikan runtime bridge TU tetap tertutup sampai approval gate final.
3. Daftarkan command provisioning Core Bridge secara eksplisit.
4. Pastikan provisioning Core Bridge hanya menulis ke database lokal KP saat `--execute --confirm-execute`.
5. Blok Core user yang masih `must_change_password` dari provisioning/login KP.
6. Dokumentasikan hasil gate dan rekomendasi tahap production berikutnya.

Command yang diharapkan:
- `php artisan kp:production-readiness-gate`
- `php artisan kp:production-readiness-gate --report-json`

Validasi:
- lint file baru/diubah;
- `php artisan test --filter=ProductionReadinessGateCommandTest`;
- `php artisan test --filter=CoreBridgeProvisioningCommandTest`;
- `php artisan kp:production-readiness-gate`;
- sebelum commit, jalankan validasi penuh: integration diagnostics, route list, test, build, dan git status.

Rekomendasi KP-22:
Staging deployment checklist dan UAT production rehearsal sebelum release production.

