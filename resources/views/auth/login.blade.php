@extends('layouts.guest')

@section('content')
<div class="relative flex min-h-screen w-full items-center justify-center overflow-hidden bg-slate-950 px-4 py-5 text-slate-900 sm:px-6 lg:h-screen lg:px-8 lg:py-4">
    <div class="absolute inset-0 bg-[linear-gradient(135deg,rgba(13,148,136,0.42),rgba(15,23,42,0.9)_42%,rgba(37,99,235,0.24))]"></div>
    <div class="absolute inset-x-0 top-0 h-32 bg-linear-to-b from-white/10 to-transparent"></div>
    <div class="absolute bottom-0 left-0 right-0 h-40 bg-linear-to-t from-slate-950 to-transparent"></div>

    <div class="relative grid w-full max-w-6xl overflow-hidden rounded-2xl border border-white/15 bg-white shadow-2xl shadow-black/35 lg:max-h-[calc(100vh-2rem)] lg:grid-cols-[1fr_0.92fr]">
        <section class="relative flex min-h-100 flex-col justify-between overflow-hidden bg-slate-900 px-6 py-7 text-white sm:px-8 lg:min-h-0 lg:px-9 lg:py-8 xl:px-10">
            <div class="absolute inset-0 bg-[linear-gradient(145deg,rgba(20,184,166,0.25),rgba(15,23,42,0.16)_40%,rgba(37,99,235,0.18))]"></div>
            <div class="absolute inset-0 bg-[image:linear-gradient(rgba(255,255,255,.65)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,.65)_1px,transparent_1px)] bg-[size:28px_28px] opacity-[0.08]"></div>

            <div class="relative">
                <div class="flex items-center gap-4">
                    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-white p-2 shadow-xl shadow-teal-950/30">
                        <img src="{{ asset('images/logo-fakultas-farmasi-ubp.png') }}" alt="Logo Fakultas Farmasi UBP" class="h-full w-full object-contain">
                    </div>
                    <div>
                        <p class="text-sm font-bold uppercase text-teal-100">SI-KP Farmasi UBP</p>
                        <p class="mt-1 text-sm text-slate-300">Sistem Informasi Kerja Praktek</p>
                    </div>
                </div>

                <div class="mt-8 grid items-start gap-5 xl:mt-10 xl:grid-cols-[minmax(0,1fr)_210px]">
                    <div class="min-w-0">
                    <p class="inline-flex rounded-full border border-teal-300/30 bg-teal-300/10 px-3 py-1 text-xs font-semibold uppercase text-teal-100">Portal Akademik KP</p>
                    <h1 class="mt-4 max-w-lg text-3xl font-black leading-tight text-white sm:text-4xl lg:text-4xl">
                        Portal Kerja Praktek Farmasi UBP
                    </h1>
                    <p class="mt-4 max-w-lg text-sm leading-6 text-slate-200">
                        Kelola pendaftaran, bimbingan, laporan, dan sidang dalam satu sistem.
                    </p>
                    </div>

                    <div class="hidden w-full rounded-2xl border border-white/12 bg-white/10 p-4 shadow-2xl shadow-slate-950/25 backdrop-blur sm:max-w-sm lg:block xl:max-w-none">
                        <div class="flex items-center justify-between border-b border-white/10 pb-3">
                            <span class="text-xs font-bold uppercase text-teal-100">Progress KP</span>
                            <span class="rounded-full bg-emerald-300 px-2 py-1 text-[11px] font-black text-emerald-950">Aktif</span>
                        </div>
                        <div class="mt-4 space-y-3">
                            <div>
                                <div class="flex justify-between text-xs text-slate-200">
                                    <span>Bimbingan</span>
                                    <span>75%</span>
                                </div>
                                <div class="mt-2 h-2 overflow-hidden rounded-full bg-white/15">
                                    <div class="h-full w-3/4 rounded-full bg-teal-300"></div>
                                </div>
                            </div>
                            <div>
                                <div class="flex justify-between text-xs text-slate-200">
                                    <span>Logbook</span>
                                    <span>12/16</span>
                                </div>
                                <div class="mt-2 h-2 overflow-hidden rounded-full bg-white/15">
                                    <div class="h-full w-2/3 rounded-full bg-sky-300"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="relative mt-7">
                <div class="grid gap-3 sm:grid-cols-3">
                    @foreach ([
                        ['01', 'Daftar KP'],
                        ['02', 'Bimbingan'],
                        ['03', 'Sidang & Nilai'],
                    ] as [$number, $label])
                        <div class="rounded-xl border border-white/12 bg-white/10 p-3 shadow-lg shadow-slate-950/10 backdrop-blur">
                            <p class="text-xs font-black text-teal-200">{{ $number }}</p>
                            <p class="mt-2 text-sm font-bold text-white">{{ $label }}</p>
                        </div>
                    @endforeach
                </div>

                <div class="mt-3 hidden gap-3 xl:grid xl:grid-cols-2">
                @foreach ([
                    'Mahasiswa, Admin, Koordinator, Dosen Pembimbing, Penguji',
                    'Berkas, logbook, laporan, dan nilai tersusun rapi',
                ] as $feature)
                    <div class="flex items-center gap-3 rounded-xl border border-white/12 bg-white/10 px-4 py-3 text-sm font-semibold text-slate-100 shadow-lg shadow-slate-950/10 backdrop-blur">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-teal-400 text-slate-950">
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 0 1 .006 1.414l-7.2 7.27a1 1 0 0 1-1.42 0L4.29 10.14a1 1 0 0 1 1.42-1.41l3.09 3.116 6.49-6.55a1 1 0 0 1 1.414-.006Z" clip-rule="evenodd" />
                            </svg>
                        </span>
                        <span>{{ $feature }}</span>
                    </div>
                @endforeach
                </div>
            </div>
        </section>

        <section class="flex items-center bg-white px-6 py-7 sm:px-10 lg:px-12">
            <div class="w-full">
                <div class="mb-5">
                    <p class="text-sm font-semibold uppercase text-teal-700">Portal Kerja Praktek</p>
                    <h2 class="mt-2 text-2xl font-black text-slate-950">Masuk ke SI-KP</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">Akses dashboard sesuai peran Anda.</p>
                </div>

                @if(session('status'))
                    <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                        <div class="flex gap-3">
                            <svg class="mt-0.5 h-5 w-5 shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.707-9.293a1 1 0 0 0-1.414-1.414L9 10.586 7.707 9.293a1 1 0 0 0-1.414 1.414l2 2a1 1 0 0 0 1.414 0l4-4Z" clip-rule="evenodd"/>
                            </svg>
                            <span>{{ session('status') }}</span>
                        </div>
                    </div>
                @endif

                @if($errors->any())
                    <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        <div class="flex gap-3">
                            <svg class="mt-0.5 h-5 w-5 shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16ZM8.707 7.293a1 1 0 0 0-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 1 0 1.414 1.414L10 11.414l1.293 1.293a1 1 0 0 0 1.414-1.414L11.414 10l1.293-1.293a1 1 0 0 0-1.414-1.414L10 8.586 8.707 7.293Z" clip-rule="evenodd"/>
                            </svg>
                            <span>{{ $errors->first() }}</span>
                        </div>
                    </div>
                @endif

                <form method="POST" action="{{ route('login.store') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label for="email" class="mb-2 block text-sm font-bold text-slate-800">Email Akun SI-KP</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus placeholder="nama@ubp.ac.id" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-950 outline-none transition placeholder:text-slate-400 hover:border-slate-400 focus:border-teal-600 focus:bg-white focus:ring-4 focus:ring-teal-600/15">
                        @error('email')
                            <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="mb-2 block text-sm font-bold text-slate-800">Kata Sandi</label>
                        <input id="password" name="password" type="password" required placeholder="Masukkan kata sandi" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-950 outline-none transition placeholder:text-slate-400 hover:border-slate-400 focus:border-teal-600 focus:bg-white focus:ring-4 focus:ring-teal-600/15">
                        @error('password')
                            <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-between gap-4">
                        <label class="flex cursor-pointer items-center gap-3 text-sm font-medium text-slate-600">
                            <input type="checkbox" name="remember" class="h-4 w-4 rounded border-slate-300 text-teal-600 focus:ring-2 focus:ring-teal-500">
                            <span>Ingat perangkat ini</span>
                        </label>
                    </div>

                    <button type="submit" class="group flex w-full items-center justify-center gap-2 rounded-xl bg-teal-700 px-4 py-3 text-sm font-black text-white shadow-xl shadow-teal-700/25 transition hover:bg-teal-800 focus:outline-none focus:ring-4 focus:ring-teal-600/20 focus:ring-offset-2">
                        <span>Buka Dashboard KP</span>
                        <svg class="h-4 w-4 transition group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                    </button>
                </form>

                <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                    <p>Belum punya akun atau role KP belum sesuai? Hubungi <span class="font-bold text-slate-900">Admin Program</span>.</p>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
