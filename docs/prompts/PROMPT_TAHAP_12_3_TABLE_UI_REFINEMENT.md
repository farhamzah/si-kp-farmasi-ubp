# PROMPT TAHAP 12.3 - Table UI Refinement

Perbaiki UI tabel SI-KP Farmasi UBP tanpa membuat fitur baru dan tanpa mengubah business logic.

Fokus:
- Tabel tidak terlalu kosong.
- Kolom lebih proporsional.
- Row height lebih compact.
- Header table lebih modern.
- Tombol aksi tidak terlalu jauh.
- Table tetap responsive dengan horizontal scroll.
- Halaman `/pembimbing-dalam/mahasiswa-bimbingan` menjadi contoh utama.

Arahan:
- Audit `resources/css/app.css` dan view table penting.
- Buat/rapikan global class seperti `si-table-card`, `si-table-wrap`, `si-data-table`, dan `si-table-action`.
- Gunakan min-width table yang wajar, bukan terlalu besar.
- Gunakan colgroup pada halaman contoh jika perlu.
- Jangan ubah route, middleware, migration, service, atau logic bisnis.

Validasi:
- Jalankan `php artisan test`.
- Jalankan `npm run build`.
- Cek `git status`.
- Buat report `docs/reports/TAHAP_12_3_TABLE_UI_REFINEMENT.md`.
- Commit: `Refine table UI and responsive density`.
