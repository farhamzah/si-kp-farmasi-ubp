# PROMPT TAHAP 05 - Pemilihan Tempat KP / War Ticket

Tahap 5 membangun modul Pemilihan Tempat KP / War Ticket untuk aplikasi SI-KP Farmasi UBP tanpa mengulang atau merusak Tahap 1 sampai Tahap 4.

Tujuan utama:
- Mahasiswa terverifikasi dapat memilih tempat KP saat jadwal pemilihan dibuka.
- Sistem memakai first come first served berbasis kuota.
- Mahasiswa hanya boleh memiliki satu pilihan aktif per periode.
- Kuota penuh atau tertutup tidak dapat dipilih.
- Mahasiswa masuk daftar tunggu jika semua kuota penuh.
- Admin/Koordinator dapat monitoring, cancel, dan move selection dengan alasan.
- Semua aksi dicatat di log pemilihan.
- Pemilihan aman dari race condition melalui transaction dan row locking.

Batasan:
- Tidak membuat penentuan pembimbing, logbook, laporan akhir, sidang, atau penilaian.
- Pembimbing lapangan final dan penempatan KP lanjutan dibuat pada tahap berikutnya.

Database yang diminta:
- `kp_place_selections`
- `kp_selection_logs`
- `kp_waiting_lists`

Role akses:
- Mahasiswa: melihat halaman pemilihan, memilih tempat, melihat status pilihan, masuk daftar tunggu.
- Admin/Koordinator KP: monitoring selection, daftar tunggu, log pemilihan, cancel, move.
- Role lain tidak mengakses modul Tahap 5.
