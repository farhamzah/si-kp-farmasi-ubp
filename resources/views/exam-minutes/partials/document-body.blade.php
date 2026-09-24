@php
    $exam = $minute->exam;
    $assignment = $exam->assignment;
    $student = $assignment->student;
    $chairName = $exam->chair ? lecturer_display_name($exam->chair) : '-';
    $attendees = collect($minute->attendance ?? []);
    $isPdf = isset($logoSrc);
    $activeSignatures = $minute->signatures
        ->where('status', 'active')
        ->where('version', (int) $minute->document_version)
        ->keyBy('signer_key');
    $signatureQrSrcs = $signatureQrSrcs ?? [];
@endphp
<style>
    .ba-doc { font-family: Arial, sans-serif; color: #0f172a; font-size: 11px; line-height: 1.45; }
    .ba-doc * { box-sizing: border-box; }
    .ba-header { display: table; width: 100%; border-bottom: 2px solid #0f172a; padding-bottom: 8px; margin-bottom: 13px; }
    .ba-logo { display: table-cell; width: 80px; vertical-align: middle; }
    .ba-logo img { width: 68px; height: auto; }
    .ba-headtext { display: table-cell; text-align: center; vertical-align: middle; padding-right: 80px; }
    .ba-headtext strong { display: block; font-size: 16px; line-height: 1.25; }
    .ba-title { text-align: center; margin: 13px 0 16px; }
    .ba-title h1 { margin: 0; font-size: 16px; text-decoration: underline; }
    .ba-title p { margin: 2px 0 0; }
    .ba-table { width: 100%; border-collapse: collapse; }
    .ba-table td { padding: 4px 5px; border-bottom: 1px solid #dbe3ed; vertical-align: top; overflow-wrap: anywhere; }
    .ba-table .ba-colon { padding-left: 0; padding-right: 0; text-align: center; }
    .ba-table .ba-value { padding-left: 4px; }
    .ba-section { margin-top: 15px; font-weight: bold; text-transform: uppercase; }
    .ba-box { border: 1px solid #cbd5e1; padding: 10px; min-height: 46px; margin-top: 5px; overflow-wrap: anywhere; }
    .ba-signatures { width: 100%; border-collapse: collapse; margin-top: 22px; table-layout: fixed; }
    .ba-signatures td { width: 33.33%; text-align: center; vertical-align: top; padding: 0 8px; }
    .ba-sign-space { height: 64px; display: flex; align-items: center; justify-content: center; }
    .ba-sign-qr { width: 60px; height: 60px; padding: 2px; border: 1px solid #cbd5e1; }
    .ba-verification { margin-top: 20px; border: 1px solid #cbd5e1; padding: 8px; display: table; width: 100%; }
    .ba-verification-text, .ba-verification-qr { display: table-cell; vertical-align: middle; }
    .ba-verification-qr { width: 76px; text-align: right; }
    .ba-verification-qr img { width: 68px; height: 68px; }
    .ba-draft { color: #b45309; font-weight: bold; text-align: center; border: 1px solid #f59e0b; padding: 5px; margin-bottom: 10px; }
</style>

<div class="ba-doc">
    @if($minute->status !== 'terbit')
        <div class="ba-draft">DRAFT - {{ strtoupper($minute->statusLabel()) }}</div>
    @endif
    <header class="ba-header">
        <div class="ba-logo"><img src="{{ $isPdf ? $logoSrc : asset('images/logo-ubp-karawang.png') }}" alt="Logo UBP"></div>
        <div class="ba-headtext">
            <span>YAYASAN PEMBINA PERGURUAN TINGGI PANGKAL PERJUANGAN</span>
            <strong>UNIVERSITAS BUANA PERJUANGAN KARAWANG</strong>
            <strong>FAKULTAS FARMASI</strong>
            <span>Jl. HS. Ronggo Waluyo, Puseurjaya, Telukjambe Timur, Karawang 41361</span>
        </div>
    </header>

    <div class="ba-title">
        <h1>BERITA ACARA SIDANG KERJA PRAKTIK</h1>
        <p>Nomor: {{ $minute->minutes_number }}</p>
    </div>

    <p>Pada hari ini telah dilaksanakan Sidang Kerja Praktik Program Studi Farmasi, Fakultas Farmasi, Universitas Buana Perjuangan Karawang, dengan data berikut:</p>
    <table class="ba-table">
        <tr><td width="22%" style="width:22%">Nama Mahasiswa</td><td width="3%" style="width:3%" class="ba-colon">:</td><td width="75%" style="width:75%" class="ba-value"><strong>{{ $student->user->name }}</strong></td></tr>
        <tr><td width="22%">NIM</td><td width="3%" class="ba-colon">:</td><td width="75%" class="ba-value">{{ $student->nim ?: '-' }}</td></tr>
        <tr><td width="22%">Tempat KP</td><td width="3%" class="ba-colon">:</td><td width="75%" class="ba-value">{{ $assignment->place->name }}</td></tr>
        <tr><td width="22%">Periode KP</td><td width="3%" class="ba-colon">:</td><td width="75%" class="ba-value">{{ $assignment->period->name }}</td></tr>
        <tr><td width="22%">Hari/Tanggal</td><td width="3%" class="ba-colon">:</td><td width="75%" class="ba-value">{{ $exam->exam_date?->translatedFormat('l, d F Y') }}</td></tr>
        <tr><td width="22%">Waktu Pelaksanaan</td><td width="3%" class="ba-colon">:</td><td width="75%" class="ba-value">{{ substr((string) $minute->actual_start_time, 0, 5) }} - {{ substr((string) $minute->actual_end_time, 0, 5) }} WIB</td></tr>
        <tr><td width="22%">Ruang/Media</td><td width="3%" class="ba-colon">:</td><td width="75%" class="ba-value">{{ $exam->room ?: $exam->meeting_link ?: '-' }}</td></tr>
        <tr><td width="22%">Ketua Sidang</td><td width="3%" class="ba-colon">:</td><td width="75%" class="ba-value">{{ $chairName }}</td></tr>
        <tr><td width="22%">Tim Penguji</td><td width="3%" class="ba-colon">:</td><td width="75%" class="ba-value">{{ $exam->examinerNamesLabel() }}</td></tr>
        <tr><td width="22%">Kehadiran</td><td width="3%" class="ba-colon">:</td><td width="75%" class="ba-value">{{ $attendees->isEmpty() ? '-' : $attendees->map(fn($v) => ucwords(str_replace('_', ' ', $v)))->implode(', ') }}</td></tr>
    </table>

    <p class="ba-section">Keputusan Sidang</p>
    <table class="ba-table">
        <tr><td width="22%" style="width:22%">Hasil</td><td width="3%" style="width:3%" class="ba-colon">:</td><td width="75%" style="width:75%" class="ba-value"><strong>{{ $minute->resultLabel() }}</strong></td></tr>
        @if($minute->revision_deadline)<tr><td width="22%">Batas Revisi</td><td width="3%" class="ba-colon">:</td><td width="75%" class="ba-value">{{ $minute->revision_deadline->format('d M Y') }}</td></tr>@endif
        <tr><td width="22%">Status Nilai</td><td width="3%" class="ba-colon">:</td><td width="75%" class="ba-value">{{ $assignment->isAllRequiredScoresSubmitted() ? 'Seluruh nilai wajib telah disubmit' : 'Masih menunggu nilai wajib' }}</td></tr>
    </table>

    <p class="ba-section">Catatan Sidang</p>
    <div class="ba-box">{{ $minute->notes ?: 'Tidak ada catatan tambahan.' }}</div>

    <table class="ba-signatures">
        <tr>
            @php
                $chairSignature = $exam->chair ? $activeSignatures->get('lecturer_'.$exam->chair->id) : null;
            @endphp
            <td>Ketua Sidang<div class="ba-sign-space">@if($chairSignature)<img class="ba-sign-qr" src="{{ $signatureQrSrcs[$chairSignature->id] ?? route('document-signatures.qr', $chairSignature) }}" alt="QR Ketua Sidang">@endif</div><strong><u>{{ $chairName }}</u></strong></td>
            @foreach($exam->examiners->reject(fn($lecturer) => $lecturer->id === $exam->chair_lecturer_id)->take(2) as $index => $examiner)
                @php
                    $examinerSignature = $activeSignatures->get('lecturer_'.$examiner->id);
                @endphp
                <td>Anggota Penguji {{ $index + 1 }}<div class="ba-sign-space">@if($examinerSignature)<img class="ba-sign-qr" src="{{ $signatureQrSrcs[$examinerSignature->id] ?? route('document-signatures.qr', $examinerSignature) }}" alt="QR Penguji">@endif</div><strong><u>{{ lecturer_display_name($examiner) }}</u></strong></td>
            @endforeach
        </tr>
    </table>

    <div class="ba-verification">
        <div class="ba-verification-text"><strong>VERIFIKASI KEASLIAN DOKUMEN</strong><br>Kode: {{ $minute->verification_code }}<br><span style="font-size:9px">{{ $verificationUrl }}</span></div>
        <div class="ba-verification-qr"><img src="{{ $isPdf ? $qrSrc : route('exam-minutes.qr', $minute) }}" alt="QR verifikasi"></div>
    </div>
</div>
