@include('errors.layout', [
    'code' => '419',
    'title' => 'Sesi Kedaluwarsa',
    'message' => 'Sesi Anda telah kedaluwarsa. Silakan login kembali.',
    'primaryLabel' => 'Kembali ke Login',
    'primaryUrl' => route('login'),
])
