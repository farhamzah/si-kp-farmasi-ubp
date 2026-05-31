# KP-Core Mapping Coverage Report Spec

Tanggal: 2026-06-01

## Tujuan
Spesifikasi laporan coverage mapping Core-KP untuk memastikan kesiapan cutover bertahap ke Core tanpa menulis ke Core dan tanpa melemahkan authorization KP.

## Command
Command diagnostic:

```bash
php artisan kp:core-mapping-coverage
```

Opsi:

```bash
php artisan kp:core-mapping-coverage --show-users
php artisan kp:core-mapping-coverage --report-json
```

## Output Wajib
- total user
- mapped user
- unmapped user
- possible duplicate email
- role mismatch
- missing identifier
- read-only counts unchanged

## Definisi Issue
| Issue | Definisi | Severity Awal | Tindakan |
|---|---|---|---|
| `unmapped_user` | `users.core_user_id` kosong | blocker untuk Core bridge | mapping ke Core atau exclude akun |
| `possible_duplicate_email` | email normalized muncul lebih dari sekali | blocker | bersihkan data/email |
| `role_mismatch` | role KP tidak punya mapping Core canonical | blocker | perbaiki role lokal atau kontrak mapping |
| `missing_identifier` | email/login identifier kosong | blocker | lengkapi identifier |

## Read-only Guarantee
Command hanya membaca:
- `users`
- `roles`
- `user_roles`
- `students`
- `lecturers`
- `field_supervisors`

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

