# Prompt KP-36 - Report Export Coverage Analysis

Kamu adalah Codex di workspace:

`E:\Aplikasi\farmasi-ubp-workspace`

Aplikasi:

`apps/kp-farmasi`

Tugas:

Lanjutkan fitur laporan setelah Monitoring Pemilihan Tempat KP. Analisa halaman mana yang memang perlu report, lalu implementasikan report export untuk halaman yang bernilai administratif.

Fokus implementasi:

- Rekap Mahasiswa KP.
- Rekap Penempatan KP.
- Rekap Logbook KP.
- Rekap Sidang KP.
- Rekap Nilai KP.

Format wajib:

- Print Preview.
- Print via browser/printer.
- Download Word.
- Download Excel.
- Download PDF.

Guardrails:

- Jangan menulis ke Core/TU/SAFA.
- Jangan membuat token URL/autologin/SSO.
- Jangan commit `.env`, `vendor`, `node_modules`, upload storage, cache, atau log.
- Report harus role-limited untuk Admin/Koordinator.

Validasi:

- `php artisan test`
- `npm run build`
- `php artisan route:list`
- `git status --short`

