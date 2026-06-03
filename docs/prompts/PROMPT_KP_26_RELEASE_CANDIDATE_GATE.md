# PROMPT KP-26 - Release Candidate Gate

Kamu adalah Codex di workspace:

`E:\Aplikasi\farmasi-ubp-workspace`

Aplikasi:

`apps/kp-farmasi`

Konteks:
- KP-25 sudah menambahkan `php artisan kp:release-sensitive-scan`.
- Kandidat release perlu gate final sebelum tag/push/deploy production.
- Integrasi Core/TU/SAFA tetap harus mengikuti guardrail read-only dan closed bridge.

Tugas:
Kerjakan KP-26 - Release Candidate Gate & Final Go-Live Checklist.

Tujuan:
1. Tambahkan command read-only `php artisan kp:release-candidate-gate`.
2. Gate harus merangkum readiness final sebelum tag/deploy.
3. Sensitive scan harus menjadi bagian dari gate.
4. Production env checks harus jelas.
5. TU/SAFA runtime bridge tetap closed.
6. Working tree clean menjadi warning default dan blocker pada mode strict.
7. Tambahkan test.
8. Dokumentasikan report KP-26.

Guardrails:
- Jangan aktifkan write bridge TU/SAFA.
- Jangan write ke Core/TU/SAFA.
- Jangan membuat SSO/autologin/token URL.
- Jangan membuat tag otomatis.
- Jangan push otomatis tanpa instruksi user.
- Jangan commit `.env`, `.env.production`, `vendor`, `node_modules`, build output, atau upload storage.

Validasi:
- `php artisan test --filter=ReleaseCandidateGateCommandTest`
- `php artisan kp:release-candidate-gate`
- `php artisan kp:release-sensitive-scan`
- `php artisan test`
- `npm run build`
- `git status --short`
