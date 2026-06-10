# Prompt KP-34 - Core Profile Read-Only Display & Active Role Profile Context

Kamu adalah Codex di workspace:

`E:\Aplikasi\farmasi-ubp-workspace`

Aplikasi:

`apps/kp-farmasi`

Tugas:

Kerjakan KP-34 - Core Profile Read-Only Display & Active Role Profile Context.

Konteks:

- KP sudah live di VPS `kp.safaubp.com`.
- Core DB terbaca dari `farmasi_db`.
- Login Core bridge dan auto-provision sudah berjalan.
- Masalah tersisa: halaman profil KP masih bisa salah konteks pada user multi-role, misalnya role aktif `pembimbing_dalam` tetapi form profil yang tampil adalah mahasiswa.
- Core sudah memiliki profil resmi mahasiswa/dosen/tendik, kontak, foto profil, prodi, fakultas, dan departemen.

Tujuan:

1. Gunakan role aktif session untuk menentukan konteks profil KP.
2. Tampilkan profil resmi Core sebagai read-only di halaman profil KP jika mapping Core tersedia.
3. Jangan izinkan field resmi Core diubah dari KP saat Core profile tersedia.
4. Pertahankan field operasional KP yang memang khusus KP.
5. Pakai foto profil Core jika tersedia, dengan URL publik Core atau proxy read-only auth-only dari storage publik Core.
6. Jangan menulis ke database Core.
7. Jangan copy password Core.
8. Jangan membuat SSO/autologin/token URL.
9. Tambahkan test untuk multi-role, foto Core, dan read-only Core fields.
10. Buat report tahap.

Validasi wajib:

- `php artisan test --filter=CoreProfileReadOnlyDisplayTest`
- `php artisan test`
- `npm run build`
- `php artisan kp:production-readiness-gate`
- `git status --short --branch`

Setelah selesai:

- Commit dan push ke `origin main`.
- Berikan instruksi pull VPS dan catatan env `KP_CORE_BASE_URL`/`KP_CORE_PROFILE_URL` atau `KP_CORE_STORAGE_PUBLIC_PATH` bila ingin foto Core tampil di KP.
