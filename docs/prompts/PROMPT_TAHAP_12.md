# PROMPT TAHAP 12

Tahap 12 berfokus pada Stabilization, Seed Demo Lengkap, dan UAT untuk SI-KP Farmasi UBP.

Instruksi utama:
- Jangan mengulang atau merusak Tahap 1-11.
- Fokus pada stabilisasi, bugfix, seed demo, UAT, route/permission audit, error page, smoke test, dan kesiapan demo.
- Perbaiki bug login 419 Page Expired pada alur login Koordinator.
- Pastikan form login memiliki CSRF, session/cookie lokal aman, logout regenerate token, dan error 419 user-friendly.
- Buat error page 403, 404, 419, dan 500 dengan bahasa Indonesia dan UI konsisten.
- Buat `database/seeders/DemoEndToEndSeeder.php` yang idempotent dan mencakup data demo dari pendaftaran sampai nilai published.
- Buat akun demo: admin, koordinator, mahasiswa, mahasiswa2, dosen, dosen2, lapangan, penguji dengan password development `password`.
- Buat `docs/uat/UAT_CHECKLIST.md`.
- Buat `docs/audits/ROUTE_PERMISSION_AUDIT.md`.
- Tambahkan smoke/feature test penting untuk login koordinator, seeder demo, halaman error, rekap, dashboard, dan nilai demo.
- Jalankan `php artisan optimize:clear`, `php artisan migrate`, `php artisan db:seed --class=DemoEndToEndSeeder`, `php artisan test`, `npm run build`, dan `git status`.
- Buat report `docs/reports/TAHAP_12_STABILIZATION_SEED_DEMO_DAN_UAT.md`.
- Update spesifikasi dan AGENTS.md.
- Commit dengan pesan `Stabilize MVP with demo seed and UAT docs`.
