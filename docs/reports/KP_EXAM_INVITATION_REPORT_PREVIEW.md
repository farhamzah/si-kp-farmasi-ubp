# Preview Laporan dari Undangan Sidang

- Inbox Undangan Sidang menampilkan tombol **Preview Laporan** bila laporan mahasiswa memiliki tautan final atau berkas yang diunggah.
- Halaman preview juga dapat dibuka dari toolbar surat undangan. Tautan Drive final diprioritaskan jika tersedia; bila tidak, PDF lokal tampil langsung. Berkas non-PDF tersedia sebagai unduhan.
- Preview dan unduhan lokal memeriksa role aktif serta keterkaitan pengguna dengan sidang. Penguji yang tidak ditugaskan tidak mendapat akses walaupun mengetahui URL.
- Berkas lokal dilayani lewat route terlindungi, tidak dipublikasikan di direktori web. Tautan Google Drive tetap memerlukan izin berbagi dari pemilik file kepada penguji.
- Fitur ini menambah akses laporan di inbox aplikasi; tidak mengirim email otomatis atau menggabungkan berkas laporan ke PDF surat.

Verifikasi: `php artisan test --filter=KpExamSchedulingTest` (33 lulus), `npm run build` (lulus). Tidak ada migrasi.
