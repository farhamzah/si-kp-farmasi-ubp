# KP-21 - Production Readiness Gate & Core Bridge Provisioning

Tanggal: 2026-06-03

## Ringkasan
KP-21 menambahkan readiness gate read-only menuju production dan memperjelas provisioning user KP dari Core untuk kebutuhan Core Bridge. Tahap ini tidak mengaktifkan auto-sync TU/SAFA, tidak menulis ke Core/TU/SAFA, dan tidak membuat SSO/autologin/token URL.

Fokus tahap ini:
- memastikan production blocker terlihat dari command khusus;
- menjaga runtime bridge TU tetap tertutup sampai approval gate final;
- membuat provisioning Core Bridge eksplisit, terkonfirmasi, dan hanya menulis ke database lokal KP;
- menolak user Core yang masih `must_change_password` sebelum dipakai login/provisioning KP.

## File Dibuat/Diubah
Dibuat:
- `app/Console/Commands/ProductionReadinessGateCommand.php`
- `tests/Feature/ProductionReadinessGateCommandTest.php`
- `docs/reports/KP-21-PRODUCTION-READINESS-GATE-CORE-BRIDGE-PROVISIONING.md`
- `docs/prompts/PROMPT_KP_21_PRODUCTION_READINESS_GATE_CORE_BRIDGE_PROVISIONING.md`
- `docs/integration/KP-PRODUCTION-READINESS-GATE.md`

Diubah:
- `bootstrap/app.php`
- `app/Console/Commands/ProvisionCoreBridgeUserCommand.php`
- `app/Services/KpCoreBridgeProvisioningService.php`
- `tests/Feature/CoreBridgeProvisioningCommandTest.php`

Catatan working tree saat tahap ini dimulai juga sudah berisi perubahan KP-20 dan perubahan Core Bridge provisioning/dashboard yang belum di-commit. Perubahan tersebut tidak di-revert.

## Command Baru
Command:

```bash
php artisan kp:production-readiness-gate
```

Opsional:

```bash
php artisan kp:production-readiness-gate --report-json
```

Sifat command:
- dry-run;
- tidak mengirim HTTP request;
- tidak write ke Core/TU/SAFA;
- tidak write ke database KP, kecuali `--report-json` hanya menulis file report lokal di `storage/app/reports`;
- mengembalikan exit code gagal jika masih ada blocker production.

## Gate yang Dicek
Blocker utama:
- `APP_ENV=production`;
- `APP_DEBUG=false`;
- `APP_URL` memakai HTTPS;
- `APP_KEY` terisi;
- `SESSION_SECURE_COOKIE=true`;
- auth mode dan read mode dikenal;
- Core HTTP SSL verify aktif bila Core HTTP aktif;
- endpoint runtime TU/SAFA belum aktif;
- tabel inti KP tersedia.

Warning:
- queue production sebaiknya bukan `sync`;
- cache production sebaiknya bukan `file`;
- mail production sebaiknya bukan `log`.

## Hasil Gate di Environment Saat Ini
Pada environment lokal 2026-06-03:
- command berjalan read-only;
- `external_request_sent=false`;
- write ke Core/TU/SAFA: no/no/no;
- blockers: 5;
- warnings: 1;
- ready for production: no;
- ready for runtime TU bridge: no;
- read-only counts unchanged: yes.

Blocker yang muncul:
- `APP_ENV` belum production;
- `APP_DEBUG` belum false;
- `APP_URL` belum HTTPS;
- `SESSION_SECURE_COOKIE` belum true;
- tabel `kp_external_document_references` belum tersedia pada DB lokal aktif command.

## Core Bridge Provisioning
Command existing:

```bash
php artisan kp:provision-core-bridge-user --email=user@domain.test
```

Mode default adalah dry-run dan tidak menulis data. Execute wajib eksplisit:

```bash
php artisan kp:provision-core-bridge-user --email=user@domain.test --execute --confirm-execute
```

Write boundary execute:
- hanya `users`, `user_roles`, dan profil lecturer lokal KP bila relevan;
- tidak menulis password Core ke KP;
- tidak menulis ke database Core;
- tidak membuat token URL;
- role Core diterjemahkan lewat `CoreRoleTranslator`;
- `admin-core` tetap ditolak;
- Core user `must_change_password` diblok sebelum provisioning/login KP.

## Runtime TU Bridge Tetap Ditutup
KP-21 tidak membuat auto-sync TU. Readiness gate sengaja melaporkan:

```text
Ready for runtime TU bridge: no
```

Syarat sebelum bridge runtime:
- kontrak endpoint TU final dan disetujui;
- auth antar aplikasi final;
- audit trail disetujui;
- retry dan rollback disetujui;
- rate limit dan failure policy jelas;
- approval gate manual sebelum auto-sync;
- staging dry-run dengan payload nyata yang disanitasi.

## Hasil Validasi
Validasi yang sudah dijalankan:
- `php -l app\Console\Commands\ProductionReadinessGateCommand.php`: OK.
- `php -l tests\Feature\ProductionReadinessGateCommandTest.php`: OK.
- `php -l bootstrap\app.php`: OK.
- `php -l app\Services\KpCoreBridgeProvisioningService.php`: OK.
- `php -l app\Console\Commands\ProvisionCoreBridgeUserCommand.php`: OK.
- `php -l tests\Feature\CoreBridgeProvisioningCommandTest.php`: OK.
- `php artisan test --filter=ProductionReadinessGateCommandTest`: 2 passed, 11 assertions.
- `php artisan kp:production-readiness-gate`: berjalan read-only dan gagal sesuai desain karena environment masih local/dev.
- `php artisan test --filter=CoreBridgeProvisioningCommandTest`: 7 passed, 31 assertions.

Validasi penuh masih perlu dijalankan sebelum checkpoint commit:
- `php artisan kp:integration-gap-check`
- `php artisan kp:core-mapping-coverage`
- `php artisan kp:production-readiness-gate`
- `php artisan route:list`
- `php artisan test`
- `npm run build`
- `git status --short`

## Rekomendasi Lanjutan Menuju Production
1. Jalankan migration pada DB target/staging sehingga tabel `kp_external_document_references` tersedia.
2. Siapkan `.env` production: `APP_ENV=production`, `APP_DEBUG=false`, HTTPS `APP_URL`, `SESSION_SECURE_COOKIE=true`, queue/cache/mail production.
3. Jalankan readiness gate di staging dan lampirkan JSON report.
4. Provision user Core Bridge secara dry-run dulu, lalu execute hanya untuk akun yang lolos approval.
5. Baru setelah itu evaluasi KP-22: staging deployment checklist dan UAT production rehearsal.

