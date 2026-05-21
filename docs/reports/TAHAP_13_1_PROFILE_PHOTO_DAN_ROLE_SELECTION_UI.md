# TAHAP 13.1 - Profile Photo dan Role Selection UI

## 1. Ringkasan Pengerjaan
Tahap 13.1 menambahkan foto profil untuk semua user dan memoles tampilan identitas user pada topbar, dashboard, profil, dan halaman pilih role. Perubahan dibuat ringan, terarah, dan tidak mengubah logic bisnis utama modul KP.

## 2. Fitur Foto Profil
Foto profil ditambahkan pada tabel `users` melalui migration baru dengan metadata:
- `avatar_path`
- `avatar_disk`
- `avatar_original_filename`
- `avatar_mime`
- `avatar_size`

Route avatar:
- `GET /profile/avatar`
- `POST /profile/avatar`
- `DELETE /profile/avatar`

Jika user belum memiliki foto, aplikasi menampilkan avatar inisial dari nama user. Gelar umum seperti `Dr` dan `Prof` tidak dipakai sebagai inisial agar nama seperti `Dr. Rina Kartika` tampil sebagai `RK`.

## 3. Redesign Halaman Pilih Role
Halaman `/pilih-role` dibuat lebih modern:
- background soft gradient,
- header card dengan avatar/foto user, nama, email, dan tombol logout,
- alert informasi multi-role,
- role card responsive dengan icon, label pendek, deskripsi, badge akses, dan tombol `Masuk`,
- hover state lebih jelas dan CTA memakai warna teal/cyan.

Role panjang seperti `Pembimbing Dalam / Dosen` diringkas menjadi `Pembimbing Dalam` pada konteks UI ringkas.

## 4. Perbaikan Topbar User Identity
Topbar kini menampilkan avatar/foto user atau inisial fallback. Nama user dan role aktif tetap menggunakan truncate agar tidak overlap dengan tombol `Ganti Peran` dan `Keluar`.

Perbaikan Tahap 12.2 untuk topbar responsive tetap dipertahankan.

## 5. Perbaikan Halaman Profil
Halaman `Profil Saya` dan `Edit Profil` memiliki card `Foto Profil` dengan:
- avatar besar,
- nama file jika tersedia,
- input upload,
- tombol `Ubah Foto`,
- tombol `Hapus Foto` jika avatar sudah ada,
- helper text file JPG/PNG/WebP maksimal 2MB.

Informasi role, status akun, dan kelengkapan profil tetap dipertahankan.

## 6. Keamanan Upload Avatar
Keamanan yang diterapkan:
- route avatar wajib `auth`,
- user hanya mengelola avatar miliknya sendiri,
- validasi server-side memakai image file JPG/JPEG/PNG/WebP,
- SVG tidak diizinkan,
- ukuran maksimal 2MB,
- file disimpan pada disk `local`,
- path asli file tidak ditampilkan di UI,
- file lama dihapus saat upload baru atau hapus avatar, dengan fallback aman agar gagal hapus tidak membuat aplikasi error fatal.

## 7. File yang Diubah
- `database/migrations/2026_05_22_000014_add_avatar_fields_to_users_table.php`
- `app/Models/User.php`
- `app/Http/Controllers/ProfileController.php`
- `routes/web.php`
- `resources/views/components/ui/avatar.blade.php`
- `resources/views/layouts/app.blade.php`
- `resources/views/roles/select.blade.php`
- `resources/views/profile/show.blade.php`
- `resources/views/profile/edit.blade.php`
- `resources/views/dashboard/show.blade.php`
- `tests/Feature/UserImportAndProfileTest.php`
- `docs/specs/SPESIFIKASI_AWAL_APLIKASI.md`
- `AGENTS.md`

## 8. Testing
Hasil akhir:

- `php artisan migrate`: berhasil
- `php artisan test`: berhasil, 83 passed, 412 assertions
- `npm run build`: berhasil
- `git status`: clean setelah commit

## 9. Catatan Kendala
Tidak ada blocker. Preview avatar sisi browser belum dibuat dengan JavaScript khusus agar perubahan tetap ringan dan tidak menambah risiko. File yang dipilih tetap terlihat dari input file bawaan browser.

## 10. Rekomendasi Berikutnya
- Tambahkan crop/resize avatar bila nanti dibutuhkan.
- Pertimbangkan image optimization server-side untuk deployment production.
- Lakukan UAT visual pada perangkat mobile untuk halaman pilih role dan profil.
