# Production Checklist - SI-KP Farmasi UBP

Gunakan checklist ini sebelum memindahkan aplikasi ke demo server atau production.

## 1. Server Requirement
- PHP versi yang kompatibel dengan Laravel 12.
- Composer tersedia di server/build environment.
- Node.js dan NPM tersedia untuk build asset.
- MySQL/MariaDB tersedia dan dapat diakses aplikasi.
- Web server Apache atau Nginx.
- PHP extensions umum Laravel: `openssl`, `pdo`, `mbstring`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `zip`, `curl`.

## 2. Environment
- `APP_ENV=production`.
- `APP_DEBUG=false`.
- `APP_URL` sesuai domain production/demo.
- `APP_KEY` sudah di-generate.
- Credential database aman dan tidak memakai akun root bila memungkinkan.
- `SESSION_SECURE_COOKIE=true` jika memakai HTTPS.
- VPS staging/UAT dapat memakai `.env.vps.example` sebagai template aman.
- Jika auth memakai Core bridge, set `KP_MASTER_DATA_READ_MODE=core_preferred`.
- Biarkan `KP_CORE_HTTP_ENABLED=false` sampai credential Core HTTP staging resmi tersedia.
- `SESSION_DOMAIN` disesuaikan hanya jika perlu.
- `MAIL_*` dikonfigurasi bila notifikasi email akan dipakai.
- Akun demo tidak dipakai di production, atau password wajib diganti.

## 3. Install Dependency
```bash
composer install --no-dev --optimize-autoloader
npm install
npm run build
```

## 4. Laravel Commands
```bash
php artisan key:generate
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
php artisan kp:staging-rehearsal-check
php artisan kp:core-mode-preflight --auth-mode=core_bridge_with_legacy_fallback --master-data-mode=core_preferred --show-samples
```

Catatan: jika ada closure route yang membuat `route:cache` gagal, jangan dipaksa. Perbaiki route terlebih dahulu atau gunakan `config:cache` dan `view:cache`.

## 5. Permission
- Folder `storage` writable oleh user web server.
- Folder `bootstrap/cache` writable oleh user web server.
- File `.env` tidak berada di document root publik.
- Folder upload private tetap tidak diexpose langsung.
- Jika memakai shared hosting, pastikan web root mengarah ke `public`.

## 6. Web Server
- Document root wajib ke folder `public`.
- Jangan arahkan web server ke root project.
- Pastikan rewrite rules Apache/Nginx aktif.
- Pastikan file sensitif seperti `.env`, `composer.json`, dan source code tidak dapat diakses publik.

## 7. Security
- `APP_DEBUG=false`.
- HTTPS disarankan untuk production.
- Password default/demo wajib diganti.
- File upload dibatasi tipe dan ukuran.
- Download file tetap lewat route protected.
- Backup database aktif.
- Akses database memakai user dengan privilege minimum yang cukup.

## 8. Backup
- Backup database harian atau mingguan.
- Backup storage upload private.
- Simpan backup di lokasi berbeda dari server utama.
- Uji restore backup secara berkala.

## 9. Post-Deploy Smoke Test
- Buka halaman login.
- Login Admin.
- Login Koordinator KP.
- Login Mahasiswa.
- Upload file kecil.
- Download file.
- Export rekap Excel.
- Cek dashboard setiap role.
- Cek halaman error 404.
- Cek logout.
