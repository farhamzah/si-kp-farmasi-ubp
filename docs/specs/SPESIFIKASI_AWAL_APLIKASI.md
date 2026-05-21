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
- Modul dibangun bertahap agar stabil.

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
