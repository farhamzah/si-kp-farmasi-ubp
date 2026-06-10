@extends('layouts.app')

@section('title', 'Edit Profil Akademik - '.config('app.name'))
@section('page_title', 'Edit Profil Akademik')

@section('content')
<div class="mx-auto max-w-4xl space-y-6">
@php($coreManaged = (bool) $coreOfficialProfile)
@if($coreProfileUrl)
    <section class="rounded-2xl border border-blue-200 bg-blue-50 p-5 shadow-sm">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-base font-bold text-blue-950">Profil utama dikelola di Core Farmasi</h2>
                <p class="mt-1 text-sm leading-6 text-blue-900">Gunakan Core untuk memperbarui data utama seperti nomor HP dan alamat. Form KP ini dipertahankan untuk data operasional KP.</p>
            </div>
            <a href="{{ $coreProfileUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-bold text-white shadow-lg shadow-blue-700/20 transition hover:bg-blue-800">
                Ubah Profil di Core
            </a>
        </div>
    </section>
@endif

@if($coreOfficialProfile)
    <section class="overflow-hidden rounded-3xl bg-white shadow-xl shadow-cyan-950/8 ring-1 ring-cyan-100">
        <div class="relative p-6 sm:p-8">
            <div class="absolute inset-x-0 top-0 h-1 bg-linear-to-r from-cyan-700 via-teal-500 to-emerald-400"></div>
        <div class="mb-5 flex flex-col gap-3 border-b border-slate-100 pb-5 md:flex-row md:items-start md:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.22em] text-cyan-700">Data Resmi Core</p>
                <h2 class="mt-2 text-xl font-black text-slate-950">Identitas read-only</h2>
                <p class="mt-1 text-sm leading-6 text-slate-500">{{ data_get($coreOfficialProfile, 'notice') }}</p>
            </div>
            <span class="w-fit rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">Source of truth</span>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            @foreach(data_get($coreOfficialProfile, 'sections', []) as $sectionTitle => $items)
                <section class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <h3 class="text-sm font-black text-slate-950">{{ $sectionTitle }}</h3>
                    <dl class="mt-4 space-y-3">
                        @foreach($items as $label => $value)
                            <div>
                                <dt class="text-[11px] font-bold uppercase tracking-wide text-slate-500">{{ $label }}</dt>
                                <dd class="mt-1 break-words text-sm font-semibold text-slate-900">{{ filled($value) ? $value : '-' }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </section>
            @endforeach
        </div>
        </div>
    </section>
@endif

@unless($coreOfficialProfile)
<section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
    <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
        <x-ui.avatar :user="$user" size="xl" class="shadow-xl shadow-cyan-900/10" />
        <div class="min-w-0 flex-1">
            <h2 class="text-xl font-bold text-slate-950">Foto Profil</h2>
            <p class="mt-1 text-sm leading-6 text-slate-500">Gunakan foto JPG/PNG/WebP maksimal 2MB. Foto akan tampil di topbar, dashboard, dan halaman pilih role.</p>
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

<form method="POST" action="{{ route('profile.update') }}">
    @csrf
    @method('PUT')

    <section class="rounded-2xl bg-white p-8 shadow-sm ring-1 ring-slate-200">
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-slate-950">Pembaruan Data Operasional KP</h2>
            <p class="mt-2 text-slate-600">
                @if($coreManaged)
                    Field identitas resmi dikunci karena sudah dikelola Core. Isi hanya data tambahan yang dibutuhkan workflow KP.
                @else
                    Lengkapi informasi akademis wajib untuk mengaktifkan fitur utama sistem.
                @endif
            </p>
        </div>

        @if($errors->any())
            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-800">
                <div class="flex gap-3">
                    <svg class="w-5 h-5 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <span>{{ $errors->first() }}</span>
                </div>
            </div>
        @endif

        <div class="grid gap-5 md:grid-cols-2">
            @if($profileType === 'mahasiswa')
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Nomor Induk Mahasiswa (NIM)</label>
                    <input value="{{ $coreManaged ? data_get($coreOfficialProfile, 'linked_profile.student_number') : $profile?->nim }}" disabled class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-500 cursor-not-allowed">
                    <p class="mt-1.5 text-xs text-slate-500">Data NIM tidak dapat diubah di KP. Hubungi admin Core jika ada kesalahan data resmi.</p>
                </div>
                @unless($coreManaged)
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Nomor Telepon Aktif</label>
                        <input name="phone" value="{{ old('phone', $profile?->phone) }}" placeholder="+62..." class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 outline-none transition">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Program Studi</label>
                        <input name="study_program" value="{{ old('study_program', $profile?->study_program) }}" placeholder="Farmasi" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 outline-none transition">
                    </div>
                @endunless
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Tingkat Semester</label>
                    <input name="semester" type="number" min="1" max="14" value="{{ old('semester', $profile?->semester) }}" placeholder="7" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 outline-none transition">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Kelas</label>
                    <input name="class_name" value="{{ old('class_name', $profile?->class_name) }}" placeholder="A" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 outline-none transition">
                </div>
                @unless($coreManaged)
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Jenis Kelamin</label>
                        <select name="gender" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 outline-none transition">
                            <option value="">Pilih jenis kelamin</option>
                            <option value="Laki-laki" @selected(old('gender', $profile?->gender) === 'Laki-laki')>Laki-laki</option>
                            <option value="Perempuan" @selected(old('gender', $profile?->gender) === 'Perempuan')>Perempuan</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Tempat Lahir</label>
                        <input name="birth_place" value="{{ old('birth_place', $profile?->birth_place) }}" placeholder="Kota/Kabupaten" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 outline-none transition">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Tanggal Lahir</label>
                        <input name="birth_date" type="date" value="{{ old('birth_date', $profile?->birth_date?->format('Y-m-d')) }}" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 outline-none transition">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Alamat Lengkap</label>
                        <textarea name="address" rows="3" placeholder="Jl. ... No. ... Kelurahan ... Kecamatan ..." class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 outline-none transition resize-none">{{ old('address', $profile?->address) }}</textarea>
                    </div>
                @endunless
            @elseif($profileType === 'dosen')
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Nomor Induk Dosen Nasional (NIDN/NIP)</label>
                    <input value="{{ $coreManaged ? (data_get($coreOfficialProfile, 'linked_profile.nidn') ?: data_get($coreOfficialProfile, 'linked_profile.lecturer_number')) : $profile?->nidn_nip }}" disabled class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-500 cursor-not-allowed">
                    <p class="mt-1.5 text-xs text-slate-500">Data NIDN/NIP tidak dapat diubah di KP. Hubungi admin Core jika ada kesalahan data resmi.</p>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Nomor Pegawai</label>
                    <input value="{{ $coreManaged ? data_get($coreOfficialProfile, 'linked_profile.nip') : $profile?->employee_number }}" disabled class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-500 cursor-not-allowed">
                </div>
                @unless($coreManaged)
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Nomor Telepon Aktif</label>
                        <input name="phone" value="{{ old('phone', $profile?->phone) }}" placeholder="+62..." class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 outline-none transition">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Program Studi/Prodi</label>
                        <input name="study_program" value="{{ old('study_program', $profile?->study_program) }}" placeholder="Farmasi" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 outline-none transition">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Departemen/Unit</label>
                        <input name="department" value="{{ old('department', $profile?->department) }}" placeholder="Teknologi Farmasi" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 outline-none transition">
                    </div>
                @endunless
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Bidang Keahlian/Expertise</label>
                    <input name="expertise" value="{{ old('expertise', $profile?->expertise) }}" placeholder="Misal: Teknologi Sediaan, Farmakologi, dll" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 outline-none transition">
                </div>
                @unless($coreManaged)
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Alamat Lengkap</label>
                        <textarea name="address" rows="3" placeholder="Jl. ... No. ... Kelurahan ... Kecamatan ..." class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 outline-none transition resize-none">{{ old('address', $profile?->address) }}</textarea>
                    </div>
                @endunless
            @elseif($profileType === 'pembimbing_lapangan')
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Nomor Telepon Aktif</label>
                    <input name="phone" value="{{ old('phone', $profile?->phone) }}" placeholder="+62..." class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 outline-none transition">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Nama Institusi/Tempat KP</label>
                    <input name="institution_name" value="{{ old('institution_name', $profile?->institution_name) }}" placeholder="Nama tempat kerja praktik" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 outline-none transition">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Jabatan/Posisi</label>
                    <input name="position" value="{{ old('position', $profile?->position) }}" placeholder="Misal: Apoteker, Manajer, Supervisor, dll" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 outline-none transition">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Alamat Lengkap</label>
                    <textarea name="address" rows="3" placeholder="Jl. ... No. ... Kelurahan ... Kecamatan ..." class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 outline-none transition resize-none">{{ old('address', $profile?->address) }}</textarea>
                </div>
            @else
                @if($coreManaged)
                    <div class="md:col-span-2 rounded-xl border border-blue-200 bg-blue-50 px-5 py-4 text-sm leading-6 text-blue-900">
                        Identitas akun admin dikelola dari Core Farmasi. Tidak ada field operasional KP yang perlu diisi untuk role aktif ini.
                    </div>
                @else
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Nama Lengkap</label>
                        <input name="name" value="{{ old('name', $user->name) }}" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 outline-none transition">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Alamat Email</label>
                        <input name="email" type="email" value="{{ old('email', $user->email) }}" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 outline-none transition">
                    </div>
                @endif
            @endif
        </div>

        <div class="mt-8 flex justify-end gap-3">
            <a href="{{ route('profile.show') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                Batal
            </a>
            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-linear-to-r from-teal-600 to-teal-700 px-5 py-2.5 text-sm font-bold text-white hover:from-teal-700 hover:to-teal-800 transition-all shadow-lg shadow-teal-600/30">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Simpan Perubahan
            </button>
        </div>
    </section>
</form>
</div>
@endsection
