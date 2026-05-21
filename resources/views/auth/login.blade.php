@extends('layouts.guest')

@section('content')
<div class="flex min-h-screen items-center justify-center px-4 py-10">
    <div class="grid w-full max-w-5xl overflow-hidden rounded-2xl border border-white/70 bg-white shadow-xl shadow-teal-900/10 lg:grid-cols-[1.05fr_0.95fr]">
        <section class="bg-slate-900 px-8 py-10 text-white md:px-10">
            <div class="mb-12 flex items-center gap-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-teal-500 font-bold">KP</div>
                <div>
                    <p class="text-sm font-semibold text-teal-200">SI-KP Farmasi UBP</p>
                    <p class="text-xs text-slate-300">Sistem Informasi Kerja Praktek</p>
                </div>
            </div>
            <h1 class="max-w-md text-3xl font-bold leading-tight md:text-4xl">Kelola Kerja Praktek Farmasi dengan alur yang jelas.</h1>
            <p class="mt-4 max-w-md text-sm leading-6 text-slate-300">Masuk untuk mengakses dashboard sesuai peran Anda sebagai mahasiswa, admin, koordinator, pembimbing, atau penguji.</p>
            <div class="mt-8 grid gap-3 text-sm text-slate-200 sm:grid-cols-2">
                <div class="rounded-xl bg-white/10 p-4">Multi-role siap digunakan</div>
                <div class="rounded-xl bg-white/10 p-4">Dashboard responsif</div>
                <div class="rounded-xl bg-white/10 p-4">Akses dilindungi middleware</div>
                <div class="rounded-xl bg-white/10 p-4">UI berbahasa Indonesia</div>
            </div>
        </section>

        <section class="px-6 py-8 md:px-10 md:py-10">
            <h2 class="text-2xl font-bold text-slate-950">Masuk</h2>
            <p class="mt-2 text-sm text-slate-500">Gunakan email dan password akun SI-KP.</p>

            @if(session('status'))
                <div class="mt-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
            @endif

            @if($errors->any())
                <div class="mt-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login.store') }}" class="mt-6 space-y-5">
                @csrf
                <div>
                    <label for="email" class="text-sm font-semibold text-slate-700">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus class="mt-2 w-full rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-teal-500 focus:ring-4 focus:ring-teal-100">
                </div>
                <div>
                    <label for="password" class="text-sm font-semibold text-slate-700">Password</label>
                    <input id="password" name="password" type="password" required class="mt-2 w-full rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-teal-500 focus:ring-4 focus:ring-teal-100">
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="remember" class="rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                    Ingat saya
                </label>
                <button class="w-full rounded-lg bg-teal-600 px-4 py-3 text-sm font-bold text-white shadow-sm hover:bg-teal-700 focus:outline-none focus:ring-4 focus:ring-teal-100">Masuk ke Dashboard</button>
            </form>
        </section>
    </div>
</div>
@endsection
