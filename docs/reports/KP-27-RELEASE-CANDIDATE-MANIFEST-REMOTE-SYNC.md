# KP-27 - Release Candidate Manifest & Remote Sync

Tanggal: 2026-06-04

## Ringkasan
KP-27 menyinkronkan checkpoint KP-25 dan KP-26 ke remote, lalu membuat manifest release candidate untuk handoff staging/production rehearsal. Tahap ini tidak membuat tag, tidak deploy, dan tidak mengubah integrasi runtime.

## Remote Sync
- Branch lokal: `main`
- Remote: `origin/main`
- Push dilakukan dari `2016574` ke `6df48d2`.
- Status setelah push: branch lokal sinkron dengan `origin/main`.

## File Dibuat
- `docs/releases/KP-FARMASI-RC-2026-06-04.md`
- `docs/reports/KP-27-RELEASE-CANDIDATE-MANIFEST-REMOTE-SYNC.md`
- `docs/prompts/PROMPT_KP_27_RELEASE_CANDIDATE_MANIFEST.md`

## Isi Manifest
Manifest release candidate mencatat:
- commit release candidate `6df48d2`,
- commit penting lintas KP-14 sampai KP-26,
- gate wajib sebelum tag/deploy,
- hasil validasi lokal terakhir,
- blocker production env yang masih harus diselesaikan di server,
- guardrails release,
- keputusan bahwa release candidate boleh untuk rehearsal tetapi belum boleh go-live final.

## Status Go-Live
Belum go-live final karena environment lokal masih development. Blocker yang harus diselesaikan di server:
- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://domain-resmi`
- `SESSION_SECURE_COOKIE=true`
- mail service production
- backup DB/storage
- rotasi/nonaktif akun demo
- approval UAT/go-live

## Guardrails
- Tidak membuat tag otomatis.
- Tidak deploy otomatis.
- Tidak write ke Core/TU/SAFA.
- Runtime TU/SAFA bridge tetap tertutup.
- Tidak membuat SSO/autologin/token URL.
- Tidak commit `.env`, `.env.production`, `vendor`, `node_modules`, build output, upload storage, cache, atau log.

## Rekomendasi KP-28
KP-28 sebaiknya dilakukan di server/staging final:
1. set production-like `.env`,
2. jalankan `php artisan optimize:clear`,
3. jalankan `php artisan migrate --force` setelah backup,
4. jalankan `php artisan kp:release-candidate-gate --strict-git`,
5. jalankan `php artisan kp:production-readiness-gate`,
6. lakukan smoke test manual tiap role,
7. baru buat tag release bila semua gate lulus dan approval diberikan.
