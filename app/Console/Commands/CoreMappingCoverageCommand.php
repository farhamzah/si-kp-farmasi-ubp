<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\CoreRoleTranslator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class CoreMappingCoverageCommand extends Command
{
    protected $signature = 'kp:core-mapping-coverage
        {--show-users : Show users with mapping gaps or role mismatches}
        {--report-json : Save diagnostic report as JSON}';

    protected $description = 'Read-only diagnostic for Core-KP identity and role mapping coverage';

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

        if ($this->option('report-json')) {
            $path = $this->writeJsonReport($report);
            $this->info("JSON report written: {$path}");
        }

        return self::SUCCESS;
    }

    private function buildReport(): array
    {
        $users = User::query()->with('roles')->orderBy('id')->get();
        $emails = $users->map(fn (User $user) => $this->normalize($user->email))->filter();
        $duplicateEmails = $emails->duplicates()->unique()->values()->all();
        $rows = [];

        foreach ($users as $user) {
            $kpRoles = $user->roles->pluck('name')->values()->all();
            $expectedCoreRoles = CoreRoleTranslator::kpRolesToCore($kpRoles);
            $issues = [];

            if (! $user->core_user_id) {
                $issues[] = 'unmapped_user';
            }

            if ($this->normalize($user->email) === '') {
                $issues[] = 'missing_identifier';
            }

            if (in_array($this->normalize($user->email), $duplicateEmails, true)) {
                $issues[] = 'possible_duplicate_email';
            }

            if ($user->core_user_id && $expectedCoreRoles === []) {
                $issues[] = 'role_mismatch';
            }

            if ($issues || $this->option('show-users')) {
                $rows[] = [
                    'id' => $user->id,
                    'email' => $user->email,
                    'core_user_id' => $user->core_user_id,
                    'kp_roles' => $kpRoles,
                    'expected_core_roles' => $expectedCoreRoles,
                    'issues' => $issues,
                ];
            }
        }

        $issueCounts = collect($rows)
            ->flatMap(fn (array $row) => $row['issues'])
            ->countBy()
            ->all();

        return [
            'generated_at' => now()->toIso8601String(),
            'role_contract' => [
                'core_to_kp' => CoreRoleTranslator::CORE_TO_KP,
                'kp_to_core' => CoreRoleTranslator::KP_TO_CORE,
                'denied_core_roles' => CoreRoleTranslator::DENIED_CORE_ROLES,
            ],
            'summary' => [
                'total_users' => $users->count(),
                'mapped_users' => $users->whereNotNull('core_user_id')->count(),
                'unmapped_users' => $users->whereNull('core_user_id')->count(),
                'possible_duplicate_emails' => count($duplicateEmails),
                'role_mismatch' => $issueCounts['role_mismatch'] ?? 0,
                'missing_identifier' => $issueCounts['missing_identifier'] ?? 0,
            ],
            'issue_counts' => $issueCounts,
            'duplicate_emails' => $duplicateEmails,
            'rows' => $rows,
        ];
    }

    private function renderReport(array $report): void
    {
        $this->info('KP Core mapping coverage');
        $this->line('Total user: '.$report['summary']['total_users']);
        $this->line('Mapped user: '.$report['summary']['mapped_users']);
        $this->line('Unmapped user: '.$report['summary']['unmapped_users']);
        $this->line('Possible duplicate email: '.$report['summary']['possible_duplicate_emails']);
        $this->line('Role mismatch: '.$report['summary']['role_mismatch']);
        $this->line('Missing identifier: '.$report['summary']['missing_identifier']);
        $this->line('Read-only counts unchanged: '.($report['read_only_counts']['unchanged'] ? 'yes' : 'no'));

        if ($this->option('show-users')) {
            $this->newLine();
            $this->line('Rows:');
            foreach ($report['rows'] as $row) {
                $this->line('  '.json_encode($row, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            }
        }
    }

    private function counts(): array
    {
        return [
            'users' => DB::table('users')->count(),
            'students' => DB::table('students')->count(),
            'lecturers' => DB::table('lecturers')->count(),
            'field_supervisors' => DB::table('field_supervisors')->count(),
            'user_roles' => DB::table('user_roles')->count(),
        ];
    }

    private function normalize(mixed $value): string
    {
        return strtolower(trim((string) $value));
    }

    private function writeJsonReport(array $report): string
    {
        $directory = storage_path('app/reports');
        File::ensureDirectoryExists($directory);

        $path = $directory.'/kp-core-mapping-coverage-'.now()->format('Ymd-His').'.json';
        File::put($path, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return $path;
    }
}

