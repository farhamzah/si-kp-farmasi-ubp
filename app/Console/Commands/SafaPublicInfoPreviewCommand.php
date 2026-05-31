<?php

namespace App\Console\Commands;

use App\Services\Integration\KpSafaPublicInfoPreviewService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SafaPublicInfoPreviewCommand extends Command
{
    protected $signature = 'kp:safa-public-info-preview {--period-id= : Preview a specific KP period}';

    protected $description = 'Dry-run SAFA public-info payload preview without external requests or writes';

    public function handle(KpSafaPublicInfoPreviewService $service): int
    {
        $before = $this->counts();
        $payload = $service->preview($this->option('period-id') ? (int) $this->option('period-id') : null);
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
            'kp_periods' => DB::table('kp_periods')->count(),
            'kp_document_requirements' => DB::table('kp_document_requirements')->count(),
        ];
    }
}
