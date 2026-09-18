<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KpPostExamReport extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_WAITING = 'menunggu_validasi';

    public const STATUS_REVISION = 'revisi';

    public const STATUS_APPROVED = 'disetujui';

    protected $fillable = [
        'kp_assignment_id', 'version', 'status', 'original_filename', 'file_path', 'file_disk',
        'file_mime', 'file_size', 'document_url', 'document_label', 'submitted_at', 'reviewed_by',
        'reviewed_at', 'review_note', 'approved_at',
    ];

    protected function casts(): array
    {
        return ['submitted_at' => 'datetime', 'reviewed_at' => 'datetime', 'approved_at' => 'datetime'];
    }

    public function assignment()
    {
        return $this->belongsTo(KpAssignment::class, 'kp_assignment_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function hasDocument(): bool
    {
        return filled($this->file_path) || filled($this->document_url);
    }

    public function canBeEditedByStudent(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_REVISION], true);
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED && $this->hasDocument();
    }

    public function statusLabel(): string
    {
        return [
            self::STATUS_DRAFT => 'Belum Dikirim',
            self::STATUS_WAITING => 'Menunggu Validasi Koordinator',
            self::STATUS_REVISION => 'Perlu Perbaikan',
            self::STATUS_APPROVED => 'Disetujui Koordinator',
        ][$this->status] ?? ucfirst($this->status);
    }

    public function statusBadgeClass(): string
    {
        return [
            self::STATUS_DRAFT => 'bg-slate-100 text-slate-700 ring-slate-200',
            self::STATUS_WAITING => 'bg-amber-100 text-amber-800 ring-amber-200',
            self::STATUS_REVISION => 'bg-blue-100 text-blue-800 ring-blue-200',
            self::STATUS_APPROVED => 'bg-emerald-100 text-emerald-800 ring-emerald-200',
        ][$this->status] ?? 'bg-slate-100 text-slate-700 ring-slate-200';
    }

    public function documentLabel(): string
    {
        return $this->document_label ?: $this->original_filename ?: 'Dokumen Final Pascasidang';
    }
}
