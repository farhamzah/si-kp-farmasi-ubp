# Prompt KP-29 - Core Academic Unit Alignment

Kamu adalah Codex di workspace:

`E:\Aplikasi\farmasi-ubp-workspace`

Aplikasi:

`apps/kp-farmasi`

Konteks:
- KP sudah memiliki kontrak Core/TU/SAFA dan diagnostic read-only.
- Core adalah sumber utama struktur akademik.
- Struktur Core yang harus diikuti: fakultas > program studi > department.
- Fakultas bukan department.
- Jangan menulis ke Core/TU/SAFA.
- Jangan melakukan koreksi otomatis pada data lokal KP.

Tugas:
1. Formalisasi mapping akademik KP ke Core.
2. Pastikan `Farmasi` sebagai prodi legacy KP diarahkan ke `Farmasi S1`.
3. Pastikan `Farmasi Klinis` sebagai department legacy KP diarahkan ke `Farmakologi dan Farmasi Klinik`.
4. Pastikan `Fakultas Farmasi` terdeteksi sebagai faculty label, bukan department.
5. Buat command diagnostic read-only `php artisan kp:core-academic-unit-check`.
6. Tambahkan test untuk mapper dan command.
7. Update dokumen policy dan mapping coverage spec.

Validasi:
- `php artisan kp:core-academic-unit-check --show-rows`
- `php artisan test`
- `npm run build`
- `git status --short --branch`

Guardrails:
- Tidak commit `.env`, `vendor`, `node_modules`, upload storage, atau file sensitif.
- Jangan revert perubahan user.
- Jangan membuat write bridge aktif.
