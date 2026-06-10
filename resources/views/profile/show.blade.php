@extends('layouts.app')

@section('title', 'Profil Akademik - '.config('app.name'))
@section('page_title', 'Profil Akademik')

@section('content')
<div class="grid gap-6 xl:grid-cols-[0.8fr_1.2fr]">
    @if($coreProfileUrl)
        <section class="rounded-2xl border border-blue-200 bg-blue-50 p-5 shadow-sm xl:col-span-2">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-base font-bold text-blue-950">Profil utama dikelola di Core Farmasi</h2>
                    <p class="mt-1 text-sm leading-6 text-blue-900">Data identitas utama, nomor HP, dan alamat diperbarui melalui Core. KP tetap menyimpan data operasional KP seperti berkas, penilaian, dan workflow.</p>
                </div>
                <a href="{{ $coreProfileUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-bold text-white shadow-lg shadow-blue-700/20 transition hover:bg-blue-800">
                    Ubah Profil di Core
                </a>
            </div>
        </section>
    @endif

    @if($coreOfficialProfile)
        <section class="overflow-hidden rounded-3xl bg-white shadow-xl shadow-cyan-950/8 ring-1 ring-cyan-100 xl:col-span-2">
            <div class="relative grid gap-0 xl:grid-cols-[1.05fr_0.95fr]">
                <div class="absolute inset-x-0 top-0 h-1 bg-linear-to-r from-cyan-700 via-teal-500 to-emerald-400"></div>
                <div class="p-6 sm:p-8">
                    <div class="flex flex-col gap-5 md:flex-row md:items-start">
                    @if(data_get($coreOfficialProfile, 'user.profile_photo_url'))
                        <img src="{{ data_get($coreOfficialProfile, 'user.profile_photo_url') }}" alt="Foto profil Core" class="h-24 w-24 rounded-[1.5rem] object-cover shadow-xl shadow-cyan-900/15 ring-4 ring-white">
                    @else
                        <div class="flex h-24 w-24 items-center justify-center rounded-[1.5rem] bg-cyan-50 text-2xl font-black text-cyan-700 shadow-xl shadow-cyan-900/10 ring-4 ring-white">
                            {{ $user->initials() }}
                        </div>
                    @endif
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-xs font-black uppercase tracking-[0.22em] text-cyan-700">Profil Resmi Core</p>
                                <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">Read-only</span>
                            </div>
                            <h3 class="mt-3 text-3xl font-black tracking-tight text-slate-950">{{ data_get($coreOfficialProfile, 'user.name', $user->name) }}</h3>
                            <p class="mt-2 break-words text-sm font-semibold uppercase tracking-widest text-slate-500">{{ data_get($coreOfficialProfile, 'user.email', $user->email) }}</p>
                            <p class="mt-4 max-w-2xl text-sm leading-7 text-slate-600">{{ data_get($coreOfficialProfile, 'notice') }}</p>
                            @if($coreProfileUrl)
                                <a href="{{ $coreProfileUrl }}" target="_blank" rel="noopener noreferrer" class="mt-5 inline-flex items-center justify-center rounded-2xl border border-cyan-200 bg-white px-4 py-2.5 text-sm font-black text-cyan-700 shadow-sm transition hover:bg-cyan-50">
                                    Buka Profil Core
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="border-t border-cyan-100 bg-slate-50/80 p-6 xl:border-l xl:border-t-0 sm:p-8">
                    <div class="grid gap-4 md:grid-cols-3 xl:grid-cols-1">
                        @foreach(data_get($coreOfficialProfile, 'sections', []) as $sectionTitle => $items)
                            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                                <h4 class="text-sm font-black text-slate-950">{{ $sectionTitle }}</h4>
                                <dl class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                                    @foreach($items as $label => $value)
                                        <div>
                                            <dt class="text-[11px] font-black uppercase tracking-wide text-slate-500">{{ $label }}</dt>
                                            <dd class="mt-1 break-words text-sm font-bold text-slate-950">{{ filled($value) ? $value : '-' }}</dd>
                                        </div>
                                    @endforeach
                                </dl>
                            </section>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif

    <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div class="mb-6 flex items-center gap-4 border-b border-slate-200 pb-6">
            <x-ui.avatar :user="$user" size="lg" class="shadow-md shadow-teal-500/15" />
            <div>
                <h2 class="text-xl font-bold text-slate-950">{{ $user->name }}</h2>
                <p class="mt-1 text-xs font-medium uppercase tracking-widest text-slate-500">{{ $user->email }}</p>
            </div>
        </div>

        <div class="mb-6 space-y-3 text-sm">
            <div class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-3">
                <span class="font-medium text-slate-600">Status Akun</span>
                <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800">{{ $user->status === 'active' ? 'Aktif' : 'Nonaktif' }}</span>
            </div>
            <div class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-3">
                <span class="font-medium text-slate-600">Kelengkapan Data</span>
                <span class="rounded-full {{ $user->profile_completed ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }} px-3 py-1 text-xs font-semibold">{{ $user->profile_completed ? 'Lengkap' : 'Belum Lengkap' }}</span>
            </div>
            <div class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-3">
                <span class="font-medium text-slate-600">Role Aktif</span>
                <span class="text-xs font-semibold text-slate-700">{{ \App\Support\RoleDashboard::labelFor(session('active_role')) }}</span>
            </div>
        </div>

        <a href="{{ route('profile.edit') }}" class="block w-full rounded-lg bg-linear-to-r from-teal-600 to-teal-700 px-4 py-2.5 text-center text-sm font-bold text-white shadow-lg shadow-teal-600/30 transition hover:from-teal-700 hover:to-teal-800">Edit Data Operasional KP</a>
    </section>

    @unless($coreOfficialProfile)
    <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
            <x-ui.avatar :user="$user" size="xl" class="shadow-xl shadow-cyan-900/10" />
            <div class="min-w-0 flex-1">
                <h3 class="text-lg font-bold text-slate-950">Foto Profil</h3>
                <p class="mt-1 text-sm leading-6 text-slate-500">Foto ini ditampilkan pada topbar, dashboard, dan halaman pilih role. Gunakan foto JPG/PNG/WebP maksimal 2MB.</p>
                @if($user->avatar_original_filename)
                    <p class="mt-2 truncate text-xs font-semibold text-cyan-700">{{ $user->avatar_original_filename }}</p>
                @endif
            </div>
        </div>

        @error('avatar')
            <div class="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">{{ $message }}</div>
        @enderror

        <div class="mt-5 grid gap-3 md:grid-cols-[1fr_auto]">
            <form method="POST" action="{{ route('profile.avatar.update') }}" enctype="multipart/form-data" class="flex flex-col gap-3 sm:flex-row">
                @csrf
                <input type="file" name="avatar" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm file:mr-4 file:rounded-lg file:border-0 file:bg-cyan-50 file:px-3 file:py-1.5 file:text-sm file:font-bold file:text-cyan-700 focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-500/20">
                <button class="flex-none rounded-xl bg-cyan-700 px-4 py-2.5 text-sm font-bold text-white shadow-lg shadow-cyan-700/20 transition hover:bg-cyan-800">Ubah Foto</button>
            </form>
            @if($user->hasAvatar())
                <form method="POST" action="{{ route('profile.avatar.delete') }}">
                    @csrf
                    @method('DELETE')
                    <button class="w-full rounded-xl border border-rose-200 bg-white px-4 py-2.5 text-sm font-bold text-rose-700 transition hover:bg-rose-50">Hapus Foto</button>
                </form>
            @endif
        </div>
    </section>
    @endunless

    <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <h3 class="text-lg font-bold text-slate-950">Peran Akademik</h3>
        <p class="mb-5 mt-1 text-sm text-slate-500">Peran yang terikat pada akun Anda dalam sistem.</p>
        <div class="grid gap-3 md:grid-cols-2">
            @foreach($user->roles as $role)
                <div class="rounded-xl border border-slate-200 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h4 class="font-bold text-slate-950">{{ $role->label }}</h4>
                            <p class="mt-1.5 text-xs leading-5 text-slate-500">{{ $role->description }}</p>
                        </div>
                        @if(session('active_role') === $role->name)
                            <span class="rounded-lg bg-teal-100 px-2.5 py-1 text-xs font-bold text-teal-700">Aktif</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 xl:col-span-2">
        <h3 class="text-lg font-bold text-slate-950">Data Operasional KP</h3>
        <p class="mb-5 mt-1 text-sm text-slate-500">Informasi {{ ucwords(str_replace('_', ' ', $profileType)) }} yang tersimpan di KP untuk kebutuhan workflow kerja praktek.</p>
        @if($operationalAttributes)
            <div class="grid gap-3 md:grid-cols-3">
                @foreach($operationalAttributes as $label => $value)
                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <p class="mb-2 text-xs font-black uppercase tracking-wider text-slate-500">{{ $label }}</p>
                        <p class="text-sm font-bold text-slate-950">{{ $value ?: '-' }}</p>
                    </div>
                @endforeach
            </div>
        @elseif($coreOfficialProfile)
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm leading-6 text-emerald-800">
                Belum ada data operasional tambahan yang perlu diisi di KP untuk role aktif ini. Data identitas yang sama sudah diambil dari Core.
            </div>
        @else
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800">Profil khusus belum terdaftar. Silakan lengkapi profil bila diperlukan.</div>
        @endif
    </section>
</div>
@endsection
