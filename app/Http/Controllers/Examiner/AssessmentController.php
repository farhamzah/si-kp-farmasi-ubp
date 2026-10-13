<?php

namespace App\Http\Controllers\Examiner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Assessment\SaveScoreRequest;
use App\Http\Requests\Assessment\SubmitScoreRequest;
use App\Models\KpAssessmentComponent;
use App\Models\KpExam;
use App\Services\KpAssessmentService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AssessmentController extends Controller
{
    public function index(): View
    {
        $lecturer = request()->user()->lecturer;
        $tab = in_array(request()->query('tab'), ['queue', 'minutes', 'completed'], true) ? request()->query('tab') : 'queue';
        $exams = KpExam::with(['assignment.student.user', 'assignment.period.assessmentComponents', 'assignment.place', 'assignment.scores', 'assignment.finalScore', 'examiner', 'examiners', 'minutes'])
            ->whereHas('assignment', fn (Builder $query) => $query->whereIn('status', ['aktif', 'berjalan', 'selesai']))
            ->where('status', '!=', 'dibatalkan')
            ->where(function (Builder $query) use ($lecturer): void {
                $query->forExaminer($lecturer?->id);
                if ($lecturer) {
                    $query->orWhere('chair_lecturer_id', $lecturer->id);
                }
            })
            ->latest('exam_date')->get();

        $groups = ['queue' => collect(), 'minutes' => collect(), 'completed' => collect()];
        foreach ($exams as $exam) {
            $isExaminer = $exam->hasExaminer($lecturer?->id);
            $isChair = $lecturer && (int) $exam->chair_lecturer_id === (int) $lecturer->id;
            $submitted = $isExaminer && $exam->assignment->areRequiredScoresSubmittedBy(request()->user()->id, 'penguji');
            $group = $isExaminer && ! $submitted && ! $exam->assignment->finalScore?->isLocked()
                ? 'queue'
                : ($isChair && $exam->minutes?->status !== 'terbit' ? 'minutes' : 'completed');
            $groups[$group]->push($exam);
        }

        $page = LengthAwarePaginator::resolveCurrentPage();
        $selected = $groups[$tab];
        $paginated = new LengthAwarePaginator($selected->forPage($page, 10)->values(), $selected->count(), 10, $page, [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
            'query' => ['tab' => $tab],
        ]);

        return view('examiner.assessments.index', [
            'exams' => $paginated,
            'tab' => $tab,
            'counts' => collect($groups)->map(fn ($items) => $items->count()),
        ]);
    }

    public function show(KpExam $exam): View
    {
        abort_unless(in_array($exam->assignment?->status, ['aktif', 'berjalan', 'selesai'], true) && $exam->status !== 'dibatalkan', 404);
        $lecturerId = request()->user()->lecturer?->id;
        $isExaminer = $exam->hasExaminer($lecturerId);
        $isChair = $lecturerId && (int) $exam->chair_lecturer_id === (int) $lecturerId;
        abort_unless($isExaminer || $isChair, 403);

        app(KpAssessmentService::class)->ensureDefaultComponents($exam->assignment->period, request()->user());
        $exam->load(['chair.user', 'minutes']);
        $assignment = $exam->assignment->load(['student.user', 'period', 'place', 'scores.component', 'finalScore']);
        $components = $assignment->period->assessmentComponents()->where('status', 'aktif')->where('assessor_type', 'penguji')->orderBy('sort_order')->get();
        $chairScoreSubmitted = ! $isExaminer || $components
            ->where('is_required', true)
            ->every(fn (KpAssessmentComponent $component): bool => $assignment->scores
                ->where('kp_assessment_component_id', $component->id)
                ->where('assessor_user_id', request()->user()->id)
                ->whereIn('status', ['submitted', 'locked'])
                ->isNotEmpty());

        return view('examiner.assessments.show', [
            'exam' => $exam,
            'assignment' => $assignment,
            'components' => $components,
            'assessorType' => 'penguji',
            'isExaminer' => $isExaminer,
            'isChair' => $isChair,
            'chairScoreSubmitted' => $chairScoreSubmitted,
        ]);
    }

    public function save(SaveScoreRequest $request, KpExam $exam, KpAssessmentService $service): RedirectResponse
    {
        foreach ($request->validated('scores') as $row) {
            $service->saveScore($request->user(), $exam->assignment, KpAssessmentComponent::findOrFail($row['component_id']), (float) $row['score'], $row['note'] ?? null);
        }

        return back()->with('status', 'Nilai sidang berhasil disimpan.');
    }

    public function submit(SubmitScoreRequest $request, KpExam $exam, KpAssessmentService $service): RedirectResponse
    {
        $rows = $request->validated('scores', []);
        $rows === []
            ? $service->submitScores($request->user(), $exam->assignment, 'penguji')
            : $service->saveAndSubmitScores($request->user(), $exam->assignment, 'penguji', $rows);

        $response = redirect()->route('examiner.assessments.show', $exam)
            ->with('status', 'Nilai sidang berhasil disubmit.');

        return (int) $exam->chair_lecturer_id === (int) ($request->user()->lecturer?->id ?: 0)
            ? $response->withFragment('berita-acara')
            : $response;
    }
}
