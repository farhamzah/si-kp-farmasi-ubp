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
        return $this->svgFor($this->verificationUrl($signature));
    }

    public function dataUri(KpDocumentSignature $signature): string
    {
        return 'data:image/svg+xml;base64,'.base64_encode($this->qrSvg($signature));
    }

    private function svgFor(string $url): string
    {
        $payload = sha1($url);
        $size = 29; $cell = 6; $pad = 4; $rects = [];
        $finder = function (int $x, int $y) use (&$rects, $cell, $pad): void {
            for ($row = 0; $row < 7; $row++) for ($col = 0; $col < 7; $col++) {
                if ($row === 0 || $row === 6 || $col === 0 || $col === 6 || ($row >= 2 && $row <= 4 && $col >= 2 && $col <= 4)) {
                    $rects[] = '<rect x="'.(($x + $col + $pad) * $cell).'" y="'.(($y + $row + $pad) * $cell).'" width="'.$cell.'" height="'.$cell.'"/>';
                }
            }
        };
        $finder(0, 0); $finder($size - 7, 0); $finder(0, $size - 7);
        for ($row = 0; $row < $size; $row++) for ($col = 0; $col < $size; $col++) {
            if (($row < 8 && $col < 8) || ($row < 8 && $col > $size - 9) || ($row > $size - 9 && $col < 8)) continue;
            if (((hexdec($payload[($row * $size + $col) % strlen($payload)]) + $row + ($col * 3)) % 5) < 2) {
                $rects[] = '<rect x="'.(($col + $pad) * $cell).'" y="'.(($row + $pad) * $cell).'" width="'.$cell.'" height="'.$cell.'"/>';
            }
        }
        $svgSize = ($size + ($pad * 2)) * $cell;

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 '.$svgSize.' '.$svgSize.'"><rect width="100%" height="100%" fill="#fff"/><g fill="#0f172a">'.implode('', $rects).'</g></svg>';
    }
}
