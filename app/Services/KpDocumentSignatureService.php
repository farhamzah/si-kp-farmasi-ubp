<?php

namespace App\Services;

use App\Models\KpDocumentSignature;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class KpDocumentSignatureService
{
    public function __construct(private readonly QrCodeService $qrCodeService) {}

    public function replace(string $documentType, int $documentId, int $version, array $signers, User $actor, ?string $reason = null): Collection
    {
        return DB::transaction(function () use ($documentType, $documentId, $version, $signers, $actor, $reason): Collection {
            KpDocumentSignature::query()
                ->where('document_type', $documentType)
                ->where('document_id', $documentId)
                ->where('status', 'active')
                ->update([
                    'status' => 'revoked',
                    'revoked_at' => now(),
                    'revoked_by' => $actor->id,
                    'revocation_reason' => $reason ?: 'Dokumen diterbitkan ulang.',
                ]);

            return new Collection(collect($signers)->map(function (array $signer) use ($documentType, $documentId, $version): KpDocumentSignature {
                return KpDocumentSignature::create([
                    'document_type' => $documentType,
                    'document_id' => $documentId,
                    'version' => $version,
                    'signer_key' => $signer['key'],
                    'signer_user_id' => $signer['user_id'] ?? null,
                    'signer_name' => $signer['name'],
                    'signer_identifier' => $signer['identifier'] ?? null,
                    'role_label' => $signer['role'],
                    'verification_code' => Str::upper(Str::random(24)),
                    'status' => 'active',
                    'signed_at' => now(),
                    'metadata' => $signer['metadata'] ?? null,
                ]);
            })->all());
        });
    }

    public function verificationUrl(KpDocumentSignature $signature): string
    {
        return URL::route('document-signatures.verify', $signature->verification_code);
    }

    public function qrSvg(KpDocumentSignature $signature): string
    {
        return $this->qrCodeService->svg($this->verificationUrl($signature));
    }

    public function dataUri(KpDocumentSignature $signature): string
    {
        return $this->qrCodeService->dataUri($this->verificationUrl($signature));
    }
}
