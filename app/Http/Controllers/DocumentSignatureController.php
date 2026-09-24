<?php

namespace App\Http\Controllers;

use App\Models\KpDocumentSignature;
use App\Services\KpDocumentSignatureService;
use Illuminate\Http\Response;
use Illuminate\View\View;

class DocumentSignatureController extends Controller
{
    public function verify(string $code): View
    {
        $signature = KpDocumentSignature::query()
            ->with('revokedBy')
            ->where('verification_code', $code)
            ->first();

        return view('document-signatures.verify', compact('signature'));
    }

    public function qr(KpDocumentSignature $signature, KpDocumentSignatureService $service): Response
    {
        return response($service->qrSvg($signature), 200, ['Content-Type' => 'image/svg+xml']);
    }
}
