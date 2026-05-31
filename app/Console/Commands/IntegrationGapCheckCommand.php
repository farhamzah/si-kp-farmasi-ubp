<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Throwable;

class IntegrationGapCheckCommand extends Command
{
    protected $signature = 'kp:integration-gap-check
        {--check-kp-db : Attempt read-only KP database counts}
        {--check-core-db : Attempt read-only Core database counts}
        {--report-json : Save diagnostic report as JSON}';

    protected $description = 'Read-only workspace integration gap diagnostic for KP/Core/TU/SAFA readiness';

    public function handle(): int
    {
        $before = $this->counts();
        $report = $this->buildReport($before);
        $after = $this->counts();

        $report['read_only_counts'] = [
            'before' => $before,
            'after' => $after,
            'unchanged' => $before === $after,
        ];

        $this->renderReport($report);

        if ($this->option('report-json')) {
            $path = $this->writeJsonReport($report);
            $this->info("JSON report written: {$path}");
        }

        return self::SUCCESS;
    }

    private function buildReport(array $counts): array
    {
        $workspace = dirname(base_path(), 2);
        $apps = [
            'kp-farmasi' => base_path(),
            'core-farmasi' => $workspace.DIRECTORY_SEPARATOR.'apps'.DIRECTORY_SEPARATOR.'core-farmasi',
            'tu-farmasi' => $workspace.DIRECTORY_SEPARATOR.'apps'.DIRECTORY_SEPARATOR.'tu-farmasi',
            'safa-ubp' => $workspace.DIRECTORY_SEPARATOR.'apps'.DIRECTORY_SEPARATOR.'safa-ubp',
        ];

        return [
            'generated_at' => now()->toIso8601String(),
            'workspace' => $workspace,
            'guardrails' => [
                'core_write_enabled' => false,
                'tu_write_enabled' => false,
                'safa_write_enabled' => false,
                'sso_or_token_url_enabled' => false,
            ],
            'kp_modes' => [
                'auth' => config('kp_auth.mode', 'legacy'),
                'master_data_read' => config('kp_master_data.read_mode', 'legacy'),
                'core_http_enabled' => (bool) config('core_farmasi.enabled', false),
                'core_http_read_mode' => config('core_farmasi.read_mode', 'legacy'),
            ],
            'apps' => collect($apps)->map(fn (string $path) => [
                'path' => $path,
                'exists' => File::isDirectory($path),
                'docs_exists' => File::isDirectory($path.DIRECTORY_SEPARATOR.'docs'),
                'routes_exists' => File::exists($path.DIRECTORY_SEPARATOR.'routes'.DIRECTORY_SEPARATOR.'web.php'),
            ])->all(),
            'documents' => [
                'core_internal_api' => File::exists($apps['core-farmasi'].DIRECTORY_SEPARATOR.'docs'.DIRECTORY_SEPARATOR.'CORE-INTERNAL-API.md'),
                'tu_document_link' => File::exists($apps['tu-farmasi'].DIRECTORY_SEPARATOR.'docs'.DIRECTORY_SEPARATOR.'TU-31-DOCUMENT-LINK-ROUTING-METADATA.md'),
                'tu_archive' => File::exists($apps['tu-farmasi'].DIRECTORY_SEPARATOR.'docs'.DIRECTORY_SEPARATOR.'TU-30-ARCHIVE-FROM-FINAL-UPLOAD.md'),
                'safa_lab_card' => File::exists($apps['safa-ubp'].DIRECTORY_SEPARATOR.'docs'.DIRECTORY_SEPARATOR.'reports'.DIRECTORY_SEPARATOR.'SAFA-LAB-PORTAL-CARD-PREPARATION-REPORT.md'),
            ],
            'counts' => $counts,
            'gaps' => [
                'core' => [
                    'local KP user/student/lecturer profiles still exist and need duplicate-data governance.',
                    'Core app access mapping must stay explicit; admin-kp must not imply admin-core.',
                    'Core-only mode should remain gated by preflight until mapping coverage is complete.',
                ],
                'tu' => [
                    'KP documents have no TU document_link/archive references yet.',
                    'No KP-to-TU bridge contract exists for placement letters, invitations, minutes, or final score recaps.',
                    'Future bridge should reference TU archives instead of duplicating uploads where possible.',
                ],
                'safa' => [
                    'KP has no public-info feed contract for SAFA announcements or portal card metadata.',
                    'SAFA must only receive public KP period/timeline/requirement information, never student private data.',
                ],
            ],
        ];
    }

    private function renderReport(array $report): void
    {
        $this->info('KP workspace integration gap check');
        $this->line('Workspace: '.$report['workspace']);
        $this->line('Auth mode: '.$report['kp_modes']['auth']);
        $this->line('Master data read mode: '.$report['kp_modes']['master_data_read']);
        $this->line('Core HTTP enabled: '.($report['kp_modes']['core_http_enabled'] ? 'yes' : 'no'));
        $this->line('Read-only counts unchanged: '.($report['read_only_counts']['unchanged'] ? 'yes' : 'no'));

        $this->newLine();
        $this->line('Apps:');
        foreach ($report['apps'] as $app => $data) {
            $this->line(sprintf(
                '  %s: exists=%s docs=%s routes=%s',
                $app,
                $data['exists'] ? 'yes' : 'no',
                $data['docs_exists'] ? 'yes' : 'no',
                $data['routes_exists'] ? 'yes' : 'no',
            ));
        }

        $this->newLine();
        $this->line('Guardrails:');
        foreach ($report['guardrails'] as $key => $value) {
            $this->line('  '.$key.': '.($value ? 'yes' : 'no'));
        }
    }

    private function counts(): array
    {
        return [
            'kp' => [
                'users' => $this->option('check-kp-db') ? $this->safeCount(DB::connection(), 'users') : null,
                'students' => $this->option('check-kp-db') ? $this->safeCount(DB::connection(), 'students') : null,
                'lecturers' => $this->option('check-kp-db') ? $this->safeCount(DB::connection(), 'lecturers') : null,
                'field_supervisors' => $this->option('check-kp-db') ? $this->safeCount(DB::connection(), 'field_supervisors') : null,
                'kp_registrations' => $this->option('check-kp-db') ? $this->safeCount(DB::connection(), 'kp_registrations') : null,
                'kp_documents' => $this->option('check-kp-db') ? $this->safeCount(DB::connection(), 'kp_documents') : null,
                'kp_assignments' => $this->option('check-kp-db') ? $this->safeCount(DB::connection(), 'kp_assignments') : null,
            ],
            'core' => [
                'users' => $this->option('check-core-db') ? $this->safeCount(DB::connection('core'), 'users') : null,
                'students' => $this->option('check-core-db') ? $this->safeCount(DB::connection('core'), 'students') : null,
                'lecturers' => $this->option('check-core-db') ? $this->safeCount(DB::connection('core'), 'lecturers') : null,
                'user_app_accesses' => $this->option('check-core-db') ? $this->safeCount(DB::connection('core'), 'user_app_accesses') : null,
            ],
        ];
    }

    private function safeCount($connection, string $table): ?int
    {
        try {
            return $connection->table($table)->count();
        } catch (Throwable) {
            return null;
        }
    }

    private function writeJsonReport(array $report): string
    {
        $directory = storage_path('app/reports');
        File::ensureDirectoryExists($directory);

        $path = $directory.'/kp-integration-gap-check-'.now()->format('Ymd-His').'.json';
        File::put($path, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return $path;
    }
}
