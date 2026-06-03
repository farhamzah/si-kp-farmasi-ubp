# KP-26 - Release Candidate Gate & Final Go-Live Checklist

Tanggal: 2026-06-03

## Ringkasan
KP-26 menambahkan gate read-only terakhir sebelum release tag/push/deploy production. Gate ini merangkum status kandidat release tanpa mengaktifkan integrasi write ke Core, TU, atau SAFA.

## File Dibuat/Diubah
Dibuat:
- `app/Console/Commands/ReleaseCandidateGateCommand.php`
- `tests/Feature/ReleaseCandidateGateCommandTest.php`
- `docs/reports/KP-26-RELEASE-CANDIDATE-GATE-FINAL-GO-LIVE-CHECKLIST.md`
- `docs/prompts/PROMPT_KP_26_RELEASE_CANDIDATE_GATE.md`

Diubah:
- `bootstrap/app.php`

## Command

```bash
php artisan kp:release-candidate-gate
```

Mode strict sebelum tag:

```bash
php artisan kp:release-candidate-gate --strict-git
```

## Cakupan Gate
- Command diagnostic wajib terdaftar.
- `kp:release-sensitive-scan` harus bersih.
- `APP_ENV=production`.
- `APP_DEBUG=false`.
- `APP_URL` harus HTTPS.
- `SESSION_SECURE_COOKIE=true`.
- Runtime write bridge TU/SAFA tetap tertutup.
- SSO/autologin/token URL tetap tidak aktif.
- Tabel inti KP tersedia.
- Working tree clean menjadi warning default dan blocker pada `--strict-git`.
- Queue/cache/mail production memberi warning bila masih memakai default lokal.

## Manual Sign-Off Wajib Sebelum Go-Live
- Production environment diverifikasi.
- Backup database diverifikasi.
- Backup storage diverifikasi.
- UAT acceptance disetujui.
- Owner rollback ditetapkan.
- Domain dan SSL diverifikasi.
- Mail service production diverifikasi.
- Akun demo dinonaktifkan atau password dirotasi.

## Guardrails
- Command read-only.
- Tidak mengirim external HTTP request.
- Tidak write ke Core/TU/SAFA.
- Tidak membuat tag otomatis.
- Tidak push otomatis.
- Tidak deploy otomatis.
- Runtime TU/SAFA bridge tetap closed.

## Status Lokal Saat Implementasi
Pada environment lokal, gate diperkirakan gagal karena `.env` masih development:
- `APP_ENV` bukan `production`.
- `APP_DEBUG` belum `false`.
- `APP_URL` belum HTTPS production.
- `SESSION_SECURE_COOKIE` belum `true`.

Kondisi tersebut benar untuk local development dan harus diselesaikan di server production sebelum tag/deploy.

## Rekomendasi KP-27
KP-27 sebaiknya menjalankan final checkpoint/tag release setelah:
1. production `.env` dikonfigurasi di server,
2. backup DB/storage diverifikasi,
3. `kp:release-candidate-gate --strict-git` lulus,
4. `php artisan test` dan `npm run build` lulus,
5. approval go-live diberikan.
