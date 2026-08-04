<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Http\Requests\Management\FinalizeScoreRequest;
use App\Http\Requests\Management\UnlockScoreRequest;
use App\Models\KpAssignment;
use App\Models\KpFinalScore;
use App\Models\KpPeriod;
use App\Models\KpScoreVisibilityOverride;
use App\Services\KpAssessmentService;
use App\Support\KpScoreCalculator;
use App\Support\StudentScoreVisibility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ScoreMonitoringController extends Controller
{
    public function index(Request $request): View
    {
        $periods = KpPeriod::latest()->get();
        $selectedPeriod = $request->filled('period')
            ? $periods->firstWhere('id', (int) $request->period)
            : $periods->first();

        $assignments = KpAssignment::with(['period', 'student.user', 'place', 'internalSupervisor.user', 'fieldSupervisor.user', 'exam.examiner.user', 'exam.examiners.user', 'scores.component', 'finalScore'])
            ->when($request->filled('period'), fn ($q) => $q->where('kp_period_id', $request->period))
            ->when($request->filled('q'), fn ($q) => $q->whereHas('student', fn ($s) => $s->where('nim', 'like', "%{$request->q}%")->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$request->q}%"))))
            ->latest()
            ->paginate(10)->withQueryString();

        return view('management.scores.index', [
            'assignments' => $assignments,
            'periods' => $periods,
            'selectedPeriod' => $selectedPeriod,
            'filters' => $request->only(['period', 'q']),
        ]);
    }

    public function show(KpAssignment $assignment, KpAssessmentService $service, KpScoreCalculator $calculator, StudentScoreVisibility $visibility): View
    {
        $assignment->loadMissing('period');
        $service->ensureDefaultComponents($assignment->period, request()->user());
        $assignment->refresh();
        $assignmentForView = $assignment->load(['period.assessmentComponents', 'student.user', 'place', 'internalSupervisor.user', 'fieldSupervisor.user', 'exam.examiner.user', 'exam.examiners.user', 'scores.component', 'scores.assessor', 'logbooks', 'finalScore', 'finalReport']);

        return view('management.scores.show', [
            'assignment' => $assignmentForView,
            'breakdown' => $calculator->breakdown($assignmentForView),
            'scoreVisibility' => $visibility->resolve($assignmentForView),
        ]);
    }

    public function updatePeriodVisibility(Request $request, KpPeriod $period): RedirectResponse
    {
        $validated = $request->validate([
            'score_visible_to_students' => ['required', 'boolean'],
        ]);

        $period->update([
            'score_visible_to_students' => (bool) $validated['score_visible_to_students'],
            'updated_by' => $request->user()?->id,
        ]);

        return back()->with('status', $period->score_visible_to_students
            ? 'Akses nilai mahasiswa periode ini dibuka.'
            : 'Akses nilai mahasiswa periode ini ditutup.');
    }

    public function updateVisibilityOverride(Request $request, KpAssignment $assignment): RedirectResponse
    {
        $validated = $request->validate([
            'visibility_override' => ['required', Rule::in(['inherit', 'allow', 'deny'])],
            'visibility_note' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($validated['visibility_override'] === 'inherit') {
            KpScoreVisibilityOverride::query()
                ->where('kp_period_id', $assignment->kp_period_id)
                ->where('student_id', $assignment->student_id)
                ->delete();

            return back()->with('status', 'Akses nilai mahasiswa kembali mengikuti pengaturan periode.');
        }

        $override = KpScoreVisibilityOverride::firstOrNew([
            'kp_period_id' => $assignment->kp_period_id,
            'student_id' => $assignment->student_id,
        ]);

        if (! $override->exists) {
            $override->created_by = $request->user()?->id;
        }

        $override->fill([
            'can_view' => $validated['visibility_override'] === 'allow',
            'note' => $validated['visibility_note'] ?? null,
            'updated_by' => $request->user()?->id,
        ])->save();

        return back()->with('status', $override->can_view
            ? 'Mahasiswa ini boleh melihat nilai secara khusus.'
            : 'Akses nilai mahasiswa ini ditahan.');
    }

    public function calculate(KpAssignment $assignment, KpAssessmentService $service): RedirectResponse
    {
        $service->calculateFinalScore($assignment);
        return back()->with('status', 'Nilai akhir berhasil dihitung.');
    }

    public function override(Request $request, KpAssignment $assignment, KpAssessmentService $service): RedirectResponse
    {
        $validated = $request->validate([
            'attendance_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'attendance_note' => ['nullable', 'string', 'max:2000'],
            'scores' => ['nullable', 'array'],
            'scores.*.component_id' => ['required', Rule::exists('kp_assessment_components', 'id')],
            'scores.*.score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'scores.*.note' => ['nullable', 'string', 'max:2000'],
        ]);

        $rows = collect($validated['scores'] ?? [])
            ->filter(fn (array $row) => $row['score'] !== null && $row['score'] !== '')
            ->values()
            ->all();

        $service->overrideScores(
            $request->user(),
            $assignment,
            $rows,
            $request->filled('attendance_score') ? (float) $validated['attendance_score'] : null,
            $validated['attendance_note'] ?? null
        );

        $service->calculateFinalScore($assignment->fresh());

        return back()->with('status', 'Koreksi nilai berhasil disimpan dan nilai akhir dihitung ulang.');
    }

    public function finalize(FinalizeScoreRequest $request, KpAssignment $assignment, KpAssessmentService $service): RedirectResponse
    {
        $service->finalizeScore($request->user(), $assignment, $request->note);
        return back()->with('status', 'Nilai akhir berhasil dikunci.');
    }

    public function publish(KpFinalScore $finalScore, KpAssessmentService $service): RedirectResponse
    {
        $service->publishScore(request()->user(), $finalScore);
        return back()->with('status', 'Nilai akhir berhasil dipublish.');
    }

    public function unlock(UnlockScoreRequest $request, KpFinalScore $finalScore, KpAssessmentService $service): RedirectResponse
    {
        $service->unlockScore($request->user(), $finalScore, $request->reason);
        return back()->with('status', 'Nilai akhir berhasil dibuka kembali.');
    }
}
