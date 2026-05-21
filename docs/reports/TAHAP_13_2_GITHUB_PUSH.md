# TAHAP 13.2 - GitHub Push

## 1. Repository
https://github.com/farhamzah/si-kp-farmasi-ubp.git

## 2. Branch
main

## 3. Verifikasi Sebelum Push
Hasil verifikasi:
- `php artisan test`: berhasil, 83 passed, 412 assertions
- `npm run build`: berhasil
- `git status`: clean sebelum push setelah commit dokumentasi Tahap 13.2

## 4. Remote
```text
origin  https://github.com/farhamzah/si-kp-farmasi-ubp.git (fetch)
origin  https://github.com/farhamzah/si-kp-farmasi-ubp.git (push)
```

## 5. Commit Terakhir
Commit terakhir sebelum push:

```text
7cb6bac Add profile photos and polish role selection UI
```

Commit dokumentasi push:

```text
TBD Document GitHub push setup
```

## 6. Hasil Push
TBD setelah `git push -u origin main`.

## 7. Catatan
- `.gitignore` sudah mengabaikan `.env`, `vendor`, `node_modules`, `public/storage`, `public/build`, log, cache, dan file key storage.
- File tracked di `storage` hanya `.gitignore`, bukan file upload user.
- Jika GitHub meminta authentication, gunakan mekanisme login/token lokal Git dan jangan menyimpan token di file project.
