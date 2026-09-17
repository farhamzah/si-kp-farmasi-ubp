<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\KpQuestionnaire;
use App\Models\KpQuestionnaireResponse;
use App\Services\KpQuestionnaireAnalyticsService;
use App\Services\KpQuestionnaireDefaultService;
use App\Support\SimplePdfReport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class QuestionnaireResultController extends Controller
{
    public function index(Request $request, KpQuestionnaireDefaultService $defaults, KpQuestionnaireAnalyticsService $analytics): View
    {
        $defaults->ensureDefaults($request->user());

        $responses = $this->responseQuery($request)
            ->latest('submitted_at')
            ->paginate(15)
            ->withQueryString();

        return view('management.questionnaires.results', [
            'responses' => $responses,
            'audiences' => KpQuestionnaire::AUDIENCE_LABELS,
            'filters' => $request->only(['audience', 'q']),
            'summaries' => $analytics->summarize($request->audience, $request->q),
        ]);
    }

    public function preview(
        string $type,
        Request $request,
        KpQuestionnaireDefaultService $defaults,
        KpQuestionnaireAnalyticsService $analytics
    ): View {
        $this->ensureReportType($type);
        $defaults->ensureDefaults($request->user());

        return view('management.questionnaires.report-preview', [
            'type' => $type,
            'title' => $type === 'summary' ? 'Ringkasan Hasil Kuisioner KP' : 'Daftar Respons Kuisioner KP',
            'summaries' => $type === 'summary' ? $analytics->summarize($request->audience, $request->q) : [],
            'responses' => $type === 'responses' ? $this->responseQuery($request)->latest('submitted_at')->get() : collect(),
            'filters' => $this->filterSummary($request),
            'printMode' => $request->boolean('print'),
        ]);
    }

    public function download(
        string $type,
        Request $request,
        KpQuestionnaireDefaultService $defaults,
        KpQuestionnaireAnalyticsService $analytics
    ): Response {
        $this->ensureReportType($type);
        $defaults->ensureDefaults($request->user());

        [$title, $headings, $rows] = $type === 'summary'
            ? $this->summaryPdfRows($analytics->summarize($request->audience, $request->q))
            : $this->responsePdfRows($this->responseQuery($request)->latest('submitted_at')->get());

        $filename = ($type === 'summary' ? 'hasil-kuisioner-kp-' : 'daftar-respons-kuisioner-kp-').now()->format('Ymd-His').'.pdf';

        return response(SimplePdfReport::table(
            $title,
            $this->filterSummary($request),
            $headings,
            $rows
        ), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function show(KpQuestionnaireResponse $response, KpQuestionnaireAnalyticsService $analytics): View
    {
        return view('management.questionnaires.result-show', [
            'response' => $response->load(['questionnaire.questions', 'answers.question', 'respondent', 'assignment.student.user', 'assignment.period', 'assignment.place', 'place', 'period']),
            'answerMap' => $response->answerMap(),
            'score' => $analytics->responseScore($response),
        ]);
    }

    private function responseQuery(Request $request): Builder
    {
        return KpQuestionnaireResponse::query()
            ->with(['questionnaire', 'respondent', 'assignment.student.user', 'assignment.period', 'assignment.place', 'place', 'period'])
            ->where('status', 'submitted')
            ->when($request->filled('audience'), fn ($query) => $query->whereHas('questionnaire', fn ($questionnaire) => $questionnaire->where('audience', $request->audience)))
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = '%'.$request->q.'%';
                $query->where(function ($query) use ($term): void {
                    $query->whereHas('respondent', fn ($user) => $user->where('name', 'like', $term)->orWhere('email', 'like', $term))
                        ->orWhereHas('assignment.student.user', fn ($user) => $user->where('name', 'like', $term)->orWhere('email', 'like', $term))
                        ->orWhereHas('assignment.place', fn ($place) => $place->where('name', 'like', $term))
                        ->orWhereHas('place', fn ($place) => $place->where('name', 'like', $term))
                        ->orWhereHas('period', fn ($period) => $period->where('name', 'like', $term));
                });
            });
    }

    private function summaryPdfRows(array $summaries): array
    {
        $headings = ['No', 'Kuisioner', 'Sasaran', 'Respons', 'Pertanyaan', 'Rata-rata', 'Capaian', 'Aspek Terkuat', 'Perlu Perhatian'];
        $rows = collect($summaries)->values()->map(fn (array $summary, int $index): array => [
            $index + 1,
            $summary['questionnaire']->title,
            $summary['questionnaire']->audienceLabel(),
            $summary['response_count'],
            $summary['question_count'],
            ($summary['average'] ?? '-').' ('.$summary['label'].')',
            $summary['percentage'] === null ? '-' : $summary['percentage'].'%',
            ($summary['strongest']['section'] ?? '-').' ('.($summary['strongest']['average'] ?? '-').')',
            ($summary['weakest']['section'] ?? '-').' ('.($summary['weakest']['average'] ?? '-').')',
        ])->all();

        return ['Ringkasan Hasil Kuisioner KP', $headings, $rows];
    }

    private function responsePdfRows(Collection $responses): array
    {
        $headings = ['No', 'Kuisioner', 'Jenis', 'Responden', 'Konteks KP', 'Periode', 'Submit'];
        $rows = $responses->values()->map(function (KpQuestionnaireResponse $response, int $index): array {
            $isPlaceQuestionnaire = $response->questionnaire->audience === KpQuestionnaire::AUDIENCE_FIELD_SUPERVISOR;
            $place = $response->place?->name ?? $response->assignment?->place?->name ?? '-';
            $period = $response->period?->name ?? $response->assignment?->period?->name ?? '-';
            $student = $response->assignment?->student?->user?->name ?? '-';

            return [
                $index + 1,
                $response->questionnaire->title,
                $response->questionnaire->audienceLabel(),
                $response->respondent->name.' - '.$response->respondent->email,
                $isPlaceQuestionnaire ? $place : $student.' - '.$place,
                $period,
                $response->submitted_at?->format('d M Y H:i') ?? '-',
            ];
        })->all();

        return ['Daftar Respons Kuisioner KP', $headings, $rows];
    }

    private function filterSummary(Request $request): array
    {
        return [
            'Jenis kuisioner' => $request->filled('audience')
                ? (KpQuestionnaire::AUDIENCE_LABELS[$request->audience] ?? ucfirst((string) $request->audience))
                : 'Semua jenis kuisioner',
            'Pencarian' => $request->filled('q') ? (string) $request->q : '-',
            'Dicetak pada' => now()->format('d M Y H:i'),
        ];
    }

    private function ensureReportType(string $type): void
    {
        abort_unless(in_array($type, ['summary', 'responses'], true), 404);
    }
}
