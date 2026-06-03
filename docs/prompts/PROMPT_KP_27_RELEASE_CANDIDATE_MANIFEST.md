# PROMPT KP-27 - Release Candidate Manifest

Kamu adalah Codex di workspace:

`E:\Aplikasi\farmasi-ubp-workspace`

Aplikasi:

`apps/kp-farmasi`

Konteks:
- KP-25 selesai dan menambahkan `kp:release-sensitive-scan`.
- KP-26 selesai dan menambahkan `kp:release-candidate-gate`.
- Commit KP-25 dan KP-26 sudah dipush ke `origin/main`.
- Commit remote terbaru: `6df48d2 Add KP release candidate gate`.

Tugas:
Kerjakan KP-27 - Release Candidate Manifest & Remote Sync.

Tujuan:
1. Pastikan branch lokal sinkron dengan `origin/main`.
2. Buat manifest release candidate yang menunjuk commit terbaru.
3. Catat hasil validasi terakhir.
4. Catat blocker production env yang masih harus diselesaikan.
5. Tegaskan release candidate belum boleh go-live final sebelum gate production lulus.
6. Jangan membuat tag otomatis.
7. Jangan deploy otomatis.

File yang dibuat:
- `docs/releases/KP-FARMASI-RC-2026-06-04.md`
- `docs/reports/KP-27-RELEASE-CANDIDATE-MANIFEST-REMOTE-SYNC.md`
- `docs/prompts/PROMPT_KP_27_RELEASE_CANDIDATE_MANIFEST.md`

Guardrails:
- Jangan write ke Core/TU/SAFA.
- Jangan aktifkan runtime bridge TU/SAFA.
- Jangan membuat SSO/autologin/token URL.
- Jangan commit `.env`, `.env.production`, `vendor`, `node_modules`, build output, upload storage, cache, atau log.

Validasi:
- `php artisan kp:release-sensitive-scan`
- `php artisan kp:release-candidate-gate`
- `git status --short --branch`
