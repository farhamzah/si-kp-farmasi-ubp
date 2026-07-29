<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KpQuestionnaireQuestion extends Model
{
    public const TYPE_SCALE = 'scale';
    public const TYPE_CHOICE = 'choice';
    public const TYPE_NUMBER = 'number';
    public const TYPE_TEXTAREA = 'textarea';

    public const TYPE_LABELS = [
        self::TYPE_SCALE => 'Skala 1-5',
        self::TYPE_CHOICE => 'Pilihan',
        self::TYPE_NUMBER => 'Angka',
        self::TYPE_TEXTAREA => 'Jawaban panjang',
    ];

    protected $fillable = [
        'kp_questionnaire_id',
        'section',
        'question_text',
        'answer_type',
        'options',
        'is_required',
        'sort_order',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'is_required' => 'boolean',
        ];
    }

    public function questionnaire()
    {
        return $this->belongsTo(KpQuestionnaire::class, 'kp_questionnaire_id');
    }

    public function answers()
    {
        return $this->hasMany(KpQuestionnaireAnswer::class, 'kp_questionnaire_question_id');
    }

    public function typeLabel(): string
    {
        return self::TYPE_LABELS[$this->answer_type] ?? $this->answer_type;
    }

    public function optionList(): array
    {
        return collect($this->options ?? [])->filter()->values()->all();
    }

    public function statusBadgeClass(): string
    {
        return $this->status === 'aktif' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600';
    }
}
