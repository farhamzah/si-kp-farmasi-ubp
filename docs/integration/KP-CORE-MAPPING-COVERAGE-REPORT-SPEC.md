# KP-Core Mapping Coverage Report Spec

Tanggal: 2026-06-01

## Tujuan
Spesifikasi laporan coverage mapping Core-KP untuk memastikan kesiapan cutover bertahap ke Core tanpa menulis ke Core dan tanpa melemahkan authorization KP.

## Command
Command diagnostic:

```bash
php artisan kp:core-mapping-coverage
```

Command diagnostic unit akademik:

```bash
php artisan kp:core-academic-unit-check
```

Opsi:

```bash
php artisan kp:core-mapping-coverage --show-users
php artisan kp:core-mapping-coverage --report-json
php artisan kp:core-academic-unit-check --show-rows
```

## Output Wajib
- total user
- mapped user
- unmapped user
- possible duplicate email
- role mismatch
- missing identifier
- read-only counts unchanged

## Output Wajib Unit Akademik
- hierarchy Core: `faculty > study_program > department`
- jumlah Core faculties, study programs, departments
- jumlah unique KP study programs
- study program mapped/unmapped
- jumlah unique KP departments
- department mapped/unmapped
- faculty label used as department
- read-only counts unchanged

## Definisi Issue
| Issue | Definisi | Severity Awal | Tindakan |
|---|---|---|---|
| `unmapped_user` | `users.core_user_id` kosong | blocker untuk Core bridge | mapping ke Core atau exclude akun |
| `possible_duplicate_email` | email normalized muncul lebih dari sekali | blocker | bersihkan data/email |
| `role_mismatch` | role KP tidak punya mapping Core canonical | blocker | perbaiki role lokal atau kontrak mapping |
| `missing_identifier` | email/login identifier kosong | blocker | lengkapi identifier |
| `unmapped_study_program` | nilai prodi lokal KP tidak punya alias/canonical Core | warning menuju blocker sebelum cutover | tambah mapping atau koreksi data |
| `canonical_study_program_missing_in_core` | alias KP mengarah ke label canonical yang tidak ditemukan di Core | blocker sebelum cutover | sesuaikan kontrak atau data Core |
| `unmapped_department` | nilai department lokal KP tidak punya alias/canonical Core | warning menuju blocker sebelum cutover | tambah mapping atau koreksi data |
| `canonical_department_missing_in_core` | alias KP mengarah ke department canonical yang tidak ditemukan di Core | blocker sebelum cutover | sesuaikan kontrak atau data Core |
| `faculty_label_used_as_department` | nilai fakultas seperti `Fakultas Farmasi` berada pada kolom department KP | warning data legacy | pindahkan/mapping sebagai fakultas, bukan department |

## Kontrak Unit Akademik
Urutan unit akademik mengikuti Core:

1. `faculty`
2. `study_program`
3. `department`

Keputusan mapping awal:
- `Farmasi` -> `Farmasi S1` sebagai program studi.
- `S1 Farmasi` -> `Farmasi S1` sebagai program studi.
- `Farmasi Klinis` -> `Farmakologi dan Farmasi Klinik` sebagai department.
- `Fakultas Farmasi` -> faculty, tidak boleh dianggap department.

## Read-only Guarantee
Command hanya membaca:
- `users`
- `roles`
- `user_roles`
- `students`
- `lecturers`
- `field_supervisors`
- `students.study_program` dan `lecturers.study_program`
- `lecturers.department`
- Core `faculties`, `study_programs`, dan `departments` untuk command unit akademik

Command tidak membaca/menulis TU/SAFA dan tidak menulis Core. Opsi `--report-json` hanya menulis file diagnostic lokal KP di `storage/app/reports`.

## Hasil Lokal KP-15
Pada data lokal saat KP-15:
- total user: 8
- mapped user: 8
- unmapped user: 0
- possible duplicate email: 0
- role mismatch: 0
- missing identifier: 0
- read-only counts unchanged: yes

## Readiness Threshold
Sebelum staging Core bridge:
- mapped user UAT aktif: 100%
- role mismatch: 0
- missing identifier: 0
- duplicate email: 0
- akun admin KP memakai `admin-kp`, bukan `admin-core`
- command test lulus dan report disimpan bila perlu untuk signoff
