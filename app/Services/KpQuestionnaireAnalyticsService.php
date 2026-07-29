<?php

namespace App\Services;

use App\Models\KpQuestionnaire;
use App\Models\KpQuestionnaireAnswer;
use App\Models\KpQuestionnaireQuestion;
use App\Models\KpQuestionnaireResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class KpQuestionnaireAnalyticsService
{
    public function summarize(?string $audience = null, ?string $term = null): array
    {
        $questionnaires = KpQuestionnaire::query()
            ->with(['questions' => fn ($query) => $query->orderBy('sort_order')->orderBy('id')])
            ->when($audience, fn (Builder $query) => $query->where('audience', $audience))
            ->orderBy('audience')
            ->orderBy('title')
            ->get();

        return $questionnaires
            ->map(fn (KpQuestionnaire $questionnaire): array => $this->summarizeQuestionnaire($questionnaire, $term))
            ->filter(fn (array $summary): bool => $summary['response_count'] > 0 || $summary['question_count'] > 0)
            ->values()
            ->all();
    }

    public function responseScore(KpQuestionnaireResponse $response): array
    {
        $response->loadMissing(['questionnaire.questions', 'answers.question']);
        $scaleAnswers = $response->answers
            ->filter(fn (KpQuestionnaireAnswer $answer): bool => $answer->question?->answer_type === KpQuestionnaireQuestion::TYPE_SCALE)
            ->map(fn (KpQuestionnaireAnswer $answer): ?float => $this->numericValue($answer->answer_value))
            ->filter(fn (?float $value): bool => $value !== null);

        $average = $scaleAnswers->isEmpty() ? null : round($scaleAnswers->avg(), 2);

        return [
            'average' => $average,
            'percentage' => $average === null ? null : round(($average / 5) * 100),
            'label' => $this->scoreLabel($average),
            'conclusion' => $this->conclusion($average),
        ];
    }

    private function summarizeQuestionnaire(KpQuestionnaire $questionnaire, ?string $term): array
    {
        $responses = $this->responsesFor($questionnaire, $term)->get();
        $scaleQuestions = $questionnaire->questions
            ->where('status', 'aktif')
            ->where('answer_type', KpQuestionnaireQuestion::TYPE_SCALE);
        $sectionSummaries = $this->sectionSummaries($responses, $scaleQuestions);
        $average = $this->averageFromSections($sectionSummaries);

        return [
            'questionnaire' => $questionnaire,
            'response_count' => $responses->count(),
            'question_count' => $questionnaire->questions->where('status', 'aktif')->count(),
            'scale_question_count' => $scaleQuestions->count(),
            'average' => $average,
            'percentage' => $average === null ? null : round(($average / 5) * 100),
            'label' => $this->scoreLabel($average),
            'conclusion' => $this->conclusion($average),
            'sections' => $sectionSummaries,
            'strongest' => $this->pickSection($sectionSummaries, 'desc'),
            'weakest' => $this->pickSection($sectionSummaries, 'asc'),
            'open_feedback' => $this->openFeedback($responses),
            'distribution' => $this->distribution($responses),
        ];
    }

    private function responsesFor(KpQuestionnaire $questionnaire, ?string $term): Builder
    {
        return KpQuestionnaireResponse::query()
            ->with(['answers.question', 'respondent', 'assignment.student.user', 'assignment.period', 'assignment.place', 'place', 'period'])
            ->where('kp_questionnaire_id', $questionnaire->id)
            ->where('status', 'submitted')
            ->when($term, function (Builder $query) use ($term): void {
                $like = '%'.$term.'%';
                $query->where(function (Builder $query) use ($like): void {
                    $query->whereHas('respondent', fn (Builder $user) => $user->where('name', 'like', $like)->orWhere('email', 'like', $like))
                        ->orWhereHas('assignment.student.user', fn (Builder $user) => $user->where('name', 'like', $like)->orWhere('email', 'like', $like))
                        ->orWhereHas('assignment.place', fn (Builder $place) => $place->where('name', 'like', $like))
                        ->orWhereHas('place', fn (Builder $place) => $place->where('name', 'like', $like))
                        ->orWhereHas('period', fn (Builder $period) => $period->where('name', 'like', $like));
                });
            });
    }

    private function sectionSummaries(Collection $responses, Collection $scaleQuestions): array
    {
        return $scaleQuestions
            ->groupBy(fn (KpQuestionnaireQuestion $question): string => $question->section ?: 'Umum')
            ->map(function (Collection $questions, string $section) use ($responses): array {
                $questionIds = $questions->pluck('id')->all();
                $values = $responses
                    ->flatMap(fn (KpQuestionnaireResponse $response): Collection => $response->answers)
                    ->filter(fn (KpQuestionnaireAnswer $answer): bool => in_array($answer->kp_questionnaire_question_id, $questionIds, true))
                    ->map(fn (KpQuestionnaireAnswer $answer): ?float => $this->numericValue($answer->answer_value))
                    ->filter(fn (?float $value): bool => $value !== null);

                $average = $values->isEmpty() ? null : round($values->avg(), 2);

                return [
                    'section' => $section,
                    'average' => $average,
                    'percentage' => $average === null ? null : round(($average / 5) * 100),
                    'answer_count' => $values->count(),
                    'question_count' => $questions->count(),
                    'label' => $this->scoreLabel($average),
                ];
            })
            ->values()
            ->all();
    }

    private function averageFromSections(array $sections): ?float
    {
        $averages = collect($sections)->pluck('average')->filter(fn ($value): bool => $value !== null);

        return $averages->isEmpty() ? null : round($averages->avg(), 2);
    }

    private function pickSection(array $sections, string $direction): ?array
    {
        $collection = collect($sections)->filter(fn (array $section): bool => $section['average'] !== null);

        if ($collection->isEmpty()) {
            return null;
        }

        return $direction === 'asc'
            ? $collection->sortBy('average')->first()
            : $collection->sortByDesc('average')->first();
    }

    private function openFeedback(Collection $responses): array
    {
        return $responses
            ->flatMap(fn (KpQuestionnaireResponse $response): Collection => $response->answers)
            ->filter(fn (KpQuestionnaireAnswer $answer): bool => in_array($answer->question?->answer_type, [KpQuestionnaireQuestion::TYPE_TEXTAREA, KpQuestionnaireQuestion::TYPE_CHOICE], true))
            ->map(fn (KpQuestionnaireAnswer $answer): array => [
                'question' => $answer->question?->question_text,
                'answer' => trim((string) $answer->answer_value),
            ])
            ->filter(fn (array $item): bool => $item['answer'] !== '')
            ->take(6)
            ->values()
            ->all();
    }

    private function distribution(Collection $responses): array
    {
        $distribution = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];

        $responses
            ->flatMap(fn (KpQuestionnaireResponse $response): Collection => $response->answers)
            ->filter(fn (KpQuestionnaireAnswer $answer): bool => $answer->question?->answer_type === KpQuestionnaireQuestion::TYPE_SCALE)
            ->each(function (KpQuestionnaireAnswer $answer) use (&$distribution): void {
                $value = $this->numericValue($answer->answer_value);

                if ($value !== null && isset($distribution[(int) $value])) {
                    $distribution[(int) $value]++;
                }
            });

        return $distribution;
    }

    private function numericValue(?string $value): ?float
    {
        if ($value === null || ! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    private function scoreLabel(?float $average): string
    {
        return match (true) {
            $average === null => 'Belum ada data',
            $average >= 4.5 => 'Sangat baik',
            $average >= 4.0 => 'Baik',
            $average >= 3.0 => 'Cukup',
            $average >= 2.0 => 'Perlu perbaikan',
            default => 'Prioritas perbaikan',
        };
    }

    private function conclusion(?float $average): string
    {
        return match (true) {
            $average === null => 'Belum ada respons yang bisa diolah. Dorong responden mengisi kuisioner terlebih dahulu.',
            $average >= 4.5 => 'Kepuasan sangat kuat. Pertahankan pola pelaksanaan dan jadikan praktik baik untuk periode berikutnya.',
            $average >= 4.0 => 'Kepuasan baik. Evaluasi aspek terlemah agar mutu pelaksanaan KP tetap meningkat.',
            $average >= 3.0 => 'Kepuasan cukup. Perlu tindak lanjut pada aspek dengan skor rendah sebelum periode berikutnya.',
            $average >= 2.0 => 'Ada masalah yang perlu diperbaiki. Koordinator sebaiknya meninjau komentar responden dan membuat rencana perbaikan.',
            default => 'Kondisi kritis. Perlu evaluasi khusus dan tindakan korektif sebelum pelaksanaan KP berikutnya.',
        };
    }
}
