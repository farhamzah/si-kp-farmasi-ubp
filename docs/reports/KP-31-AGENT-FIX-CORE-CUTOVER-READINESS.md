# KP-31 - Agent Fix Core Cutover Readiness

Tanggal: 2026-06-04

## Sumber
QA report: KP Farmasi Agent Fix Report 2026-06-04.

## Keputusan P1
Target master data untuk staging/production:

```env
KP_AUTH_MODE=core_bridge_with_legacy_fallback
KP_MASTER_DATA_READ_MODE=core_preferred
```

Mode `core_only` belum dipilih karena data profil student/lecturer Core belum lengkap untuk seluruh skenario UAT KP.

## Perubahan
- Production readiness gate kini mengecek `KP_MASTER_DATA_READ_MODE`.
- Jika auth memakai Core bridge tetapi master data masih `legacy`, gate menjadi blocker.
- Dokumen cutover master data dibuat di `docs/integration/KP-CORE-MASTER-DATA-CUTOVER-DECISION.md`.
- Dokumen Core HTTP smoke diperjelas: HTTP adapter disabled/missing env adalah expected local status sampai credential staging resmi tersedia.

## Temuan Lokal
`php artisan kp:core-mode-preflight --auth-mode=core_bridge_with_legacy_fallback --master-data-mode=core_preferred --show-samples`:
- KP DB connected: yes
- Core DB connected: yes
- Blockers: none
- Status: WARN

Penyebab:
- Mapping user sudah lengkap.
- Mapping profil mahasiswa/dosen masih stale atau belum tersedia di Core.
- Core saat ini hanya punya 1 student dan 1 lecturer yang terbaca, sedangkan data demo KP membutuhkan 2 students dan 5 lecturers.

`php artisan kp:sync-core-mapping --dry-run --show-samples`:
- users: skip 9, blocker 0
- students: blocker 2
- lecturers: blocker 4, skip 1
- field supervisors: skip 1

## Keputusan Core HTTP
Core HTTP adapter tidak diaktifkan di local karena env/credential staging belum tersedia.

Status:
- `php artisan kp:core-smoke-test` disabled/missing env adalah expected untuk local.
- HTTP smoke menjadi wajib bila integrasi runtime Core API diputuskan wajib.
- Tidak ada secret yang boleh masuk output/report/repository.

## Gap yang Masih Terbuka
- Core perlu menyediakan readiness support untuk `kp-farmasi`, termasuk command Core non-destruktif bila tersedia di agent Core.
- Core perlu menyediakan profil student/lecturer UAT utama agar `core_preferred` tidak fallback ke legacy.
- TU payload preview masih perlu data demo lengkap untuk semua dokumen assignment.
- TU runtime bridge tetap closed sampai kontrak runtime disetujui.

## Guardrails
- Tidak ada write ke Core/TU/SAFA.
- Tidak ada SSO/autologin/token URL.
- Tidak ada credential/token/password production di file.
- Local write hanya boleh command eksplisit dengan confirmation flag.

## Validasi
- `php -l app/Console/Commands/ProductionReadinessGateCommand.php`: lulus
- `php artisan test --filter=ProductionReadinessGateCommandTest`: 3 passed, 14 assertions
- `php artisan kp:production-readiness-gate`: gagal expected local dengan blocker environment dan `master_data_core_bridge_aligned`
- `php artisan kp:core-smoke-test`: sukses expected local, adapter disabled/missing env
- `php artisan kp:core-mode-preflight --auth-mode=core_bridge_with_legacy_fallback --master-data-mode=core_preferred --show-samples`: WARN, blockers none
- `php artisan kp:sync-core-mapping --dry-run --show-samples`: gagal expected karena Core profile student/lecturer UAT belum lengkap
- `php artisan route:list`: berhasil, 213 routes
- `php artisan kp:release-sensitive-scan`: findings 0
- `php artisan test`: 179 passed, 1010 assertions
- `npm run build`: berhasil
