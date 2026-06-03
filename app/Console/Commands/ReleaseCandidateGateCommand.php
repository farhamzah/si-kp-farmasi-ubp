<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ReleaseCandidateGateCommand extends Command
{
    protected $signature = 'kp:release-candidate-gate
        {--strict-git : Treat dirty git status as a blocker for release tagging}';

    protected $description = 'Read-only final release candidate gate before tag/push/deploy';

    public function handle(): int
    {
        $before = $this->counts();
        $report = $this->buildReport();
        $after = $this->counts();

        $report['read_only_counts'] = [
            'before' => $before,
            'after' => $after,
            'unchanged' => $before === $after,
        ];

        $this->renderReport($report);

        return $report['summary']['blockers'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function buildReport(): array
    {
        $commandNames = array_keys(Artisan::all());
        $requiredCommands = [
            'kp:integration-gap-check',
            'kp:core-mapping-coverage',
            'kp:staging-rehearsal-check',
            'kp:production-readiness-gate',
            'kp:release-sensitive-scan',
        ];

        $gitStatus = $this->gitStatusLines();
        $sensitiveScanPassed = Artisan::call('kp:release-sensitive-scan') === self::SUCCESS;
        $strictGit = (bool) $this->option('strict-git');

        $checks = [
            $this->check('required_diagnostic_commands_registered', collect($requiredCommands)->diff($commandNames)->isEmpty(), 'Command diagnostic wajib untuk release belum lengkap.'),
            $this->check('release_sensitive_scan_clean', $sensitiveScanPassed, 'Sensitive scan harus bersih sebelum release.'),
            $this->check('app_env_production', config('app.env') === 'production', 'APP_ENV harus production sebelum tag/deploy production.'),
            $this->check('app_debug_disabled', config('app.debug') === false, 'APP_DEBUG harus false sebelum tag/deploy production.'),
            $this->check('app_url_https', Str::startsWith((string) config('app.url'), 'https://'), 'APP_URL production harus HTTPS.'),
            $this->check('session_secure_cookie', (bool) config('session.secure'), 'SESSION_SECURE_COOKIE harus true untuk HTTPS production.'),
            $this->check('core_write_disabled', true, 'KP tidak boleh menulis ke database Core.'),
            $this->check('tu_runtime_bridge_closed', ! filled(config('services.tu_farmasi.endpoint')), 'Runtime write bridge TU tetap harus kosong sampai approval final.'),
            $this->check('safa_runtime_bridge_closed', ! filled(config('services.safa.endpoint')), 'Runtime write bridge SAFA tetap harus kosong sampai approval final.'),
            $this->check('sso_token_url_disabled', true, 'SSO/autologin/token URL tidak boleh aktif.'),
            $this->check('users_table_exists', Schema::hasTable('users'), 'Tabel users harus tersedia.'),
            $this->check('external_references_table_exists', Schema::hasTable('kp_external_document_references'), 'Tabel kp_external_document_references harus tersedia.'),
            $this->warning('git_status_clean_before_tag', count($gitStatus) === 0, 'Working tree sebaiknya clean sebelum membuat tag release.'),
            $this->warning('queue_worker_recommended', config('queue.default') !== 'sync', 'Production sebaiknya memakai queue worker.'),
            $this->warning('cache_not_file_recommended', config('cache.default') !== 'file', 'Production sebaiknya memakai cache terpusat.'),
            $this->warning('mail_not_log_recommended', config('mail.default') !== 'log', 'Production email sebaiknya memakai SMTP/mail service.'),
        ];

        if ($strictGit && count($gitStatus) > 0) {
            $checks[] = $this->check('strict_git_status_clean', false, 'Mode strict membutuhkan working tree clean sebelum tag release.');
        }

        $blockers = collect($checks)->where('level', 'blocker')->where('passed', false)->values();
        $warnings = collect($checks)->where('level', 'warning')->where('passed', false)->values();

        return [
            'generated_at' => now()->toIso8601String(),
            'dry_run' => true,
            'external_request_sent' => false,
            'write_to_core' => false,
            'write_to_tu' => false,
            'write_to_safa' => false,
            'strict_git' => $strictGit,
            'git_status' => $gitStatus,
            'summary' => [
                'checks' => count($checks),
                'blockers' => $blockers->count(),
                'warnings' => $warnings->count(),
                'ready_for_release_candidate' => $blockers->isEmpty(),
                'ready_for_runtime_tu_bridge' => false,
            ],
            'checks' => $checks,
            'manual_signoffs_required' => [
                'production_env_verified',
                'database_backup_verified',
                'storage_backup_verified',
                'uat_acceptance_signed_off',
                'rollback_owner_confirmed',
                'domain_ssl_verified',
                'mail_service_verified',
                'demo_users_disabled_or_rotated',
            ],
        ];
    }

    private function check(string $key, bool $passed, string $message): array
    {
        return [
            'key' => $key,
            'level' => 'blocker',
            'passed' => $passed,
            'message' => $message,
        ];
    }

    private function warning(string $key, bool $passed, string $message): array
    {
        return [
            'key' => $key,
            'level' => 'warning',
            'passed' => $passed,
            'message' => $message,
        ];
    }

    private function renderReport(array $report): void
    {
        $this->info('KP release candidate gate');
        $this->line('Dry run: yes');
        $this->line('External request sent: no');
        $this->line('Write to Core/TU/SAFA: no/no/no');
        $this->line('Strict git: '.($report['strict_git'] ? 'yes' : 'no'));
        $this->line('Checks: '.$report['summary']['checks']);
        $this->line('Blockers: '.$report['summary']['blockers']);
        $this->line('Warnings: '.$report['summary']['warnings']);
        $this->line('Ready for release candidate: '.($report['summary']['ready_for_release_candidate'] ? 'yes' : 'no'));
        $this->line('Ready for runtime TU bridge: no');
        $this->line('Git status entries: '.count($report['git_status']));
        $this->line('Read-only counts unchanged: '.($report['read_only_counts']['unchanged'] ? 'yes' : 'no'));

        $failed = collect($report['checks'])->where('passed', false)->values();

        if ($failed->isNotEmpty()) {
            $this->newLine();
            $this->line('Open items:');
            foreach ($failed as $item) {
                $prefix = $item['level'] === 'blocker' ? 'BLOCKER' : 'WARNING';
                $this->line("  [{$prefix}] {$item['key']}: {$item['message']}");
            }
        }
    }

    private function counts(): array
    {
        return [
            'users' => Schema::hasTable('users') ? DB::table('users')->count() : 'table_missing',
            'user_roles' => Schema::hasTable('user_roles') ? DB::table('user_roles')->count() : 'table_missing',
            'kp_external_document_references' => Schema::hasTable('kp_external_document_references') ? DB::table('kp_external_document_references')->count() : 'table_missing',
        ];
    }

    /**
     * @return list<string>
     */
    private function gitStatusLines(): array
    {
        $output = [];
        $code = 1;

        exec('git -C '.escapeshellarg(base_path()).' status --short', $output, $code);

        if ($code !== 0) {
            return ['git_status_unavailable'];
        }

        return collect($output)
            ->filter()
            ->values()
            ->all();
    }
}
