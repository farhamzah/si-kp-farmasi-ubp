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

## 15. Aturan Penempatan KP
- Penempatan KP resmi dibuat dari selection tempat yang masih aktif.
- Satu mahasiswa hanya boleh memiliki satu assignment non-batal per periode.
- Penentuan dan perubahan pembimbing wajib melalui `KpAssignmentService`.
- Pembimbing Dalam harus berasal dari `lecturers` yang user-nya memiliki role `pembimbing_dalam`.
- Pembimbing Lapangan harus berasal dari `field_supervisors` yang user-nya memiliki role `pembimbing_lapangan`.
- Jika pembimbing lapangan dipilih, mapping tempat-pembimbing di `kp_place_field_supervisors` harus dijaga.
- Mahasiswa hanya melihat penempatannya sendiri; pembimbing hanya melihat assignment yang ditugaskan kepadanya.
- Semua perubahan assignment wajib dicatat di `kp_assignment_logs`.

## 16. Aturan Logbook KP
- Logbook KP hanya dapat dibuat oleh mahasiswa yang memiliki assignment aktif atau berjalan.
- Mahasiswa hanya boleh mengakses dan mengubah logbook miliknya sendiri.
- Logbook status `disetujui` tidak boleh diedit oleh mahasiswa.
- Validasi logbook wajib melalui `KpLogbookService`.
- Pembimbing Lapangan hanya boleh validasi logbook assignment yang ditugaskan kepadanya.
- Pembimbing Dalam hanya boleh memantau dan memberi komentar pada logbook mahasiswa bimbingannya.
- Admin dan Koordinator KP boleh memonitor semua logbook.
- Bukti kegiatan wajib divalidasi server-side, disimpan di storage non-public, dan diunduh lewat route protected.
- Semua perubahan status, submit, validasi, revisi, penolakan, upload/ganti bukti, dan komentar wajib dicatat di `kp_logbook_logs`.

## 17. Aturan Laporan Akhir KP
- Laporan akhir hanya dapat dibuat oleh mahasiswa yang memiliki assignment aktif atau berjalan.
- Satu assignment hanya memiliki satu record `kp_final_reports`, sedangkan revisi disimpan sebagai versi baru di `kp_final_report_files`.
- Mahasiswa hanya boleh mengelola laporan miliknya sendiri dan tidak boleh upload setelah laporan disetujui.
- Review laporan akhir wajib melalui `KpFinalReportService`.
- Pembimbing Dalam hanya boleh approve/revisi/tolak laporan mahasiswa bimbingannya.
- Admin dan Koordinator KP hanya melakukan monitoring pada Tahap 8.
- File laporan wajib divalidasi server-side, disimpan di storage non-public, dan diunduh melalui route protected.
- Semua upload, submit, review, revisi, penolakan, approval, dan download penting harus dicatat di `kp_final_report_logs` bila relevan.

## 18. Aturan Sidang KP
- Pengajuan sidang hanya dapat dilakukan jika assignment aktif/berjalan dan laporan akhir sudah disetujui.
- Mahasiswa hanya boleh mengajukan dan melihat sidang miliknya sendiri.
- Penjadwalan sidang wajib melalui `KpExamService`.
- Pembimbing Dalam otomatis berasal dari assignment.
- Penguji wajib berasal dari `lecturers` yang user-nya memiliki role `penguji`.
- Penguji tidak boleh sama dengan Pembimbing Dalam.
- Admin dan Koordinator KP dapat memonitor, menjadwalkan, menjadwalkan ulang, membatalkan, dan menandai sidang selesai.
- Pembimbing Dalam dan Penguji hanya boleh melihat jadwal sidang yang terkait dengan profil lecturer masing-masing.
- Semua submit, review, schedule, reschedule, cancel, dan complete wajib dicatat di `kp_exam_logs`.

## 19. Aturan Penilaian KP
- Komponen penilaian dikelola per periode oleh Admin/Koordinator.
- Input nilai wajib melalui `KpAssessmentService`.
- Pembimbing Dalam hanya boleh menilai assignment dengan `internal_supervisor_id` miliknya.
- Pembimbing Lapangan hanya boleh menilai assignment dengan `field_supervisor_id` miliknya.
- Penguji hanya boleh menilai exam dengan `examiner_id` miliknya.
- Nilai harus divalidasi 0-100 dan weighted score dihitung dari bobot komponen.
- Nilai final/published tidak boleh diubah penilai; unlock hanya Admin/Koordinator.
- Mahasiswa hanya melihat nilai setelah dipublish.
- Semua perubahan penting nilai wajib dicatat di `kp_score_logs`.

## 20. Aturan Rekap, Export, dan QA
- Rekap dan export hanya boleh diakses Admin/Koordinator.
- Export Excel harus memiliki header jelas dan tidak membuka data ke role lain.
- Setiap tahap besar wajib menjalankan `php artisan migrate`, `php artisan test`, dan `npm run build`.
- UI/UX harus dijaga konsisten: route aktif tidak overlap, menu yang sudah dibuat tidak diberi badge "Segera", table responsive, dan empty state informatif.
- Report tahap wajib mencatat hasil test/build, kendala, dan status git.

## 21. Aturan Stabilisasi dan Demo
- Sebelum commit, wajib menjalankan test dan build yang relevan; untuk tahap akhir gunakan `php artisan test` dan `npm run build`.
- Bugfix tidak boleh menghapus atau melemahkan fitur existing tanpa alasan yang dicatat di report.
- Error page 403, 404, 419, dan 500 harus user-friendly, berbahasa Indonesia, dan tidak menampilkan detail teknis.
- Route baru wajib memiliki middleware `auth` dan role middleware sesuai kebutuhan.
- Seeder demo wajib idempotent, aman dijalankan berulang, dan tidak menjadi rekomendasi password production.
- Route download file baru wajib tetap melewati controller protected dan validasi ownership/role.

## 22. Aturan Production Readiness
- Route baru wajib dilindungi middleware `auth`, `active`, `role.selected`, dan role spesifik jika route tersebut hanya untuk area tertentu.
- File upload wajib disimpan non-public dan hanya boleh diakses melalui route protected, kecuali asset publik yang memang dirancang untuk publik.
- Deployment production wajib memakai `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` domain resmi, dan cookie secure saat memakai HTTPS.
- Dokumentasi report wajib dibuat untuk setiap tahap atau patch rilis agar keputusan teknis dan hasil QA tetap terlacak.
- Sebelum commit rilis, wajib menjalankan `php artisan test` dan `npm run build`; untuk tahap production readiness juga jalankan `php artisan optimize:clear` dan `php artisan migrate`.
- Akun demo dan password default hanya untuk development/UAT internal; production wajib mengganti atau menonaktifkannya.

## 23. Aturan Avatar dan Identitas User
- Upload avatar wajib divalidasi server-side sebagai gambar JPG, JPEG, PNG, atau WebP dengan ukuran maksimal yang ditentukan fitur.
- SVG tidak boleh diizinkan sebagai avatar karena berisiko membawa konten aktif.
- Avatar user disimpan melalui storage yang aman dan path file tidak boleh diekspos langsung ke UI.
- Jika avatar tidak tersedia, UI wajib menampilkan inisial user yang rapi.
- Topbar wajib menjaga nama user dan role panjang dengan truncate agar tidak overlap dengan tombol aksi/logout.
