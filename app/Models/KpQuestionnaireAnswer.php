<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KpQuestionnaireAnswer extends Model
{
    protected $fillable = [
        'kp_questionnaire_response_id',
        'kp_questionnaire_question_id',
        'answer_value',
    ];

    public function response()
    {
        return $this->belongsTo(KpQuestionnaireResponse::class, 'kp_questionnaire_response_id');
    }

    public function question()
    {
        return $this->belongsTo(KpQuestionnaireQuestion::class, 'kp_questionnaire_question_id');
    }
}
