# KP Core HTTP Adapter Staging Smoke Test

## Purpose
Checklist ini dipakai untuk menguji koneksi KP Farmasi ke Core Farmasi API di staging secara read-only. Tujuannya memastikan adapter HTTP KP bisa membaca endpoint Core app-client tanpa cutover, tanpa mengganti auth KP, dan tanpa write-back.

Rencana eksekusi gabungan KP/TU dan SOP credential tersedia di Core:
- `apps/core-farmasi/docs/CORE-APP-CLIENT-CREDENTIAL-SOP.md`
- `apps/core-farmasi/docs/CORE-KP-TU-STAGING-SMOKE-EXECUTION-PLAN.md`
- `apps/core-farmasi/docs/templates/KP-TU-STAGING-SMOKE-RESULT-TEMPLATE.md`

## Preconditions
- Core staging running.
- KP staging running.
- Core app client untuk `kp-farmasi` sudah dibuat di Core.
- Core application `kp-farmasi` aktif.
- Ability minimal app client:
  - `read:users`
  - `read:students`
  - `read:lecturers`
  - `read:study-programs`
  - `read:app-access`
  - `read:leadership`
- Client secret dicatat aman oleh admin/devops dan tidak masuk repository, report, screenshot, atau log.
- KP env staging sudah diisi.
- `KP_CORE_HTTP_ENABLED=true` hanya di staging.
- `KP_CORE_READ_MODE=legacy` untuk smoke awal. Jika ingin shadow mode, gunakan nilai operasional yang disepakati, bukan `core_only`.
- Backup/snapshot staging tersedia bila dibutuhkan oleh SOP lingkungan.

## Environment Variables
Gunakan placeholder berikut di staging. Jangan commit real value.

```env
KP_CORE_HTTP_ENABLED=true
KP_CORE_READ_MODE=legacy
KP_CORE_BASE_URL=https://core-staging.example.test
KP_CORE_PROFILE_URL=https://core-staging.example.test/profile
KP_CORE_APP_CODE=kp-farmasi
KP_CORE_CLIENT_ID=<staging-client-id>
KP_CORE_CLIENT_SECRET=<staging-client-secret>
KP_CORE_TIMEOUT=5
KP_CORE_CONNECT_TIMEOUT=3
KP_CORE_VERIFY_SSL=true
KP_CORE_FAIL_SILENTLY=true
```

## Smoke Test Steps
1. Test Core health endpoint dari network staging.
2. Jalankan KP smoke command:

```bash
php artisan kp:core-smoke-test --user-id=<core-user-id>
```

3. Test directory users dengan app client credential.
4. Test directory students dengan `limit=1`.
5. Test directory lecturers dengan `limit=1`.
6. Test study programs dengan `limit=1`.
7. Test app access check untuk user yang memang punya role KP.
8. Test current leadership untuk `position_type=dekan` dan `unit_type=faculty`.
9. Verifikasi link "Ubah Profil di Core" muncul jika `KP_CORE_PROFILE_URL` dikonfigurasi.
10. Pastikan link profile mengarah ke Core `/profile` atau `/profile/edit` tanpa token, user session, atau secret.
11. Jika user belum login Core, pastikan Core meminta login sendiri. Tidak ada SSO yang diharapkan.
12. Uji invalid credential di staging terbatas dan pastikan response `401`.
13. Uji client tanpa ability yang dibutuhkan dan pastikan response `403`.
14. Pastikan rate limit tidak terpicu untuk traffic normal smoke test.
15. Simulasikan Core unavailable atau credential disabled dan pastikan KP tetap fail safe.
16. Cek Core API request logs: request tercatat tanpa secret/body/header sensitif.
17. Cek KP logs: tidak ada client secret, token, password, atau hash.
18. Konfirmasi tidak ada perubahan database KP.
19. Konfirmasi form lokal KP tetap legacy/operational dan tidak menjadi duplicate canonical profile edit baru.

## Expected Results
- Semua read-only calls yang diuji sukses.
- Endpoint invalid credential mengembalikan `401`.
- Missing ability mengembalikan `403`.
- KP tetap memakai auth/guard lokal.
- Tidak ada SSO.
- Tidak ada token URL.
- Tidak ada write-back ke Core.
- Link Core Profile Portal aman dan tidak membawa token/secret.
- Tidak ada secret di logs.
- KP tetap functional jika Core unreachable.

## Rollback / Disable Plan
1. Set:

```env
KP_CORE_HTTP_ENABLED=false
KP_CORE_READ_MODE=legacy
```

2. Clear config/cache KP:

```bash
php artisan optimize:clear
```

3. Tidak perlu database rollback karena adapter read-only dan tidak ada migration.
4. Jika credential dicurigai bocor, rotate/revoke app client di Core.

## Go / No-Go Criteria
Go jika:
- Semua endpoint read-only utama OK.
- Invalid credential dan missing ability ditolak sesuai ekspektasi.
- Tidak ada secret leak di Core/KP logs.
- KP tidak crash saat Core unavailable.
- Tidak ada mutasi database KP/Core dari smoke test.

No-Go jika:
- Auth error terjadi pada credential valid.
- Ability yang dibutuhkan belum lengkap.
- Core unavailable membuat flow KP rusak.
- Secret muncul di log/report/output.
- Ada unexpected database mutation.
- Rate limit terlalu ketat untuk smoke traffic normal.
- Profile URL mengandung token, secret, user session data, atau membuat auto-login.
