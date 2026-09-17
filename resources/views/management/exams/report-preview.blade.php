<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Jadwal Sidang KP</title>
    <style>
        @page { size: A4 landscape; margin: 10mm; }
        * { box-sizing: border-box; }
        body { background: #eaf7f8; color: #0f172a; font-family: Arial, sans-serif; margin: 0; padding: 24px; }
        .toolbar { display: flex; gap: 8px; justify-content: flex-end; margin: 0 auto 14px; max-width: 1120px; }
        .toolbar a, .toolbar button { background: #fff; border: 1px solid #cbd5e1; border-radius: 8px; color: #0f172a; cursor: pointer; font-size: 13px; font-weight: 700; padding: 9px 13px; text-decoration: none; }
        .toolbar .primary { background: #0e7490; border-color: #0e7490; color: #fff; }
        .sheet { background: #fff; box-shadow: 0 10px 30px rgba(15, 23, 42, .12); margin: 0 auto; max-width: 1120px; min-height: 730px; padding: 28px 34px; }
        .brand-row { align-items: center; display: flex; gap: 18px; justify-content: center; text-align: center; }
        .logo { height: 68px; object-fit: contain; width: 82px; }
        .foundation { font-size: 10px; font-weight: 700; }
        .university { font-size: 18px; font-weight: 800; margin-top: 2px; }
        .faculty { font-size: 16px; font-weight: 800; }
        .address { font-size: 9px; margin-top: 4px; }
        .divider { border-top: 2px solid #0f172a; margin: 10px 0 14px; }
        h1 { font-size: 18px; margin: 0; text-align: center; text-decoration: underline; }
        .generated { color: #64748b; font-size: 10px; margin: 5px 0 0; text-align: center; }
        .filter-grid { display: grid; gap: 7px; grid-template-columns: repeat(4, 1fr); margin: 16px 0 12px; }
        .filter-grid div { border: 1px solid #dbe3ef; border-radius: 6px; padding: 7px 9px; }
        .filter-grid span { color: #64748b; display: block; font-size: 8px; font-weight: 700; text-transform: uppercase; }
        .filter-grid strong { display: block; font-size: 10px; margin-top: 3px; }
        table { border-collapse: collapse; table-layout: fixed; width: 100%; }
        th, td { border: 1px solid #cbd5e1; overflow-wrap: anywhere; padding: 7px; text-align: left; vertical-align: top; }
        th { background: #e6f7fa; color: #155e75; font-size: 8px; text-transform: uppercase; }
        td { font-size: 9px; line-height: 1.35; }
        .number { text-align: center; width: 4%; }
        .schedule { width: 12%; } .student { width: 15%; } .location { width: 13%; } .status { width: 9%; }
        .backdate { color: #a16207; font-size: 8px; font-weight: 700; }
        .empty { color: #64748b; padding: 30px; text-align: center; }
        footer { color: #64748b; font-size: 8px; margin-top: 10px; text-align: right; }
        @media print { body { background: #fff; padding: 0; } .toolbar { display: none; } .sheet { box-shadow: none; max-width: none; min-height: 0; padding: 0; } }
    </style>
</head>
<body @if($printMode) onload="window.print()" @endif>
    <div class="toolbar">
        <a href="{{ route('management.exams.index', $query) }}">Kembali</a>
        <a href="{{ route('management.exams.report.pdf', $query) }}">Download PDF</a>
        <button type="button" class="primary" onclick="window.print()">Print</button>
    </div>
    <main class="sheet">@include('management.exams.partials.report-body')</main>
</body>
</html>
