# KP-TU Dry-Run Payload Preview

Tanggal: 2026-06-01

## Tujuan
Dokumen ini menjelaskan preview payload dokumen KP untuk TU. Preview ini hanya dry-run lokal di KP, tidak mengirim HTTP request, tidak menulis ke TU, dan tidak membuat arsip/document link runtime.

## Command
```bash
php artisan kp:tu-document-payload-preview
```

Opsi:
```bash
php artisan kp:tu-document-payload-preview --limit=1
php artisan kp:tu-document-payload-preview --assignment-id=1
php artisan kp:tu-document-payload-preview --document-type=placement_letter
```

## Struktur Root Payload
```json
{
  "source_app": "kp-farmasi",
  "contract_version": "kp-tu-doc-v1",
  "dry_run": true,
  "external_request_sent": false,
  "filters": {},
  "summary": {},
  "documents": [],
  "validation_warnings": [],
  "read_only_counts": {
    "unchanged": true
  }
}
```

## Document Types
| `document_type` | `service_code` | Source Module |
|---|---|---|
| `placement_letter` | `KP_PLACEMENT_LETTER` | assignment |
| `supervisor_assignment_letter` | `KP_SUPERVISOR_ASSIGNMENT_LETTER` | assignment |
| `examiner_assignment_letter` | `KP_EXAMINER_ASSIGNMENT_LETTER` | exam |
| `exam_invitation` | `KP_EXAM_INVITATION` | exam |
| `exam_minutes` | `KP_EXAM_MINUTES` | exam |
| `score_recap` | `KP_SCORE_RECAP` | assessment |
| `final_report_archive` | `KP_FINAL_REPORT_ARCHIVE` | final_report |

## Snapshot Yang Dibentuk
Setiap item dokumen memuat:
- `source_app`
- `source_module`
- `source_reference_id`
- `document_type`
- `service_code`
- `dry_run`
- `status`
- `student`
- `period`
- `placement`
- `supervisors`
- `examiner`
- `exam_schedule`
- `grade`
- `file_reference`
- `validation_warnings`

## File Reference
Preview tidak mengekspos path file privat. Field `file_reference.file_path_exposed` selalu `false`, dan `download_owner_app` bernilai `kp-farmasi`.

## Validation Warnings
Preview memberi warning untuk kondisi yang belum siap generate, misalnya:
- assignment tidak punya mahasiswa/periode/tempat;
- pembimbing belum lengkap;
- jadwal sidang belum ada;
- sidang belum selesai untuk berita acara;
- final score belum ada untuk rekap nilai;
- laporan akhir belum disetujui untuk arsip final.

## Read-only Guarantee
Command hanya membaca tabel KP yang terkait assignment, exam, final report, dan final score. Tidak ada request HTTP ke TU dan tidak ada insert/update/delete. Count sebelum dan sesudah ditampilkan dalam `read_only_counts`.

