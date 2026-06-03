# KP-20 - Manual External Document Linking & Status Lifecycle

Tanggal: 2026-06-01

## Ringkasan
KP-20 menambahkan lifecycle manual untuk referensi dokumen eksternal TU. Admin dan Koordinator KP dapat mengisi metadata link eksternal secara manual, mengubah status reference, dan mencatat error/catatan proses tanpa membuat koneksi runtime ke TU.

Tahap ini tetap local-only: write hanya ke tabel KP `kp_external_document_references`, tidak ada HTTP request ke TU/SAFA, tidak ada write ke Core/TU/SAFA, tidak ada duplicate upload, dan tidak menyimpan token/password/secret/signed URL/path internal.

## File Dibuat/Diubah
Dibuat:
- `app/Http/Requests/Management/UpdateExternalDocumentReferenceRequest.php`
- `resources/views/management/integration/external-document-references/edit.blade.php`
- `tests/Feature/ExternalDocumentReferenceLifecycleTest.php`
- `docs/reports/KP-20-MANUAL-EXTERNAL-DOCUMENT-LINKING-STATUS-LIFECYCLE.md`
- `docs/prompts/PROMPT_KP_20_MANUAL_EXTERNAL_DOCUMENT_LINKING_STATUS_LIFECYCLE.md`

Diubah:
- `app/Http/Controllers/Management/ExternalDocumentReferenceController.php`
- `app/Models/KpExternalDocumentReference.php`
- `resources/views/management/integration/external-document-references/index.blade.php`
- `routes/web.php`
- `docs/integration/KP-TU-EXTERNAL-DOCUMENT-REFERENCE-DESIGN.md`

## Route dan Form
Route baru:
- `GET /management/integration/external-document-references/{reference}/edit`
- `PATCH /management/integration/external-document-references/{reference}`

Route berada di group management existing:
- `auth`
- `active`
- `role.selected`
- `role:admin,koordinator_kp`

Form edit mengelola:
- `external_document_id`
- `external_document_number`
- `external_status`
- `reference_url`
- `last_error`
- `synced_at`

Halaman edit juga menampilkan snapshot aman:
- document type
- service code
- source module
- source reference type/id
- created at
- updated at

## Status Lifecycle
Status yang diterapkan:
- `draft`: baru dibuat lokal dari preview payload TU.
- `pending_external`: dokumen sedang/akan diproses manual oleh TU.
- `linked`: sudah memiliki nomor dokumen/reference URL aman dari TU.
- `failed`: ada kendala proses/linking.
- `archived`: reference tidak aktif tetapi tetap disimpan untuk riwayat.

Jika status diubah ke `linked` dan `synced_at` tidak diisi, sistem mengisi `synced_at` dengan waktu saat update.

## Validasi URL dan Teks Aman
`reference_url` nullable. Bila diisi, harus URL `http`/`https` normal dan akan ditolak jika mengandung indikasi sensitif:
- `token`
- `access_token`
- `signature`
- `signed`
- `secret`
- `password`
- `private`
- `file_path`
- `storage`
- `storage/app`
- `C:\`
- `E:\`
- `/storage/`
- `/private/`

Local filesystem path, internal storage path, signed URL, token URL, dan URL credential tidak disimpan.

Field teks manual `external_document_id`, `external_document_number`, dan `last_error` juga menolak marker sensitif yang sama agar catatan/error tidak menjadi tempat penyimpanan token, secret, signed URL, atau path internal.

## Bukti Update Hanya Lokal KP
Update manual memakai model lokal `KpExternalDocumentReference` dan hanya memanggil `update()` pada record `kp_external_document_references`. Test memastikan jumlah user tidak berubah dan hanya satu record reference lokal yang dimutasi.

## Bukti Tidak Ada HTTP Request ke TU/SAFA
Controller, FormRequest, dan model tidak memakai HTTP client. Test lifecycle memakai `Http::fake()` dan `Http::assertNothingSent()` pada update manual.

## Bukti Tidak Ada Write ke Core/TU/SAFA
Tidak ada koneksi database Core/TU/SAFA yang dipanggil. Tidak ada service bridge runtime baru. Write boundary tetap tabel lokal KP `kp_external_document_references`.

## Bukti Tidak Ada Duplicate Upload
Fitur hanya menyimpan ID/nomor/status/URL/catatan reference. Tidak ada field upload, tidak ada storage write, dan tidak ada file yang dikirim ulang.

## Hasil Validasi
Validasi akhir KP-20:
- `git status --short`: hanya perubahan KP-20.
- `php artisan kp:integration-gap-check`: berhasil.
- `php artisan kp:core-mapping-coverage`: berhasil.
- `php artisan kp:tu-document-payload-preview --limit=1`: berhasil.
- `php artisan kp:safa-public-info-preview`: berhasil.
- `php artisan kp:external-document-reference-preview`: berhasil.
- `php artisan route:list`: berhasil, 213 routes.
- `php artisan test --filter=ExternalDocumentReference`: berhasil, 13 passed, 180 assertions.
- `php artisan test`: berhasil, 152 passed, 879 assertions.
- `npm run build`: berhasil.
- `git status --short` setelah validasi: hanya file KP-20; tidak ada `.env`, `vendor`, `node_modules`, upload storage, cache/log, atau build output.

## Rekomendasi KP-21
KP-21 sebaiknya membuat runtime bridge readiness gate atau approval checklist sebelum koneksi otomatis ke TU. Auto-sync tetap belum dibuat sampai kontrak endpoint TU, auth, audit trail, retry, rollback, rate limit, dan approval gate final jelas.
