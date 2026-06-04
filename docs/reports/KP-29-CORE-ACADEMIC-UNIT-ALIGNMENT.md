# KP-29 - Core Academic Unit Alignment

Tanggal: 2026-06-04

## Tujuan
Menegaskan alignment unit akademik KP terhadap Core setelah ditemukan potensi mismatch label prodi/department Farmasi.

## Keputusan
- KP mengikuti struktur Core: fakultas > program studi > department.
- Fakultas bukan department.
- `Fakultas Farmasi` tidak boleh menjadi canonical department KP.
- Label prodi legacy `Farmasi` dimapping sebagai alias ke `Farmasi S1`.
- Label department legacy `Farmasi Klinis` dimapping sebagai alias ke `Farmakologi dan Farmasi Klinik`.
- Diagnostic bersifat read-only dan tidak melakukan koreksi otomatis.

## File Dibuat/Diubah
- `app/Support/CoreAcademicUnitMapper.php`
- `app/Console/Commands/CoreAcademicUnitCheckCommand.php`
- `tests/Feature/CoreAcademicUnitMapperTest.php`
- `tests/Feature/CoreAcademicUnitCheckCommandTest.php`
- `bootstrap/app.php`
- `docs/integration/KP-CORE-FIELD-AUTHORITY-POLICY.md`
- `docs/integration/KP-CORE-MAPPING-COVERAGE-REPORT-SPEC.md`
- `docs/prompts/PROMPT_KP_29_CORE_ACADEMIC_UNIT_ALIGNMENT.md`

## Command
```bash
php artisan kp:core-academic-unit-check
php artisan kp:core-academic-unit-check --show-rows
```

Output utama:
- hierarchy Core
- jumlah Core faculties, study programs, departments
- prodi KP mapped/unmapped
- department KP mapped/unmapped
- faculty label used as department
- read-only counts unchanged

## Guardrails
- Tidak ada write ke database Core.
- Tidak ada write ke TU/SAFA.
- Tidak ada SSO/autologin/token URL.
- Tidak ada koreksi otomatis data lokal KP.
- Tidak ada file sensitif yang perlu dicommit.

## Hasil Diagnostic Lokal
`php artisan kp:core-academic-unit-check --show-rows`:
- Core faculties: 1
- Core study programs: 2
- Core departments: 4
- KP study programs: 2
- Study program mapped: 2
- Study program unmapped: 0
- KP departments: 3
- Department mapped: 2
- Department unmapped: 0
- Faculty label used as department: 1
- Read-only counts unchanged: yes

Mapping terdeteksi:
- `Farmasi` -> `Farmasi S1`
- `Farmasi S1` -> `Farmasi S1`
- `Farmasi Klinis` -> `Farmakologi dan Farmasi Klinik`
- `Teknologi Sediaan Farmasi` -> `Teknologi Sediaan Farmasi`
- `Fakultas Farmasi` -> warning `faculty_label_used_as_department`

## Validasi
- `php -l app/Support/CoreAcademicUnitMapper.php`: lulus
- `php -l app/Console/Commands/CoreAcademicUnitCheckCommand.php`: lulus
- `php -l bootstrap/app.php`: lulus
- `php artisan kp:core-academic-unit-check --show-rows`: lulus dengan 1 warning data legacy
- `php artisan test --filter=CoreAcademicUnit`: 5 passed, 17 assertions
- `php artisan kp:core-mapping-coverage`: mapped user 9/9, mismatch 0
- `php artisan kp:master-data-read-check`: warnings none, failures none
- `php artisan route:list`: berhasil, 213 routes
- `php artisan test`: 175 passed, 996 assertions
- `npm run build`: berhasil
- `php artisan kp:release-sensitive-scan`: findings 0

## Rekomendasi Lanjutan
- Lakukan cleansing data legacy KP untuk nilai `lecturers.department = Fakultas Farmasi`.
- Tambahkan kolom/snapshot faculty bila KP perlu menampilkan fakultas secara eksplisit.
- Sebelum cutover Core-preferred, pastikan `kp:core-academic-unit-check` tidak memiliki warning.
