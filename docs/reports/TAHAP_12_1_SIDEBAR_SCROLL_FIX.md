# TAHAP 12.1 - Sidebar Scroll Fix

## 1. Masalah
Sidebar role dengan menu panjang seperti Admin dan Koordinator KP tidak bisa discroll pada desktop. Akibatnya item menu bagian bawah dapat terpotong dan sulit diakses pada viewport pendek.

## 2. Perbaikan
- Sidebar desktop dibuat setinggi viewport dengan `lg:h-screen`, `lg:flex`, `lg:flex-col`, dan `lg:overflow-hidden`.
- Header/logo sidebar dibuat `flex-none` agar tetap berada di atas.
- Area navigation dibuat `lg:flex-1`, `lg:min-h-0`, `lg:overflow-y-auto`, dan `lg:overflow-x-hidden` agar daftar menu dapat discroll vertikal.
- Mobile tetap mempertahankan menu horizontal scroll yang sudah ada.
- Ditambahkan class `si-sidebar-scroll` untuk scrollbar halus tanpa dependency baru.

## 3. File yang Diubah
- `resources/views/layouts/app.blade.php`
- `resources/css/app.css`

## 4. Testing
- `php artisan test`: berhasil, 77 passed, 376 assertions.
- `npm run build`: berhasil.
- `git status`: clean setelah commit.

## 5. Catatan
Perbaikan hanya menyentuh layout scroll sidebar dan polish scrollbar kecil. Logic role, mapping menu, active state, dan route tidak diubah.
