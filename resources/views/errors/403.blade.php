@include('errors.layout', [
    'code' => '403',
    'title' => 'Akses Ditolak',
    'message' => 'Anda tidak memiliki akses ke halaman ini.',
    'primaryLabel' => auth()->check() ? 'Kembali ke Dashboard' : 'Kembali ke Login',
    'primaryUrl' => auth()->check() ? route('dashboard') : route('login'),
])
