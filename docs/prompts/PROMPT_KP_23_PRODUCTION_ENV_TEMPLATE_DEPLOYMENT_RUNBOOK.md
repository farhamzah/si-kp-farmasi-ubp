# PROMPT KP-23 - Production Environment Template & Deployment Runbook

Kamu adalah Codex di workspace:

`E:\Aplikasi\farmasi-ubp-workspace`

Aplikasi:

`apps/kp-farmasi`

Konteks:
- KP-20 manual external document linking selesai.
- KP-21 production readiness gate selesai.
- KP-22 staging rehearsal check selesai dan migration KP lokal sudah dijalankan.
- Production gate masih menunggu environment production.

Tugas:
Kerjakan KP-23 - Production Environment Template & Deployment Runbook.

Tujuan:
1. Buat `.env.production.example` yang aman, tanpa secret nyata.
2. Buat runbook deployment production.
3. Update staging checklist agar menunjuk ke runbook production.
4. Tambahkan test yang memastikan template production aman dan sesuai readiness gate.

Guardrails:
- Jangan buat/commit `.env`.
- Jangan buat/commit `.env.production`.
- Jangan isi password/token/secret nyata.
- Jangan aktifkan runtime bridge TU/SAFA.
- Jangan write ke Core/TU/SAFA.

Validasi:
- `php artisan test --filter=ProductionReadinessTest`
- validasi penuh sebelum checkpoint commit.

Rekomendasi KP-24:
Checkpoint commit aman untuk KP-20 sampai KP-23.

