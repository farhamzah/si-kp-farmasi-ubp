# Prompt KP-30 - Local Academic Unit Cleanup

Kamu adalah Codex di workspace:

`E:\Aplikasi\farmasi-ubp-workspace`

Aplikasi:

`apps/kp-farmasi`

Konteks:
- KP-29 sudah menambahkan diagnostic `kp:core-academic-unit-check`.
- Struktur akademik mengikuti Core: fakultas > program studi > department.
- `Fakultas Farmasi` adalah faculty label, bukan department.
- Warning lokal masih muncul bila data legacy KP menaruh `Fakultas Farmasi` di `lecturers.department`.

Tugas:
1. Buat command cleanup lokal dengan mode dry-run default.
2. Command hanya boleh menulis database KP saat `--execute --confirm-execute` diberikan.
3. Cleanup `lecturers.department = Fakultas Farmasi` menjadi `Farmakologi dan Farmasi Klinik`.
4. Update seeder demo agar tidak membuat warning baru.
5. Tambahkan test dry-run, confirmation guard, dan execute lokal.
6. Jalankan diagnostic dan validasi.

Validasi:
- `php artisan kp:academic-unit-cleanup --show-rows`
- `php artisan kp:academic-unit-cleanup --execute --confirm-execute`
- `php artisan kp:core-academic-unit-check --show-rows`
- `php artisan test`
- `npm run build`
- `git status --short --branch`

Guardrails:
- Jangan menulis ke Core/TU/SAFA.
- Jangan commit `.env`, `vendor`, `node_modules`, upload storage, atau file sensitif.
- Jangan revert perubahan user.
