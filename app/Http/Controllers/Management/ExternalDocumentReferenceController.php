<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\KpExternalDocumentReference;
use App\Services\Integration\KpExternalDocumentReferenceService;
use App\Services\Integration\KpTuDocumentPayloadPreviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExternalDocumentReferenceController extends Controller
{
    public function index(Request $request, KpTuDocumentPayloadPreviewService $tuPayloadService, KpExternalDocumentReferenceService $referenceService): View
    {
        $filters = $this->filters($request);
        $tuPayload = $tuPayloadService->preview($filters['assignment_id'], $filters['document_type'], $filters['limit']);
        $preview = $referenceService->previewFromTuPayload($tuPayload);

        $references = KpExternalDocumentReference::query()
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('management.integration.external-document-references.index', [
            'references' => $references,
            'preview' => $preview,
            'documentTypes' => KpTuDocumentPayloadPreviewService::DOCUMENTS,
            'filters' => $filters,
        ]);
    }

    public function storeDrafts(Request $request, KpTuDocumentPayloadPreviewService $tuPayloadService, KpExternalDocumentReferenceService $referenceService): RedirectResponse
    {
        $filters = $this->filters($request);
        $tuPayload = $tuPayloadService->preview($filters['assignment_id'], $filters['document_type'], $filters['limit']);
        $preview = $referenceService->previewFromTuPayload($tuPayload);
        $result = $referenceService->persistLocalDrafts($preview, $request->user()?->id);

        return redirect()
            ->route('management.integration.external-document-references.index', $request->only(['assignment_id', 'document_type', 'limit']))
            ->with('status', "Draft referensi lokal dibuat/diperbarui: {$result['created']} baru, {$result['updated']} diperbarui. Tidak ada request ke TU.");
    }

    private function filters(Request $request): array
    {
        $documentType = $request->string('document_type')->toString() ?: null;

        if ($documentType && ! array_key_exists($documentType, KpTuDocumentPayloadPreviewService::DOCUMENTS)) {
            $documentType = null;
        }

        return [
            'assignment_id' => $request->integer('assignment_id') ?: null,
            'document_type' => $documentType,
            'limit' => max(1, min($request->integer('limit') ?: 1, 25)),
        ];
    }
}
