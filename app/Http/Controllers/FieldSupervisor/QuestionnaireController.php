<?php

namespace App\Http\Controllers\FieldSupervisor;

use App\Http\Controllers\Controller;
use App\Models\KpAssignment;
use App\Models\KpQuestionnaire;
use App\Models\KpQuestionnaireResponse;
use App\Services\KpQuestionnaireDefaultService;
use App\Services\KpQuestionnaireResponseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuestionnaireController extends Controller
{
    public function index(Request $request, KpQuestionnaireDefaultService $defaults): View
    {
        $defaults->ensureDefaults($request->user());
        $fieldSupervisorId = $request->user()->fieldSupervisor?->id ?: 0;

        $assignments = KpAssignment::query()
            ->with(['student.user', 'period', 'place'])
            ->where('field_supervisor_id', $fieldSupervisorId)
            ->whereIn('status', ['aktif', 'berjalan', 'selesai'])
            ->latest('assigned_at')
            ->get();

        $contexts = $assignments
            ->filter(fn (KpAssignment $assignment): bool => $assignment->kp_place_id !== null && $assignment->kp_period_id !== null)
            ->groupBy(fn (KpAssignment $assignment): string => $assignment->kp_period_id.'-'.$assignment->kp_place_id)
            ->map(function ($group): array {
                $assignment = $group->sortByDesc(fn (KpAssignment $assignment): int => $assignment->assigned_at?->timestamp ?? 0)->first();

                return [
                    'key' => $assignment->kp_period_id.'-'.$assignment->kp_place_id,
                    'assignment' => $assignment,
                    'place' => $assignment->place,
                    'period' => $assignment->period,
                    'student_count' => $group->pluck('student_id')->unique()->count(),
                    'students' => $group
                        ->map(fn (KpAssignment $assignment): ?string => $assignment->student?->user?->name)
                        ->filter()
                        ->unique()
                        ->values(),
                ];
            })
            ->values();

        $questionnaires = KpQuestionnaire::query()
            ->active()
            ->where('audience', KpQuestionnaire::AUDIENCE_FIELD_SUPERVISOR)
            ->withCount('activeQuestions')
            ->latest()
            ->get();

        $submitted = KpQuestionnaireResponse::query()
            ->with('assignment')
            ->where('respondent_user_id', $request->user()->id)
            ->whereIn('kp_questionnaire_id', $questionnaires->pluck('id'))
            ->where('status', 'submitted')
            ->get()
            ->groupBy(fn (KpQuestionnaireResponse $response): string => ($response->kp_period_id ?? $response->assignment?->kp_period_id).'-'.($response->kp_place_id ?? $response->assignment?->kp_place_id));

        return view('field-supervisor.questionnaires.index', [
            'contexts' => $contexts,
            'questionnaires' => $questionnaires,
            'submitted' => $submitted,
        ]);
    }

    public function show(Request $request, KpAssignment $assignment, KpQuestionnaire $questionnaire): View
    {
        abort_unless($request->user()->fieldSupervisor?->id === $assignment->field_supervisor_id, 403);
        abort_unless($questionnaire->audience === KpQuestionnaire::AUDIENCE_FIELD_SUPERVISOR, 403);
        abort_unless($questionnaire->status === 'aktif' && (! $questionnaire->kp_period_id || $questionnaire->kp_period_id === $assignment->kp_period_id), 403);

        $response = KpQuestionnaireResponse::query()
            ->where('kp_questionnaire_id', $questionnaire->id)
            ->where('respondent_user_id', $request->user()->id)
            ->where(function ($query) use ($assignment): void {
                $query->where(function ($query) use ($assignment): void {
                    $query->where('kp_place_id', $assignment->kp_place_id)
                        ->where('kp_period_id', $assignment->kp_period_id);
                })->orWhere('kp_assignment_id', $assignment->id);
            })
            ->with('answers')
            ->first();

        $studentCount = KpAssignment::query()
            ->where('field_supervisor_id', $assignment->field_supervisor_id)
            ->where('kp_place_id', $assignment->kp_place_id)
            ->where('kp_period_id', $assignment->kp_period_id)
            ->whereIn('status', ['aktif', 'berjalan', 'selesai'])
            ->distinct('student_id')
            ->count('student_id');

        return view('field-supervisor.questionnaires.show', [
            'assignment' => $assignment->load(['student.user', 'period', 'place', 'internalSupervisor.user']),
            'questionnaire' => $questionnaire->load('activeQuestions'),
            'response' => $response,
            'answerMap' => $response?->answerMap() ?? [],
            'studentCount' => $studentCount,
        ]);
    }

    public function submit(Request $request, KpAssignment $assignment, KpQuestionnaire $questionnaire, KpQuestionnaireResponseService $service): RedirectResponse
    {
        abort_unless($request->user()->fieldSupervisor?->id === $assignment->field_supervisor_id, 403);
        abort_unless($questionnaire->audience === KpQuestionnaire::AUDIENCE_FIELD_SUPERVISOR, 403);
        abort_unless($questionnaire->status === 'aktif' && (! $questionnaire->kp_period_id || $questionnaire->kp_period_id === $assignment->kp_period_id), 403);

        $service->submit($request, $questionnaire, $request->user(), 'pembimbing_lapangan', $assignment, true);

        return redirect()->route('field-supervisor.questionnaires.index')->with('status', 'Kuisioner tempat KP berhasil dikirim.');
    }
}
