<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\KpQuestionnaire;
use App\Models\KpQuestionnaireQuestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class QuestionnaireQuestionController extends Controller
{
    public function store(Request $request, KpQuestionnaire $questionnaire): RedirectResponse
    {
        $questionnaire->questions()->create($this->validated($request));

        return back()->with('status', 'Pertanyaan kuisioner berhasil ditambahkan.');
    }

    public function update(Request $request, KpQuestionnaireQuestion $question): RedirectResponse
    {
        $question->update($this->validated($request));

        return back()->with('status', 'Pertanyaan kuisioner berhasil diperbarui.');
    }

    public function destroy(KpQuestionnaireQuestion $question): RedirectResponse
    {
        abort_if($question->answers()->exists(), 422, 'Pertanyaan yang sudah dijawab tidak dapat dihapus.');

        $question->delete();

        return back()->with('status', 'Pertanyaan kuisioner berhasil dihapus.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'section' => ['nullable', 'string', 'max:120'],
            'question_text' => ['required', 'string', 'max:1000'],
            'answer_type' => ['required', Rule::in(array_keys(KpQuestionnaireQuestion::TYPE_LABELS))],
            'options_text' => ['nullable', 'string', 'max:1000'],
            'is_required' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'status' => ['required', Rule::in(['aktif', 'nonaktif'])],
        ]);

        $data['options'] = $data['answer_type'] === KpQuestionnaireQuestion::TYPE_CHOICE
            ? collect(preg_split('/\r\n|\r|\n/', (string) ($data['options_text'] ?? '')))->map(fn ($option) => trim($option))->filter()->values()->all()
            : null;
        $data['is_required'] = $request->boolean('is_required');
        unset($data['options_text']);

        return $data;
    }
}
