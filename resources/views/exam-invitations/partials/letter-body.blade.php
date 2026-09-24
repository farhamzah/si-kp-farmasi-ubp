@php
    $exam = $invitation->exam;
    $assignment = $exam->assignment;
    $student = $assignment?->student;
    $date = $exam->exam_date?->translatedFormat('l, d F Y') ?: '-';
    $time = substr((string) $exam->start_time, 0, 5).' - '.substr((string) $exam->end_time, 0, 5).' WIB';
    $location = $exam->room ?: $exam->meeting_link ?: '-';
    $logoSrc = $logoSrc ?? asset('images/logo-ubp-karawang.png');
    $qrSrc = $qrSrc ?? route('exam-invitations.qr', $invitation);
    $activeSignatures = $invitation->signatures
        ->where('status', 'active')
        ->where('version', (int) $invitation->document_version)
        ->keyBy('signer_key');
    $signatureQrSrcs = $signatureQrSrcs ?? [];
@endphp

<style>
    .letter { width: 100%; max-width: 100%; overflow: hidden; color: #111827; font-family: "DejaVu Sans", Arial, sans-serif; font-size: 10pt; line-height: 1.38; }
    .letter, .letter * { box-sizing: border-box; }
    .letter table { width: 100%; max-width: 100%; }
    .letter p { margin: 0 0 8px; text-align: justify; }
    .letter-header { width: 100%; border-collapse: collapse; border-bottom: 3px double #111827; margin-bottom: 12px; }
    .letter-header td { vertical-align: middle; padding: 0 0 9px; }
    .letter-logo-cell { width: 88px; text-align: center; }
    .letter-logo { display: block; width: 76px; height: auto; margin: 0 auto; }
    .letter-campus { padding-right: 82px !important; text-align: center; }
    .letter-campus div { letter-spacing: 0; }
    .letter-foundation { font-size: 9pt; font-weight: 700; }
    .letter-university { margin-top: 2px; font-size: 14pt; font-weight: 800; }
    .letter-faculty { margin-top: 1px; font-size: 12.5pt; font-weight: 800; }
    .letter-address { margin-top: 3px; font-size: 8.3pt; line-height: 1.28; }
    .letter-title { margin: 11px 0 14px; text-align: center; }
    .letter-title-main { font-size: 13pt; font-weight: 800; text-decoration: underline; }
    .letter-number { margin-top: 2px; font-size: 9.5pt; }
    .letter-addressee { margin-bottom: 9px; }
    .letter-details { width: 100%; margin: 8px 0 12px; border-collapse: collapse; table-layout: fixed; page-break-inside: avoid; }
    .letter-details td { border-bottom: 1px solid #e5e7eb; padding: 3px 3px; vertical-align: top; }
    .letter-details .label { width: 29%; padding-left: 0; text-align: left !important; white-space: nowrap; }
    .letter-details .colon { width: 3%; text-align: center; }
    .letter-details .value { width: 68%; padding-right: 0; font-weight: 500; text-align: left !important; white-space: normal; overflow-wrap: anywhere; word-break: break-word; }
    .letter-details .student-name { font-weight: 800; }
    .verification-table { width: 100%; margin-top: 14px; border-collapse: collapse; page-break-inside: avoid; }
    .verification-table td { vertical-align: middle; }
    .verification-copy { padding: 8px 10px; border: 1px solid #cbd5e1; background: #f8fafc; font-size: 7.6pt; line-height: 1.35; }
    .verification-title { font-size: 8.2pt; font-weight: 800; }
    .verification-url { margin-top: 2px; color: #334155; word-break: break-all; }
    .verification-qr-cell { width: 86px; padding-left: 10px; text-align: right; }
    .verification-qr { width: 76px; height: 76px; padding: 4px; border: 1px solid #cbd5e1; }
    .signature { width: 100%; margin-top: 16px; border-collapse: collapse; table-layout: fixed; page-break-inside: avoid; }
    .signature td { width: 33.333%; padding: 0 6px; vertical-align: top; text-align: center; font-size: 8.5pt; }
    .signature-role { min-height: 26px; font-weight: 600; }
    .signature-space { height: 64px; display: flex; align-items: center; justify-content: center; }
    .signature-qr { width: 60px; height: 60px; padding: 2px; border: 1px solid #cbd5e1; }
    .signature-name { font-weight: 800; text-decoration: underline; }
    .signature-id { margin-top: 1px; }
</style>

<div class="letter">
    <table class="letter-header">
        <tr>
            <td class="letter-logo-cell">
                <img src="{{ $logoSrc }}" alt="Logo UBP Karawang" class="letter-logo">
            </td>
            <td class="letter-campus">
                <div class="letter-foundation">YAYASAN PEMBINA PERGURUAN TINGGI PANGKAL PERJUANGAN</div>
                <div class="letter-university">UNIVERSITAS BUANA PERJUANGAN KARAWANG</div>
                <div class="letter-faculty">FAKULTAS FARMASI</div>
                <div class="letter-address">Jl. HS. Ronggo Waluyo, Puseurjaya, Telukjambe Timur, Karawang 41361<br>www.ubpkarawang.ac.id</div>
            </td>
        </tr>
    </table>

    <div class="letter-title">
        <div class="letter-title-main">SURAT UNDANGAN SIDANG KERJA PRAKTIK</div>
        <div class="letter-number">Nomor: {{ $invitation->letter_number }}</div>
    </div>

    <div class="letter-addressee">Yth. Bapak/Ibu Pembimbing dan Penguji Sidang Kerja Praktik<br>di tempat</div>

    <p>Dengan hormat,</p>
    <p>Sehubungan dengan pelaksanaan Sidang Kerja Praktik Program Studi Farmasi Fakultas Farmasi Universitas Buana Perjuangan Karawang, kami mengundang Bapak/Ibu untuk hadir dan melaksanakan penilaian sidang Kerja Praktik mahasiswa berikut:</p>

    <table class="letter-details">
        <tr><td class="label">Nama Mahasiswa</td><td class="colon">:</td><td class="value student-name">{{ $student?->user?->name ?? '-' }}</td></tr>
        <tr><td class="label">NIM</td><td class="colon">:</td><td class="value">{{ $student?->nim ?: '-' }}</td></tr>
        <tr><td class="label">Program Studi</td><td class="colon">:</td><td class="value">{{ $student?->study_program ?: 'Farmasi S1' }}</td></tr>
        <tr><td class="label">Tempat KP</td><td class="colon">:</td><td class="value">{{ $assignment?->place?->name ?? '-' }}</td></tr>
        <tr><td class="label">Periode KP</td><td class="colon">:</td><td class="value">{{ $assignment?->period?->name ?? '-' }}</td></tr>
        <tr><td class="label">Hari/Tanggal</td><td class="colon">:</td><td class="value">{{ $date }}</td></tr>
        <tr><td class="label">Waktu</td><td class="colon">:</td><td class="value">{{ $time }}</td></tr>
        <tr><td class="label">Ruang/Media</td><td class="colon">:</td><td class="value">{{ $location }}</td></tr>
        <tr><td class="label">Pembimbing Dalam</td><td class="colon">:</td><td class="value">{{ $exam->supervisor ? lecturer_display_name($exam->supervisor) : '-' }}</td></tr>
        <tr><td class="label">Pembimbing Lapangan</td><td class="colon">:</td><td class="value">{{ $assignment?->fieldSupervisor?->user?->name ?? '-' }}</td></tr>
        <tr><td class="label">Penguji</td><td class="colon">:</td><td class="value">{{ $exam->examinerNamesLabel() }}</td></tr>
    </table>

    <p>Demikian surat undangan ini disampaikan. Atas perhatian, kehadiran, dan kerja sama Bapak/Ibu, kami ucapkan terima kasih.</p>

    <table class="verification-table">
        <tr>
            <td class="verification-copy">
                <div class="verification-title">VERIFIKASI KEASLIAN SURAT</div>
                <div>Kode: <strong>{{ $invitation->verification_code }}</strong></div>
                <div class="verification-url">{{ $verificationUrl }}</div>
            </td>
            <td class="verification-qr-cell">
                <img src="{{ $qrSrc }}" alt="QR Verifikasi" class="verification-qr">
            </td>
        </tr>
    </table>

    <table class="signature">
        <tr>
            <td>
                <div class="signature-role">Koordinator Sidang</div>
                @php
                    $signature = $activeSignatures->get('coordinator');
                @endphp
                <div class="signature-space">@if($signature)<img class="signature-qr" src="{{ $signatureQrSrcs[$signature->id] ?? route('document-signatures.qr', $signature) }}" alt="QR Koordinator">@endif</div>
                <div class="signature-name">{{ $invitation->coordinator_name }}</div>
                <div class="signature-id">NUPTK. {{ $invitation->coordinator_nuptk ?: '-' }}</div>
            </td>
            <td>
                <div class="signature-role">Ketua Program Studi Farmasi</div>
                @php
                    $signature = $activeSignatures->get('head_program');
                @endphp
                <div class="signature-space">@if($signature)<img class="signature-qr" src="{{ $signatureQrSrcs[$signature->id] ?? route('document-signatures.qr', $signature) }}" alt="QR Kaprodi">@endif</div>
                <div class="signature-name">{{ $invitation->head_program_name }}</div>
                <div class="signature-id">NUPTK. {{ $invitation->head_program_nuptk ?: '-' }}</div>
            </td>
            <td>
                <div class="signature-role">Dekan Fakultas Farmasi</div>
                @php
                    $signature = $activeSignatures->get('dean');
                @endphp
                <div class="signature-space">@if($signature)<img class="signature-qr" src="{{ $signatureQrSrcs[$signature->id] ?? route('document-signatures.qr', $signature) }}" alt="QR Dekan">@endif</div>
                <div class="signature-name">{{ $invitation->dean_name }}</div>
                <div class="signature-id">NUPTK. {{ $invitation->dean_nuptk ?: '-' }}</div>
            </td>
        </tr>
    </table>
</div>
