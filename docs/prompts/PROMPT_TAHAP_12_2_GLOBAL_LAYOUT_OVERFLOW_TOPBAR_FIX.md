# PROMPT TAHAP 12.2 - Global Layout Overflow dan Topbar Fix

Perbaiki bug UI global SI-KP Farmasi UBP tanpa membuat fitur baru.

Fokus:
- Layout global agar tidak memotong konten/table.
- Horizontal scroll pada tabel yang kolomnya banyak.
- Topbar kanan agar nama user dan role panjang tidak menumpuk dengan tombol logout.

Target:
- Root app dan main shell memakai `min-w-0` dan overflow yang benar.
- Table/card lebar memakai wrapper `overflow-x-auto`.
- Kolom kanan tabel tidak terpotong.
- User pill di topbar memiliki max-width dan truncate.
- Role panjang dipendekkan di topbar.
- Logout tetap `flex-none` dan dapat diklik.
- Sidebar scroll fix Tahap 12.1 tetap aman.
- Jangan ubah logic bisnis, route, middleware, migration, atau service.

Validasi:
- Jalankan `php artisan test`.
- Jalankan `npm run build`.
- Cek `git status`.
- Buat report `docs/reports/TAHAP_12_2_GLOBAL_LAYOUT_OVERFLOW_TOPBAR_FIX.md`.
- Commit: `Fix global layout overflow and topbar`.
