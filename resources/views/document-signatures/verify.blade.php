@extends('layouts.guest')
@section('title', 'Verifikasi Penandatangan Dokumen')
@section('content')
<div class="mx-auto max-w-2xl rounded-3xl bg-white p-7 shadow-sm ring-1 ring-slate-200">
    @if($signature)
        <p class="text-xs font-black uppercase tracking-widest {{ $signature->isActive() ? 'text-emerald-700' : 'text-amber-700' }}">{{ $signature->isActive() ? 'Tanda Tangan Terverifikasi' : 'Tanda Tangan Dicabut' }}</p>
        <h1 class="mt-2 text-2xl font-black text-slate-950">{{ $signature->signer_name }}</h1>
        <p class="mt-1 text-sm font-bold text-cyan-700">{{ $signature->role_label }}</p>
        <div class="mt-6 grid gap-3 sm:grid-cols-2">
            <div class="rounded-2xl bg-slate-50 p-4"><p class="text-xs font-black uppercase text-slate-500">Dokumen</p><p class="mt-1 font-bold">{{ $signature->documentLabel() }}</p></div>
            <div class="rounded-2xl bg-slate-50 p-4"><p class="text-xs font-black uppercase text-slate-500">Versi</p><p class="mt-1 font-bold">{{ $signature->version }}</p></div>
            <div class="rounded-2xl bg-slate-50 p-4"><p class="text-xs font-black uppercase text-slate-500">NUPTK/NIDN</p><p class="mt-1 font-bold">{{ $signature->signer_identifier ?: '-' }}</p></div>
            <div class="rounded-2xl bg-slate-50 p-4"><p class="text-xs font-black uppercase text-slate-500">Ditandatangani</p><p class="mt-1 font-bold">{{ $signature->signed_at?->format('d M Y H:i') }}</p></div>
            <div class="rounded-2xl bg-slate-50 p-4"><p class="text-xs font-black uppercase text-slate-500">Nomor Dokumen</p><p class="mt-1 font-bold">{{ $signature->metadata['document_number'] ?? '-' }}</p></div>
            <div class="rounded-2xl bg-slate-50 p-4"><p class="text-xs font-black uppercase text-slate-500">Mahasiswa</p><p class="mt-1 font-bold">{{ $signature->metadata['student_name'] ?? '-' }}</p><p class="text-sm text-slate-600">{{ $signature->metadata['student_nim'] ?? '-' }}</p></div>
        </div>
        @unless($signature->isActive())
            <div class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">QR ini tidak berlaku karena dokumen telah dibangun ulang. Alasan: {{ $signature->revocation_reason ?: '-' }}</div>
        @endunless
    @else
        <p class="text-xs font-black uppercase tracking-widest text-red-700">Tidak Valid</p>
        <h1 class="mt-2 text-2xl font-black">Penandatangan tidak ditemukan</h1>
        <p class="mt-2 text-sm text-slate-600">Kode QR tidak terdaftar dalam sistem SI-KP Farmasi UBP.</p>
    @endif
</div>
@endsection
