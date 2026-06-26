# KP-36 - Report Export Coverage Analysis

Tanggal: 2026-06-26

## Ringkasan

KP-36 memperluas kemampuan laporan setelah fitur export Monitoring Pemilihan Tempat KP. Analisa dilakukan untuk menentukan halaman mana yang layak memiliki output cetak/download resmi.

## Halaman Yang Perlu Laporan

Halaman berikut perlu laporan karena dipakai untuk rekap, arsip, evaluasi, atau kebutuhan administrasi:

- Monitoring Pemilihan Tempat KP: sudah tersedia Print Preview, Print, Word, Excel, PDF.
- Rekap Mahasiswa KP: ditambahkan Print Preview, Print, Word, Excel, PDF.
- Rekap Penempatan KP: ditambahkan Print Preview, Print, Word, Excel, PDF.
- Rekap Logbook KP: ditambahkan Print Preview, Print, Word, Excel, PDF.
- Rekap Sidang KP: ditambahkan Print Preview, Print, Word, Excel, PDF.
- Rekap Nilai KP: ditambahkan Print Preview, Print, Word, Excel, PDF.

## Halaman Yang Belum Perlu Laporan Resmi

Halaman berikut masih lebih cocok sebagai halaman operasional/master data:

- Periode KP.
- Tempat KP.
- Kuota Tempat KP.
- Persyaratan Dokumen.
- Manajemen User.
- Builder Kompetensi.

Halaman tersebut dapat ditambahkan export bila ada kebutuhan arsip master data, tetapi bukan prioritas dibanding rekap akademik/administratif.

## Format Laporan

- Print Preview: tampilan browser khusus laporan.
- Print: membuka preview dan menjalankan dialog print browser.
- Word: file `.doc` berbasis HTML yang dapat dibuka Microsoft Word.
- Excel: file `.xlsx` melalui `maatwebsite/excel`.
- PDF: file `.pdf` ringan berbasis generator internal untuk tabel laporan.

## Guardrails

- Laporan hanya membaca database lokal KP.
- Tidak ada write ke Core/TU/SAFA.
- Tidak ada token URL/autologin/SSO.
- Tidak mengekspos file sensitif.
- Akses tetap dibatasi role Admin dan Koordinator melalui route middleware management yang sudah ada.

## Validasi

- `php artisan test tests\Feature\KpRecapExportAndDashboardTest.php`
- `php artisan test tests\Feature\KpPlaceSelectionWarTicketTest.php`
- `php artisan route:list`
- `php artisan test`
- `npm run build`

