# PROMPT TAHAP 04 - Pendaftaran KP dan Verifikasi Berkas

Tahap 4 membangun modul Pendaftaran KP dan Verifikasi Berkas pada aplikasi SI-KP Farmasi UBP tanpa mengulang atau merusak Tahap 1, 2, dan 3.

Tujuan utama:
- Mahasiswa dapat mendaftar KP pada periode yang dibuka.
- Admin dan Koordinator KP dapat mengelola persyaratan dokumen per periode.
- Mahasiswa dapat upload berkas persyaratan.
- Admin dan Koordinator KP dapat memverifikasi, meminta revisi, atau menolak dokumen dan pendaftaran.
- Mahasiswa terverifikasi menjadi eligible untuk Tahap 5, yaitu pemilihan tempat KP/war ticket.

Batasan:
- Tidak membuat pemilihan tempat KP, daftar tunggu, penentuan pembimbing, logbook, laporan akhir, sidang, atau penilaian.
- File upload disimpan di storage Laravel non-public dan download harus lewat route protected.

Database yang diminta:
- `kp_document_requirements`
- `kp_registrations`
- `kp_documents`
- `kp_registration_logs`

Role akses:
- Mahasiswa: melihat periode terbuka, membuat pendaftaran, upload dokumen, submit, melihat status, download dokumen sendiri.
- Admin dan Koordinator KP: CRUD persyaratan dokumen, review pendaftaran, review dokumen, download dokumen, verifikasi final.
- Role lain tidak diberi akses ke modul Tahap 4.

Dokumentasi wajib:
- Report Tahap 4 di `docs/reports/TAHAP_04_PENDAFTARAN_DAN_VERIFIKASI_BERKAS.md`.
- Prompt archive di `docs/prompts/PROMPT_TAHAP_04.md`.
- Update spesifikasi awal aplikasi.
