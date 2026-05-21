@extends('layouts.app')

@section('title', 'Dashboard '.$roleData['label'].' - '.config('app.name'))
@section('page_title', 'Dashboard '.$roleData['label'])

@section('content')
@php
    $firstName = explode(' ', auth()->user()->name)[0];
    $featureDescriptions = [
        'Pendaftaran KP' => 'Mulai pengajuan kerja praktek dan pantau status verifikasi.',
        'Berkas Persyaratan' => 'Kelola dokumen wajib sebelum pemilihan tempat KP.',
        'Berkas KP' => 'Simpan dan lengkapi berkas administrasi kerja praktek.',
        'Pemilihan Tempat KP' => 'Pilih tempat KP sesuai periode dan kuota yang tersedia.',
        'Logbook' => 'Catat aktivitas harian selama pelaksanaan kerja praktek.',
        'Laporan Akhir' => 'Susun dan ajukan laporan akhir untuk proses review.',
        'Sidang' => 'Pantau jadwal sidang dan kelengkapan sebelum presentasi.',
        'Nilai' => 'Lihat rekap penilaian dari pembimbing dan penguji.',
    ];
@endphp
<div class="space-y-6">
    <!-- Welcome Banner -->
    <section class="relative overflow-hidden rounded-[1.75rem] border border-cyan-100 bg-white p-7 shadow-2xl shadow-sky-900/8 md:p-8">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(14,165,233,0.18),transparent_34%),linear-gradient(135deg,rgba(236,254,255,0.95),rgba(255,255,255,0.72)_48%,rgba(240,249,255,0.92))]"></div>
        <div class="relative grid gap-7 lg:grid-cols-[minmax(0,1fr)_330px] lg:items-center">
            <div>
                <span class="inline-flex items-center gap-2 rounded-full border border-cyan-200 bg-cyan-50 px-4 py-1.5 text-xs font-black uppercase tracking-widest text-cyan-800">
                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M6 4a2 2 0 11-4 0 2 2 0 014 0zM15 4a2 2 0 11-4 0 2 2 0 014 0zM13 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    {{ $roleData['label'] }}
                </span>
                <h1 class="mt-5 max-w-3xl text-3xl font-black tracking-tight text-slate-950 md:text-4xl">Selamat datang kembali, {{ $firstName }}</h1>
                <p class="mt-4 max-w-3xl text-base leading-8 text-slate-600">Pantau proses Kerja Praktek Farmasi dari pendaftaran, berkas, pemilihan tempat, bimbingan, laporan, hingga sidang dalam satu dashboard yang rapi.</p>
            </div>
            <div class="rounded-3xl border border-cyan-100 bg-white/80 p-5 shadow-xl shadow-sky-900/8 backdrop-blur">
                <p class="text-xs font-black uppercase tracking-widest text-cyan-700">Alur Kerja Praktek</p>
                <div class="mt-4 space-y-3">
                    @foreach([['01', 'Pendaftaran'], ['02', 'Pemilihan Tempat'], ['03', 'Bimbingan & Sidang']] as [$number, $label])
                        <div class="flex items-center gap-3 rounded-2xl bg-sky-50 px-4 py-3">
                            <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-cyan-700 text-xs font-black text-white">{{ $number }}</span>
                            <span class="text-sm font-black text-slate-800">{{ $label }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <!-- Alerts Section -->
    @if(! auth()->user()->profile_completed)
        <div class="rounded-2xl border border-amber-200 bg-linear-to-r from-amber-50 to-white px-5 py-4 text-sm text-amber-900 shadow-lg shadow-amber-900/5">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <p class="font-semibold">Profil Akademik Belum Lengkap</p>
                    <p class="mt-1 text-xs text-amber-800 opacity-75">Silakan <a href="{{ route('profile.edit') }}" class="font-semibold underline hover:opacity-100 transition">lengkapi data profil</a> Anda untuk akses fitur utama.</p>
                </div>
            </div>
        </div>
    @endif

    @if(auth()->user()->must_change_password)
        <div class="rounded-2xl border border-cyan-200 bg-linear-to-r from-cyan-50 to-white px-5 py-4 text-sm text-cyan-900 shadow-lg shadow-cyan-900/5">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <p class="font-semibold">Pembaruan Kata Sandi Diperlukan</p>
                    <p class="mt-1 text-xs text-sky-800 opacity-75">Untuk keamanan akun, silakan perbarui kata sandi awal Anda pada tahap berikutnya.</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Statistics Section for Admin -->
    @if($adminStats)
        <section>
            <div class="mb-6">
                <h2 class="text-lg font-bold text-slate-950">Metrik Administrasi</h2>
                <p class="mt-1 text-sm text-slate-500">Ringkasan data pengguna dan status sistem.</p>
            </div>
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-5">
                <div class="rounded-xl bg-linear-to-br from-white to-slate-50 p-6 shadow-sm ring-1 ring-slate-100 hover:shadow-md transition-all">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-widest text-slate-500">Total Pengguna</p>
                            <p class="mt-3 text-3xl font-bold text-slate-950">{{ $adminStats['total_users'] }}</p>
                        </div>
                        <div class="rounded-lg bg-blue-50 p-2.5 text-blue-600">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"/>
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="rounded-xl bg-linear-to-br from-white to-emerald-50 p-6 shadow-sm ring-1 ring-emerald-100 hover:shadow-md transition-all">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-widest text-slate-500">Status Aktif</p>
                            <p class="mt-3 text-3xl font-bold text-emerald-700">{{ $adminStats['active_users'] }}</p>
                        </div>
                        <div class="rounded-lg bg-emerald-100 p-2.5 text-emerald-600">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="rounded-xl bg-linear-to-br from-white to-rose-50 p-6 shadow-sm ring-1 ring-rose-100 hover:shadow-md transition-all">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-widest text-slate-500">Status Nonaktif</p>
                            <p class="mt-3 text-3xl font-bold text-rose-700">{{ $adminStats['inactive_users'] }}</p>
                        </div>
                        <div class="rounded-lg bg-rose-100 p-2.5 text-rose-600">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="rounded-xl bg-linear-to-br from-white to-amber-50 p-6 shadow-sm ring-1 ring-amber-100 hover:shadow-md transition-all">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-widest text-slate-500">Profil Tak Lengkap</p>
                            <p class="mt-3 text-3xl font-bold text-amber-700">{{ $adminStats['incomplete_profiles'] }}</p>
                        </div>
                        <div class="rounded-lg bg-amber-100 p-2.5 text-amber-600">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M13.816 4.477a.75.75 0 01.504.692v7.018A2.25 2.25 0 015.5 15H2.75A.75.75 0 012 14.25v-8.5a.75.75 0 011.072-.7l10.944 7.062z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="rounded-xl bg-linear-to-br from-white to-purple-50 p-6 shadow-sm ring-1 ring-purple-100 hover:shadow-md transition-all">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-widest text-slate-500">Import Terakhir</p>
                            <p class="mt-3 text-lg font-bold text-purple-700">{{ $adminStats['last_import']?->created_at?->format('d M Y') ?? 'Belum' }}</p>
                        </div>
                        <div class="rounded-lg bg-purple-100 p-2.5 text-purple-600">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M12 2a1 1 0 01.22.032l.312.052a1 1 0 01.16.05c.7.24 1.45.642 2.05 1.238.6.597 1 1.35 1.24 2.05a1 1 0 01.05.16l.052.312A1 1 0 0117 6v8a1 1 0 01-.032.22l-.052.312a1 1 0 01-.05.16c-.24.7-.642 1.45-1.238 2.05-.597.6-1.35 1-2.05 1.24a1 1 0 01-.16.05l-.312.052A1 1 0 0113 18H7a1 1 0 01-.22-.032l-.312-.052a1 1 0 01-.16-.05c-.7-.24-1.45-.642-2.05-1.238-.6-.597-1-1.35-1.24-2.05a1 1 0 01-.05-.16l-.052-.312A1 1 0 013 14V6a1 1 0 01.032-.22l.052-.312a1 1 0 01.05-.16c.24-.7.642-1.45 1.238-2.05.597-.6 1.35-1 2.05-1.24a1 1 0 01.16-.05l.312-.052A1 1 0 017 2h6z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif

    @if($kpStats)
        <section>
            <div class="mb-6">
                <h2 class="text-lg font-bold text-slate-950">Ringkasan Kerja Praktek</h2>
                <p class="mt-1 text-sm text-slate-500">Fondasi data periode, tempat, dan kuota untuk tahap pendaftaran berikutnya.</p>
            </div>
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-5">
                <div class="rounded-xl bg-linear-to-br from-white to-slate-50 p-6 shadow-sm ring-1 ring-slate-100">
                    <p class="text-xs font-semibold uppercase tracking-widest text-slate-500">Total Periode</p>
                    <p class="mt-3 text-3xl font-bold text-slate-950">{{ $kpStats['total_periods'] }}</p>
                </div>
                <div class="rounded-xl bg-linear-to-br from-white to-emerald-50 p-6 shadow-sm ring-1 ring-emerald-100">
                    <p class="text-xs font-semibold uppercase tracking-widest text-slate-500">Periode Dibuka</p>
                    <p class="mt-3 text-3xl font-bold text-emerald-700">{{ $kpStats['open_periods'] }}</p>
                </div>
                <div class="rounded-xl bg-linear-to-br from-white to-teal-50 p-6 shadow-sm ring-1 ring-teal-100">
                    <p class="text-xs font-semibold uppercase tracking-widest text-slate-500">Tempat Aktif</p>
                    <p class="mt-3 text-3xl font-bold text-teal-700">{{ $kpStats['active_places'] }}</p>
                </div>
                <div class="rounded-xl bg-linear-to-br from-white to-sky-50 p-6 shadow-sm ring-1 ring-sky-100">
                    <p class="text-xs font-semibold uppercase tracking-widest text-slate-500">Total Kuota</p>
                    <p class="mt-3 text-3xl font-bold text-sky-700">{{ $kpStats['total_quota'] }}</p>
                </div>
                <div class="rounded-xl bg-linear-to-br from-white to-amber-50 p-6 shadow-sm ring-1 ring-amber-100">
                    <p class="text-xs font-semibold uppercase tracking-widest text-slate-500">Kuota Dibuka</p>
                    <p class="mt-3 text-3xl font-bold text-amber-700">{{ $kpStats['open_quotas'] }}</p>
                </div>
            </div>
        </section>
    @endif

    @if($registrationStats)
        <section>
            <div class="mb-6">
                <h2 class="text-lg font-bold text-slate-950">Ringkasan Pendaftaran KP</h2>
                <p class="mt-1 text-sm text-slate-500">Monitoring status pendaftaran dan berkas mahasiswa.</p>
            </div>
            <div class="grid gap-4 md:grid-cols-5">
                @foreach([
                    ['Total', $registrationStats['total'], 'text-slate-950'],
                    ['Menunggu', $registrationStats['pending'], 'text-sky-700'],
                    ['Revisi', $registrationStats['revision'], 'text-amber-700'],
                    ['Terverifikasi', $registrationStats['verified'], 'text-emerald-700'],
                    ['Ditolak', $registrationStats['rejected'], 'text-rose-700'],
                ] as [$label, $value, $color])
                    <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
                        <p class="text-xs font-semibold uppercase tracking-widest text-slate-500">{{ $label }}</p>
                        <p class="mt-3 text-3xl font-bold {{ $color }}">{{ $value }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if($selectionStats)
        <section>
            <div class="mb-6">
                <h2 class="text-lg font-bold text-slate-950">Ringkasan Pemilihan Tempat</h2>
                <p class="mt-1 text-sm text-slate-500">Monitoring hasil war ticket dan daftar tunggu.</p>
            </div>
            <div class="grid gap-4 md:grid-cols-4">
                @foreach([
                    ['Sudah Memilih', $selectionStats['selected'], 'text-emerald-700'],
                    ['Daftar Tunggu', $selectionStats['waiting'], 'text-amber-700'],
                    ['Sisa Kuota', $selectionStats['remaining_quota'], 'text-teal-700'],
                    ['Tempat Penuh', $selectionStats['full_places'], 'text-rose-700'],
                ] as [$label, $value, $color])
                    <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
                        <p class="text-xs font-semibold uppercase tracking-widest text-slate-500">{{ $label }}</p>
                        <p class="mt-3 text-3xl font-bold {{ $color }}">{{ $value }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if($assignmentStats)
        <section>
            <div class="mb-6">
                <h2 class="text-lg font-bold text-slate-950">Ringkasan Penempatan KP</h2>
                <p class="mt-1 text-sm text-slate-500">Status penempatan dan pembimbing KP.</p>
            </div>
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-5">
                @foreach($assignmentStats as $label => $value)
                    <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
                        <p class="text-xs font-semibold uppercase tracking-widest text-slate-500">{{ str_replace('_', ' ', ucfirst($label)) }}</p>
                        <p class="mt-3 text-3xl font-bold text-teal-700">{{ $value }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if($logbookStats)
        <section>
            <div class="mb-6">
                <h2 class="text-lg font-bold text-slate-950">Ringkasan Logbook KP</h2>
                <p class="mt-1 text-sm text-slate-500">Pemantauan aktivitas harian dan validasi kegiatan KP.</p>
            </div>
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-5">
                @foreach($logbookStats as $label => $value)
                    <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
                        <p class="text-xs font-semibold uppercase tracking-widest text-slate-500">{{ str_replace('_', ' ', ucfirst($label)) }}</p>
                        <p class="mt-3 text-3xl font-bold text-teal-700">{{ $value }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if($finalReportStats)
        <section>
            <div class="mb-6">
                <h2 class="text-lg font-bold text-slate-950">Ringkasan Laporan Akhir</h2>
                <p class="mt-1 text-sm text-slate-500">Status upload, review, dan approval laporan akhir KP.</p>
            </div>
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                @foreach($finalReportStats as $label => $value)
                    <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
                        <p class="text-xs font-semibold uppercase tracking-widest text-slate-500">{{ str_replace('_', ' ', ucfirst($label)) }}</p>
                        <p class="mt-3 text-2xl font-bold text-teal-700">{{ $value }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if($examStats)
        <section>
            <div class="mb-6">
                <h2 class="text-lg font-bold text-slate-950">Ringkasan Sidang KP</h2>
                <p class="mt-1 text-sm text-slate-500">Status pengajuan, penjadwalan, dan pelaksanaan sidang Kerja Praktek.</p>
            </div>
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                @foreach($examStats as $label => $value)
                    <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
                        <p class="text-xs font-semibold uppercase tracking-widest text-slate-500">{{ str_replace('_', ' ', ucfirst($label)) }}</p>
                        <p class="mt-3 text-2xl font-bold text-cyan-700">{{ $value ?: '-' }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if($scoreStats)
        <section>
            <div class="mb-6">
                <h2 class="text-lg font-bold text-slate-950">Ringkasan Nilai KP</h2>
                <p class="mt-1 text-sm text-slate-500">Status kelengkapan, finalisasi, dan publikasi nilai Kerja Praktek.</p>
            </div>
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                @foreach($scoreStats as $label => $value)
                    <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
                        <p class="text-xs font-semibold uppercase tracking-widest text-slate-500">{{ str_replace('_', ' ', ucfirst($label)) }}</p>
                        <p class="mt-3 text-2xl font-bold text-emerald-700">{{ $value ?: '-' }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if($studentRegistration)
        <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-lg font-bold text-slate-950">Status Pendaftaran KP</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ $studentRegistration->period->name ?? '-' }}</p>
                </div>
                <span class="rounded-full {{ $studentRegistration->statusBadgeClass() }} px-3 py-1 text-xs font-semibold">{{ $studentRegistration->statusLabel() }}</span>
            </div>
            <div class="mt-5 grid gap-4 md:grid-cols-3">
                <div class="rounded-xl bg-slate-50 p-4"><p class="text-xs text-slate-500">Progress Berkas</p><p class="mt-2 text-2xl font-bold">{{ $studentRegistration->progressPercentage() }}%</p></div>
                <div class="rounded-xl bg-slate-50 p-4"><p class="text-xs text-slate-500">Verifikasi</p><p class="mt-2 text-sm font-bold">{{ $studentRegistration->isVerified() ? 'Terverifikasi' : 'Belum selesai' }}</p></div>
                <div class="rounded-xl bg-slate-50 p-4"><p class="text-xs text-slate-500">Pemilihan Tempat</p><p class="mt-2 text-sm font-bold">{{ $studentRegistration->selectionStatusLabel() }}</p>@if($studentRegistration->activePlaceSelection)<p class="mt-1 text-xs text-slate-500">{{ $studentRegistration->activePlaceSelection->place->name }}</p>@endif</div>
            </div>
        </section>
    @endif

    <!-- User Status Cards -->
    <section class="grid gap-4 md:grid-cols-3">
        <div class="rounded-3xl bg-white p-6 shadow-xl shadow-sky-900/6 ring-1 ring-sky-100 transition-all hover:-translate-y-0.5 hover:shadow-2xl">
            <div class="flex items-center justify-between mb-4">
                <p class="text-xs font-black text-slate-500 uppercase tracking-widest">Status Profil Akademik</p>
                <div class="rounded-2xl {{ auth()->user()->profile_completed ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600' }} p-3">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                        <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-black text-slate-950">{{ auth()->user()->profile_completed ? 'Lengkap' : 'Belum Lengkap' }}</p>
            <span class="mt-4 inline-flex rounded-full {{ auth()->user()->profile_completed ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }} px-3 py-1 text-xs font-black">{{ auth()->user()->profile_completed ? 'Terpenuhi' : 'Tindakan Diperlukan' }}</span>
        </div>
        <div class="rounded-3xl bg-white p-6 shadow-xl shadow-sky-900/6 ring-1 ring-sky-100 transition-all hover:-translate-y-0.5 hover:shadow-2xl">
            <div class="flex items-center justify-between mb-4">
                <p class="text-xs font-black text-slate-500 uppercase tracking-widest">Peran Aktif</p>
                <div class="rounded-2xl bg-cyan-50 text-cyan-700 p-3">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v1h8v-1zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-1a4 4 0 00-4-4l-.5-.5-4 4v1h8.5z"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-black text-slate-950">{{ $roleData['label'] }}</p>
            <p class="mt-2 text-xs text-slate-500">Peran saat ini dalam sistem akademik.</p>
        </div>
        <div class="rounded-3xl bg-white p-6 shadow-xl shadow-sky-900/6 ring-1 ring-sky-100 transition-all hover:-translate-y-0.5 hover:shadow-2xl">
            <div class="flex items-center justify-between mb-4">
                <p class="text-xs font-black text-slate-500 uppercase tracking-widest">Status Akun</p>
                <div class="rounded-2xl bg-emerald-50 text-emerald-600 p-3">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-black text-slate-950">Aktif</p>
            <p class="mt-2 text-xs text-slate-500">Akun Anda siap digunakan.</p>
        </div>
    </section>

    <!-- Features Overview -->
    <section>
        <div class="mb-6">
            <h2 class="text-xl font-black text-slate-950">Modul Akademik</h2>
            <p class="mt-1 text-sm leading-6 text-slate-500">Fitur-fitur utama untuk mengelola proses Kerja Praktek Farmasi secara bertahap.</p>
        </div>
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach($features as $feature)
                <div class="group relative overflow-hidden rounded-3xl bg-white p-6 shadow-xl shadow-sky-900/6 ring-1 ring-sky-100 transition-all hover:-translate-y-0.5 hover:shadow-2xl hover:ring-cyan-100">
                    <div class="absolute inset-x-0 top-0 h-1 bg-linear-to-r from-cyan-500 via-sky-400 to-teal-400"></div>
                    <div class="absolute inset-0 bg-linear-to-br from-cyan-50/60 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"/>
                    <div class="relative z-10">
                        <div class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-cyan-50 text-sm font-bold text-cyan-700 ring-1 ring-cyan-100 transition-all group-hover:bg-cyan-700 group-hover:text-white">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <h4 class="font-black text-slate-950 group-hover:text-cyan-800 transition-colors">{{ $feature }}</h4>
                        <p class="mt-2 text-sm leading-6 text-slate-500">{{ $featureDescriptions[$feature] ?? 'Modul ini sedang disiapkan untuk mendukung proses kerja praktek.' }}</p>
                        <div class="mt-5 flex items-center justify-between gap-3">
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600">Segera tersedia</span>
                            <span class="text-xs font-black uppercase tracking-widest text-cyan-700">SI-KP</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
</div>
@endsection
