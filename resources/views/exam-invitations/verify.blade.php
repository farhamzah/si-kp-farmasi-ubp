<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verifikasi Surat Undangan Sidang KP</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-cyan-50 font-sans text-slate-900">
    <main class="mx-auto flex min-h-screen max-w-3xl items-center px-4 py-10">
        <section class="w-full rounded-3xl bg-white p-6 shadow-sm ring-1 ring-cyan-100 md:p-8">
            @if($invitation)
                @php($exam = $invitation->exam)
                @php($assignment = $exam?->assignment)
                <p class="text-xs font-black uppercase tracking-widest text-emerald-700">Surat Valid</p>
                <h1 class="mt-2 text-2xl font-black text-slate-950">Undangan Sidang Kerja Praktik</h1>
                <p class="mt-2 text-sm leading-6 text-slate-600">Kode verifikasi cocok dengan data surat yang tersimpan di SI-KP Farmasi UBP.</p>

                <div class="mt-5 grid gap-3 text-sm">
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <p class="text-xs font-black uppercase tracking-widest text-slate-500">Nomor Surat</p>
                        <p class="mt-1 font-black">{{ $invitation->letter_number }}</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <p class="text-xs font-black uppercase tracking-widest text-slate-500">Mahasiswa</p>
                        <p class="mt-1 font-black">{{ $assignment?->student?->user?->name ?? '-' }}</p>
                        <p class="text-slate-500">{{ $assignment?->student?->nim ?: '-' }}</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <p class="text-xs font-black uppercase tracking-widest text-slate-500">Jadwal</p>
                        <p class="mt-1 font-black">{{ $exam?->scheduleLabel() ?? '-' }}</p>
                        <p class="text-slate-500">{{ $exam?->room ?: $exam?->meeting_link ?: '-' }}</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <p class="text-xs font-black uppercase tracking-widest text-slate-500">Diterbitkan</p>
                        <p class="mt-1 font-black">{{ $invitation->generated_at?->format('d M Y H:i') ?? '-' }}</p>
                    </div>
                </div>
            @else
                <p class="text-xs font-black uppercase tracking-widest text-red-700">Tidak Valid</p>
                <h1 class="mt-2 text-2xl font-black text-slate-950">Surat tidak ditemukan</h1>
                <p class="mt-2 text-sm leading-6 text-slate-600">Kode verifikasi tidak cocok dengan surat undangan sidang yang tersimpan.</p>
            @endif
        </section>
    </main>
</body>
</html>
