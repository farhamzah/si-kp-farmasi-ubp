<header class="report-header">
    <div class="brand-row">
        @if($logoSrc)<img src="{{ $logoSrc }}" alt="Logo UBP" class="logo">@endif
        <div>
            <div class="foundation">YAYASAN PEMBINA PERGURUAN TINGGI PANGKAL PERJUANGAN</div>
            <div class="university">UNIVERSITAS BUANA PERJUANGAN KARAWANG</div>
            <div class="faculty">FAKULTAS FARMASI</div>
            <div class="address">Jl. HS. Ronggo Waluyo, Puseurjaya, Telukjambe Timur, Karawang 41361</div>
        </div>
    </div>
    <div class="divider"></div>
    <h1>DAFTAR JADWAL SIDANG KERJA PRAKTIK</h1>
    <p class="generated">Dicetak dari SI-KP Farmasi UBP pada {{ now()->translatedFormat('d F Y H:i') }} WIB</p>
</header>

<div class="filter-grid">
    @foreach($filters as $label => $value)
        <div><span>{{ $label }}</span><strong>{{ $value }}</strong></div>
    @endforeach
    <div><span>Total jadwal</span><strong>{{ $exams->count() }} sidang</strong></div>
</div>

<table>
    <thead>
        <tr>
            <th class="number">No</th>
            <th class="schedule">Hari / Tanggal</th>
            <th class="time">Waktu</th>
            <th class="student">Mahasiswa</th>
            <th class="title">Judul Laporan</th>
            <th class="location">Ruang / Media</th>
            <th class="team">Tim Sidang</th>
        </tr>
    </thead>
    <tbody>
        @forelse($exams as $exam)
            <tr>
                <td class="number">{{ $loop->iteration }}</td>
                <td><strong>{{ $exam->exam_date?->translatedFormat('l') }}</strong><br>{{ $exam->exam_date?->translatedFormat('d F Y') }}<br><span class="muted">{{ $exam->statusLabel() }}</span></td>
                <td>{{ substr((string) $exam->start_time, 0, 5) }}–{{ substr((string) $exam->end_time, 0, 5) }} WIB</td>
                <td><strong>{{ $exam->assignment?->student?->user?->name ?? '-' }}</strong><br>{{ $exam->assignment?->student?->nim ?? '-' }}</td>
                <td>{{ $exam->assignment?->finalReport?->report_title ?: $exam->assignment?->finalReport?->final_document_label ?: 'Judul belum diisi' }}</td>
                <td><strong>{{ $exam->room ?: ($exam->mode === 'online' ? 'Daring' : '-') }}</strong><br>{{ $exam->modeLabel() }}<br><span class="muted">KP: {{ $exam->assignment?->place?->name ?? '-' }}</span></td>
                <td><strong>Ketua:</strong> {{ $exam->chair ? lecturer_display_name($exam->chair) : '-' }}<br><strong>Pembimbing:</strong> {{ $exam->supervisor ? lecturer_display_name($exam->supervisor) : '-' }}<br><strong>Penguji:</strong> {{ $exam->examinerNamesLabel() }}</td>
            </tr>
        @empty
            <tr><td colspan="7" class="empty">Belum ada jadwal sidang sesuai filter.</td></tr>
        @endforelse
    </tbody>
</table>

<footer>Dokumen ini dapat dibagikan sebagai informasi jadwal. Perubahan resmi tetap mengikuti data terbaru pada SI-KP Farmasi UBP.</footer>
