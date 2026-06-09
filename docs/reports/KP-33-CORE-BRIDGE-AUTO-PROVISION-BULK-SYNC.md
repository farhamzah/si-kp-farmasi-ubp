# KP-33 - Core Bridge Auto-Provision & Bulk Sync

Tanggal: 2026-06-09

## Tujuan
KP-33 menutup gap production ketika user Core sudah valid dan punya app access `kp-farmasi`, tetapi belum bisa login KP karena belum ada bridge user lokal KP.

## Perubahan
- Login Core bridge sekarang melakukan auto-provision bridge user lokal KP saat login pertama jika:
  - Core user aktif;
  - password Core valid;
  - `must_change_password=false`;
  - memiliki app access aktif `kp-farmasi`;
  - role Core dapat diterjemahkan ke role KP.
- Role lokal KP disinkronkan dari Core setiap login.
- Role lokal yang sudah tidak ada di Core dicabut dari KP saat sync.
- Profil lokal minimum dibuat saat provisioning:
  - `students` untuk Core student;
  - `lecturers` untuk Core lecturer.
- Menambahkan command bulk sync:

```bash
php artisan kp:provision-core-bridge-users
php artisan kp:provision-core-bridge-users --execute --confirm-execute
```

Default command bulk adalah dry-run dan tidak menulis data.

## Guardrails
- KP tidak menulis ke database Core.
- KP tidak menyalin password Core.
- Bridge user lokal memakai password random internal.
- Tidak ada SSO, autologin, atau token URL.
- User Core inactive tetap diblokir.
- User Core dengan `must_change_password=true` tetap diblokir.
- App access `kp-farmasi` tetap wajib.
- `admin-core` tidak diterjemahkan menjadi role KP.

## Dampak Login
- User dengan satu role KP langsung masuk dashboard role tersebut.
- User dengan lebih dari satu role KP diarahkan ke `/pilih-role`.
- User Core baru yang valid dapat login tanpa admin menjalankan provision satu per satu.

## Dampak VPS
Setelah pull ke VPS, jalankan:

```bash
composer install --no-dev --optimize-autoloader
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Opsional bulk sync semua user Core dengan app access `kp-farmasi`:

```bash
php artisan kp:provision-core-bridge-users
php artisan kp:provision-core-bridge-users --execute --confirm-execute
```

## Validasi
- `php -l app/Services/CoreBridgeAuthService.php`: PASS.
- `php -l app/Services/KpCoreBridgeProvisioningService.php`: PASS.
- `php -l app/Console/Commands/ProvisionCoreBridgeUsersCommand.php`: PASS.
- `php artisan test --filter=CoreBridgeAuthTest`: PASS.
- `php artisan test --filter=CoreBridgeProvisioningCommandTest`: PASS.
- `php artisan test --filter=CoreBridgeBulkProvisioningCommandTest`: PASS.
- `php artisan test`: PASS, 190 tests / 1083 assertions.
- `npm run build`: PASS.
- `php artisan kp:production-readiness-gate`: FAIL expected di local karena `.env` lokal bukan production/VPS (`APP_ENV`, `APP_DEBUG`, `APP_URL`, secure cookie, dan master data mode lokal). Gate harus dijalankan ulang di VPS setelah pull.

## Rekomendasi Lanjutan
- Jalankan bulk sync di VPS setelah deploy untuk mengurangi friction login pertama.
- Pastikan Core memakai role/app access yang konsisten untuk `kp-farmasi`.
- Ganti password Core yang pernah tertulis di chat/log manual.
