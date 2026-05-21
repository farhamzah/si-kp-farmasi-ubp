@include('errors.layout', [
    'code' => '404',
    'title' => 'Halaman Tidak Ditemukan',
    'message' => 'Halaman yang Anda cari tidak tersedia atau sudah dipindahkan.',
    'primaryLabel' => auth()->check() ? 'Kembali ke Dashboard' : 'Kembali ke Login',
    'primaryUrl' => auth()->check() ? route('dashboard') : route('login'),
])
