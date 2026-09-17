<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page { size: A4 landscape; margin: 10mm; }
        * { box-sizing: border-box; }
        body { color: #0f172a; font-family: DejaVu Sans, sans-serif; margin: 0; }
        .brand-row { text-align: center; }
        .logo { float: left; height: 60px; object-fit: contain; width: 78px; }
        .foundation { font-size: 8px; font-weight: 700; }
        .university { font-size: 15px; font-weight: 800; margin-top: 2px; }
        .faculty { font-size: 13px; font-weight: 800; }
        .address { font-size: 7px; margin-top: 3px; }
        .divider { border-top: 2px solid #0f172a; clear: both; margin: 8px 0 11px; }
        h1 { font-size: 14px; margin: 0; text-align: center; text-decoration: underline; }
        .generated { color: #64748b; font-size: 7px; margin: 4px 0 0; text-align: center; }
        .filter-grid { margin: 11px 0 9px; width: 100%; }
        .filter-grid div { border: 1px solid #dbe3ef; display: inline-block; margin-right: 1%; padding: 5px 7px; vertical-align: top; width: 22.8%; }
        .filter-grid span { color: #64748b; display: block; font-size: 6px; font-weight: 700; text-transform: uppercase; }
        .filter-grid strong { display: block; font-size: 7px; margin-top: 2px; }
        table { border-collapse: collapse; table-layout: fixed; width: 100%; }
        th, td { border: 1px solid #cbd5e1; overflow-wrap: anywhere; padding: 5px; text-align: left; vertical-align: top; }
        th { background: #e6f7fa; color: #155e75; font-size: 6px; text-transform: uppercase; }
        td { font-size: 6.5px; line-height: 1.3; }
        tr { page-break-inside: avoid; }
        .number { text-align: center; width: 4%; }
        .schedule { width: 12%; } .student { width: 15%; } .location { width: 13%; } .status { width: 9%; }
        .backdate { color: #a16207; font-size: 6px; font-weight: 700; }
        .empty { color: #64748b; padding: 25px; text-align: center; }
        footer { color: #64748b; font-size: 6px; margin-top: 7px; text-align: right; }
    </style>
</head>
<body>@include('management.exams.partials.report-body')</body>
</html>
