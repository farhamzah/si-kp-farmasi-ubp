# Prompt KP-32 - VPS Predeploy Hardening

Kamu adalah Codex di workspace:

`E:\Aplikasi\farmasi-ubp-workspace`

Aplikasi:

`apps/kp-farmasi`

Konteks:
- KP Farmasi siap untuk VPS staging/UAT, tetapi belum production final.
- Production gate local masih gagal expected karena env lokal dan master data current masih legacy.
- Target VPS staging: Core bridge auth dan master data `core_preferred`.

Tugas:
1. Tambahkan template env VPS aman tanpa secret.
2. Perketat staging rehearsal check agar menangkap auth Core bridge + master data legacy.
3. Update deployment checklist/runbook.
4. Tambahkan test untuk env VPS dan staging guardrail.
5. Jalankan validasi.

Guardrails:
- Jangan commit `.env`.
- Jangan commit credential/token/password.
- Jangan tulis ke Core/TU/SAFA.
- Jangan aktifkan runtime TU/SAFA.
