<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page { size: A4 landscape; margin: 10mm; }
        * { box-sizing: border-box; }
        body { color: #0f172a; font-family: DejaVu Sans, sans-serif; margin: 0; }
        .brand-row { text-align: center; } .logo { float: left; height: 60px; object-fit: contain; width: 78px; }
        .foundation { font-size: 8px; font-weight: 700; } .university { font-size: 15px; font-weight: 800; } .faculty { font-size: 13px; font-weight: 800; } .address { font-size: 7px; margin-top: 3px; }
        .divider { border-top: 2px solid #0f172a; clear: both; margin: 8px 0 11px; } h1 { font-size: 14px; margin: 0; text-align: center; text-decoration: underline; } .generated { color: #64748b; font-size: 7px; text-align: center; }
        .meta { margin: 11px 0 9px; width: 100%; } .meta div { border: 1px solid #dbe3ef; display: inline-block; margin-right: 1%; padding: 5px 7px; vertical-align: top; width: 22.8%; } .meta span { color: #64748b; display: block; font-size: 6px; font-weight: 700; text-transform: uppercase; } .meta strong { display: block; font-size: 7px; margin-top: 2px; }
        table { border-collapse: collapse; table-layout: fixed; width: 100%; } th, td { border: 1px solid #cbd5e1; overflow-wrap: anywhere; padding: 5px; text-align: left; vertical-align: top; } th { background: #fff7ed; color: #9a3412; font-size: 6px; text-transform: uppercase; } td { font-size: 6.5px; line-height: 1.3; } tr { page-break-inside: avoid; }
        .number { text-align: center; width: 4%; } .assessor { width: 20%; } .role { width: 14%; } .count { width: 10%; } .muted { color: #64748b; font-size: 6px; } .student-item + .student-item { border-top: 1px solid #e2e8f0; margin-top: 3px; padding-top: 3px; } .empty { color: #64748b; padding: 25px; text-align: center; } footer { color: #64748b; font-size: 6px; margin-top: 7px; text-align: right; }
    </style>
</head>
<body>@include('management.scores.partials.pending-report-body')</body>
</html>
