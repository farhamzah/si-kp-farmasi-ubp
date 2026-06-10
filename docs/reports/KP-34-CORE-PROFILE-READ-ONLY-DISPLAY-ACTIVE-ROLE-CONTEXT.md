# KP-34 - Core Profile Read-Only Display & Active Role Profile Context

Tanggal: 2026-06-11

## Ringkasan

KP-34 memperbaiki halaman profil KP agar memakai konteks role aktif, bukan urutan role/profil lama. Ini menutup kasus multi-role ketika user yang sedang aktif sebagai `pembimbing_dalam` masih melihat form mahasiswa karena akun juga punya role/profile mahasiswa.

Tahap ini juga menambahkan tampilan data resmi Core sebagai read-only di halaman profil KP. Core tetap menjadi sumber identitas resmi, sedangkan KP hanya menyimpan data operasional kerja praktek dan bridge reference lokal.

## Perubahan Utama

- Menambahkan `CoreProfileReadService` untuk membaca profil resmi Core secara read-only melalui koneksi `core`.
- Menambahkan helper profil di model `User`:
  - `profileTypeForRole()`
  - `activeProfileType()`
  - `activeProfileModel()`
  - `profileModelForType()`
  - `isProfileCompleteForType()`
- `ProfileController` sekarang memakai active role session untuk menentukan tipe profil.
- Halaman `/profil-saya` menampilkan blok `Data Resmi Core` read-only jika profil Core tersedia.
- Halaman `/profile/edit` mengunci field resmi Core saat profil Core tersedia.
- Field operasional yang masih bisa diisi di KP:
  - Mahasiswa: semester dan kelas.
  - Dosen: bidang keahlian/expertise.
  - Pembimbing lapangan: tetap KP-local karena belum ada profil eksternal khusus di Core.
- Status kelengkapan profil KP tidak diturunkan hanya karena snapshot lokal tidak menyalin semua field Core.

## Field Authority

| Area | Source of truth | Perilaku KP |
| --- | --- | --- |
| Nama, email, username, status akun | Core | read-only |
| NIM/NIDN/NIDK/NIP/NUPTK/NIK | Core | read-only, NIK dimasking |
| Foto profil Core | Core | ditampilkan jika URL Core tersedia |
| Fakultas | Core | read-only |
| Program studi | Core | read-only |
| Departemen | Core | read-only |
| Telepon/alamat resmi | Core | read-only saat Core profile tersedia |
| Semester/kelas KP | KP | editable operational field |
| Expertise dosen KP | KP | editable operational field |
| Pembimbing lapangan eksternal | KP | editable sampai Core punya model khusus |

## Guardrails

- Tidak ada write ke database Core.
- Tidak ada copy password Core.
- Tidak ada SSO/autologin/token URL.
- Core profile URL disanitasi oleh `CoreFarmasiClient`.
- Jika Core DB/profile tidak tersedia, halaman KP fallback ke profil lokal.

## File Dibuat/Diubah

- `app/Services/CoreProfileReadService.php`
- `app/Models/User.php`
- `app/Http/Controllers/ProfileController.php`
- `resources/views/profile/show.blade.php`
- `resources/views/profile/edit.blade.php`
- `tests/Feature/CoreProfileReadOnlyDisplayTest.php`
- `docs/reports/KP-34-CORE-PROFILE-READ-ONLY-DISPLAY-ACTIVE-ROLE-CONTEXT.md`
- `docs/prompts/PROMPT_KP_34_CORE_PROFILE_READ_ONLY_DISPLAY_ACTIVE_ROLE_CONTEXT.md`

## Validasi

- `php artisan test --filter=CoreProfileReadOnlyDisplayTest`: PASS, 2 tests / 18 assertions.
- `php artisan test`: PASS, 192 tests / 1101 assertions.
- `npm run build`: PowerShell blocked by local execution policy for `npm.ps1`; repeated with `npm.cmd run build`: PASS.
- `php artisan kp:production-readiness-gate`: ran read-only, FAIL expected on local `.env` because local environment is not production.
  - Blockers local: `APP_ENV`, `APP_DEBUG`, non-HTTPS `APP_URL`, insecure session cookie, and local Core bridge/master-data mode alignment.
  - VPS must be checked after pull with production `.env`.
- `git status --short --branch`: only KP-34 files changed/untracked before commit.

## Catatan Deploy VPS

Setelah commit masuk ke remote:

```bash
cd /var/www/si-kp-farmasi-ubp
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan kp:production-readiness-gate
```

Jika ingin foto profil Core tampil di KP, isi salah satu environment berikut dengan domain Core yang benar:

```env
KP_CORE_BASE_URL=https://core.safaubp.com
# atau
KP_CORE_PROFILE_URL=https://core.safaubp.com/profile
```

## Rekomendasi KP-35

- Tambahkan diagnostic command untuk mengecek coverage profil Core per role aktif.
- Pertimbangkan fallback avatar otomatis dari Core bila avatar KP kosong.
- Evaluasi apakah semester/kelas juga sebaiknya dipindah ke Core atau tetap menjadi operational field KP.
