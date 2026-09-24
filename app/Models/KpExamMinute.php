<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KpExamMinute extends Model
{
    protected $fillable = [
        'kp_exam_id', 'minutes_number', 'chair_lecturer_id', 'status', 'document_version',
        'last_rebuild_reason', 'rebuilt_by', 'rebuilt_at', 'result',
        'actual_start_time', 'actual_end_time', 'revision_deadline', 'notes', 'attendance',
        'verification_code', 'closed_by', 'closed_at', 'published_by', 'published_at', 'snapshot',
    ];

    protected function casts(): array
    {
        return [
            'revision_deadline' => 'date',
            'attendance' => 'array',
            'closed_at' => 'datetime',
            'published_at' => 'datetime',
            'rebuilt_at' => 'datetime',
            'snapshot' => 'array',
        ];
    }

    public function exam() { return $this->belongsTo(KpExam::class, 'kp_exam_id'); }
    public function chair() { return $this->belongsTo(Lecturer::class, 'chair_lecturer_id'); }
    public function closedBy() { return $this->belongsTo(User::class, 'closed_by'); }
    public function publishedBy() { return $this->belongsTo(User::class, 'published_by'); }
    public function signatures()
    {
        return $this->hasMany(KpDocumentSignature::class, 'document_id')
            ->where('document_type', KpDocumentSignature::DOCUMENT_MINUTE)
            ->orderBy('id');
    }
    public function activeSignatures()
    {
        return $this->signatures()->where('status', 'active')->where('version', $this->document_version);
    }

    public function statusLabel(): string
    {
        return ['menunggu_nilai' => 'Menunggu Nilai', 'siap_terbit' => 'Siap Diterbitkan', 'terbit' => 'Terbit'][$this->status] ?? ucfirst($this->status);
    }

    public function resultLabel(): string
    {
        return ['lulus' => 'Lulus', 'lulus_revisi' => 'Lulus dengan Revisi', 'belum_lulus' => 'Belum Lulus'][$this->result] ?? ucfirst($this->result);
    }
}
