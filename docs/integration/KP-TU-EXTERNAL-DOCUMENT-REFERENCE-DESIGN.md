# KP-TU External Document Reference Design

Tanggal: 2026-06-01

## Tujuan
Dokumen ini mendefinisikan desain reference lokal KP untuk dokumen administrasi yang kelak dikelola/diarsipkan di TU. Prinsipnya adalah link/reference first: KP menyimpan status, nomor, external ID, dan metadata aman, bukan menggandakan upload dokumen TU.

## Tabel Lokal
Tabel: `kp_external_document_references`

Kolom:
- `id`
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

## Source Reference
`source_reference_type` dan `source_reference_id` menyimpan pointer sumber lokal tanpa polymorphic relation Laravel. Alasan desain:
- payload TU KP-16 sudah memakai format referensi kontrak seperti `kp_assignment:2`, `kp_exam:3`, `kp_final_score:4`, atau `kp_final_report:5`;
- satu dokumen eksternal bisa berasal dari modul yang berbeda;
- table ini adalah integration reference, bukan domain relation utama;
- string reference lebih stabil untuk contract/audit lintas aplikasi.

## Document Types
Reference awal mengikuti dry-run TU payload:
- `placement_letter`
- `supervisor_assignment_letter`
- `examiner_assignment_letter`
- `exam_invitation`
- `exam_minutes`
- `score_recap`
- `final_report_archive`

## URL dan Metadata Safety
`reference_url` hanya boleh berupa URL normal tanpa token, signed URL, credential, private path, atau temporary secret. Saat ini KP-18 belum mengisi URL dari TU karena belum ada runtime bridge.

`metadata` dan `last_payload_snapshot` disanitasi oleh `KpExternalDocumentReferenceService` agar tidak menyimpan key/string sensitif seperti:
- token;
- password;
- secret;
- signed URL;
- meeting link privat;
- `file_path`;
- internal storage path.

## Command Preview
Command:

```bash
php artisan kp:external-document-reference-preview
```

Opsi:

```bash
php artisan kp:external-document-reference-preview --limit=1
php artisan kp:external-document-reference-preview --assignment-id=1
php artisan kp:external-document-reference-preview --document-type=placement_letter
```

Default command adalah read-only:
- membaca TU dry-run payload lokal;
- membentuk preview draft reference;
- tidak menyimpan record;
- tidak mengirim HTTP request;
- tidak menulis ke TU/Core/SAFA.

## Local Draft Persistence
KP-18 menyediakan method eksplisit `persistLocalDrafts()` pada service untuk fondasi tahap berikutnya. Method ini tidak dipanggil oleh command default. Jika kelak dipakai, penyimpanan tetap hanya ke database lokal KP, bukan ke TU.

## Local Draft Management KP-19
KP-19 menambahkan halaman management lokal:

```text
GET /management/integration/external-document-references
POST /management/integration/external-document-references/drafts
```

Route dilindungi middleware `auth`, `active`, `role.selected`, dan `role:admin,koordinator_kp`.

Aksi POST bersifat eksplisit dan hanya membuat/memperbarui draft lokal di tabel `kp_external_document_references`. Tidak ada request HTTP ke TU, tidak ada upload file, dan tidak ada write ke Core/TU/SAFA.

Status draft lokal menggunakan `draft`. Status lanjutan yang disiapkan:
- `pending_external`
- `linked`
- `failed`
- `archived`

Duplicate dicegah oleh unique key `external_app + document_type + source_reference_type + source_reference_id` dan service menggunakan `updateOrCreate()`.

## Guardrails
- Tidak ada duplicate upload.
- Tidak ada write bridge aktif ke TU.
- Tidak ada request HTTP nyata ke TU.
- Tidak ada SSO/autologin/token URL.
- Tidak ada exposure path internal/file privat.
