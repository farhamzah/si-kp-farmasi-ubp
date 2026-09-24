<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KpDocumentSignature extends Model
{
    public const DOCUMENT_INVITATION = 'exam_invitation';
    public const DOCUMENT_MINUTE = 'exam_minute';

    protected $fillable = [
        'document_type', 'document_id', 'version', 'signer_key', 'signer_user_id',
        'signer_name', 'signer_identifier', 'role_label', 'verification_code',
        'status', 'signed_at', 'revoked_at', 'revoked_by', 'revocation_reason', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'signed_at' => 'datetime',
            'revoked_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function signerUser() { return $this->belongsTo(User::class, 'signer_user_id'); }
    public function revokedBy() { return $this->belongsTo(User::class, 'revoked_by'); }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function documentLabel(): string
    {
        return $this->document_type === self::DOCUMENT_INVITATION
            ? 'Surat Undangan Sidang KP'
            : 'Berita Acara Sidang KP';
    }
}
