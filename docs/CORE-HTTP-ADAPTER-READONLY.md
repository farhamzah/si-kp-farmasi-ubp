# KP Core HTTP Adapter Read-Only

Dokumen ini menjelaskan skeleton adapter HTTP read-only KP Farmasi ke Core Farmasi API. Adapter ini disiapkan untuk smoke test dan integrasi bertahap, bukan cutover production.

## Purpose
- Membaca data Core melalui endpoint app-client read-only.
- Menjaga KP tetap memakai auth/guard lokal.
- Menjaga mode default KP tetap legacy.
- Menyediakan fallback aman saat Core API belum aktif atau tidak tersedia.

## Non-Goals
- Tidak membuat SSO.
- Tidak membuat auto-login.
- Tidak membuat cross-app session.
- Tidak mengirim token atau secret lewat URL.
- Tidak menulis data ke Core.
- Tidak mengganti auth KP.
- Tidak menghapus Core DB read-only bridge existing.

## Environment Variables
Default semua aman dan nonaktif:

```env
KP_CORE_HTTP_ENABLED=false
KP_CORE_BASE_URL=
KP_CORE_PROFILE_URL=
KP_CORE_APP_CODE=kp-farmasi
KP_CORE_CLIENT_ID=
KP_CORE_CLIENT_SECRET=
KP_CORE_TIMEOUT=5
KP_CORE_CONNECT_TIMEOUT=3
KP_CORE_VERIFY_SSL=true
KP_CORE_READ_MODE=legacy
KP_CORE_FAIL_SILENTLY=true
```

Real client secret harus disimpan di environment atau secret manager staging/production, bukan repository, dokumentasi, report, atau log.

`KP_CORE_PROFILE_URL` bersifat optional. Jika kosong dan `KP_CORE_BASE_URL` diisi, adapter dapat menurunkan link browser ke `https://core.example.test/profile`. Link ini hanya untuk membuka Profile Portal Core, bukan SSO, bukan auto-login, dan tidak boleh berisi token/secret.

## Service
Service `App\Services\CoreFarmasiClient` memakai Laravel HTTP client dengan header app-client:

```http
X-Core-App-Code: kp-farmasi
X-Core-Client-Id: <client-id>
X-Core-Client-Secret: <client-secret>
Accept: application/json
```

Jika `KP_CORE_HTTP_ENABLED=false` atau credential belum lengkap, service mengembalikan `null` atau collection kosong dan tidak memanggil HTTP.

## Endpoint Mapping
- `getUser($id)` -> `GET /api/v1/internal/directory/users/{id}`
- `searchUsers($params)` -> `GET /api/v1/internal/directory/users`
- `getStudent($id)` -> `GET /api/v1/internal/directory/students/{id}`
- `searchStudents($params)` -> `GET /api/v1/internal/directory/students`
- `getLecturer($id)` -> `GET /api/v1/internal/directory/lecturers/{id}`
- `searchLecturers($params)` -> `GET /api/v1/internal/directory/lecturers`
- `getStudyProgram($id)` -> `GET /api/v1/internal/directory/study-programs/{id}`
- `listStudyPrograms($params)` -> `GET /api/v1/internal/directory/study-programs`
- `getCurrentLeadership($params)` -> `GET /api/v1/internal/leadership/current`
- `checkUserAppAccess($userId)` -> `GET /api/v1/internal/apps/kp-farmasi/users/{user}/access`

Semua call bersifat read-only.

## Core Profile Link
KP menampilkan notice "Profil utama dikelola di Core Farmasi" pada halaman profil lokal jika `KP_CORE_PROFILE_URL` atau `KP_CORE_BASE_URL` dikonfigurasi.

- Link target: Core `/profile` atau `/profile/edit`.
- Link dibuka sebagai browser link biasa.
- Jika user belum login Core, user login di Core.
- Tidak ada token URL.
- Tidak ada SSO.
- Tidak ada auto-login.
- Tidak ada write-back dari KP ke Core.
- Form operasional KP tetap untuk data khusus KP, bukan canonical profile ownership.

## Smoke Test Steps
1. Pastikan Core staging sudah punya app client aktif untuk `kp-farmasi`.
2. Berikan ability minimal sesuai endpoint yang diuji, misalnya `read:app-access`, `read:leadership`, `read:users`, `read:students`, `read:lecturers`, dan `read:study-programs`.
3. Set env KP staging dengan `KP_CORE_HTTP_ENABLED=true`, `KP_CORE_BASE_URL`, `KP_CORE_CLIENT_ID`, dan `KP_CORE_CLIENT_SECRET`.
4. Jalankan test adapter dengan HTTP fake di local/CI.
5. Jalankan smoke request manual di staging tanpa menampilkan secret.
6. Jika Core unavailable atau credential invalid, KP harus tetap aman dan tidak mengganti auth lokal.

Checklist staging lengkap tersedia di `docs/CORE-HTTP-ADAPTER-STAGING-SMOKE-TEST.md`.

## Safety Notes
- Default adapter disabled.
- Default read mode tetap `legacy`.
- Secret tidak disimpan di file.
- Secret tidak masuk URL.
- Error 401/403/404/429/500 ditangani aman saat `KP_CORE_FAIL_SILENTLY=true`.
- Cutover ke Core HTTP tidak dilakukan di tahap ini.
