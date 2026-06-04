# KP-Core Master Data Cutover Decision

Tanggal: 2026-06-04

## Keputusan
Target master data KP untuk staging/production adalah:

```env
KP_AUTH_MODE=core_bridge_with_legacy_fallback
KP_MASTER_DATA_READ_MODE=core_preferred
```

Alasan:
- Auth KP sudah memakai Core bridge dengan fallback legacy.
- Core adalah source of truth identitas dan master data akademik.
- `core_preferred` tetap aman untuk fase transisi karena jatuh ke legacy bila record Core belum tersedia.
- `core_only` belum dipilih karena profil mahasiswa/dosen Core belum lengkap untuk seluruh data KP UAT.

## Kondisi Lokal 2026-06-04
Preflight target:

```bash
php artisan kp:core-mode-preflight --auth-mode=core_bridge_with_legacy_fallback --master-data-mode=core_preferred --show-samples
```

Hasil lokal:
- KP DB connected: yes
- Core DB connected: yes
- Blockers: none
- Status: WARN

Penyebab WARN:
- `students.core_student_id` lokal masih menunjuk ID Core lama untuk data demo.
- `lecturers.core_lecturer_id` lokal masih menunjuk ID Core lama untuk sebagian data demo.
- Core saat ini hanya memiliki 1 student dan 1 lecturer yang cocok dengan data nyata; data demo KP belum tersedia sebagai profil student/lecturer Core.

Diagnostic pendukung:

```bash
php artisan kp:sync-core-mapping --dry-run --show-samples
```

Hasil:
- users: mapped/skip 9, blocker 0
- students: blocker 2
- lecturers: blocker 4, skip 1
- field supervisors: skip 1

## Kriteria PASS
Sebelum staging/production cutover:
- Core menyediakan profil student untuk NIM UAT utama.
- Core menyediakan profil lecturer untuk pembimbing/penguji UAT utama.
- `php artisan kp:sync-core-mapping --dry-run --show-samples` tidak memiliki blocker untuk skenario UAT utama.
- Mapping lokal KP disinkronkan ulang melalui controlled local write:

```bash
php artisan kp:sync-core-mapping --execute --confirm-execute
```

- `php artisan kp:core-mode-preflight --auth-mode=core_bridge_with_legacy_fallback --master-data-mode=core_preferred --show-samples` menghasilkan PASS atau minimal tidak memiliki fallback warning untuk sample UAT utama.

## Production Gate
Production gate sekarang memblokir kombinasi:

```env
KP_AUTH_MODE=core_bridge*
KP_MASTER_DATA_READ_MODE=legacy
```

Jika auth sudah Core bridge, master data harus `core_preferred` atau `core_only`.

## Guardrails
- KP tidak menulis ke Core.
- KP hanya boleh menulis mapping lokal saat command controlled diberi `--execute --confirm-execute`.
- Tidak ada SSO/autologin/token URL.
- Secret Core tidak masuk output command, report, atau repository.
