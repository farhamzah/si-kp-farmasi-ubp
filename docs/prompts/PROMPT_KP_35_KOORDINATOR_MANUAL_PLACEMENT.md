# Prompt KP-35 - Koordinator Manual Placement for Non-War-Ticket Students

Kamu adalah Codex di workspace:

`E:\Aplikasi\farmasi-ubp-workspace`

Aplikasi:

`apps/kp-farmasi`

Konteks:

- KP Farmasi sudah live di `kp.safaubp.com`.
- War ticket pemilihan tempat KP tetap mengikuti jadwal periode.
- Ada kasus mahasiswa tidak ikut war ticket karena ditunjuk langsung oleh Koordinator.
- Core tetap source of truth identitas/master data.
- Transaksi pemilihan dan penempatan KP tetap lokal KP.

Tugas:

Kerjakan KP-35 - Koordinator Manual Placement for Non-War-Ticket Students.

Tujuan:

1. Tambahkan halaman Koordinator/Admin untuk memilihkan tempat KP secara manual.
2. Manual placement hanya boleh untuk mahasiswa yang pendaftarannya sudah terverifikasi dan dokumen wajibnya sudah disetujui.
3. Manual placement boleh dilakukan di luar jadwal war ticket.
4. Tetap hormati kuota tempat, status kuota terbuka, periode yang sama, dan cegah duplicate active selection/assignment.
5. Setelah manual selection dibuat, Koordinator/Admin bisa membuat penempatan KP dari detail pilihan.
6. Tambahkan test untuk alur manual placement dan validasi duplicate/unverified.

Guardrails:

- Jangan menulis ke Core/TU/SAFA.
- Jangan copy password Core.
- Jangan membuat SSO/autologin/token URL.
- Jangan bypass sisa kuota.
- Jangan bypass verifikasi dokumen pendaftaran.
- Jangan commit `.env`, `vendor`, `node_modules`, atau upload storage.

Validasi:

- `php artisan test tests\Feature\KpPlaceSelectionWarTicketTest.php`
- `php artisan route:list`
- `php artisan test`
- `npm run build`
- `git status --short`

