<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rekap Pembimbing Dalam</title>
    <style>
        body { color: #0f172a; font-family: Arial, sans-serif; margin: 32px; }
        h1 { font-size: 22px; margin: 0 0 4px; }
        .subtitle { color: #475569; font-size: 13px; margin-bottom: 20px; }
        .toolbar { display: flex; flex-wrap: wrap; gap: 8px; justify-content: flex-end; margin-bottom: 16px; }
        .toolbar button, .toolbar a { border: 1px solid #cbd5e1; border-radius: 8px; color: #0f172a; padding: 8px 12px; text-decoration: none; }
        .meta { color: #475569; display: grid; font-size: 12px; gap: 6px; grid-template-columns: repeat(4, 1fr); line-height: 1.5; margin: 18px 0; }
        .meta div { border: 1px solid #e2e8f0; border-radius: 10px; padding: 8px; }
        table { border-collapse: collapse; font-size: 12px; width: 100%; }
        th, td { border: 1px solid #dbe3ef; padding: 8px; text-align: left; vertical-align: top; }
        th { background: #f1f5f9; color: #475569; font-size: 11px; text-transform: uppercase; }
        td.number, th.number { text-align: center; }
        tr.total { background: #ecfeff; font-weight: 700; }
        @media print {
            body { margin: 16mm; }
            .toolbar { display: none; }
            a { color: inherit; text-decoration: none; }
            .meta { grid-template-columns: repeat(4, 1fr); }
        }
        @media (max-width: 768px) {
            body { margin: 18px; }
            .meta { grid-template-columns: 1fr; }
            table { font-size: 11px; }
        }
    </style>
</head>
<body @if($printMode) onload="window.print()" @endif>
    <div class="toolbar">
        <button onclick="window.print()">Print</button>
        <a href="{{ url()->previous() }}">Kembali</a>
    </div>

    <h1>Rekap Pembimbing Dalam</h1>
    <div class="subtitle">SI-KP Farmasi UBP</div>

    <div class="meta">
        @foreach($filters as $label => $value)
            <div><strong>{{ $label }}:</strong><br>{{ $value }}</div>
        @endforeach
        <div><strong>Total dosen:</strong><br>{{ max(0, $rows->count() - 1) }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Dosen Pembimbing</th>
                <th class="number">RS</th>
                <th class="number">Apotek</th>
                <th class="number">Industri</th>
                <th class="number">Lainnya</th>
                <th class="number">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr class="{{ $row['Nama Dosen Pembimbing'] === 'TOTAL' ? 'total' : '' }}">
                    <td>{{ $row['No'] }}</td>
                    <td>{{ $row['Nama Dosen Pembimbing'] }}</td>
                    <td class="number">{{ $row['RS'] }}</td>
                    <td class="number">{{ $row['Apotek'] }}</td>
                    <td class="number">{{ $row['Industri'] }}</td>
                    <td class="number">{{ $row['Lainnya'] }}</td>
                    <td class="number">{{ $row['Total'] }}</td>
                </tr>
            @empty
                <tr><td colspan="7">Belum ada data sesuai filter.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
