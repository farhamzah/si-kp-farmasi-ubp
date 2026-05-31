<?php

namespace App\Console\Commands;

use App\Services\Integration\KpExternalDocumentReferenceService;
use App\Services\Integration\KpTuDocumentPayloadPreviewService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ExternalDocumentReferencePreviewCommand extends Command
{
    protected $signature = 'kp:external-document-reference-preview
        {--assignment-id= : Preview references for a specific KP assignment}
        {--document-type= : Preview one document type}
        {--limit=5 : Maximum assignments to scan}';

    protected $description = 'Preview local TU external document references without persistence or external requests';

    public function handle(KpTuDocumentPayloadPreviewService $tuPayloadService, KpExternalDocumentReferenceService $referenceService): int
    {
        $before = $this->counts();
        $tuPayload = $tuPayloadService->preview(
            $this->option('assignment-id') ? (int) $this->option('assignment-id') : null,
            $this->option('document-type') ? (string) $this->option('document-type') : null,
            (int) $this->option('limit'),
        );
        $payload = $referenceService->previewFromTuPayload($tuPayload);
        $after = $this->counts();

        $payload['read_only_counts'] = [
            'before' => $before,
            'after' => $after,
            'unchanged' => $before === $after,
        ];

        $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }

    private function counts(): array
    {
        return [
            'kp_external_document_references' => Schema::hasTable('kp_external_document_references')
                ? DB::table('kp_external_document_references')->count()
                : 'table_missing',
            'kp_assignments' => DB::table('kp_assignments')->count(),
            'kp_exams' => DB::table('kp_exams')->count(),
            'kp_final_reports' => DB::table('kp_final_reports')->count(),
            'kp_final_scores' => DB::table('kp_final_scores')->count(),
        ];
    }
}
