# KP-30 - Local Academic Unit Cleanup

Tanggal: 2026-06-04

## Tujuan
Membersihkan warning unit akademik lokal KP setelah KP-29 menetapkan struktur Core:

1. fakultas
2. program studi
3. department

Fakultas bukan department, sehingga nilai `Fakultas Farmasi` tidak boleh dipakai sebagai canonical `lecturers.department`.

## Perubahan
- Menambahkan command lokal:

```bash
php artisan kp:academic-unit-cleanup
php artisan kp:academic-unit-cleanup --execute --confirm-execute
```

- Default command adalah dry-run dan tidak menulis data.
- Execute hanya menulis tabel lokal KP `lecturers`.
- Seeder demo end-to-end diperbaiki agar department dosen demo menjadi `Farmakologi dan Farmasi Klinik`.
- Tidak ada write ke Core/TU/SAFA.

## Mapping Cleanup
| Nilai lama KP | Nilai baru KP | Alasan |
|---|---|---|
| `Fakultas Farmasi` | `Farmakologi dan Farmasi Klinik` | `Fakultas Farmasi` adalah fakultas, bukan department |

## File Dibuat/Diubah
- `app/Console/Commands/AcademicUnitCleanupCommand.php`
- `database/seeders/DemoEndToEndSeeder.php`
- `tests/Feature/AcademicUnitCleanupCommandTest.php`
- `bootstrap/app.php`
- `docs/prompts/PROMPT_KP_30_LOCAL_ACADEMIC_UNIT_CLEANUP.md`

## Guardrails
- Tidak menulis ke database Core.
- Tidak menulis ke TU/SAFA.
- Tidak ada SSO/autologin/token URL.
- Tidak mengubah file sensitif.
- Cleanup lokal butuh flag eksplisit `--execute --confirm-execute`.

## Hasil Lokal
Dry-run awal:
- Planned updates: 3
- Rows: `dosen@sikp.test`, `dosen2@sikp.test`, `penguji@sikp.test`

Execute lokal:
- `php artisan kp:academic-unit-cleanup --execute --confirm-execute`: berhasil
- 3 row `lecturers.department` lokal berubah dari `Fakultas Farmasi` ke `Farmakologi dan Farmasi Klinik`

Diagnostic setelah cleanup:
- `php artisan kp:core-academic-unit-check --show-rows`: berhasil
- Study program mapped: 2
- Study program unmapped: 0
- Department mapped: 3
- Department unmapped: 0
- Faculty label used as department: 0
- Warnings: none
- Read-only counts unchanged: yes

## Validasi
- `php -l app/Console/Commands/AcademicUnitCleanupCommand.php`: lulus
- `php -l bootstrap/app.php`: lulus
- `php artisan test --filter=AcademicUnitCleanup`: 3 passed, 11 assertions
- `php artisan test`: 178 passed, 1007 assertions
- `npm run build`: berhasil
- `php artisan route:list`: berhasil, 213 routes
- `php artisan kp:release-sensitive-scan`: findings 0

## Rekomendasi
- Jalankan `kp:core-academic-unit-check --show-rows` setelah cleanup.
- Sebelum production cutover, pastikan `Faculty label used as department: 0`.
