# Smoke Test Checklist Setelah Deployment

| No | Skenario | Hasil yang Diharapkan | Status | Catatan |
|---|---|---|---|---|
| 1 | Buka halaman login | Login tampil tanpa error | Belum diuji |  |
| 2 | Login Admin | Masuk dashboard Admin | Belum diuji |  |
| 3 | Login Koordinator | Bisa pilih role dan masuk dashboard Koordinator | Belum diuji |  |
| 4 | Login Mahasiswa | Masuk dashboard Mahasiswa | Belum diuji |  |
| 5 | Upload file kecil | File tervalidasi dan tersimpan | Belum diuji |  |
| 6 | Download file | File hanya bisa diunduh sesuai hak akses | Belum diuji |  |
| 7 | Export rekap Excel | File Excel terunduh | Belum diuji |  |
| 8 | Cek dashboard setiap role | Dashboard tampil sesuai role | Belum diuji |  |
| 9 | Cek error 404 | Halaman 404 ramah tampil | Belum diuji |  |
| 10 | Logout | Session selesai dan kembali ke login | Belum diuji |  |
| 11 | Cek `APP_DEBUG=false` | Stack trace tidak tampil ke user | Belum diuji |  |
| 12 | Cek permission storage | Upload/cache berjalan tanpa permission error | Belum diuji |  |
| 13 | Cek backup database | Backup berjalan dan dapat direstore | Belum diuji |  |
