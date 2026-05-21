@include('errors.layout', [
    'code' => '500',
    'title' => 'Terjadi Kesalahan',
    'message' => 'Maaf, sistem belum dapat memproses permintaan Anda. Silakan coba beberapa saat lagi atau hubungi Admin.',
    'primaryLabel' => auth()->check() ? 'Kembali ke Dashboard' : 'Kembali ke Login',
    'primaryUrl' => auth()->check() ? route('dashboard') : route('login'),
])
