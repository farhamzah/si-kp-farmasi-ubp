# Patch Antrian Nilai dan Filter Pengingat - 13 Oktober 2026

## Perubahan

- Antrian penguji hanya menampilkan sidang yang masih memerlukan nilai dari akun tersebut. Nilai yang sudah disubmit berpindah ke Riwayat; tugas ketua sidang untuk berita acara berada pada tab terpisah.
- Halaman nilai sidang menyediakan tautan kembali ke antrian dan tidak lagi menggandakan pesan sukses.
- Monitoring penilai tertunda dan laporan preview/PDF dapat difilter menurut peran dan tahap sidang. Tahap "Sidang selesai" hanya berasal dari status sidang selesai; jadwal yang sudah lewat ditandai terpisah.
- Pengiriman email massal maupun per penilai mengikuti hasil filter yang terlihat. Penguji sidang yang ditunda tidak dimasukkan ke pengingat.
- Akun berperan penguji tanpa profil dosen tidak dapat melihat atau membuka sidang yang belum memiliki ketua.

## Verifikasi

- `php artisan test tests/Feature/KpAssessmentAndFinalScoreTest.php --compact`: 22 lulus, 235 assertions.
- `php artisan test --compact`: 311 lulus, 1 gagal. Kegagalan pada `StabilizationDemoSeederTest::test_demo_users_can_open_recaps_dashboards_and_score_pages`, yang masih mengharapkan nilai demo mahasiswa terbuka tanpa seluruh validasi nilai. Area tersebut tidak diubah oleh patch ini.
- `npm run build`: berhasil.
- `git diff --check`: bersih.

Tidak ada migrasi database pada patch ini. Folder `docs/manuals/` yang sudah ada tidak diubah.
