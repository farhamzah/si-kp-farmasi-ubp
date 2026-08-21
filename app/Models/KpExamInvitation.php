<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KpExamInvitation extends Model
{
    protected $fillable = [
        'kp_exam_id',
        'letter_number',
        'verification_code',
        'coordinator_name',
        'coordinator_nuptk',
        'head_program_name',
        'head_program_nuptk',
        'dean_name',
        'dean_nuptk',
        'status',
        'generated_by',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'generated_at' => 'datetime',
        ];
    }

    public function exam()
    {
        return $this->belongsTo(KpExam::class, 'kp_exam_id');
    }

    public function generatedBy()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function statusLabel(): string
    {
        return $this->status === 'published' ? 'Terbit' : 'Draft';
    }
}
