# Prompt KP-33 - Core Bridge Auto-Provision & Bulk Sync

Kamu adalah Codex di workspace:
`E:\Aplikasi\farmasi-ubp-workspace`

Aplikasi:
`apps/kp-farmasi`

Tugas:
Kerjakan KP-33 - Core Bridge Auto-Provision & Bulk Sync.

Konteks:
- KP sudah live di VPS `kp.safaubp.com`.
- Core DB terbaca dari `farmasi_db`.
- Login Core bridge berhasil untuk user yang sudah punya bridge lokal.
- Masalah: user Core valid dengan app access `kp-farmasi` belum bisa login kalau belum dibuat bridge user lokal KP.

Tujuan:
1. Auto-provision bridge user lokal KP saat login pertama jika Core user valid, aktif, password benar, `must_change_password=false`, punya app access `kp-farmasi`, dan role bisa diterjemahkan ke KP.
2. Sync role lokal KP dari Core setiap login.
3. Redirect multi-role ke `/pilih-role`.
4. Tambahkan command bulk sync `php artisan kp:provision-core-bridge-users`.
5. Default command bulk harus dry-run; execute wajib `--execute --confirm-execute`.
6. Tambahkan tests dan dokumentasi report.

Guardrails:
- Jangan menulis ke database Core.
- Jangan copy password Core.
- Jangan membuat SSO/autologin/token URL.
- Jangan membuat user KP jika Core tidak punya app access `kp-farmasi`.
- Jangan bypass `must_change_password`.

Validasi wajib:
```bash
php artisan test
npm run build
php artisan kp:production-readiness-gate
git status
```

Setelah selesai:
- Commit dan push ke `origin main`.
- Berikan instruksi pull VPS dan command bulk sync.
