# KP-35 - Koordinator Manual Placement for Non-War-Ticket Students

Tanggal: 2026-06-24

## Ringkasan

KP-35 menambahkan alur penempatan manual untuk mahasiswa yang tidak mengikuti war ticket pemilihan tempat KP karena sudah ditunjuk langsung oleh Koordinator/Admin.

Fitur ini tetap memakai data lokal KP untuk transaksi operasional KP dan tidak menulis ke Core/TU/SAFA.

## Perubahan Utama

- Menambahkan halaman `management/place-selections/manual` untuk Koordinator/Admin.
- Koordinator/Admin dapat memilih mahasiswa terverifikasi, kuota tempat KP yang masih terbuka, dan mengisi alasan penempatan manual.
- Penempatan manual tidak menunggu jadwal war ticket, tetapi tetap wajib memenuhi guardrail:
  - pendaftaran mahasiswa sudah terverifikasi;
  - dokumen wajib sudah disetujui;
  - kuota tempat berada pada periode yang sama;
  - kuota masih terbuka dan masih tersedia;
  - mahasiswa belum punya pilihan tempat aktif;
  - mahasiswa belum punya penempatan KP aktif.
- Setelah penempatan manual dibuat, detail monitoring menyediakan aksi untuk membuat penempatan KP dari pilihan tersebut.
- Waiting list mahasiswa pada periode terkait ditandai selesai bila ada.
- Log pemilihan mencatat aksi `selection_manual_by_koordinator`.

## Dampak Workflow

1. Koordinator membuka menu Monitoring Pemilihan.
2. Klik `Penempatan Manual`.
3. Pilih mahasiswa yang sudah valid.
4. Pilih kuota tempat KP yang tersedia.
5. Isi alasan penunjukan langsung.
6. Simpan.
7. Buka detail pilihan dan klik `Buat Penempatan`.
8. Lanjutkan assignment pembimbing internal/lapangan seperti alur biasa.

## Guardrails

- Tidak ada write ke Core.
- Tidak ada write ke TU.
- Tidak ada write ke SAFA.
- Tidak copy password Core.
- Tidak membuat SSO/autologin/token URL.
- Tidak bypass verifikasi dokumen pendaftaran.
- Tidak bypass sisa kuota.
- Tidak membuat duplicate pilihan/penempatan aktif.

## Validasi Lokal

- `php artisan test tests\Feature\KpPlaceSelectionWarTicketTest.php`: PASS, 11 passed / 73 assertions.
- `php artisan route:list`: PASS, 226 routes.
- `php artisan test`: PASS, 207 passed / 1213 assertions.
- `npm run build`: PASS.
- `php artisan kp:production-readiness-gate`: gagal di lokal karena MySQL lokal `sikp_farmasi_ubp` tidak menerima koneksi di `127.0.0.1:3306`; ini perlu dijalankan ulang di VPS setelah pull.

## Catatan VPS

Setelah deploy ke VPS, jalankan ulang:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan kp:production-readiness-gate
```

