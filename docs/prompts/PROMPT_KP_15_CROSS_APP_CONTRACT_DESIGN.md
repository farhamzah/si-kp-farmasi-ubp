# Prompt KP-15 - Cross-App Contract Design & Core Role Alignment

```text
Kamu adalah Codex di workspace E:\Aplikasi\farmasi-ubp-workspace.

Lanjutkan aplikasi Laravel apps/kp-farmasi. Baca AGENTS.md, docs/reports/HANDOFF_REPORT_2026_06_01.md, docs/reports/KP-14-WORKSPACE-INTEGRATION-GAP-REVIEW.md, dan dokumen kontrak KP-15.

Tugas KP-15: Cross-App Contract Design & Core Role Alignment.

Guardrails:
- Jangan menulis ke database Core/TU/SAFA.
- Jangan membuat SSO, auto-login, token URL, atau signed login URL.
- Jangan commit .env, vendor, node_modules, public/build, public/storage, atau upload storage.
- Jangan revert perubahan user.

Gunakan role translator di app/Support/CoreRoleTranslator.php dan command read-only php artisan kp:core-mapping-coverage bila perlu. Jika melanjutkan ke KP-16, fokus pada dry-run/read-only readiness atau contract refinement sebelum membuat bridge write.
```

