<?php

namespace App\Console\Commands;

use App\Services\Integration\KpTuDocumentPayloadPreviewService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TuDocumentPayloadPreviewCommand extends Command
{
    protected $signature = 'kp:tu-document-payload-preview
        {--assignment-id= : Preview a specific KP assignment}
        {--document-type= : Preview one document type}
        {--limit=5 : Maximum assignments to scan}';

    protected $description = 'Dry-run TU document payload preview without external requests or writes';

    public function handle(KpTuDocumentPayloadPreviewService $service): int
    {
        $before = $this->counts();
        $payload = $service->preview(
            $this->option('assignment-id') ? (int) $this->option('assignment-id') : null,
            $this->option('document-type') ? (string) $this->option('document-type') : null,
            (int) $this->option('limit'),
        );
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
            'kp_assignments' => DB::table('kp_assignments')->count(),
            'kp_exams' => DB::table('kp_exams')->count(),
            'kp_final_reports' => DB::table('kp_final_reports')->count(),
            'kp_final_scores' => DB::table('kp_final_scores')->count(),
        ];
    }
}

