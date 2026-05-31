# KP-TU Document Bridge Matrix

Tanggal: 2026-06-01

## Guardrails
- KP-14 tidak membuat bridge write aktif ke TU.
- KP tidak mengubah database TU.
- Bridge masa depan harus mengutamakan link/reference ke arsip TU, bukan duplicate upload.
- File privat tetap diunduh lewat aplikasi pemilik file dengan authorization.
- Tidak ada signed public URL atau token URL pada tahap mapping.

## Kondisi TU Yang Relevan
TU Farmasi sudah dirancang sebagai Document & Media Center berbasis e-Service Builder:
- `ServiceRequest` untuk transaksi layanan.
- `DocumentTemplate` dan `DocumentTemplateVersion` untuk template Word.
- `GeneratedDocument` dan `GeneratedDocumentVersion` untuk dokumen hasil generate.
- `FinalDocumentUpload` untuk scan/final document.
- `DocumentArchive` sebagai sumber arsip final.
- `DocumentLink` sebagai metadata routing/reference ke aplikasi lain, termasuk `kp-farmasi`.

Dokumen TU yang relevan:
- `TU-23-GENERATED-DOCUMENT-SERVICE.md`
- `TU-30-ARCHIVE-FROM-FINAL-UPLOAD.md`
- `TU-31-DOCUMENT-LINK-ROUTING-METADATA.md`

## Matrix
| Kebutuhan KP | Pemilik Dokumen Target | Data Dari KP | Mekanisme Bridge Target | Status Saat Ini | Gap | Rekomendasi KP-15 |
|---|---|---|---|---|---|---|
| Surat penempatan KP | TU | Mahasiswa, NIM, periode, tempat KP, pembimbing, tanggal KP | TU generate dokumen dari service/template; KP menyimpan `tu_document_link_id` atau metadata reference lokal | Belum ada field/link di KP | Belum ada contract service code dan payload | Definisikan service code `KP_PLACEMENT_LETTER` dan payload read-only |
| Surat tugas pembimbing | TU | Pembimbing Dalam/Lapangan/Penguji, assignment, periode | TU archive + `DocumentLink` `related_app=kp-farmasi` | Belum ada | Belum ada mapping satu dokumen untuk banyak dosen | Tentukan related type `lecturer` atau `app_reference` dan snapshot assignment |
| Undangan sidang | TU | Jadwal sidang, mahasiswa, pembimbing, penguji, ruang/link | TU generate undangan; KP hanya referensi arsip | Belum ada | Belum ada trigger manual/automatic | Mulai manual bridge request dari admin KP setelah jadwal final |
| Berita acara sidang | TU | Exam, status sidang, nilai ringkas, reviewer | TU template berita acara; final scan diarsipkan TU | Belum ada | Belum ada data approval/signature flow | Buat draft payload dan status dependency: hanya setelah exam `selesai` |
| Rekap nilai | KP/TU tergantung format resmi | Nilai komponen, final score, grade, publish status | KP tetap sumber nilai; TU dapat arsip dokumen resmi hasil generate | KP punya rekap/export Excel lokal | Belum ada dokumen resmi/nomor surat | Tetapkan KP sebagai source of truth nilai; TU sebagai arsip dokumen resmi bila diperlukan |
| Arsip laporan akhir | KP sebagai pemilik upload, TU sebagai arsip formal opsional | File laporan final, mahasiswa, pembimbing, status approve | Reference ke file KP atau final archive TU jika TU menerima scan resmi | KP menyimpan file non-public lokal | Risiko duplicate upload besar | Prioritaskan link/reference; upload ulang hanya bila TU butuh arsip formal bertanda tangan |
| Dokumen final KP lainnya | TU | Snapshot transaksi KP | `DocumentArchive` + `DocumentLink` | TU siap metadata link | Belum ada shared identifier | Gunakan `related_app=kp-farmasi`, `related_type=app_reference`, `related_id=kp:{model}:{id}` |

## Contract Awal Yang Perlu Dibuat
- Daftar `service_code` TU untuk dokumen KP.
- Payload JSON minimum per dokumen.
- Status kapan dokumen boleh diminta/generate.
- Mapping related id yang stabil, misalnya `kp_assignment:{id}`, `kp_exam:{id}`, `kp_final_score:{id}`.
- Permission download: siapa membuka dari KP dan siapa membuka dari TU.
- Audit trail: KP mencatat request/reference; TU mencatat generate/archive/download.

## Readiness Criteria
- Tidak ada upload ganda untuk dokumen yang sudah punya arsip TU valid.
- KP dapat menampilkan status/reference dokumen TU tanpa mengakses file langsung.
- TU dapat mengarsipkan dokumen final dengan snapshot agar tetap valid walau data Core/KP berubah.
- Semua download dokumen privat melewati controller aplikasi pemilik.
- Bridge awal bersifat admin-triggered/manual sebelum otomatisasi.

