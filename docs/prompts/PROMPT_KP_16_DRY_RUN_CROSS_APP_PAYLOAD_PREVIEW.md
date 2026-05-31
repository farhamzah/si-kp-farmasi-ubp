# Prompt KP-16 - Dry-Run Cross-App Payload Preview

```text
Kamu adalah Codex di workspace E:\Aplikasi\farmasi-ubp-workspace.

Lanjutkan aplikasi Laravel apps/kp-farmasi. Baca AGENTS.md, report KP-14, report KP-15, dan report KP-16.

KP-16 membuat dry-run payload preview:
- php artisan kp:tu-document-payload-preview
- php artisan kp:safa-public-info-preview

Guardrails:
- Jangan menulis ke Core/TU/SAFA.
- Jangan mengirim HTTP request nyata ke TU/SAFA.
- Jangan membuat SSO/autologin/token URL.
- Jangan expose path file privat.
- Jangan commit .env, vendor, node_modules, public/build, public/storage, atau upload storage.

Jika melanjutkan KP-17, fokus pada review/approval contract atau dry-run refinement sebelum membuat bridge write aktif.
```

