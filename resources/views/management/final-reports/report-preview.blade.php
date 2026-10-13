<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Monitoring Laporan KP</title>
    <style>
        @page { size: A4 landscape; margin: 12mm; }
        body { color: #0f172a; font-family: Arial, sans-serif; margin: 24px; }
        h1 { font-size: 21px; margin: 0 0 4px; }
        .subtitle { color: #475569; font-size: 12px; margin-bottom: 14px; }
        .toolbar { display: flex; flex-wrap: wrap; gap: 8px; justify-content: flex-end; margin-bottom: 16px; }
        .toolbar a, .toolbar button { background: white; border: 1px solid #cbd5e1; border-radius: 6px; color: #0f172a; cursor: pointer; font-size: 13px; padding: 8px 12px; text-decoration: none; }
        .meta { display: grid; gap: 6px; grid-template-columns: repeat(4, 1fr); margin: 12px 0; }
        .meta div { border: 1px solid #e2e8f0; font-size: 10px; line-height: 1.4; padding: 6px; }
        .table-wrap { overflow-x: auto; }
        table { border-collapse: collapse; font-size: 9px; table-layout: fixed; width: 100%; }
        th, td { border: 1px solid #dbe3ef; overflow-wrap: anywhere; padding: 5px; text-align: left; vertical-align: top; }
        th { background: #f1f5f9; color: #334155; }
        thead { display: table-header-group; }
        tr { break-inside: avoid; }
        @media print { body { margin: 0; } .toolbar { display: none; } .table-wrap { overflow: visible; } }
        @media (max-width: 700px) { .meta { grid-template-columns: repeat(2, 1fr); } table { min-width: 1050px; } }
    </style>
</head>
<body @if($printMode) onload="window.print()" @endif>
    <div class="toolbar">
        <a href="{{ route('management.final-reports.index', $reportQuery) }}">Kembali</a>
        <a href="{{ route('management.final-reports.report.download', array_merge(['format' => 'pdf'], $reportQuery)) }}">PDF</a>
        <a href="{{ route('management.final-reports.report.download', array_merge(['format' => 'excel'], $reportQuery)) }}">Excel</a>
        <a href="{{ route('management.final-reports.report.download', array_merge(['format' => 'word'], $reportQuery)) }}">Word</a>
        <button type="button" onclick="window.print()">Print</button>
    </div>
    <h1>Monitoring Laporan KP</h1>
    <div class="subtitle">SI-KP Farmasi UBP</div>
    <div class="meta">
        @foreach($filters + ['Total data' => $rows->count()] as $label => $value)
            <div><strong>{{ $label }}:</strong><br>{{ $value }}</div>
        @endforeach
    </div>
    <div class="table-wrap">
        <table>
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
    </div>
</body>
</html>
