# AGENTS.md - SI-KP Farmasi UBP

## 1. Nama Project
SI-KP Farmasi UBP - Sistem Informasi Kerja Praktek Farmasi UBP.

## 2. Tujuan Aplikasi
Aplikasi ini dibuat untuk mengelola proses Kerja Praktek Farmasi UBP mulai dari administrasi pengguna, pendaftaran, berkas, tempat KP, bimbingan, logbook, laporan, sidang, sampai penilaian.

## 3. Prinsip Pengembangan
- Cepat dibuat, mudah digunakan, murah dikembangkan, aman, dan mudah dihosting.
- Utamakan fitur modular, sederhana, dan siap dikembangkan bertahap.
- Ikuti pola Laravel bawaan sebelum menambah abstraksi baru.
- Hindari kompleksitas yang belum dibutuhkan tahap berjalan.

## 4. Stack Teknologi
- Laravel 12 atau versi stabil terbaru yang tersedia di environment.
- Blade, Tailwind CSS, Vite.
- MySQL/MariaDB untuk development dan production.
- PHPUnit untuk feature test.
- Storage lokal untuk tahap awal jika nanti ada file upload.

## 5. Aturan Keamanan
- Semua fitur utama wajib dilindungi autentikasi.
- Role dan izin akses wajib dicek di server-side melalui middleware atau policy.
- Password wajib menggunakan hash Laravel.
- User dengan status inactive tidak boleh mengakses sistem.
- Session `active_role` wajib divalidasi terhadap role user.
- Jangan hardcode data sensitif selain akun demo development di seeder.
- Gunakan validasi request dan CSRF protection bawaan Laravel.

## 6. Aturan UI/UX
- Gunakan bahasa Indonesia pada UI.
- Desain harus bersih, profesional, responsive, dan mudah dipahami.
- Warna utama bernuansa teal/biru/hijau yang cocok untuk farmasi dan kampus.
- Sidebar, topbar, card, badge status, dan feedback pesan harus konsisten.
- Placeholder fitur boleh ditampilkan, tetapi beri label yang jelas seperti "Segera tersedia".

## 7. Aturan Penamaan File
- Gunakan nama class, controller, middleware, seeder, dan model sesuai konvensi Laravel.
- Route name harus jelas dan stabil.
- File dokumentasi Markdown menggunakan huruf besar dan underscore untuk laporan tahap.
- Hindari nama file ambigu seperti `new`, `fix`, atau `temp`.

## 8. Aturan Dokumentasi
- Simpan spesifikasi di `docs/specs`.
- Simpan prompt/ringkasan instruksi tahap di `docs/prompts`.
- Simpan laporan pengerjaan tahap di `docs/reports`.
- Dokumentasi harus ringkas, jujur, dan mudah dipakai developer berikutnya.

## 9. Catatan Perubahan Besar
Setiap perubahan besar wajib dicatat di `docs/reports`, termasuk fitur yang dibuat, file penting, cara menjalankan, cara testing, kendala, dan rekomendasi tahap berikutnya.

## 10. Modularitas
Setiap fitur harus dibuat modular dan mudah dikembangkan. Pisahkan tanggung jawab controller, middleware, model, seeder, support class, view, dan test sesuai kebutuhan.

## 11. Aturan Modul User dan Import
- Akun dibuat oleh Admin, bukan registrasi mandiri.
- Import user harus melalui validasi server-side dan menyimpan riwayat batch serta error per baris.
- File import tidak boleh disimpan di public folder.
- Password awal demo/development boleh `password`, tetapi tidak boleh direkomendasikan untuk production.
- Profil user dipisah ke tabel `students`, `lecturers`, dan `field_supervisors`.
- User mengisi dan memperbarui profil sendiri, sedangkan identitas kunci seperti NIM, NIDN/NIP, dan nomor pegawai dikelola Admin.

## 12. Aturan Modul KP
- Periode KP, Tempat KP, dan Kuota Tempat KP dikelola oleh Admin dan Koordinator KP.
- Setiap kuota wajib unik berdasarkan kombinasi periode dan tempat.
- Perubahan kuota wajib dicatat di `kp_quota_logs`.
- Helper kuota seperti `filledCount`, `remainingQuota`, dan `isFull` harus tetap kompatibel dengan tahap pemilihan tempat berikutnya.
- Tempat KP yang sudah memiliki kuota sebaiknya dinonaktifkan, bukan dihapus.

## 13. Aturan Pendaftaran dan Berkas KP
- Pendaftaran KP milik mahasiswa wajib dibatasi berdasarkan user login dan profil `students`.
- Mahasiswa hanya boleh mengakses, upload, dan download dokumen pendaftarannya sendiri.
- Admin dan Koordinator KP boleh mengelola persyaratan dokumen serta memverifikasi pendaftaran dan berkas.
- File upload KP wajib divalidasi server-side, disimpan di storage non-public, dan diunduh melalui route protected.
- Status pendaftaran dan dokumen tidak boleh diubah langsung dari request bebas; gunakan controller/form request yang mencatat log ke `kp_registration_logs`.
- Mahasiswa hanya eligible untuk pemilihan tempat jika pendaftaran sudah `terverifikasi` dan semua dokumen wajib disetujui.

## 14. Aturan Pemilihan Tempat KP
- Pemilihan tempat KP menggunakan first come first served berdasarkan waktu server.
- Mahasiswa hanya boleh memilih jika pendaftaran KP sudah `terverifikasi` dan jadwal pemilihan periode sedang dibuka.
- Logic pemilihan wajib melalui `KpPlaceSelectionService`, memakai transaction dan `lockForUpdate()` pada kuota.
- Satu mahasiswa hanya boleh punya satu selection aktif per periode; gunakan validasi aplikasi dan constraint `active_key`.
- Mahasiswa tidak boleh cancel atau move selection sendiri.
- Admin dan Koordinator KP dapat cancel/move dengan alasan dan semua aksi wajib masuk `kp_selection_logs`.
- Daftar tunggu dibuat saat mahasiswa eligible belum mendapat tempat karena kuota penuh.
