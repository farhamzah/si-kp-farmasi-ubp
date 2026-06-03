# PROMPT KP-24 - Safe Checkpoint KP-20 sampai KP-23

Kamu adalah Codex di workspace:

`E:\Aplikasi\farmasi-ubp-workspace`

Aplikasi:

`apps/kp-farmasi`

Konteks:
- KP-20 sampai KP-23 sudah dikerjakan.
- Working tree berisi banyak perubahan terkait manual external document linking, Core Bridge provisioning, readiness gate, staging rehearsal, template production, runbook, dan dashboard polish.
- Belum ada checkpoint commit untuk gabungan pekerjaan ini.

Tugas:
Buat checkpoint commit aman untuk KP-20 sampai KP-23.

Langkah:
1. Review `git status --short`.
2. Pastikan tidak ada `.env`, `.env.production`, `vendor`, `node_modules`, `public/build`, upload storage, cache/log, token/password/secret nyata.
3. Jalankan validasi:
   - `php artisan kp:integration-gap-check`
   - `php artisan kp:core-mapping-coverage`
   - `php artisan kp:staging-rehearsal-check`
   - `php artisan kp:production-readiness-gate`
   - `php artisan route:list`
   - `php artisan test`
   - `npm run build`
   - `git status --short`
4. Stage hanya file relevan KP-20 sampai KP-24.
5. Commit dengan pesan:

```text
Add KP production readiness gates and deployment runbooks
```

6. Jangan push kecuali diminta atau setelah branch/remote dikonfirmasi.

Guardrails:
- Jangan commit `.env`.
- Jangan commit `.env.production`.
- Jangan commit `vendor`.
- Jangan commit `node_modules`.
- Jangan commit upload storage.
- Jangan commit cache/log/build output.
- Jangan revert perubahan user.

