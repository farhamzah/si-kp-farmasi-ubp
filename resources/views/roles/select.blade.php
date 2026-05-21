@extends('layouts.guest')

@section('content')
<div class="min-h-screen px-4 py-8">
    <div class="mx-auto max-w-5xl">
        <div class="mb-6 flex flex-col gap-3 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-sm font-semibold text-teal-700">Pilih Akses</p>
                <h1 class="mt-1 text-2xl font-bold text-slate-950">{{ auth()->user()->name }}</h1>
                <p class="text-sm text-slate-500">{{ auth()->user()->email }}</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">Logout</button>
            </form>
        </div>

        <div class="mb-6 rounded-xl border border-teal-200 bg-teal-50 px-4 py-3 text-sm text-teal-900">
            Akun Anda memiliki lebih dari satu akses. Silakan pilih peran yang ingin digunakan.
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach($roles as $role)
                <form method="POST" action="{{ route('role.set', $role) }}" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 transition hover:-translate-y-0.5 hover:shadow-md">
                    @csrf
                    <div class="flex items-start gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-teal-100 text-sm font-bold text-teal-700">{{ strtoupper(substr($role->label, 0, 2)) }}</div>
                        <div class="min-w-0 flex-1">
                            <h2 class="font-bold text-slate-950">{{ $role->label }}</h2>
                            <p class="mt-1 text-sm leading-6 text-slate-500">{{ $role->description ?: 'Akses aplikasi SI-KP Farmasi UBP.' }}</p>
                        </div>
                    </div>
                    <button class="mt-5 w-full rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-700">Masuk sebagai {{ $role->label }}</button>
                </form>
            @endforeach
        </div>
    </div>
</div>
@endsection
