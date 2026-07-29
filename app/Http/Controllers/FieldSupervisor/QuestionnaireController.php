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
            ->paginate(12)
            ->withQueryString();

        $questionnaires = KpQuestionnaire::query()
            ->active()
            ->where('audience', KpQuestionnaire::AUDIENCE_FIELD_SUPERVISOR)
            ->withCount('activeQuestions')
            ->latest()
            ->get();

        $submitted = KpQuestionnaireResponse::query()
            ->where('respondent_user_id', $request->user()->id)
            ->whereIn('kp_assignment_id', $assignments->pluck('id'))
            ->where('status', 'submitted')
            ->get()
            ->groupBy('kp_assignment_id');

        return view('field-supervisor.questionnaires.index', [
            'assignments' => $assignments,
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
            ->where('kp_assignment_id', $assignment->id)
            ->where('respondent_user_id', $request->user()->id)
            ->with('answers')
            ->first();

        return view('field-supervisor.questionnaires.show', [
            'assignment' => $assignment->load(['student.user', 'period', 'place', 'internalSupervisor.user']),
            'questionnaire' => $questionnaire->load('activeQuestions'),
            'response' => $response,
            'answerMap' => $response?->answerMap() ?? [],
        ]);
    }

    public function submit(Request $request, KpAssignment $assignment, KpQuestionnaire $questionnaire, KpQuestionnaireResponseService $service): RedirectResponse
    {
        abort_unless($request->user()->fieldSupervisor?->id === $assignment->field_supervisor_id, 403);
        abort_unless($questionnaire->audience === KpQuestionnaire::AUDIENCE_FIELD_SUPERVISOR, 403);

        $service->submit($request, $questionnaire, $request->user(), 'pembimbing_lapangan', $assignment);

        return redirect()->route('field-supervisor.questionnaires.index')->with('status', 'Kuisioner tempat KP berhasil dikirim.');
    }
}
