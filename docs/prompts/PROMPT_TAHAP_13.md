# Prompt Tahap 13 - Production Readiness dan Final UAT Fixes

Tahap 13 berfokus pada kesiapan rilis MVP SI-KP Farmasi UBP tanpa membuat fitur besar baru dan tanpa mengubah logic bisnis utama.

Ruang lingkup:
- Audit dan perapihan `.env.example` agar lengkap, aman, dan tidak berisi credential production.
- Pembuatan checklist production deployment.
- Pembuatan release notes MVP.
- Pembuatan security checklist.
- Pembuatan template isu UAT dan ringkasan UAT.
- Final UI/UX sanity check ringan untuk sidebar, topbar, table, login, error page, dashboard, dan empty state.
- Final route check melalui `php artisan route:list`.
- Pembuatan deployment smoke test checklist.
- Penambahan test ringan untuk `.env.example`, error pages, login page, dan proteksi management/export.
- Update spesifikasi awal dan AGENTS.md.
- Menjalankan `php artisan optimize:clear`, `php artisan migrate`, `php artisan test`, `npm run build`, dan `git status`.

Dokumen wajib:
- `docs/deployment/PRODUCTION_CHECKLIST.md`
- `docs/deployment/SMOKE_TEST_CHECKLIST.md`
- `docs/releases/RELEASE_NOTES_MVP.md`
- `docs/audits/SECURITY_CHECKLIST.md`
- `docs/uat/UAT_ISSUES_TEMPLATE.md`
- `docs/uat/UAT_SUMMARY.md`
- `docs/reports/TAHAP_13_PRODUCTION_READINESS_DAN_FINAL_UAT.md`

Commit akhir:

```bash
git add .
git commit -m "Prepare MVP for production readiness"
```
