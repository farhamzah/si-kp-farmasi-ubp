# KP-25 - Release Candidate Sensitive Scan

Tanggal: 2026-06-03

## Ringkasan
KP-25 menambahkan command read-only untuk memeriksa kandidat release sebelum commit/push/deploy. Pemeriksaan ini mencegah file sensitif, build output, dependency folder, upload storage, private key, dan assignment secret yang jelas ikut repository.

## File Dibuat/Diubah
Dibuat:
- `app/Console/Commands/ReleaseSensitiveScanCommand.php`
- `tests/Feature/ReleaseSensitiveScanCommandTest.php`
- `docs/reports/KP-25-RELEASE-CANDIDATE-SENSITIVE-SCAN.md`
- `docs/prompts/PROMPT_KP_25_RELEASE_CANDIDATE_SENSITIVE_SCAN.md`

Diubah:
- `bootstrap/app.php`

## Command

```bash
php artisan kp:release-sensitive-scan
```

Opsional:

```bash
php artisan kp:release-sensitive-scan --show-files
```

## Cakupan Scan
Command membaca file kandidat release dari:

```bash
git ls-files --cached --others --exclude-standard
```

Dengan cara ini `.env` lokal yang memang ignored tidak membuat false alarm, tetapi file yang tracked atau untracked non-ignored tetap diperiksa.

## Hal yang Diblok
- `.env`
- `.env.production`
- `auth.json`
- `vendor/`
- `node_modules/`
- `public/build/`
- `public/storage/`
- `storage/app/public/`
- `storage/app/private/`
- `storage/framework/cache/`
- `storage/framework/sessions/`
- `storage/framework/views/`
- `storage/logs/`
- private key block
- assignment secret yang jelas seperti password/token/secret/API key dengan nilai non-placeholder

`.env.production.example` tetap boleh ikut release selama hanya berisi placeholder kosong/aman.

## Guardrails
- Command read-only.
- Tidak mengirim HTTP request.
- Tidak write ke Core/TU/SAFA.
- Tidak membaca file ignored sebagai kandidat release.
- Tidak menyimpan report kecuali output terminal.

## Validasi
- `php artisan test --filter=ReleaseSensitiveScanCommandTest`
- `php artisan kp:release-sensitive-scan`

## Rekomendasi KP-26
KP-26 sebaiknya membuat release tag/checkpoint final setelah semua gate lulus dan `kp:release-sensitive-scan` bersih.

