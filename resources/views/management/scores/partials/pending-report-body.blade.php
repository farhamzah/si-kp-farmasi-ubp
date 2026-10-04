<header>
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
    <h1>DAFTAR PENILAI BELUM SUBMIT NILAI KP</h1>
    <p class="generated">Dicetak dari SI-KP Farmasi UBP pada {{ now()->translatedFormat('d F Y H:i') }} WIB</p>
</header>

<div class="meta">
    @foreach($filters as $label => $value)
        <div><span>{{ $label }}</span><strong>{{ $value }}</strong></div>
    @endforeach
    <div><span>Penilai tertunda</span><strong>{{ $pendingAssessors->count() }} penilai/peran</strong></div>
    <div><span>Mahasiswa terkait</span><strong>{{ $uniqueStudentCount }} mahasiswa</strong></div>
</div>

<table>
    <thead><tr><th class="number">No</th><th class="assessor">Penilai</th><th class="role">Peran</th><th class="count">Belum Dinilai</th><th>Daftar Mahasiswa</th></tr></thead>
    <tbody>
        @forelse($pendingAssessors as $pending)
            <tr>
                <td class="number">{{ $loop->iteration }}</td>
                <td><strong>{{ $pending['assessor']->name }}</strong><br><span class="muted">{{ $pending['assessor']->email }}</span></td>
                <td>{{ $pending['assessor_label'] }}</td>
                <td>{{ $pending['pending_count'] }} mahasiswa</td>
                <td>
                    @foreach($pending['items'] as $item)
                        <div class="student-item"><strong>{{ $item['student_name'] }}</strong> ({{ $item['student_number'] }}) · {{ $item['place_name'] }}</div>
                    @endforeach
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="empty">Semua penilai pada periode ini sudah melakukan submit.</td></tr>
        @endforelse
    </tbody>
</table>

<footer>Silakan membuka {{ url('/login') }} untuk melengkapi penilaian. Data terbaru tetap mengikuti SI-KP Farmasi UBP.</footer>
