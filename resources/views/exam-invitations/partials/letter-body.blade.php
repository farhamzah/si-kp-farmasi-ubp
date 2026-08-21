@php
    $exam = $invitation->exam;
    $assignment = $exam->assignment;
    $student = $assignment?->student;
    $date = $exam->exam_date?->translatedFormat('l, d F Y') ?: '-';
    $time = substr((string) $exam->start_time, 0, 5).' - '.substr((string) $exam->end_time, 0, 5).' WIB';
    $location = $exam->room ?: $exam->meeting_link ?: '-';
@endphp

<style>
    .letter p { margin: 0 0 10px; line-height: 1.65; }
    .letter-table { width: 100%; border-collapse: collapse; }
    .letter-table td { padding: 4px 0; vertical-align: top; }
    .signature td { width: 33.333%; padding-top: 28px; vertical-align: bottom; text-align: center; }
    @media print { .letter { font-size: 12pt; } }
</style>

<div class="letter">
    <div style="display:flex; align-items:center; gap:18px; border-bottom:4px double #111827; padding-bottom:14px; margin-bottom:26px;">
        <div style="width:74px; height:74px; border:1px solid #cbd5e1; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:800; color:#0369a1;">UBP</div>
        <div style="text-align:center; flex:1;">
            <div style="font-size:15px; font-weight:800; letter-spacing:.08em;">YAYASAN PEMBINA PERGURUAN TINGGI PANGKAL PERJUANGAN</div>
            <div style="font-size:19px; font-weight:900;">UNIVERSITAS BUANA PERJUANGAN KARAWANG</div>
            <div style="font-size:18px; font-weight:900;">FAKULTAS FARMASI</div>
            <div style="font-size:12px;">Jl. HS. Ronggo Waluyo, Telukjambe Timur, Karawang 41361</div>
            <div style="font-size:12px;">Website: www.ubpkarawang.ac.id</div>
        </div>
    </div>

    <div style="text-align:center; margin-bottom:22px;">
        <div style="font-weight:900; text-decoration:underline; font-size:18px;">SURAT UNDANGAN SIDANG KERJA PRAKTIK</div>
        <div style="margin-top:4px;">Nomor: {{ $invitation->letter_number }}</div>
    </div>

    <p>Yth. Bapak/Ibu Pembimbing dan Penguji Sidang Kerja Praktik<br>di tempat</p>

    <p>Dengan hormat,</p>
    <p>Sehubungan dengan pelaksanaan Sidang Kerja Praktik Program Studi Farmasi Fakultas Farmasi Universitas Buana Perjuangan Karawang, kami mengundang Bapak/Ibu untuk hadir dan melaksanakan penilaian sidang Kerja Praktik mahasiswa berikut:</p>

    <table class="letter-table" style="margin:12px 0 18px;">
        <tr><td style="width:180px;">Nama Mahasiswa</td><td style="width:18px;">:</td><td><strong>{{ $student?->user?->name ?? '-' }}</strong></td></tr>
        <tr><td>NIM</td><td>:</td><td>{{ $student?->nim ?: '-' }}</td></tr>
        <tr><td>Program Studi</td><td>:</td><td>{{ $student?->study_program ?: 'Farmasi' }}</td></tr>
        <tr><td>Tempat KP</td><td>:</td><td>{{ $assignment?->place?->name ?? '-' }}</td></tr>
        <tr><td>Periode KP</td><td>:</td><td>{{ $assignment?->period?->name ?? '-' }}</td></tr>
        <tr><td>Hari/Tanggal</td><td>:</td><td>{{ $date }}</td></tr>
        <tr><td>Waktu</td><td>:</td><td>{{ $time }}</td></tr>
        <tr><td>Ruang/Media</td><td>:</td><td>{{ $location }}</td></tr>
        <tr><td>Pembimbing Dalam</td><td>:</td><td>{{ $exam->supervisor ? lecturer_display_name($exam->supervisor) : '-' }}</td></tr>
        <tr><td>Pembimbing Lapangan</td><td>:</td><td>{{ $assignment?->fieldSupervisor?->user?->name ?? '-' }}</td></tr>
        <tr><td>Penguji</td><td>:</td><td>{{ $exam->examinerNamesLabel() }}</td></tr>
    </table>

    <p>Demikian surat undangan ini disampaikan. Atas perhatian, kehadiran, dan kerja sama Bapak/Ibu, kami ucapkan terima kasih.</p>

    <div style="display:flex; justify-content:space-between; gap:24px; margin-top:26px; align-items:flex-start;">
        <div style="font-size:12px; color:#475569;">
            <div style="font-weight:800; color:#0f172a;">Verifikasi Keaslian Surat</div>
            <div>Kode: <strong>{{ $invitation->verification_code }}</strong></div>
            <div style="word-break:break-all;">{{ $verificationUrl }}</div>
        </div>
        <img src="{{ route('exam-invitations.qr', $invitation) }}" alt="QR Verifikasi" style="width:104px; height:104px; border:1px solid #cbd5e1; padding:6px;">
    </div>

    <table class="signature" style="width:100%; margin-top:28px;">
        <tr>
            <td>
                <div>Koordinator Sidang</div>
                <div style="height:72px;"></div>
                <div style="font-weight:800; text-decoration:underline;">{{ $invitation->coordinator_name }}</div>
                <div>NUPTK. {{ $invitation->coordinator_nuptk ?: '-' }}</div>
            </td>
            <td>
                <div>Ketua Program Studi</div>
                <div style="height:72px;"></div>
                <div style="font-weight:800; text-decoration:underline;">{{ $invitation->head_program_name }}</div>
                <div>NUPTK. {{ $invitation->head_program_nuptk ?: '-' }}</div>
            </td>
            <td>
                <div>Dekan Fakultas Farmasi</div>
                <div style="height:72px;"></div>
                <div style="font-weight:800; text-decoration:underline;">{{ $invitation->dean_name }}</div>
                <div>NUPTK. {{ $invitation->dean_nuptk ?: '-' }}</div>
            </td>
        </tr>
    </table>
</div>
