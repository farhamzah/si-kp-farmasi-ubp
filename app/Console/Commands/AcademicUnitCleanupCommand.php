<?php

namespace App\Console\Commands;

use App\Support\CoreAcademicUnitMapper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AcademicUnitCleanupCommand extends Command
{
    protected $signature = 'kp:academic-unit-cleanup
        {--execute : Write cleanup changes to the local KP database}
        {--confirm-execute : Confirm local KP write}
        {--department=Farmakologi dan Farmasi Klinik : Canonical department for legacy faculty labels}
        {--show-rows : Show planned cleanup rows}';

    protected $description = 'Preview or clean local KP academic unit labels without writing to Core';

    public function handle(): int
    {
        $execute = (bool) $this->option('execute');
        $report = $this->buildReport();

        $this->renderReport($report, $execute);

        if ($execute) {
            if (! $this->option('confirm-execute')) {
                $this->error('Execute refused: missing --confirm-execute.');

                return self::FAILURE;
            }

            $this->applyPlans($report['plans']);
            $this->info('Local KP academic unit cleanup applied.');
        }

        return self::SUCCESS;
    }

    private function buildReport(): array
    {
        $targetDepartment = trim((string) $this->option('department'));
        $plans = DB::table('lecturers')
            ->leftJoin('users', 'users.id', '=', 'lecturers.user_id')
            ->select('lecturers.id', 'lecturers.department', 'lecturers.study_program', 'users.email')
            ->whereNotNull('lecturers.department')
            ->orderBy('lecturers.id')
            ->get()
            ->filter(fn (object $row) => CoreAcademicUnitMapper::isFacultyLabel($row->department))
            ->map(fn (object $row) => [
                'table' => 'lecturers',
                'id' => (int) $row->id,
                'email' => $row->email,
                'study_program' => $row->study_program,
                'old_department' => $row->department,
                'new_department' => $targetDepartment,
                'reason' => 'faculty_label_used_as_department',
            ])
            ->values()
            ->all();

        return [
            'generated_at' => now()->toIso8601String(),
            'mode' => $this->option('execute') ? 'execute' : 'dry-run',
            'hierarchy' => CoreAcademicUnitMapper::hierarchy(),
            'target_department' => $targetDepartment,
            'planned_updates' => count($plans),
            'plans' => $plans,
        ];
    }

    private function renderReport(array $report, bool $execute): void
    {
        $this->info('KP local academic unit cleanup');
        $this->line('Mode: '.($execute ? 'execute local KP updates' : 'dry-run only; no writes performed'));
        $this->line('Hierarchy: '.implode(' > ', $report['hierarchy']));
        $this->line('Target department: '.$report['target_department']);
        $this->line('Planned updates: '.$report['planned_updates']);

        if ($this->option('show-rows')) {
            $this->newLine();
            $this->line('Rows:');
            foreach ($report['plans'] as $plan) {
                $this->line('  '.json_encode($plan, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            }
        }
    }

    private function applyPlans(array $plans): void
    {
        DB::transaction(function () use ($plans): void {
            foreach ($plans as $plan) {
                DB::table($plan['table'])
                    ->where('id', $plan['id'])
                    ->update([
                        'department' => $plan['new_department'],
                        'updated_at' => now(),
                    ]);
            }
        });
    }
}
