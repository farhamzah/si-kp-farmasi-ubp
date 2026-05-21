@extends('layouts.app')

@section('title', 'Edit Profil - '.config('app.name'))
@section('page_title', 'Edit Profil')

@section('content')
<form method="POST" action="{{ route('profile.update') }}" class="mx-auto max-w-4xl">
    @csrf
    @method('PUT')

    <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div class="mb-5">
            <h2 class="text-lg font-bold text-slate-950">Profil {{ str_replace('_', ' ', ucwords($profileType, '_')) }}</h2>
            <p class="mt-1 text-sm text-slate-500">Lengkapi data wajib agar fitur utama dapat digunakan.</p>
        </div>

        @if($errors->any())
            <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>
        @endif

        <div class="grid gap-4 md:grid-cols-2">
            @if($profileType === 'mahasiswa')
                <div>
                    <label class="text-sm font-semibold text-slate-700">NIM</label>
                    <input value="{{ $profile?->nim }}" disabled class="mt-1 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-500">
                </div>
                <div>
                    <label class="text-sm font-semibold text-slate-700">No HP</label>
                    <input name="phone" value="{{ old('phone', $profile?->phone) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="text-sm font-semibold text-slate-700">Program Studi</label>
                    <input name="study_program" value="{{ old('study_program', $profile?->study_program) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="text-sm font-semibold text-slate-700">Semester</label>
                    <input name="semester" type="number" min="1" max="14" value="{{ old('semester', $profile?->semester) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="text-sm font-semibold text-slate-700">Kelas</label>
                    <input name="class_name" value="{{ old('class_name', $profile?->class_name) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="text-sm font-semibold text-slate-700">Jenis Kelamin</label>
                    <select name="gender" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Pilih</option>
                        <option value="Laki-laki" @selected(old('gender', $profile?->gender) === 'Laki-laki')>Laki-laki</option>
                        <option value="Perempuan" @selected(old('gender', $profile?->gender) === 'Perempuan')>Perempuan</option>
                    </select>
                </div>
                <div>
                    <label class="text-sm font-semibold text-slate-700">Tempat Lahir</label>
                    <input name="birth_place" value="{{ old('birth_place', $profile?->birth_place) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="text-sm font-semibold text-slate-700">Tanggal Lahir</label>
                    <input name="birth_date" type="date" value="{{ old('birth_date', $profile?->birth_date?->format('Y-m-d')) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div class="md:col-span-2">
                    <label class="text-sm font-semibold text-slate-700">Alamat</label>
                    <textarea name="address" rows="3" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">{{ old('address', $profile?->address) }}</textarea>
                </div>
            @elseif($profileType === 'dosen')
                <div>
                    <label class="text-sm font-semibold text-slate-700">NIDN/NIP</label>
                    <input value="{{ $profile?->nidn_nip }}" disabled class="mt-1 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-500">
                </div>
                <div>
                    <label class="text-sm font-semibold text-slate-700">Nomor Pegawai</label>
                    <input value="{{ $profile?->employee_number }}" disabled class="mt-1 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-500">
                </div>
                <div>
                    <label class="text-sm font-semibold text-slate-700">No HP</label>
                    <input name="phone" value="{{ old('phone', $profile?->phone) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="text-sm font-semibold text-slate-700">Program Studi</label>
                    <input name="study_program" value="{{ old('study_program', $profile?->study_program) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="text-sm font-semibold text-slate-700">Departemen</label>
                    <input name="department" value="{{ old('department', $profile?->department) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="text-sm font-semibold text-slate-700">Bidang Keahlian</label>
                    <input name="expertise" value="{{ old('expertise', $profile?->expertise) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div class="md:col-span-2">
                    <label class="text-sm font-semibold text-slate-700">Alamat</label>
                    <textarea name="address" rows="3" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">{{ old('address', $profile?->address) }}</textarea>
                </div>
            @elseif($profileType === 'pembimbing_lapangan')
                <div>
                    <label class="text-sm font-semibold text-slate-700">No HP</label>
                    <input name="phone" value="{{ old('phone', $profile?->phone) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="text-sm font-semibold text-slate-700">Institusi</label>
                    <input name="institution_name" value="{{ old('institution_name', $profile?->institution_name) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="text-sm font-semibold text-slate-700">Jabatan</label>
                    <input name="position" value="{{ old('position', $profile?->position) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div class="md:col-span-2">
                    <label class="text-sm font-semibold text-slate-700">Alamat</label>
                    <textarea name="address" rows="3" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">{{ old('address', $profile?->address) }}</textarea>
                </div>
            @else
                <div>
                    <label class="text-sm font-semibold text-slate-700">Nama</label>
                    <input name="name" value="{{ old('name', $user->name) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="text-sm font-semibold text-slate-700">Email</label>
                    <input name="email" type="email" value="{{ old('email', $user->email) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
            @endif
        </div>

        <div class="mt-6 flex justify-end gap-2">
            <a href="{{ route('profile.show') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Batal</a>
            <button class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white">Simpan Profil</button>
        </div>
    </section>
</form>
@endsection
