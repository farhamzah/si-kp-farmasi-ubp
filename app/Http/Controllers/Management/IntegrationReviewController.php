<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Services\Integration\KpSafaPublicInfoPreviewService;
use App\Services\Integration\KpTuDocumentPayloadPreviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IntegrationReviewController extends Controller
{
    public function tuPayloadPreview(Request $request, KpTuDocumentPayloadPreviewService $service): View
    {
        $payload = $this->tuPayload($request, $service);

        return view('management.integration.tu-payload-preview', [
            'payload' => $payload,
            'documentTypes' => KpTuDocumentPayloadPreviewService::DOCUMENTS,
            'filters' => $payload['filters'],
        ]);
    }

    public function tuPayloadPreviewJson(Request $request, KpTuDocumentPayloadPreviewService $service): JsonResponse
    {
        return response()->json($this->sanitizeForReview($this->tuPayload($request, $service)));
    }

    public function safaPublicInfoPreview(Request $request, KpSafaPublicInfoPreviewService $service): View
    {
        $payload = $this->safaPayload($request, $service);

        return view('management.integration.safa-public-info-preview', [
            'payload' => $payload,
            'periodId' => $request->integer('period_id') ?: null,
        ]);
    }

    public function safaPublicInfoPreviewJson(Request $request, KpSafaPublicInfoPreviewService $service): JsonResponse
    {
        return response()->json($this->sanitizeForReview($this->safaPayload($request, $service)));
    }

    private function tuPayload(Request $request, KpTuDocumentPayloadPreviewService $service): array
    {
        $documentType = $request->string('document_type')->toString() ?: null;

        if ($documentType && ! array_key_exists($documentType, KpTuDocumentPayloadPreviewService::DOCUMENTS)) {
            $documentType = null;
        }

        return $service->preview(
            $request->integer('assignment_id') ?: null,
            $documentType,
            $request->integer('limit') ?: 5,
        );
    }

    private function safaPayload(Request $request, KpSafaPublicInfoPreviewService $service): array
    {
        return $service->preview($request->integer('period_id') ?: null);
    }

    private function sanitizeForReview(array $payload): array
    {
        return $this->removeSensitiveKeys($payload);
    }

    private function removeSensitiveKeys(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        $sanitized = [];

        foreach ($value as $key => $item) {
            $keyName = is_string($key) ? strtolower($key) : '';

            if ($keyName !== '' && preg_match('/token|password|secret|signed|meeting_link|internal_path|storage_path/', $keyName)) {
                continue;
            }

            $sanitized[$key] = $this->removeSensitiveKeys($item);
        }

        return $sanitized;
    }
}
