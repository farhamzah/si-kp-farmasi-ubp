# PROMPT KP-20 - Manual External Document Linking & Status Lifecycle

Kamu adalah Codex di workspace:

`E:\Aplikasi\farmasi-ubp-workspace`

Lanjutkan aplikasi Laravel:

`apps/kp-farmasi`

Konteks:
- KP-17, KP-18, dan KP-19 sudah selesai, divalidasi, di-commit, dan dipush.
- KP punya tabel lokal `kp_external_document_references`.
- KP-19 sudah menambahkan halaman draft reference lokal dan aksi eksplisit membuat draft dari payload TU.
- Semua guardrails integrasi tetap berlaku: tidak ada write ke Core/TU/SAFA, tidak ada HTTP request nyata ke TU/SAFA, tidak ada SSO/autologin/token URL, tidak duplicate upload dokumen, dan tidak menyimpan token/password/secret/signed URL/path internal.

Tugas:
Kerjakan KP-20 - Manual External Document Linking & Status Lifecycle.

Tujuan:
1. Admin/Koordinator dapat mengelola status referensi dokumen eksternal TU secara manual.
2. Admin/Koordinator dapat mengisi nomor dokumen TU, external document ID, reference URL aman, status, dan catatan/error.
3. Semua perubahan hanya ditulis ke tabel lokal KP `kp_external_document_references`.
4. Tetap tidak ada HTTP request ke TU/SAFA.
5. Tetap tidak ada write ke Core/TU/SAFA.
6. Tetap tidak duplicate upload dokumen.
7. Tetap tidak menyimpan token/password/secret/signed URL/private path/internal storage path.

Implementasi:
- Tambahkan FormRequest untuk validasi update manual.
- Tambahkan route edit dan update untuk reference.
- Tambahkan form edit manual linking/status.
- Tampilkan snapshot aman dari reference.
- Tambahkan test lifecycle untuk akses role, update status, validasi URL, dan boundary read/write.
- Update report KP-20 dan dokumen desain external document reference.

Validasi wajib:
- `git status --short`
- `php artisan kp:integration-gap-check`
- `php artisan kp:core-mapping-coverage`
- `php artisan kp:tu-document-payload-preview --limit=1`
- `php artisan kp:safa-public-info-preview`
- `php artisan kp:external-document-reference-preview`
- `php artisan route:list`
- `php artisan test`
- `npm run build`
- `git status --short`

Rekomendasi KP-21:
Runtime bridge readiness gate atau approval checklist sebelum koneksi otomatis ke TU. Jangan membuat auto-sync sampai kontrak endpoint TU, auth, audit, retry, rollback, dan approval gate final jelas.

