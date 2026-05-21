# Prompt Tahap 13.2 - GitHub Push

Tahap 13.2 berfokus pada menghubungkan repository lokal SI-KP Farmasi UBP ke GitHub dan melakukan push branch utama dengan aman.

Repository GitHub:

```text
https://github.com/farhamzah/si-kp-farmasi-ubp.git
```

Langkah utama:
- Cek `git status`.
- Pastikan working tree bersih sebelum push.
- Rename branch utama menjadi `main`.
- Pastikan remote `origin` mengarah ke repository GitHub yang benar.
- Pastikan `.gitignore` melindungi `.env`, `vendor`, `node_modules`, dan upload storage.
- Jalankan `php artisan test`.
- Jalankan `npm run build`.
- Cek `git status`.
- Commit report/prompt Tahap 13.2.
- Push dengan `git push -u origin main`.

Catatan keamanan:
- Jangan commit token GitHub.
- Jangan commit `.env`.
- Jangan commit file upload storage.
- Jika GitHub meminta autentikasi, gunakan mekanisme lokal user atau token melalui prompt Git, bukan disimpan dalam project.
