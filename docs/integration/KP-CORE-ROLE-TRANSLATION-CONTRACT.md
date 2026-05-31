# KP-Core Role Translation Contract

Tanggal: 2026-06-01

## Tujuan
Dokumen ini memformalkan translasi role dari Core Farmasi ke role lokal KP Farmasi. Kontrak ini menjaga backward compatibility role KP existing, sambil memungkinkan Core menjadi sumber app access lintas aplikasi.

## Guardrails
- Core app access adalah sumber izin lintas aplikasi, tetapi KP tetap melakukan authorization lokal.
- KP tidak menulis role ke Core.
- `admin-core` tidak boleh otomatis menjadi admin KP.
- Tidak ada SSO, auto-login, token URL, atau bypass guard KP.
- Role KP existing tetap snake_case agar route/middleware/test existing tidak rusak.

## Canonical Mapping
| Core role/app access slug | KP role lokal | Catatan |
|---|---|---|
| `admin-kp` | `admin` | Admin aplikasi KP, bukan admin Core global |
| `mahasiswa` | `mahasiswa` | Peserta KP |
| `dosen` | `pembimbing_dalam` | Alias minimal untuk dosen bila Core belum granular; staging sebaiknya pakai `pembimbing-dalam` |
| `koordinator-kp` | `koordinator_kp` | Koordinator operasional KP |
| `pembimbing-dalam` | `pembimbing_dalam` | Dosen pembimbing internal |
| `pembimbing-lapangan` | `pembimbing_lapangan` | Pembimbing luar/lapangan |
| `penguji` | `penguji` | Penguji sidang KP |

## Reverse Mapping KP ke Core
| KP role lokal | Core role/app access slug |
|---|---|
| `admin` | `admin-kp` |
| `mahasiswa` | `mahasiswa` |
| `koordinator_kp` | `koordinator-kp` |
| `pembimbing_dalam` | `pembimbing-dalam` |
| `pembimbing_lapangan` | `pembimbing-lapangan` |
| `penguji` | `penguji` |

## Role Yang Ditolak
| Core role | Keputusan | Alasan |
|---|---|---|
| `admin-core` | Tidak diterjemahkan ke role KP | Admin Core tidak otomatis punya akses admin KP |
| role tak dikenal | Tidak diterjemahkan | Fail closed agar tidak membuka akses salah |

## Normalisasi
- Input Core boleh kebab-case atau snake_case, tetapi output KP selalu snake_case.
- Input KP boleh snake_case atau kebab-case, tetapi output Core selalu kebab-case.
- Translasi harus unique dan mengabaikan role yang tidak dikenal.

## Implementasi Kode
Kontrak kode ada di:

```text
app/Support/CoreRoleTranslator.php
```

Test:

```text
tests/Feature/CoreRoleTranslatorTest.php
```

## Readiness Criteria
- Semua route/middleware KP tetap memakai role lokal existing.
- Core app access staging memakai slug canonical.
- `kp:core-mapping-coverage` tidak menemukan role mismatch untuk akun UAT.
- Admin KP memiliki `admin-kp`; tidak mengandalkan `admin-core`.

