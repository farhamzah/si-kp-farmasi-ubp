# KP-SAFA Public Info Bridge Matrix

Tanggal: 2026-06-01

## Guardrails
- SAFA adalah portal/link dan informasi publik, bukan auth bridge.
- Tidak ada SSO, auto-login, token URL, password, atau secret pada URL SAFA.
- Tidak ada data mahasiswa personal, nilai, berkas, assignment personal, atau status pendaftaran individu yang tampil publik.
- KP-14 tidak menulis ke database SAFA.

## Kondisi SAFA Yang Relevan
SAFA UBP memiliki:
- `PortalApplication` untuk card/link aplikasi.
- `Announcement` untuk pengumuman publik dengan jadwal tampil.
- Landing page dan redirect `/go/{portalApplication:slug}`.
- Pola guardrail dari Lab card: card hanya portal link, bukan mekanisme auth.

## Matrix
| Informasi KP | Boleh Publik? | Sumber KP | Target SAFA | Status Saat Ini | Gap | Rekomendasi KP-15 |
|---|---|---|---|---|---|---|
| Card aplikasi KP | Ya | Metadata aplikasi/deployment | `PortalApplication` | Belum terlihat seeder card KP khusus | Belum ada URL staging/production KP final | Buat rencana card `KP Farmasi UBP` dengan URL dashboard/login tanpa token |
| Pengumuman pembukaan KP | Ya | `KpPeriod` status dan tanggal | `Announcement` | Belum ada bridge/feed | Belum ada approval publikasi | Buat public-info DTO read-only dari KP untuk periode aktif |
| Timeline pendaftaran | Ya | `registration_start_at`, `registration_end_at`, verification/selection dates | `Announcement` atau halaman info publik | Belum ada | Perlu format ringkas dan zona waktu jelas | Publish hanya tanggal umum, bukan daftar peserta |
| Persyaratan KP | Ya, jika umum | `KpDocumentRequirement` aktif per periode | Announcement/detail publik | Belum ada | Perlu filter: hanya persyaratan umum, tanpa status dokumen mahasiswa | Buat whitelist field publik: nama, deskripsi, wajib/opsional, tipe file umum, ukuran maksimal |
| Tempat KP tersedia | Terbatas | `KpPlace`, `KpPlaceQuota` | Mungkin info publik ringkas | Belum ada | Kuota real-time bisa memicu ekspektasi dan race | Untuk publik tampilkan daftar tempat/jenis umum; sisa kuota hanya di KP setelah login |
| Status pendaftaran mahasiswa | Tidak | `KpRegistration` | Tidak boleh ke SAFA | Tidak ada | Harus tetap dilarang | Tegaskan di policy public-info |
| Berkas mahasiswa | Tidak | `KpDocument` | Tidak boleh ke SAFA | Tidak ada | Harus tetap dilarang | File tetap private di KP |
| Penempatan/pembimbing personal | Tidak | `KpAssignment` | Tidak boleh ke SAFA | Tidak ada | Harus tetap dilarang | Tampil hanya setelah login KP |
| Jadwal sidang personal | Tidak, kecuali agenda publik disetujui | `KpExam` | Umumnya tidak ke SAFA | Tidak ada | Perlu keputusan prodi jika jadwal sidang publik | Default private; public hanya agregat/acara resmi tanpa nilai |
| Nilai akhir | Tidak | `KpFinalScore` | Tidak boleh ke SAFA | Tidak ada | Harus tetap dilarang | Tetap hanya di KP setelah publish dan login mahasiswa |

## Public Field Whitelist Awal
- Nama aplikasi KP.
- Deskripsi aplikasi KP.
- Link login/dashboard KP tanpa token.
- Nama periode KP.
- Tahun akademik dan semester.
- Tanggal umum pendaftaran, verifikasi, pemilihan, dan pelaksanaan.
- Persyaratan dokumen umum.
- Kontak/admin umum jika sudah disetujui.

## Readiness Criteria
- Ada policy tertulis public/private data KP.
- Ada source DTO/feed read-only di KP atau export manual yang hanya memuat whitelist.
- SAFA card KP memakai URL normal, tidak berisi credential/token.
- Pengumuman publik melalui approval admin, bukan otomatis dari perubahan draft KP.
- Tidak ada data mahasiswa individual di SAFA.

