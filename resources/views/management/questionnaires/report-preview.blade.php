<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    <style>
        @page { size: A4 landscape; margin: 12mm; }
        * { box-sizing: border-box; }
        body { background: #f8fafc; color: #0f172a; font-family: Arial, sans-serif; margin: 0; padding: 28px; }
        .sheet { background: white; margin: 0 auto; max-width: 1200px; padding: 28px; }
        .toolbar { display: flex; gap: 8px; justify-content: flex-end; margin: 0 auto 14px; max-width: 1200px; }
        .toolbar button, .toolbar a { background: white; border: 1px solid #cbd5e1; border-radius: 8px; color: #0f172a; cursor: pointer; font-size: 13px; font-weight: 700; padding: 9px 13px; text-decoration: none; }
        .brand { color: #0e7490; font-size: 11px; font-weight: 800; letter-spacing: 1px; text-transform: uppercase; }
        h1 { font-size: 24px; margin: 5px 0; }
        .generated { color: #64748b; font-size: 12px; }
        .meta { display: grid; gap: 8px; grid-template-columns: repeat(3, 1fr); margin: 20px 0; }
        .meta-item { border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 12px; }
        .meta-label { color: #64748b; display: block; font-size: 9px; font-weight: 800; margin-bottom: 4px; text-transform: uppercase; }
        .summary { border: 1px solid #dbeafe; border-radius: 10px; break-inside: avoid; margin-top: 16px; padding: 18px; }
        .summary h2 { font-size: 17px; margin: 3px 0 5px; }
        .audience { color: #0e7490; font-size: 10px; font-weight: 800; text-transform: uppercase; }
        .conclusion { color: #475569; font-size: 11px; line-height: 1.5; margin: 0 0 14px; }
        .metrics { display: grid; gap: 8px; grid-template-columns: repeat(5, 1fr); margin-bottom: 14px; }
        .metric { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px; }
        .metric strong { display: block; font-size: 16px; margin-top: 3px; }
        .metric span { color: #64748b; font-size: 9px; font-weight: 800; text-transform: uppercase; }
        .two-columns { display: grid; gap: 12px; grid-template-columns: 1fr 1fr; }
        table { border-collapse: collapse; font-size: 10px; width: 100%; }
        th, td { border: 1px solid #dbe3ef; padding: 7px; text-align: left; vertical-align: top; }
        th { background: #f1f5f9; color: #475569; font-size: 9px; text-transform: uppercase; }
        .section-title { font-size: 11px; margin: 0 0 7px; }
        .empty { color: #64748b; padding: 30px; text-align: center; }
        @media print {
            body { background: white; padding: 0; }
            .toolbar { display: none; }
            .sheet { max-width: none; padding: 0; }
            .summary { page-break-inside: avoid; }
        }
    </style>
</head>
<body @if($printMode) onload="window.print()" @endif>
    <div class="toolbar">
        <button type="button" onclick="window.print()">Print</button>
        <a href="{{ route('management.questionnaire-results.index', request()->only(['audience', 'q'])) }}">Kembali</a>
    </div>

    <main class="sheet">
        <div class="brand">SI-KP Farmasi UBP</div>
        <h1>{{ $title }}</h1>
        <div class="generated">Dokumen monitoring koordinator kerja praktik</div>

        <div class="meta">
            @foreach($filters as $label => $value)
                <div class="meta-item"><span class="meta-label">{{ $label }}</span>{{ $value }}</div>
            @endforeach
        </div>

        @if($type === 'summary')
            @forelse($summaries as $summary)
                <section class="summary">
                    <div class="audience">{{ $summary['questionnaire']->audienceLabel() }}</div>
                    <h2>{{ $summary['questionnaire']->title }}</h2>
                    <p class="conclusion">{{ $summary['conclusion'] }}</p>

                    <div class="metrics">
                        <div class="metric"><span>Respons</span><strong>{{ $summary['response_count'] }}</strong></div>
                        <div class="metric"><span>Pertanyaan</span><strong>{{ $summary['question_count'] }}</strong></div>
                        <div class="metric"><span>Rata-rata</span><strong>{{ $summary['average'] ?? '-' }}</strong></div>
                        <div class="metric"><span>Capaian</span><strong>{{ $summary['percentage'] === null ? '-' : $summary['percentage'].'%' }}</strong></div>
                        <div class="metric"><span>Kategori</span><strong>{{ $summary['label'] }}</strong></div>
                    </div>

                    <div class="two-columns">
                        <div>
                            <h3 class="section-title">Skor per Aspek</h3>
                            <table>
                                <thead><tr><th>Aspek</th><th>Rata-rata</th><th>Capaian</th><th>Jawaban</th></tr></thead>
                                <tbody>
                                    @forelse($summary['sections'] as $section)
                                        <tr><td>{{ $section['section'] }}</td><td>{{ $section['average'] ?? '-' }}</td><td>{{ $section['percentage'] === null ? '-' : $section['percentage'].'%' }}</td><td>{{ $section['answer_count'] }}</td></tr>
                                    @empty
                                        <tr><td colspan="4">Belum ada skor aspek.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div>
                            <h3 class="section-title">Distribusi Skor</h3>
                            <table>
                                <thead><tr><th>Skor</th><th>Jumlah Jawaban</th></tr></thead>
                                <tbody>
                                    @foreach([5, 4, 3, 2, 1] as $scale)
                                        <tr><td>{{ $scale }}</td><td>{{ $summary['distribution'][$scale] ?? 0 }}</td></tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
            @empty
                <div class="empty">Belum ada hasil kuisioner sesuai filter.</div>
            @endforelse
        @else
            <table>
                <thead>
                    <tr><th>No</th><th>Kuisioner</th><th>Responden</th><th>Konteks KP</th><th>Submit</th></tr>
                </thead>
                <tbody>
                    @forelse($responses as $response)
                        @php
                            $contextPlace = $response->place?->name ?? $response->assignment?->place?->name ?? '-';
                            $contextPeriod = $response->period?->name ?? $response->assignment?->period?->name ?? '-';
                            $contextStudent = $response->assignment?->student?->user?->name;
                            $isPlaceQuestionnaire = $response->questionnaire->audience === \App\Models\KpQuestionnaire::AUDIENCE_FIELD_SUPERVISOR;
                        @endphp
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td><strong>{{ $response->questionnaire->title }}</strong><br>{{ $response->questionnaire->audienceLabel() }}</td>
                            <td><strong>{{ $response->respondent->name }}</strong><br>{{ $response->respondent->email }}</td>
                            <td><strong>{{ $isPlaceQuestionnaire ? $contextPlace : ($contextStudent ?? '-') }}</strong><br>{{ $contextPlace }} - {{ $contextPeriod }}</td>
                            <td>{{ $response->submitted_at?->format('d M Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty">Belum ada respons sesuai filter.</td></tr>
                    @endforelse
                </tbody>
            </table>
        @endif
    </main>
</body>
</html>
