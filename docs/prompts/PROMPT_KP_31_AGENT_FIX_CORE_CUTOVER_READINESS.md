# Prompt KP-31 - Agent Fix Core Cutover Readiness

Kamu adalah Codex di workspace:

`E:\Aplikasi\farmasi-ubp-workspace`

Aplikasi:

`apps/kp-farmasi`

Konteks:
- QA report 2026-06-04 menyatakan KP lokal bisa digunakan.
- Auth mode sudah `core_bridge_with_legacy_fallback`.
- Master data read mode masih `legacy`.
- Core HTTP smoke disabled/missing env di local.
- Guardrail: jangan tulis ke Core/TU/SAFA tanpa controlled mode eksplisit.

Tugas:
1. Tetapkan target master data staging/production.
2. Jalankan preflight target secara read-only.
3. Jika target belum PASS, dokumentasikan blocker konkret.
4. Perketat production readiness gate agar auth Core bridge tidak boleh production dengan master data legacy.
5. Dokumentasikan keputusan Core HTTP adapter: disabled local expected sampai credential staging tersedia.
6. Tambahkan test untuk readiness gate baru.
7. Jalankan validasi.

Target keputusan:

```env
KP_AUTH_MODE=core_bridge_with_legacy_fallback
KP_MASTER_DATA_READ_MODE=core_preferred
```

Validasi:
- `php artisan kp:core-mode-preflight --auth-mode=core_bridge_with_legacy_fallback --master-data-mode=core_preferred --show-samples`
- `php artisan kp:sync-core-mapping --dry-run --show-samples`
- `php artisan test`
- `npm run build`
- `php artisan kp:release-sensitive-scan`
- `git status --short --branch`
