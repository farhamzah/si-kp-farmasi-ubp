<?php

namespace App\Http\Controllers\Student;

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
        $assignment = $this->activeAssignment($request);
        $questionnaires = KpQuestionnaire::query()
            ->active()
            ->where('audience', KpQuestionnaire::AUDIENCE_STUDENT)
            ->where(function ($query) use ($assignment): void {
                $query->whereNull('kp_period_id')
                    ->when($assignment, fn ($query) => $query->orWhere('kp_period_id', $assignment->kp_period_id));
            })
            ->withCount('activeQuestions')
            ->with(['responses' => fn ($query) => $query->where('respondent_user_id', $request->user()->id)])
            ->latest()
            ->get();

        return view('student.questionnaires.index', [
            'assignment' => $assignment,
            'questionnaires' => $questionnaires,
        ]);
    }

    public function show(Request $request, KpQuestionnaire $questionnaire): View
    {
        $assignment = $this->activeAssignmentOrFail($request);
        abort_unless($questionnaire->audience === KpQuestionnaire::AUDIENCE_STUDENT, 403);
        abort_unless($questionnaire->status === 'aktif' && (! $questionnaire->kp_period_id || $questionnaire->kp_period_id === $assignment->kp_period_id), 403);

        $response = KpQuestionnaireResponse::query()
            ->where('kp_questionnaire_id', $questionnaire->id)
            ->where('kp_assignment_id', $assignment->id)
            ->where('respondent_user_id', $request->user()->id)
            ->with('answers')
            ->first();

        return view('student.questionnaires.show', [
            'assignment' => $assignment->load(['period', 'place', 'internalSupervisor.user', 'fieldSupervisor.user']),
            'questionnaire' => $questionnaire->load('activeQuestions'),
            'response' => $response,
            'answerMap' => $response?->answerMap() ?? [],
        ]);
    }

    public function submit(Request $request, KpQuestionnaire $questionnaire, KpQuestionnaireResponseService $service): RedirectResponse
    {
        $assignment = $this->activeAssignmentOrFail($request);
        abort_unless($questionnaire->audience === KpQuestionnaire::AUDIENCE_STUDENT, 403);

        $service->submit($request, $questionnaire, $request->user(), 'mahasiswa', $assignment);

        return redirect()->route('student.questionnaires.index')->with('status', 'Kuisioner KP berhasil dikirim.');
    }

    private function activeAssignment(Request $request): ?KpAssignment
    {
        $student = $request->user()->student;

        if (! $student) {
            return null;
        }

        return $student->assignments()
            ->with(['period', 'place', 'internalSupervisor.user', 'fieldSupervisor.user'])
            ->whereIn('status', ['aktif', 'berjalan', 'selesai'])
            ->latest('assigned_at')
            ->first();
    }

    private function activeAssignmentOrFail(Request $request): KpAssignment
    {
        $assignment = $this->activeAssignment($request);
        abort_unless($assignment, 403);

        return $assignment;
    }
}
