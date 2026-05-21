# Prompt Tahap 06.5 - UI/UX Overhaul

Fokus tahap ini adalah memperbaiki UI/UX aplikasi SI-KP Farmasi UBP tanpa menambah fitur baru dan tanpa melanjutkan Tahap 7.

## Instruksi Utama
- Overhaul app shell, sidebar, topbar, dashboard, halaman pendaftaran KP, card, form, table, badge, alert, empty state, dan progress step.
- Gunakan arah desain modern admin dashboard untuk sistem akademik/farmasi.
- Warna utama: teal, emerald, sky/cyan, navy lembut, background terang.
- Jaga fitur lama, route, middleware, model, migration, dan logic bisnis.
- Buat design system ringan dan komponen Blade reusable bila memungkinkan.

## Acceptance
- Halaman `/mahasiswa/pendaftaran-kp` jauh lebih rapi dan profesional.
- Sidebar dan active menu jelas.
- Topbar lebih modern.
- Konten memakai max-width nyaman.
- Empty state, progress step, table, form, badge, dan button konsisten.
- `php artisan test` dan `npm run build` harus berhasil.
- Buat report `docs/reports/TAHAP_06_5_UI_UX_OVERHAUL.md`.
