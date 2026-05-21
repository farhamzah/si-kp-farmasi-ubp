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
