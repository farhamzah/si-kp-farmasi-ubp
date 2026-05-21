# PROMPT TAHAP 11 - Rekap, Export, dan Polishing Akhir

Tahap 11 memfinalisasi MVP SI-KP Farmasi UBP dengan rekap data, export Excel, polishing dashboard, QA/hardening dasar, dan dokumentasi panduan.

## Tujuan
- Membuat rekap mahasiswa KP, penempatan, logbook, sidang, dan nilai.
- Menyediakan export Excel untuk data rekap utama.
- Memperbaiki dashboard akhir setiap role agar lebih informatif.
- Melakukan audit UI/UX dan hardening keamanan dasar.
- Membuat panduan penggunaan per role dan panduan deployment.
- Menyatakan status MVP siap demo jika test/build passed.

## Batasan
Tidak membuat tanda tangan digital, sertifikat otomatis, WhatsApp, email production, pembayaran, API mobile, atau dokumen resmi.

## Acceptance Utama
- Admin/Koordinator dapat membuka rekap dan export.
- Role lain tidak bisa membuka rekap/export management.
- Dashboard semua role bisa dibuka.
- Dokumentasi panduan tersedia.
- `php artisan migrate`, `php artisan test`, dan `npm run build` berhasil.
