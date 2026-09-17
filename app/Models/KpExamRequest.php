<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KpExamRequest extends Model
{
    public const PAYMENT_PROOF_PENDING = 'menunggu_validasi';
    public const PAYMENT_PROOF_APPROVED = 'disetujui';
    public const PAYMENT_PROOF_REVISION = 'revisi';

    protected $fillable = [
        'kp_assignment_id',
        'requested_by',
        'status',
        'request_note',
        'payment_proof_url',
        'payment_proof_label',
        'payment_proof_original_filename',
        'payment_proof_path',
        'payment_proof_disk',
        'payment_proof_mime',
        'payment_proof_size',
        'payment_proof_status',
        'payment_proof_review_note',
        'payment_proof_reviewed_by',
        'payment_proof_reviewed_at',
        'submitted_at',
        'reviewed_by',
        'reviewed_at',
        'review_note',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'payment_proof_reviewed_at' => 'datetime',
        ];
    }

    public function assignment() { return $this->belongsTo(KpAssignment::class, 'kp_assignment_id'); }
    public function requestedBy() { return $this->belongsTo(User::class, 'requested_by'); }
    public function reviewedBy() { return $this->belongsTo(User::class, 'reviewed_by'); }
    public function paymentProofReviewedBy() { return $this->belongsTo(User::class, 'payment_proof_reviewed_by'); }
    public function exam() { return $this->hasOne(KpExam::class, 'kp_exam_request_id'); }
    public function logs() { return $this->hasMany(KpExamLog::class, 'kp_exam_request_id'); }

    public function statusLabel(): string
    {
        return [
            'draft' => 'Draft',
            'diajukan' => 'Diajukan',
            'disetujui' => 'Disetujui',
            'dijadwalkan' => 'Dijadwalkan',
            'revisi' => 'Revisi',
            'ditolak' => 'Ditolak',
            'dibatalkan' => 'Dibatalkan',
        ][$this->status] ?? ucfirst((string) $this->status);
    }

    public function statusBadgeClass(): string
    {
        return [
            'diajukan' => 'bg-amber-100 text-amber-800 ring-amber-200',
            'disetujui' => 'bg-emerald-100 text-emerald-800 ring-emerald-200',
            'dijadwalkan' => 'bg-emerald-100 text-emerald-800 ring-emerald-200',
            'revisi' => 'bg-blue-100 text-blue-800 ring-blue-200',
            'ditolak' => 'bg-red-100 text-red-800 ring-red-200',
            'dibatalkan' => 'bg-red-100 text-red-800 ring-red-200',
            'draft' => 'bg-slate-100 text-slate-700 ring-slate-200',
        ][$this->status] ?? 'bg-slate-100 text-slate-700 ring-slate-200';
    }

    public function canBeScheduled(): bool
    {
        return $this->status === 'disetujui';
    }

    public function isActive(): bool
    {
        return ! in_array($this->status, ['ditolak', 'dibatalkan'], true);
    }

    public function hasPaymentProof(): bool
    {
        return filled($this->payment_proof_url) || filled($this->payment_proof_path);
    }

    public function paymentProofLabel(): string
    {
        return $this->payment_proof_original_filename ?: ($this->payment_proof_label ?: ($this->payment_proof_url ? 'Link bukti pembayaran KP' : 'Belum tersedia'));
    }

    public function paymentProofStatus(): string
    {
        if (! $this->hasPaymentProof()) {
            return 'belum_upload';
        }

        return $this->payment_proof_status ?: self::PAYMENT_PROOF_PENDING;
    }

    public function paymentProofApproved(): bool
    {
        return $this->hasPaymentProof() && $this->paymentProofStatus() === self::PAYMENT_PROOF_APPROVED;
    }

    public function paymentProofStatusLabel(): string
    {
        return [
            'belum_upload' => 'Belum upload',
            self::PAYMENT_PROOF_PENDING => 'Menunggu validasi',
            self::PAYMENT_PROOF_APPROVED => 'Disetujui koordinator',
            self::PAYMENT_PROOF_REVISION => 'Perlu diganti',
        ][$this->paymentProofStatus()] ?? ucfirst((string) $this->paymentProofStatus());
    }

    public function paymentProofBadgeClass(): string
    {
        return [
            'belum_upload' => 'bg-slate-100 text-slate-700 ring-slate-200',
            self::PAYMENT_PROOF_PENDING => 'bg-amber-100 text-amber-800 ring-amber-200',
            self::PAYMENT_PROOF_APPROVED => 'bg-emerald-100 text-emerald-800 ring-emerald-200',
            self::PAYMENT_PROOF_REVISION => 'bg-blue-100 text-blue-800 ring-blue-200',
        ][$this->paymentProofStatus()] ?? 'bg-slate-100 text-slate-700 ring-slate-200';
    }

    public function canReplacePaymentProof(): bool
    {
        return $this->isActive();
    }
}
