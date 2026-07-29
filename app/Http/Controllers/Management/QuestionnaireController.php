<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\KpPeriod;
use App\Models\KpQuestionnaire;
use App\Models\KpQuestionnaireQuestion;
use App\Services\KpQuestionnaireDefaultService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class QuestionnaireController extends Controller
{
    public function index(Request $request, KpQuestionnaireDefaultService $defaults): View
    {
        $defaults->ensureDefaults($request->user());

        $selectedId = $request->integer('questionnaire');
        $questionnaires = KpQuestionnaire::query()
            ->with(['period', 'questions', 'responses'])
            ->when($request->filled('audience'), fn ($query) => $query->where('audience', $request->audience))
            ->orderBy('audience')
            ->orderByDesc('id')
            ->get();

        $selected = $questionnaires->firstWhere('id', $selectedId) ?? $questionnaires->first();

        return view('management.questionnaires.index', [
            'questionnaires' => $questionnaires,
            'selected' => $selected,
            'periods' => KpPeriod::latest()->get(),
            'audiences' => KpQuestionnaire::AUDIENCE_LABELS,
            'types' => KpQuestionnaireQuestion::TYPE_LABELS,
            'filters' => $request->only(['audience', 'questionnaire']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedQuestionnaire($request);
        $data['created_by'] = $request->user()->id;

        $questionnaire = KpQuestionnaire::create($data);

        return redirect()->route('management.questionnaires.index', ['questionnaire' => $questionnaire->id])->with('status', 'Kuisioner KP berhasil dibuat.');
    }

    public function update(Request $request, KpQuestionnaire $questionnaire): RedirectResponse
    {
        $data = $this->validatedQuestionnaire($request);
        $data['updated_by'] = $request->user()->id;
        $questionnaire->update($data);

        return back()->with('status', 'Kuisioner KP berhasil diperbarui.');
    }

    public function destroy(KpQuestionnaire $questionnaire): RedirectResponse
    {
        abort_if($questionnaire->responses()->exists(), 422, 'Kuisioner yang sudah memiliki jawaban tidak dapat dihapus.');

        $questionnaire->delete();

        return redirect()->route('management.questionnaires.index')->with('status', 'Kuisioner KP berhasil dihapus.');
    }

    private function validatedQuestionnaire(Request $request): array
    {
        return $request->validate([
            'kp_period_id' => ['nullable', 'integer', 'exists:kp_periods,id'],
            'audience' => ['required', Rule::in(array_keys(KpQuestionnaire::AUDIENCE_LABELS))],
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in(['aktif', 'nonaktif'])],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);
    }
}
