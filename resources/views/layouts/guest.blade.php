<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'SI-KP Farmasi UBP') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-900">
    <main class="min-h-screen bg-[radial-gradient(circle_at_top_left,#ccfbf1,transparent_32%),linear-gradient(135deg,#f8fafc,#ecfeff)]">
        {{ $slot ?? '' }}
        @yield('content')
    </main>
</body>
</html>
