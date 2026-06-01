# KP-19 - Local External Document Reference Draft Management

Tanggal: 2026-06-01

## Ringkasan
KP-19 menambahkan pengelolaan draft referensi dokumen eksternal TU di database lokal KP. Admin dan Koordinator KP dapat melihat daftar reference lokal, melihat preview draft dari payload TU, dan membuat/memperbarui draft reference lokal secara eksplisit.

Tahap ini tetap tidak membuat write bridge ke TU, tidak mengirim HTTP request ke TU/SAFA, tidak menulis ke Core/TU/SAFA, tidak membuat SSO/autologin/token URL, dan tidak melakukan duplicate upload dokumen.

## File Dibuat/Diubah
Dibuat:
- `app/Http/Controllers/Management/ExternalDocumentReferenceController.php`
- `resources/views/management/integration/external-document-references/index.blade.php`
- `tests/Feature/ExternalDocumentReferenceManagementTest.php`
- `docs/reports/KP-19-LOCAL-EXTERNAL-DOCUMENT-REFERENCE-DRAFT-MANAGEMENT.md`
- `docs/prompts/PROMPT_KP_19_LOCAL_EXTERNAL_DOCUMENT_REFERENCE_DRAFT_MANAGEMENT.md`

Diubah:
- `routes/web.php`
- `resources/views/management/integration/tu-payload-preview.blade.php`
- `resources/views/management/integration/safa-public-info-preview.blade.php`
- `app/Models/KpExternalDocumentReference.php`
- `app/Services/Integration/KpExternalDocumentReferenceService.php`
- `tests/Feature/KpExternalDocumentReferenceTest.php`
- `docs/integration/KP-TU-EXTERNAL-DOCUMENT-REFERENCE-DESIGN.md`

## Route dan Menu
Route baru:
- `GET /management/integration/external-document-references`
- `POST /management/integration/external-document-references/drafts`

Keduanya berada di group management existing:
- `auth`
- `active`
- `role.selected`
- `role:admin,koordinator_kp`

Menu utama tetap `Review Integrasi`; halaman TU dan SAFA sekarang memiliki link `Draft Reference`.

## Cara Draft Reference Dibuat
Admin/Koordinator membuka halaman draft reference, memilih filter opsional:
- `assignment_id`
- `document_type`
- `limit`

Tombol `Preview Draft dari Payload TU` hanya melakukan GET/read. Tombol `Buat Draft Referensi Lokal` melakukan POST eksplisit yang:
1. membaca TU dry-run payload lokal;
2. membentuk draft reference melalui `KpExternalDocumentReferenceService`;
3. menyimpan atau memperbarui record di `kp_external_document_references`;
4. tidak mengirim request ke TU.

## Bukti Hanya Write Lokal KP
Aksi POST hanya memanggil `persistLocalDrafts()` dan menulis ke tabel lokal `kp_external_document_references`. Test memastikan count tabel sumber seperti `kp_assignments` dan `users` tidak berubah.

## Bukti Tidak Ada HTTP Request ke TU/SAFA
Tidak ada HTTP client pada controller/service. Test menggunakan `Http::fake()` dan `Http::assertNothingSent()` pada aksi create draft.

## Bukti Tidak Ada Write ke Core/TU/SAFA
Tidak ada koneksi database Core/TU/SAFA yang dipakai. Semua persistence hanya Eloquent model lokal KP `KpExternalDocumentReference`.

## Sanitasi Metadata dan Payload Snapshot
Service menghapus key/string sensitif dari snapshot:
- token;
- password;
- secret;
- signed URL;
- meeting link privat;
- `file_path`;
- storage/internal path.

Test memverifikasi snapshot tersimpan tidak mengandung marker sensitif seperti `storage/app`, `signed_url`, `password`, `secret`, atau `token`.

## Idempotency dan Duplicate Prevention
Tabel memiliki unique constraint:

```text
external_app + document_type + source_reference_type + source_reference_id
```

Service memakai `updateOrCreate()`, sehingga POST kedua untuk payload yang sama memperbarui draft existing dan tidak membuat duplicate.

## Hasil Validasi
Validasi akhir 2026-06-01:

- `git status --short` sebelum validasi: hanya perubahan KP-19.
- `php -l app\Http\Controllers\Management\ExternalDocumentReferenceController.php`: OK.
- `php -l app\Services\Integration\KpExternalDocumentReferenceService.php`: OK.
- `php -l app\Models\KpExternalDocumentReference.php`: OK.
- `php artisan test --filter=ExternalDocumentReference`: berhasil, 7 passed, 78 assertions.
- `php artisan kp:integration-gap-check`: berhasil; semua aplikasi workspace terdeteksi, guardrails write off, read-only counts unchanged.
- `php artisan kp:core-mapping-coverage`: berhasil; total user 8, mapped user 8, unmapped user 0, possible duplicate 0, role mismatch 0, missing identifier 0, read-only counts unchanged.
- `php artisan kp:tu-document-payload-preview --limit=1`: berhasil; assignments scanned 1, documents previewed 7, `dry_run=true`, `external_request_sent=false`, read-only counts unchanged.
- `php artisan kp:safa-public-info-preview`: berhasil; periode `KP Farmasi Demo 2026`, requirements 4, `dry_run=true`, `external_request_sent=false`, read-only counts unchanged.
- `php artisan kp:external-document-reference-preview`: berhasil; documents scanned 14, references previewed 14, `dry_run=true`, `external_request_sent=false`, `local_persistence_performed=false`, read-only counts unchanged.
- `php artisan route:list`: berhasil, 211 routes.
- `php artisan test`: berhasil, 146 passed, 777 assertions.
- `npm run build`: berhasil, Vite production build selesai.
- `git status --short` setelah validasi: hanya perubahan KP-19; tidak ada `.env`, `vendor`, `node_modules`, upload storage, cache/log, atau build output.

## Rekomendasi KP-20
1. Tambahkan halaman detail reference untuk audit metadata/snapshot tersanitasi.
2. Tambahkan approval checklist sebelum status berubah ke `pending_external`.
3. Tambahkan audit log khusus ketika draft reference dibuat/diperbarui.
4. Siapkan kontrak status mapping TU sebelum bridge runtime dibuat.
