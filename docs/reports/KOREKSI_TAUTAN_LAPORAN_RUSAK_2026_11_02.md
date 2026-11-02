# Koreksi tautan laporan yang terhapus

## Perubahan

- Laporan sebelum sidang yang sedang direview atau sudah disetujui dapat dikembalikan koordinator untuk penggantian tautan Drive, selama belum ada sidang yang tercatat.
- Pengembalian memerlukan alasan, menyimpan tautan lama dan pelaku di `kp_final_report_logs`, menghapus tautan rusak dari laporan aktif, serta mereset persetujuan kedua pembimbing. Mahasiswa menyimpan tautan baru dan submit ulang.
- Jika sidang sudah tercatat, persetujuan laporan sebelum sidang tidak dibuka ulang. Mahasiswa memakai bagian Dokumen Final Pascasidang setelah berita acara tersedia; koordinator memvalidasinya dari menu Dokumen Pascasidang.
- Label dan petunjuk di halaman mahasiswa membedakan kedua dokumen tersebut dan menyediakan tautan lompat ke form pascasidang.

## Operasional

Koordinator membuka Monitoring Laporan > Detail, memeriksa tautan, mengisi alasan, lalu memilih Kembalikan Tautan Rusak. Mahasiswa mengunggah file pengganti ke Drive, menyimpan link file baru di Laporan Akhir, dan menekan Submit Final untuk Review. Kedua pembimbing atau koordinator kemudian meninjau ulang.

Untuk laporan setelah sidang, mahasiswa mengunggah PDF bertanda tangan ke folder Drive Pascasidang dan mengirim link melalui Dokumen Final Pascasidang. Jika berita acara belum terbit, form itu belum tersedia. Nilai tetap mengikuti validasi dokumen pascasidang dan syarat lainnya.

## Verifikasi

- `php artisan test --filter=KpFinalReportTest`
- `php artisan test --filter=StudentScoreVisibilityTest`
- `php artisan view:cache`
- `npm run build`
- `php artisan test --compact`: 321 lulus, 1 gagal pada `StabilizationDemoSeederTest::test_demo_users_can_open_recaps_dashboards_and_score_pages`. Tes demo tersebut masih mengharapkan nilai mahasiswa terlihat sebelum syarat akses lengkap; kegagalan ini sudah ada sebelum patch dan tidak terkait koreksi tautan.

Tidak ada migrasi database. File lama di Google Drive tidak dipulihkan oleh aplikasi; pemulihan dari Sampah Drive tetap dilakukan oleh pemilik Drive bila masih memungkinkan.
