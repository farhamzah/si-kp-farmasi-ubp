# Spesifikasi Awal Aplikasi

## Nama Aplikasi
SI-KP Farmasi UBP - Sistem Informasi Kerja Praktek Farmasi UBP.

## Tujuan Aplikasi
Menyediakan sistem informasi terpusat untuk mengelola proses Kerja Praktek Farmasi UBP secara lebih rapi, aman, dan mudah digunakan oleh mahasiswa, admin, koordinator, pembimbing, dan penguji.

## Role Pengguna
- Mahasiswa
- Admin
- Koordinator KP
- Pembimbing Dalam / Dosen
- Pembimbing Luar / Lapangan
- Penguji

Sistem mendukung multi-role sehingga satu user dapat memiliki lebih dari satu peran.

## Prinsip Aplikasi
- Cepat dibuat dan mudah dikembangkan.
- Murah dihosting dan tidak bergantung pada layanan berbayar eksternal.
- Aman melalui autentikasi, middleware role, dan validasi server-side.
- UI/UX bersih, modern, responsive, dan berbahasa Indonesia.
- UI menggunakan design system modern yang konsisten untuk layout, sidebar, topbar, card, form, table, badge, alert, empty state, dan progress step.
- Modul dibangun bertahap agar stabil.

## Design System UI
- Arah visual aplikasi adalah modern admin dashboard untuk sistem kampus/farmasi.
- Warna utama menggunakan teal, emerald, sky/cyan, dan navy lembut dengan background terang.
- Layout utama menggunakan content container dengan lebar maksimum nyaman agar halaman tidak melebar berlebihan.
- Card menggunakan permukaan putih, border halus, radius konsisten, dan shadow lembut.
- Button, badge, alert, table, form input, dan empty state dibuat konsisten agar mudah dikembangkan pada tahap berikutnya.
- Tampilan harus responsive untuk desktop, tablet, dan mobile, serta tetap menjaga kontras dan aksesibilitas.

## Alur Besar Aplikasi
1. User login menggunakan email dan password.
2. Sistem memvalidasi status akun dan role.
3. User single-role langsung masuk dashboard sesuai role.
4. User multi-role memilih akses terlebih dahulu.
5. Role aktif disimpan ke session.
6. User mengakses dashboard dan menu sesuai role aktif.
7. User dapat logout dan session role aktif dihapus.

## Modul Besar Aplikasi
- Autentikasi dan manajemen role.
- Dashboard per role.
- Profil user.
- Manajemen user dan import Excel.
- Pendaftaran KP.
- Berkas persyaratan.
- Tempat KP dan kuota.
- Logbook.
- Laporan akhir.
- Sidang.
- Penilaian dan rekap.

## Fondasi Manajemen User dan Profil
- Akun pengguna dibuat oleh Admin melalui form manual atau import Excel, bukan registrasi mandiri.
- Admin dapat memberi satu atau banyak role pada user.
- Admin dapat mengelola status akun, reset password development, dan melihat kelengkapan profil.
- Admin dapat import Excel untuk mahasiswa, dosen, pembimbing lapangan, atau mixed.
- User melengkapi profil masing-masing setelah login.
- Data profil dipisah ke tabel `students`, `lecturers`, dan `field_supervisors`.
- Modul manajemen user dan profil menjadi fondasi untuk modul KP berikutnya seperti pendaftaran, tempat KP, logbook, sidang, dan penilaian.

## Fondasi Periode, Tempat, dan Kuota KP
- Admin dan Koordinator KP dapat mengelola Periode KP.
- Periode KP menyimpan jadwal pendaftaran, verifikasi dokumen, pemilihan tempat, tanggal pelaksanaan KP, status, dan deskripsi.
- Admin dan Koordinator KP dapat mengelola Tempat KP beserta tipe, alamat, kota, kontak, email, dan status aktif/nonaktif.
- Admin dan Koordinator KP dapat mengelola Kuota Tempat KP per periode.
- Kuota tempat KP unik berdasarkan kombinasi periode dan tempat.
- Log perubahan kuota disimpan untuk audit perubahan kuota, status buka/tutup, dan user yang melakukan aksi.
- Kuota ini akan dipakai pada tahap pemilihan tempat KP/war ticket.

## Fondasi Pendaftaran KP dan Verifikasi Berkas
- Admin dan Koordinator KP dapat mengatur persyaratan dokumen per periode KP.
- Mahasiswa dapat membuat pendaftaran KP pada periode yang pendaftarannya sedang dibuka.
- Mahasiswa wajib melengkapi profil sebelum mendaftar KP.
- Mahasiswa dapat upload berkas persyaratan melalui storage Laravel non-public.
- Download dokumen dilakukan melalui route yang dilindungi autentikasi dan role.
- Admin dan Koordinator KP dapat review dokumen, memberi catatan revisi, menolak dokumen, dan memverifikasi pendaftaran.
- Status pendaftaran meliputi `draft`, `menunggu_verifikasi`, `revisi`, `terverifikasi`, `ditolak`, dan `dibatalkan`.
- Status dokumen meliputi `belum_upload`, `menunggu`, `disetujui`, `revisi`, dan `ditolak`.
- Mahasiswa hanya dapat mengikuti pemilihan tempat KP/war ticket setelah pendaftarannya terverifikasi dan dokumen wajib disetujui.

## Fondasi Pemilihan Tempat KP / War Ticket
- Pemilihan tempat KP menggunakan model first come first served berbasis kuota.
- Hanya mahasiswa dengan pendaftaran `terverifikasi` yang dapat memilih tempat.
- Mahasiswa hanya dapat memilih pada rentang `selection_start_at` sampai `selection_end_at` periode terkait.
- Pilihan tempat terkunci untuk mahasiswa dan tidak dapat diubah sendiri.
- Admin dan Koordinator KP dapat membatalkan atau memindahkan pilihan dengan alasan.
- Kuota penuh atau ditutup tidak dapat dipilih.
- Mahasiswa dapat masuk daftar tunggu jika belum mendapat tempat karena kuota penuh.
- Semua aksi pemilihan, kegagalan pemilihan, daftar tunggu, cancel, dan move dicatat di log pemilihan.
- Proteksi race condition dilakukan dengan database transaction, `lockForUpdate()` pada row kuota, validasi ulang dalam transaksi, dan constraint `active_key` untuk mencegah dua pilihan aktif pada periode yang sama.

## Fondasi Penempatan KP dan Pembimbing
- Mahasiswa yang sudah memilih tempat KP dapat dibuatkan penempatan KP resmi.
- Setiap penempatan memiliki satu tempat KP, satu Pembimbing Dalam/Dosen, dan satu Pembimbing Lapangan/Luar.
- Koordinator KP menentukan Pembimbing Dalam.
- Admin dan Koordinator KP dapat menentukan Pembimbing Lapangan.
- Pembimbing Dalam harus memiliki role `pembimbing_dalam`.
- Pembimbing Lapangan harus memiliki role `pembimbing_lapangan`.
- Mahasiswa dapat melihat penempatan miliknya sendiri.
- Pembimbing Dalam hanya melihat mahasiswa bimbingannya.
- Pembimbing Lapangan hanya melihat mahasiswa KP yang ditugaskan kepadanya.
- Assignment menjadi dasar Tahap 7 Logbook KP.

## Fondasi Logbook KP
- Mahasiswa dapat mengisi logbook kegiatan setelah memiliki assignment/penempatan KP berstatus aktif atau berjalan.
- Logbook terhubung langsung ke `kp_assignments` sehingga akses mahasiswa, pembimbing dalam, dan pembimbing lapangan dapat dibatasi berdasarkan penempatan.
- Mahasiswa dapat menyimpan logbook sebagai draft, mengedit status draft/revisi, submit untuk validasi, serta upload bukti kegiatan opsional.
- Bukti kegiatan disimpan di storage Laravel non-public dan hanya diunduh melalui route yang dilindungi autentikasi dan role.
- Pembimbing Lapangan memvalidasi logbook mahasiswa yang ditugaskan kepadanya dengan status disetujui, revisi, atau ditolak.
- Pembimbing Dalam memantau logbook mahasiswa bimbingan dan dapat memberi komentar/catatan pemantauan.
- Admin dan Koordinator KP dapat memonitor seluruh logbook dan memberi komentar monitoring.
- Semua perubahan status, upload/ganti bukti, submit, validasi, penolakan, revisi, dan komentar penting dicatat pada `kp_logbook_logs`.
- Logbook menjadi dasar pemantauan pelaksanaan KP dan fondasi untuk Tahap 8 Laporan Akhir KP.

## Fondasi Laporan Akhir KP
- Mahasiswa dengan assignment aktif/berjalan dapat upload Laporan Akhir KP.
- Satu assignment memiliki satu status laporan akhir aktif pada `kp_final_reports`.
- Setiap upload laporan atau revisi disimpan sebagai versi baru di `kp_final_report_files`.
- Mahasiswa dapat submit laporan untuk review setelah file tersedia.
- Pembimbing Dalam/Dosen menjadi reviewer utama dan dapat menyetujui, meminta revisi, atau menolak laporan.
- Laporan yang sudah disetujui menjadi syarat untuk tahap berikutnya, yaitu Pengajuan dan Penjadwalan Sidang KP.
- Admin dan Koordinator KP dapat memonitor seluruh laporan akhir dan melihat log aktivitas.
- File laporan disimpan aman di storage non-public dan hanya dapat diakses melalui route protected sesuai role/ownership.
- Semua aktivitas penting laporan akhir dicatat pada `kp_final_report_logs`.

## Fondasi Pengajuan dan Penjadwalan Sidang KP
- Mahasiswa dapat mengajukan Sidang KP setelah Laporan Akhir berstatus `disetujui`.
- Satu assignment memiliki satu pengajuan/sidang sederhana pada tahap ini.
- Admin dan Koordinator KP dapat memonitor pengajuan, mereview, menjadwalkan, membatalkan, dan menandai sidang selesai.
- Sidang memiliki dua penilai utama: Pembimbing Dalam dari assignment dan satu Penguji Sidang.
- Penguji harus berasal dari lecturer yang user-nya memiliki role `penguji`.
- Penguji tidak boleh sama dengan Pembimbing Dalam.
- Mahasiswa, Pembimbing Dalam, dan Penguji dapat melihat jadwal sidang sesuai hak akses masing-masing.
- Pembimbing Lapangan belum memiliki akses modul sidang pada tahap ini.
- Semua aktivitas pengajuan, review, penjadwalan, cancel, dan complete dicatat pada `kp_exam_logs`.
- Bugfix UI Tahap 9 memperbaiki active state sidebar mahasiswa dan halaman login agar lebih fit di viewport desktop/laptop.
