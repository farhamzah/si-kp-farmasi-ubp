<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class KpQuestionnaire extends Model
{
    public const AUDIENCE_STUDENT = 'student';
    public const AUDIENCE_FIELD_SUPERVISOR = 'field_supervisor';

    public const AUDIENCE_LABELS = [
        self::AUDIENCE_STUDENT => 'Mahasiswa',
        self::AUDIENCE_FIELD_SUPERVISOR => 'Pembimbing Lapangan / Tempat KP',
    ];

    protected $fillable = [
        'kp_period_id',
        'audience',
        'title',
        'description',
        'status',
        'starts_at',
        'ends_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function period()
    {
        return $this->belongsTo(KpPeriod::class, 'kp_period_id');
    }

    public function questions()
    {
        return $this->hasMany(KpQuestionnaireQuestion::class, 'kp_questionnaire_id')->orderBy('sort_order')->orderBy('id');
    }

    public function activeQuestions()
    {
        return $this->questions()->where('status', 'aktif');
    }

    public function responses()
    {
        return $this->hasMany(KpQuestionnaireResponse::class, 'kp_questionnaire_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'aktif')
            ->where(function (Builder $query): void {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function (Builder $query): void {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            });
    }

    public function audienceLabel(): string
    {
        return self::AUDIENCE_LABELS[$this->audience] ?? $this->audience;
    }

    public function statusLabel(): string
    {
        return $this->status === 'aktif' ? 'Aktif' : 'Nonaktif';
    }

    public function statusBadgeClass(): string
    {
        return $this->status === 'aktif' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600';
    }
}
