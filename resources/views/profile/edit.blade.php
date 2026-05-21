@extends('layouts.app')

@section('title', 'Edit Profil Akademik - '.config('app.name'))
@section('page_title', 'Edit Profil Akademik')

@section('content')
<form method="POST" action="{{ route('profile.update') }}" class="mx-auto max-w-4xl">
    @csrf
    @method('PUT')

    <section class="rounded-2xl bg-white p-8 shadow-sm ring-1 ring-slate-200">
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-slate-950">Pembaruan Data Profil</h2>
            <p class="mt-2 text-slate-600">Lengkapi informasi akademis wajib untuk mengaktifkan fitur utama sistem.</p>
        </div>

        @if($errors->any())
            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-800">
                <div class="flex gap-3">
                    <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
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
                    <input value="{{ $profile?->nim }}" disabled class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-500 cursor-not-allowed">
                    <p class="mt-1.5 text-xs text-slate-500">Data NIM tidak dapat diubah. Hubungi admin jika ada kesalahan data.</p>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Nomor Telepon Aktif</label>
                    <input name="phone" value="{{ old('phone', $profile?->phone) }}" placeholder="+62..." class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 outline-none transition">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Program Studi</label>
                    <input name="study_program" value="{{ old('study_program', $profile?->study_program) }}" placeholder="Farmasi" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 outline-none transition">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Tingkat Semester</label>
                    <input name="semester" type="number" min="1" max="14" value="{{ old('semester', $profile?->semester) }}" placeholder="7" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 outline-none transition">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Kelas</label>
                    <input name="class_name" value="{{ old('class_name', $profile?->class_name) }}" placeholder="A" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 outline-none transition">
                </div>
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
            @elseif($profileType === 'dosen')
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Nomor Induk Dosen Nasional (NIDN/NIP)</label>
                    <input value="{{ $profile?->nidn_nip }}" disabled class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-500 cursor-not-allowed">
                    <p class="mt-1.5 text-xs text-slate-500">Data NIDN/NIP tidak dapat diubah. Hubungi admin jika ada kesalahan data.</p>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Nomor Pegawai</label>
                    <input value="{{ $profile?->employee_number }}" disabled class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-500 cursor-not-allowed">
                </div>
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
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Bidang Keahlian/Expertise</label>
                    <input name="expertise" value="{{ old('expertise', $profile?->expertise) }}" placeholder="Misal: Teknologi Sediaan, Farmakologi, dll" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 outline-none transition">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Alamat Lengkap</label>
                    <textarea name="address" rows="3" placeholder="Jl. ... No. ... Kelurahan ... Kecamatan ..." class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 outline-none transition resize-none">{{ old('address', $profile?->address) }}</textarea>
                </div>
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
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Nama Lengkap</label>
                    <input name="name" value="{{ old('name', $user->name) }}" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 outline-none transition">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Alamat Email</label>
                    <input name="email" type="email" value="{{ old('email', $user->email) }}" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 outline-none transition">
                </div>
            @endif
        </div>

        <div class="mt-8 flex justify-end gap-3">
            <a href="{{ route('profile.show') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                Batal
            </a>
            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-teal-600 to-teal-700 px-5 py-2.5 text-sm font-bold text-white hover:from-teal-700 hover:to-teal-800 transition-all shadow-lg shadow-teal-600/30">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Simpan Perubahan
            </button>
        </div>
    </section>
</form>
@endsection
