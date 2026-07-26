<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KpReportGuidanceLog extends Model
{
    public const REVIEWER_INTERNAL = 'internal';
    public const REVIEWER_FIELD = 'field';

    protected $fillable = [
        'kp_assignment_id',
        'reviewer_type',
        'guidance_date',
        'topic',
        'student_note',
        'document_url',
        'document_label',
        'status',
        'submitted_at',
        'validated_by',
        'validated_at',
        'validation_note',
    ];

    protected $casts = [
        'guidance_date' => 'date',
        'submitted_at' => 'datetime',
        'validated_at' => 'datetime',
    ];

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(KpAssignment::class, 'kp_assignment_id');
    }

    public function validatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function statusLabel(): string
    {
        return [
            'draft' => 'Draft',
            'menunggu_validasi' => 'Menunggu Validasi',
            'disetujui' => 'Disetujui',
            'revisi' => 'Revisi',
            'ditolak' => 'Ditolak',
        ][$this->status] ?? ucfirst((string) $this->status);
    }

    public function statusBadgeClass(): string
    {
        return [
            'draft' => 'bg-slate-100 text-slate-700 ring-slate-200',
            'menunggu_validasi' => 'bg-amber-100 text-amber-800 ring-amber-200',
            'disetujui' => 'bg-emerald-100 text-emerald-800 ring-emerald-200',
            'revisi' => 'bg-blue-100 text-blue-800 ring-blue-200',
            'ditolak' => 'bg-red-100 text-red-800 ring-red-200',
        ][$this->status] ?? 'bg-slate-100 text-slate-700 ring-slate-200';
    }

    public function reviewerType(): string
    {
        return $this->reviewer_type ?: self::REVIEWER_INTERNAL;
    }

    public function isForInternalSupervisor(): bool
    {
        return $this->reviewerType() === self::REVIEWER_INTERNAL;
    }

    public function isForFieldSupervisor(): bool
    {
        return $this->reviewerType() === self::REVIEWER_FIELD;
    }

    public function reviewerTypeLabel(): string
    {
        return [
            self::REVIEWER_INTERNAL => 'Pembimbing Dalam',
            self::REVIEWER_FIELD => 'Pembimbing Lapangan',
        ][$this->reviewerType()] ?? 'Pembimbing Dalam';
    }
}
