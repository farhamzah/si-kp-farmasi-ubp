# PROMPT TAHAP 12.1 - Sidebar Scroll Fix

Perbaiki bug UI sidebar SI-KP Farmasi UBP yang tidak bisa scroll ketika menu panjang, terutama pada role Admin dan Koordinator KP.

Target:
- Sidebar tetap setinggi viewport.
- Header/logo tetap di atas.
- Area menu/navigation bisa scroll vertikal.
- Menu bawah tidak terpotong.
- Active menu tetap jelas.
- Desktop dan mobile tetap rapi.
- Jangan mengubah logic role/menu.
- Jangan merusak behavior sidebar yang sudah ada.

Implementasi:
- Audit layout utama, sidebar partial/component, `resources/views/layouts/*`, `resources/views/components/*`, dan `resources/css/app.css`.
- Terapkan struktur Tailwind seperti `h-screen flex flex-col overflow-hidden`, `flex-none`, `flex-1 min-h-0 overflow-y-auto`.
- Tambahkan scrollbar halus lokal bila perlu tanpa dependency baru.

Validasi:
- Jalankan `php artisan test`.
- Jalankan `npm run build`.
- Cek `git status`.
- Buat report `docs/reports/TAHAP_12_1_SIDEBAR_SCROLL_FIX.md`.
- Commit: `Fix scrollable sidebar navigation`.
