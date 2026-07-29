<?php

namespace App\Services;

use App\Models\KpAssignment;
use App\Models\KpQuestionnaire;
use App\Models\KpQuestionnaireQuestion;
use App\Models\KpQuestionnaireResponse;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class KpQuestionnaireResponseService
{
    public function submit(Request $request, KpQuestionnaire $questionnaire, User $user, string $role, ?KpAssignment $assignment): KpQuestionnaireResponse
    {
        $questionnaire->loadMissing('activeQuestions');
        $answers = $request->input('answers', []);

        foreach ($questionnaire->activeQuestions as $question) {
            $value = $answers[$question->id] ?? null;
            if ($question->is_required && blank($value)) {
                throw ValidationException::withMessages([
                    'answers.'.$question->id => 'Pertanyaan wajib diisi.',
                ]);
            }

            $this->validateAnswer($question, $value);
        }

        return DB::transaction(function () use ($questionnaire, $user, $role, $assignment, $answers): KpQuestionnaireResponse {
            $response = KpQuestionnaireResponse::updateOrCreate(
                [
                    'kp_questionnaire_id' => $questionnaire->id,
                    'kp_assignment_id' => $assignment?->id,
                    'respondent_user_id' => $user->id,
                ],
                [
                    'respondent_role' => $role,
                    'status' => 'submitted',
                    'submitted_at' => now(),
                ],
            );

            foreach ($questionnaire->activeQuestions as $question) {
                $response->answers()->updateOrCreate(
                    ['kp_questionnaire_question_id' => $question->id],
                    ['answer_value' => $this->normalizeAnswer($answers[$question->id] ?? null)],
                );
            }

            return $response->load(['questionnaire.questions', 'answers.question', 'assignment.student.user', 'assignment.period', 'assignment.place']);
        });
    }

    private function validateAnswer(KpQuestionnaireQuestion $question, mixed $value): void
    {
        if (blank($value)) {
            return;
        }

        if ($question->answer_type === KpQuestionnaireQuestion::TYPE_SCALE && (! is_numeric($value) || (int) $value < 1 || (int) $value > 5)) {
            throw ValidationException::withMessages(['answers.'.$question->id => 'Skala harus bernilai 1 sampai 5.']);
        }

        if ($question->answer_type === KpQuestionnaireQuestion::TYPE_NUMBER && (! is_numeric($value) || (float) $value < 0)) {
            throw ValidationException::withMessages(['answers.'.$question->id => 'Jawaban harus berupa angka positif.']);
        }

        if ($question->answer_type === KpQuestionnaireQuestion::TYPE_CHOICE && ! in_array($value, $question->optionList(), true)) {
            throw ValidationException::withMessages(['answers.'.$question->id => 'Pilihan tidak valid.']);
        }
    }

    private function normalizeAnswer(mixed $value): ?string
    {
        return filled($value) ? trim((string) $value) : null;
    }
}
