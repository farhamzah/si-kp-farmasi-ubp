# KP-TU Document Bridge Contract

Tanggal: 2026-06-01

## Tujuan
Kontrak awal payload dokumen KP ke TU. Tahap ini hanya desain contract; belum membuat write bridge aktif ke TU dan belum mengubah database TU.

## Guardrails
- TU adalah pemilik arsip/dokumen administrasi.
- KP tetap sumber operasional KP dan nilai.
- Bridge awal bersifat reference/link atau request payload, bukan duplicate upload massal.
- File privat tetap diunduh lewat aplikasi pemilik file.
- Tidak ada signed public URL, token URL, SSO, atau auto-login.

## Envelope Payload Umum
```json
{
  "source_app": "kp-farmasi",
  "contract_version": "kp-tu-doc-v1",
  "service_code": "KP_PLACEMENT_LETTER",
  "request_key": "kp_assignment:123",
  "requested_by": {
    "kp_user_id": 1,
    "core_user_id": 10,
    "name": "Admin KP"
  },
  "subject": {},
  "context": {},
  "documents": [],
  "metadata": {}
}
```

## Shared Subject Fields
| Field | Wajib | Catatan |
|---|---|---|
| `kp_student_id` | ya untuk dokumen mahasiswa | ID lokal KP |
| `core_student_id` | jika mapped | reference Core |
| `nim` | ya | snapshot aman |
| `student_name` | ya | snapshot dokumen |
| `study_program` | ya | snapshot dari Core/KP |
| `kp_period_id` | ya | ID lokal KP |
| `period_name` | ya | snapshot |

## Document Contracts
| Dokumen | `service_code` | Trigger Readiness | `request_key` | Payload Kunci |
|---|---|---|---|---|
| Surat penempatan KP | `KP_PLACEMENT_LETTER` | assignment aktif/berjalan | `kp_assignment:{id}` | tempat KP, alamat, tanggal mulai/selesai, pembimbing dalam/lapangan |
| Surat tugas pembimbing | `KP_SUPERVISOR_ASSIGNMENT_LETTER` | pembimbing sudah ditetapkan | `kp_assignment:{id}:supervisors` | daftar pembimbing, role pembimbing, periode, mahasiswa |
| Surat tugas penguji | `KP_EXAMINER_ASSIGNMENT_LETTER` | exam scheduled | `kp_exam:{id}:examiner` | penguji, jadwal, mahasiswa, pembimbing |
| Undangan sidang KP | `KP_EXAM_INVITATION` | exam scheduled/final | `kp_exam:{id}:invitation` | tanggal, jam, ruang/link, peserta sidang |
| Berita acara sidang KP | `KP_EXAM_MINUTES` | exam selesai | `kp_exam:{id}:minutes` | status sidang, penguji, pembimbing, catatan ringkas |
| Rekap nilai KP | `KP_SCORE_RECAP` | final score finalized/published | `kp_final_score:{id}` | nilai akhir, grade, status publish, komponen ringkas |
| Arsip laporan akhir/final | `KP_FINAL_REPORT_ARCHIVE` | laporan disetujui | `kp_final_report:{id}` | metadata file final, versi, approver, status |

## Document Reference Dari TU ke KP
Jika TU sudah membuat archive/link, KP cukup menyimpan reference lokal pada tahap berikutnya:
- `related_app = kp-farmasi`
- `related_type = app_reference`
- `related_id = kp_assignment:{id}` atau model key lain
- `visibility = app_only` atau `internal`
- `relation_label` sesuai dokumen

## Permission
- Admin/Koordinator KP dapat melihat status reference semua dokumen KP.
- Mahasiswa hanya melihat dokumen miliknya jika policy mengizinkan.
- Pembimbing/Penguji hanya melihat dokumen yang terkait assignment/exam miliknya.
- Download file final TU tetap melewati controller TU.

## Audit
KP perlu mencatat:
- request dibuat;
- reference TU diterima;
- status dokumen berubah;
- user yang memicu aksi.

TU mencatat:
- generate;
- nomor surat;
- upload final;
- archive;
- document link;
- download.

## Non-goals KP-15
- Tidak membuat API write ke TU.
- Tidak membuat migration reference dokumen KP.
- Tidak mengirim file KP ke TU.
- Tidak membuat public/signed URL.

