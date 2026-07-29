<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\KpQuestionnaire;
use App\Models\KpQuestionnaireResponse;
use App\Services\KpQuestionnaireAnalyticsService;
use App\Services\KpQuestionnaireDefaultService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuestionnaireResultController extends Controller
{
    public function index(Request $request, KpQuestionnaireDefaultService $defaults, KpQuestionnaireAnalyticsService $analytics): View
    {
        $defaults->ensureDefaults($request->user());

        $responses = KpQuestionnaireResponse::query()
            ->with(['questionnaire', 'respondent', 'assignment.student.user', 'assignment.period', 'assignment.place'])
            ->where('status', 'submitted')
            ->when($request->filled('audience'), fn ($query) => $query->whereHas('questionnaire', fn ($questionnaire) => $questionnaire->where('audience', $request->audience)))
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = '%'.$request->q.'%';
                $query->where(function ($query) use ($term): void {
                    $query->whereHas('respondent', fn ($user) => $user->where('name', 'like', $term)->orWhere('email', 'like', $term))
                        ->orWhereHas('assignment.student.user', fn ($user) => $user->where('name', 'like', $term)->orWhere('email', 'like', $term))
                        ->orWhereHas('assignment.place', fn ($place) => $place->where('name', 'like', $term));
                });
            })
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

    public function show(KpQuestionnaireResponse $response, KpQuestionnaireAnalyticsService $analytics): View
    {
        return view('management.questionnaires.result-show', [
            'response' => $response->load(['questionnaire.questions', 'answers.question', 'respondent', 'assignment.student.user', 'assignment.period', 'assignment.place']),
            'answerMap' => $response->answerMap(),
            'score' => $analytics->responseScore($response),
        ]);
    }
}
