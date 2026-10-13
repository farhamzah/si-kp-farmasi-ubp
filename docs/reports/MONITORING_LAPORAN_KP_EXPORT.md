# Ekspor Monitoring Laporan KP

- Koordinator dan admin dapat membuka preview, print A4 lanskap, serta mengunduh PDF, Excel, dan Word (`.doc`) dari halaman Monitoring Laporan.
- Semua format memakai filter dan urutan yang sama dengan daftar di layar, tetapi memuat seluruh hasil, bukan hanya halaman paginasi aktif.
- Kolom meliputi mahasiswa, NIM, periode, tempat, kedua pembimbing beserta status bimbingan dan persetujuan laporan, status laporan, dan versi.
- Word mengikuti pola aplikasi yang sudah ada: dokumen HTML berformat `.doc` agar dapat dibuka di Word tanpa dependensi baru.
- Route ekspor berada di grup management yang memeriksa autentikasi dan role admin/koordinator.

Verifikasi: `php artisan test --filter=KpFinalReportTest`, `php artisan test --filter=KpInternalSupervisorWorkloadReportTest`, dan `npm run build` lulus. Suite penuh: 312 lulus, 1 gagal pada `StabilizationDemoSeederTest` karena fixture demo masih mengharapkan nilai mahasiswa tampil tanpa seluruh validasi nilai.

Deploy: `git pull`, `npm run build`, `php artisan optimize:clear`. Tidak ada migrasi.
