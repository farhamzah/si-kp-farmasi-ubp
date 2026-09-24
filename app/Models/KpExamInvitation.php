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
        'document_version',
        'last_rebuild_reason',
        'rebuilt_by',
        'rebuilt_at',
        'generated_by',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'generated_at' => 'datetime',
            'rebuilt_at' => 'datetime',
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

    public function signatures()
    {
        return $this->hasMany(KpDocumentSignature::class, 'document_id')
            ->where('document_type', KpDocumentSignature::DOCUMENT_INVITATION)
            ->orderBy('id');
    }

    public function activeSignatures()
    {
        return $this->signatures()->where('status', 'active')->where('version', $this->document_version);
    }

    public function statusLabel(): string
    {
        return $this->status === 'published' ? 'Terbit' : 'Draft';
    }
}
