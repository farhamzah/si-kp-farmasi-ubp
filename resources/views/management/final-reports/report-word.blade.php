<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Monitoring Laporan KP</title>
    <style>
        @page Section1 { size: 29.7cm 21cm; margin: 1.2cm; }
        div.Section1 { page: Section1; }
        body { color: #0f172a; font-family: Arial, sans-serif; font-size: 8pt; }
        h1 { font-size: 17pt; margin-bottom: 2pt; }
        .meta, .report { border-collapse: collapse; width: 100%; }
        .meta { margin: 10pt 0; }
        .meta td, .report th, .report td { border: 1px solid #dbe3ef; padding: 5pt; vertical-align: top; word-wrap: break-word; }
        .meta td { width: 25%; }
        .report { table-layout: fixed; }
        .report th { background: #f1f5f9; color: #334155; }
    </style>
</head>
<body><div class="Section1">
    <h1>Monitoring Laporan KP</h1>
    <div>SI-KP Farmasi UBP</div>
    <table class="meta">
        @foreach(array_chunk($filters + ['Total data' => $rows->count()], 4, true) as $filterRow)
            <tr>
                @foreach($filterRow as $label => $value)
                    <td><strong>{{ $label }}:</strong><br>{{ $value }}</td>
                @endforeach
                @for($i = count($filterRow); $i < 4; $i++)<td></td>@endfor
            </tr>
        @endforeach
    </table>
    <table class="report">
        <thead><tr>
            @foreach(array_keys($rows->first() ?? ['No' => '', 'Mahasiswa' => '', 'NIM' => '', 'Periode' => '', 'Tempat KP' => '', 'Pembimbing Dalam' => '', 'Pembimbing Lapangan' => '', 'Status Laporan' => '', 'Versi' => '']) as $heading)
                <th>{{ $heading }}</th>
            @endforeach
        </tr></thead>
        <tbody>
            @forelse($rows as $row)
                <tr>@foreach($row as $value)<td>{{ $value }}</td>@endforeach</tr>
            @empty
                <tr><td colspan="9">Belum ada data sesuai filter.</td></tr>
            @endforelse
        </tbody>
    </table>
</div></body>
</html>
