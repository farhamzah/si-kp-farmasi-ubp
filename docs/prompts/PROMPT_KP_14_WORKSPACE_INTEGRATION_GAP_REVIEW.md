# Prompt KP-14 - Workspace Integration Gap Review

Gunakan prompt ini untuk melanjutkan atau mengulang audit KP-14.

```text
Kamu adalah Codex di workspace E:\Aplikasi\farmasi-ubp-workspace.

Lanjutkan aplikasi Laravel apps/kp-farmasi. Baca apps/kp-farmasi/AGENTS.md dan docs/reports/HANDOFF_REPORT_2026_06_01.md.

Tugas: KP-14 Workspace Integration Gap Review & Cross-App Readiness.

Audit KP dalam konteks apps/core-farmasi, apps/tu-farmasi, dan apps/safa-ubp. Jangan menulis ke database Core/TU/SAFA. Jangan membuat SSO/autologin/token URL. Jangan membuat integrasi write aktif. Fokus pada gap matrix dan readiness plan.

File target:
- docs/reports/KP-14-WORKSPACE-INTEGRATION-GAP-REVIEW.md
- docs/integration/KP-CORE-INTEGRATION-GAP-MATRIX.md
- docs/integration/KP-TU-DOCUMENT-BRIDGE-MATRIX.md
- docs/integration/KP-SAFA-PUBLIC-INFO-BRIDGE-MATRIX.md

Jika command kp:integration-gap-check sudah ada, jalankan untuk diagnostic read-only. Setelah selesai jalankan php artisan route:list, php artisan test, npm run build, dan git status. Laporkan file dibuat/diubah, gap utama, rekomendasi KP-15, hasil validasi, dan guardrails.
```

