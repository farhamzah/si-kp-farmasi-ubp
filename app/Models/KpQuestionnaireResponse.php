<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KpQuestionnaireResponse extends Model
{
    protected $fillable = [
        'kp_questionnaire_id',
        'kp_assignment_id',
        'respondent_user_id',
        'respondent_role',
        'status',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
        ];
    }

    public function questionnaire()
    {
        return $this->belongsTo(KpQuestionnaire::class, 'kp_questionnaire_id');
    }

    public function assignment()
    {
        return $this->belongsTo(KpAssignment::class, 'kp_assignment_id');
    }

    public function respondent()
    {
        return $this->belongsTo(User::class, 'respondent_user_id');
    }

    public function answers()
    {
        return $this->hasMany(KpQuestionnaireAnswer::class, 'kp_questionnaire_response_id');
    }

    public function answerMap(): array
    {
        $this->loadMissing('answers');

        return $this->answers
            ->mapWithKeys(fn (KpQuestionnaireAnswer $answer): array => [$answer->kp_questionnaire_question_id => $answer->answer_value])
            ->all();
    }

    public function isSubmitted(): bool
    {
        return $this->status === 'submitted';
    }
}
