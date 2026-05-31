# KP-18 - External Document Reference Design for TU Bridge

Tanggal: 2026-06-01

## Ringkasan
KP-18 menambahkan fondasi lokal untuk menyimpan referensi dokumen eksternal TU tanpa membuat write bridge aktif. Desain ini menyiapkan tabel reference, model, service draft reference, command preview read-only, test, dan dokumentasi desain.

Tahap ini tetap tidak mengirim HTTP request ke TU, tidak menulis ke Core/TU/SAFA, tidak membuat SSO/autologin/token URL, dan tidak mengekspos path internal/file privat.

## File Dibuat/Diubah
Dibuat:
- `database/migrations/2026_06_01_000018_create_kp_external_document_references_table.php`
- `app/Models/KpExternalDocumentReference.php`
- `app/Services/Integration/KpExternalDocumentReferenceService.php`
- `app/Console/Commands/ExternalDocumentReferencePreviewCommand.php`
- `tests/Feature/ExternalDocumentReferencePreviewCommandTest.php`
- `tests/Feature/KpExternalDocumentReferenceTest.php`
- `docs/reports/KP-18-EXTERNAL-DOCUMENT-REFERENCE-DESIGN.md`
- `docs/prompts/PROMPT_KP_18_EXTERNAL_DOCUMENT_REFERENCE_DESIGN.md`
- `docs/integration/KP-TU-EXTERNAL-DOCUMENT-REFERENCE-DESIGN.md`

Diubah:
- `bootstrap/app.php` untuk mendaftarkan command `kp:external-document-reference-preview`.

## Struktur Tabel Reference
Tabel: `kp_external_document_references`

Kolom utama:
- `uuid`
- `source_app`
- `external_app`
- `document_type`
- `service_code`
- `source_module`
- `source_reference_type`
- `source_reference_id`
- `external_document_id`
- `external_document_number`
- `external_status`
- `reference_url`
- `file_hash`
- `metadata`
- `last_payload_snapshot`
- `last_error`
- `synced_at`
- `created_by`
- `updated_by`
- timestamps

Index/constraint:
- index `external_app + document_type`;
- index `source_reference_type + source_reference_id`;
- unique `external_app + document_type + source_reference_type + source_reference_id`.

## Alasan Tidak Duplicate Upload
Dokumen administrasi TU nanti harus menjadi arsip/dokumen TU, sedangkan KP hanya menyimpan pointer. Dengan begitu:
- KP tidak menggandakan file arsip TU;
- status dokumen dapat diaudit melalui external ID/status/nomor;
- migrasi bridge bisa bertahap;
- file privat KP tidak perlu dibuka sebagai public URL;
- koreksi/nomor dokumen TU tidak menimbulkan salinan ganda yang saling berbeda.

## Command Preview/Dry-run
Command:

```bash
php artisan kp:external-document-reference-preview
```

Default command:
- membaca TU dry-run payload lokal;
- membentuk preview draft reference lokal;
- menampilkan `dry_run=true`;
- menampilkan `external_request_sent=false`;
- menampilkan `local_persistence_performed=false`;
- memastikan count read-only unchanged.

## Local Persistence
KP-18 belum membuat persistence otomatis dari command.

Service `KpExternalDocumentReferenceService` memiliki method eksplisit `persistLocalDrafts()` untuk fondasi tahap berikutnya. Method ini hanya menyimpan local draft ke database KP bila dipanggil langsung oleh kode/test, dan tidak menulis ke TU/Core/SAFA.

## Bukti Tidak Ada HTTP Request Keluar
Implementasi tidak memakai HTTP client. Command hanya memanggil:
- `KpTuDocumentPayloadPreviewService`
- `KpExternalDocumentReferenceService`

Tidak ada endpoint TU/SAFA yang dipanggil.

## Bukti Data Sensitif Tidak Tersimpan/Terekspos
Service melakukan sanitasi `last_payload_snapshot` untuk menghapus key atau string sensitif seperti:
- token;
- password;
- secret;
- signed URL;
- meeting link privat;
- `file_path`;
- internal storage path.

Model juga memiliki helper `isSafeReferenceUrl()` yang menolak reference URL yang mengandung token/signed/secret/private/temporary pattern.

## Hasil Validasi
Validasi akhir 2026-06-01:

- `git status --short` sebelum validasi: hanya perubahan KP-18.
- `php -l app\Models\KpExternalDocumentReference.php`: OK.
- `php -l app\Services\Integration\KpExternalDocumentReferenceService.php`: OK.
- `php -l app\Console\Commands\ExternalDocumentReferencePreviewCommand.php`: OK.
- `php artisan test --filter=ExternalDocumentReference`: berhasil, 4 passed, 51 assertions.
- `php artisan kp:integration-gap-check`: berhasil; semua aplikasi workspace terdeteksi, guardrails write off, read-only counts unchanged.
- `php artisan kp:core-mapping-coverage`: berhasil; total user 8, mapped user 8, unmapped user 0, possible duplicate 0, role mismatch 0, missing identifier 0, read-only counts unchanged.
- `php artisan kp:tu-document-payload-preview --limit=1`: berhasil; assignments scanned 1, documents previewed 7, `dry_run=true`, `external_request_sent=false`, read-only counts unchanged.
- `php artisan kp:safa-public-info-preview`: berhasil; periode `KP Farmasi Demo 2026`, requirements 4, `dry_run=true`, `external_request_sent=false`, read-only counts unchanged.
- `php artisan kp:external-document-reference-preview`: berhasil; documents scanned 14, references previewed 14, `dry_run=true`, `external_request_sent=false`, `local_persistence_performed=false`, read-only counts unchanged. Pada database lokal yang belum dimigrasikan, count tabel reference tampil sebagai `table_missing` dan command tetap preview/read-only.
- `php artisan route:list`: berhasil, 209 routes.
- `php artisan test`: berhasil, 143 passed, 750 assertions.
- `npm run build`: berhasil, Vite production build selesai.
- `git status --short` setelah validasi: hanya perubahan KP-18; tidak ada `.env`, `vendor`, `node_modules`, upload storage, cache/log, atau build output.

## Rekomendasi KP-19
1. Tambahkan approval checklist sebelum local draft persistence digunakan dari UI/command.
2. Tambahkan UI read-only untuk melihat local external references.
3. Tambahkan command explicit `--persist-local-draft` hanya setelah approval guard dan audit log disepakati.
4. Siapkan contract test untuk mapping external status TU ke status lokal KP.
