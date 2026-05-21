# Prompt Tahap 13.1 - Profile Photo dan Role Selection UI Polish

Tahap 13.1 berfokus pada polish identitas user tanpa membuat fitur besar baru dan tanpa mengubah logic bisnis utama.

Ruang lingkup:
- Menambah field avatar pada tabel `users` melalui migration baru.
- Menambah upload, lihat, dan hapus foto profil melalui route protected.
- Validasi avatar JPG/JPEG/PNG/WebP maksimal 2MB dan menolak SVG.
- Menampilkan avatar atau inisial user pada topbar, Profil Saya, Edit Profil, dashboard, dan halaman Pilih Role.
- Membuat helper `avatarUrl()`, `initials()`, dan `displayRoleLabel()` pada model User.
- Redesign halaman `/pilih-role` agar modern, informatif, responsive, memiliki avatar user, alert multi-role, card role, icon, deskripsi, badge, dan CTA.
- Memastikan nama user dan role panjang di topbar tetap truncate dan tidak overlap.
- Menambahkan test ringan untuk upload avatar valid, upload invalid, hapus avatar, inisial, dan role selection UI.
- Membuat report tahap dan update dokumentasi.

Command verifikasi:

```bash
php artisan migrate
php artisan test
npm run build
git status
```

Commit akhir:

```bash
git add .
git commit -m "Add profile photos and polish role selection UI"
```
