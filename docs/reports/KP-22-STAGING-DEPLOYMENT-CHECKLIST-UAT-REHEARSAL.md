# KP-22 - Staging Deployment Checklist & UAT Rehearsal

Tanggal: 2026-06-03

## Ringkasan
KP-22 menambahkan command staging rehearsal read-only dan menjalankan migration KP lokal yang masih pending agar database aktif selaras dengan kode KP-18 sampai KP-22. Tahap ini mempersiapkan aplikasi menuju staging/UAT production rehearsal tanpa mengaktifkan runtime bridge TU/SAFA.

## File Dibuat/Diubah
Dibuat:
- `app/Console/Commands/StagingRehearsalCheckCommand.php`
- `tests/Feature/StagingRehearsalCheckCommandTest.php`
- `docs/reports/KP-22-STAGING-DEPLOYMENT-CHECKLIST-UAT-REHEARSAL.md`
- `docs/prompts/PROMPT_KP_22_STAGING_DEPLOYMENT_CHECKLIST_UAT_REHEARSAL.md`
- `docs/integration/KP-STAGING-DEPLOYMENT-UAT-CHECKLIST.md`

Diubah:
- `bootstrap/app.php`

Database lokal KP:
- `php artisan migrate` dijalankan.
- Migration `2026_06_01_000018_create_kp_external_document_references_table` berhasil.
- Tidak ada write ke Core/TU/SAFA.

## Command Baru
Command:

```bash
php artisan kp:staging-rehearsal-check
```

Opsional:

```bash
php artisan kp:staging-rehearsal-check --report-json
```

Sifat command:
- dry-run;
- tidak mengirim HTTP request;
- tidak write ke Core/TU/SAFA;
- tidak write ke database KP, kecuali `--report-json` hanya membuat report lokal di `storage/app/reports`;
- mengembalikan failure jika ada blocker staging rehearsal.

## Checklist yang Dicek
Blocker:
- command diagnostic KP terdaftar;
- `routes/web.php` tersedia;
- `.env.example` tersedia;
- `public/build/manifest.json` tersedia;
- tabel `users`, `roles`, dan `kp_external_document_references` tersedia;
- tabel `migrations` tersedia;
- tidak ada pending migration;
- direktori `storage/app` dan `bootstrap/cache` tersedia;
- Core/TU/SAFA write tetap off;
- endpoint runtime TU/SAFA tetap kosong.

Warning:
- staging sebaiknya `APP_DEBUG=false`;
- staging sebaiknya memakai HTTPS;
- queue sebaiknya bukan `sync`;
- mailer staging final sebaiknya bukan `log`.

## Hasil Rehearsal
Sebelum migration:
- blockers: 2;
- pending migrations: 1;
- blocker utama: tabel `kp_external_document_references` belum tersedia.

Setelah `php artisan migrate`:
- blockers: 0;
- warnings: 3;
- ready for staging rehearsal: yes;
- ready for runtime TU bridge: no;
- pending migrations: 0;
- read-only counts unchanged: yes.

Warning tersisa:
- `APP_DEBUG` masih perlu dibuat false pada staging;
- `APP_URL` staging perlu HTTPS;
- mailer staging final sebaiknya memakai SMTP/mail sandbox.

## Production Gate Setelah KP-22
Setelah migration lokal, `php artisan kp:production-readiness-gate` masih gagal sesuai desain karena environment saat ini belum production:
- `APP_ENV` belum production;
- `APP_DEBUG` belum false;
- `APP_URL` belum HTTPS;
- `SESSION_SECURE_COOKIE` belum true;
- mailer masih `log`.

Tabel external reference sudah tidak lagi menjadi blocker production gate.

## UAT Sign-Off yang Harus Dilakukan
Minimal sign-off staging:
- Admin login dan dashboard.
- Koordinator login dan dashboard.
- Pendaftaran mahasiswa.
- Upload/verifikasi dokumen.
- Pemilihan tempat KP.
- Penempatan dan pembimbing.
- Logbook.
- Laporan akhir.
- Sidang dan penilaian.
- Review integrasi TU/SAFA.
- Manual external document linking.
- Core Bridge dry-run/provisioning dry-run.
- Backup dan rollback plan.

## Guardrails
- Tidak ada write ke Core.
- Tidak ada write ke TU.
- Tidak ada write ke SAFA.
- Tidak ada HTTP request nyata ke TU/SAFA.
- Tidak ada auto-sync.
- Tidak ada SSO/autologin/token URL.
- Tidak ada duplicate upload dokumen.
- Tidak menyimpan token/password/secret/signed URL/path internal.

## Validasi
Validasi KP-22:
- `php -l app\Console\Commands\StagingRehearsalCheckCommand.php`: OK.
- `php -l tests\Feature\StagingRehearsalCheckCommandTest.php`: OK.
- `php -l bootstrap\app.php`: OK.
- `php artisan test --filter=StagingRehearsalCheckCommandTest`: 2 passed, 10 assertions.
- `php artisan migrate:status`: satu migration pending sebelum migrate.
- `php artisan migrate`: berhasil menjalankan migration external document references.
- `php artisan kp:staging-rehearsal-check`: berhasil, blockers 0, warnings 3, pending migrations 0.
- `php artisan kp:production-readiness-gate`: berjalan read-only, masih gagal dengan 4 blocker environment production.

Validasi penuh sebelum checkpoint commit tetap perlu dijalankan:
- `php artisan kp:integration-gap-check`
- `php artisan kp:core-mapping-coverage`
- `php artisan route:list`
- `php artisan test`
- `npm run build`
- `git status --short`

## Rekomendasi KP-23
KP-23 sebaiknya fokus pada production environment template dan deployment runbook final:
- `.env.production.example` tanpa secret;
- checklist konfigurasi hosting;
- urutan deploy/migrate/cache/build;
- backup/rollback command guide;
- UAT sign-off sheet final sebelum commit release production.

