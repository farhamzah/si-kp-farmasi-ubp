<?php

namespace App\Http\Controllers\Examiner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Assessment\SaveScoreRequest;
use App\Models\KpAssessmentComponent;
use App\Models\KpExam;
use App\Services\KpAssessmentService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AssessmentController extends Controller
{
    public function index(): View
    {
        $lecturer = request()->user()->lecturer;
        $exams = KpExam::with(['assignment.student.user', 'assignment.period', 'assignment.place', 'assignment.scores'])
            ->where(function (Builder $query) use ($lecturer): void {
                $query->forExaminer($lecturer?->id)
                    ->orWhere('chair_lecturer_id', $lecturer?->id);
            })
            ->latest('exam_date')->paginate(10);

        return view('examiner.assessments.index', ['exams' => $exams]);
    }

    public function show(KpExam $exam): View
    {
        $lecturerId = request()->user()->lecturer?->id;
        $isExaminer = $exam->hasExaminer($lecturerId);
        $isChair = (int) $exam->chair_lecturer_id === (int) ($lecturerId ?: 0);
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

    public function submit(KpExam $exam, KpAssessmentService $service): RedirectResponse
    {
        $service->submitScores(request()->user(), $exam->assignment, 'penguji');

        $response = redirect()->route('examiner.assessments.show', $exam)
            ->with('status', 'Nilai sidang berhasil disubmit.');

        return (int) $exam->chair_lecturer_id === (int) (request()->user()->lecturer?->id ?: 0)
            ? $response->withFragment('berita-acara')
            : $response;
    }
}
