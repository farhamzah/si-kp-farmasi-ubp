<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Rekap Pembimbing Dalam</title>
    <style>
        @page Section1 { size: 29.7cm 21cm; margin: 1.2cm; }
        body { color: #0f172a; font-family: Arial, sans-serif; font-size: 9pt; }
        div.Section1 { page: Section1; }
        h1 { font-size: 18pt; margin: 0 0 3pt; }
        .subtitle { color: #475569; font-size: 10pt; margin: 0 0 12pt; }
        .meta { border-collapse: collapse; margin: 10pt 0 14pt; width: 100%; }
        .meta td { border: 1px solid #dbe3ef; padding: 6pt; vertical-align: top; width: 25%; }
        .meta strong { color: #334155; }
        table.report { border-collapse: collapse; table-layout: fixed; width: 100%; }
        table.report th, table.report td { border: 1px solid #dbe3ef; padding: 5pt; vertical-align: top; word-wrap: break-word; }
        table.report th { background: #f1f5f9; color: #334155; font-size: 8pt; text-transform: uppercase; }
        .number { text-align: center; }
        .total td { background: #ecfeff; font-weight: bold; }
    </style>
</head>
<body>
<div class="Section1">
    <h1>Rekap Pembimbing Dalam</h1>
    <p class="subtitle">SI-KP Farmasi UBP</p>

    <table class="meta">
        @foreach(array_chunk($filters + ['Total dosen' => max(0, $rows->count() - 1)], 4, true) as $filterRow)
            <tr>
                @foreach($filterRow as $label => $value)
                    <td><strong>{{ $label }}:</strong><br>{{ $value }}</td>
                @endforeach
                @for($i = count($filterRow); $i < 4; $i++)
                    <td></td>
                @endfor
            </tr>
        @endforeach
    </table>

    <table class="report">
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Dosen Pembimbing</th>
                <th>RS</th>
                <th>Apotek</th>
                <th>Industri</th>
                <th>Lainnya</th>
                <th>Total</th>
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
</div>
</body>
</html>
