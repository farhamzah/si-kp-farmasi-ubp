# Panduan Deployment

## Requirement Server
- PHP sesuai versi Laravel 12.
- Composer.
- Node.js dan npm untuk build asset.
- MySQL/MariaDB.
- Web server Apache/Nginx dengan document root ke folder `public`.

## Instalasi
```bash
git clone <repo-url>
cd si-kp-farmasi-ubp
composer install --no-dev --optimize-autoloader
npm install
cp .env.example .env
php artisan key:generate
```

## Konfigurasi Database
Atur `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD` pada `.env`.

```bash
php artisan migrate --seed
```

## Build Asset
```bash
npm run build
```

## Storage dan Permission
Pastikan folder berikut writable oleh web server:
- `storage`
- `bootstrap/cache`

Jalankan `php artisan storage:link` hanya jika ada kebutuhan file publik. File upload utama aplikasi disimpan non-public dan diakses melalui route protected.

## Optimasi Production
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Backup dan Keamanan
- Backup database secara rutin.
- Jangan commit `.env`.
- Jangan expose folder `storage/app` langsung ke publik.
- Gunakan HTTPS pada production.
- Ganti password demo sebelum digunakan di lingkungan nyata.
