<?php

namespace App\Console\Commands;

use App\Exceptions\CoreMasterDataUnavailableException;
use App\Models\Lecturer;
use App\Models\Student;
use App\Services\KpMasterDataReadService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MasterDataReadCheckCommand extends Command
{
    protected $signature = 'kp:master-data-read-check
        {--mode= : Read mode to test: legacy, core_preferred, core_only}
        {--student-id= : Legacy student ID to inspect}
        {--lecturer-id= : Legacy lecturer ID to inspect}
        {--show-samples : Show display data samples}
        {--report-json : Save diagnostic report as JSON}';

    protected $description = 'Read-only diagnostic for KP legacy/Core master-data display adapters';

    public function handle(KpMasterDataReadService $service): int
    {
        $mode = $service->mode($this->option('mode'));
        $report = $this->buildReport($service, $mode);

        $this->renderReport($report);

        if ($this->option('report-json')) {
            $path = $this->writeJsonReport($report);
            $this->info("JSON report written: {$path}");
        }

        return $report['failures'] ? self::FAILURE : self::SUCCESS;
    }

    private function buildReport(KpMasterDataReadService $service, string $mode): array
    {
        $student = $this->option('student-id')
            ? Student::query()->with('user')->find($this->option('student-id'))
            : Student::query()->with('user')->orderBy('id')->first();
        $lecturer = $this->option('lecturer-id')
            ? Lecturer::query()->with('user')->find($this->option('lecturer-id'))
            : Lecturer::query()->with('user')->orderBy('id')->first();
        $warnings = [];
        $failures = [];
        $studentData = null;
        $lecturerData = null;

        try {
            $studentData = $student ? $service->getStudentDisplayData($student, $mode) : null;
        } catch (CoreMasterDataUnavailableException $exception) {
            $failures[] = $exception->getMessage();
        }

        try {
            $lecturerData = $lecturer ? $service->getLecturerDisplayData($lecturer, $mode) : null;
        } catch (CoreMasterDataUnavailableException $exception) {
            $failures[] = $exception->getMessage();
        }

        foreach ([$studentData, $lecturerData] as $data) {
            if ($data?->error) {
                $warnings[] = $data->error;
            }
        }

        if (! $student) {
            $warnings[] = 'No legacy student sample found.';
        }

        if (! $lecturer) {
            $warnings[] = 'No legacy lecturer sample found.';
        }

        return [
            'generated_at' => now()->toIso8601String(),
            'current_read_mode' => config('kp_master_data.read_mode', 'legacy'),
            'tested_mode' => $mode,
            'counts' => [
                'legacy_students' => Student::query()->count(),
                'legacy_lecturers' => Lecturer::query()->count(),
                'mapped_students' => Student::query()->whereNotNull('core_student_id')->count(),
                'mapped_lecturers' => Lecturer::query()->whereNotNull('core_lecturer_id')->count(),
            ],
            'student' => $studentData?->toArray(),
            'lecturer' => $lecturerData?->toArray(),
            'warnings' => array_values(array_unique($warnings)),
            'failures' => array_values(array_unique($failures)),
        ];
    }

    private function renderReport(array $report): void
    {
        $this->info('KP master-data read adapter diagnostic');
        $this->line('Current read mode: '.$report['current_read_mode']);
        $this->line('Tested mode: '.$report['tested_mode']);

        $this->newLine();
        $this->line('Counts:');
        foreach ($report['counts'] as $key => $count) {
            $this->line("  {$key}: {$count}");
        }

        if ($this->option('show-samples')) {
            $this->newLine();
            $this->line('Samples:');
            $this->line('  student: '.json_encode($report['student'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $this->line('  lecturer: '.json_encode($report['lecturer'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        $this->newLine();
        $this->line('Warnings:');
        $report['warnings']
            ? collect($report['warnings'])->each(fn ($warning) => $this->warn("  - {$warning}"))
            : $this->line('  none');

        $this->newLine();
        $this->line('Failures:');
        $report['failures']
            ? collect($report['failures'])->each(fn ($failure) => $this->error("  - {$failure}"))
            : $this->line('  none');
    }

    private function writeJsonReport(array $report): string
    {
        $directory = storage_path('app/reports');
        File::ensureDirectoryExists($directory);

        $path = $directory.'/kp-master-data-read-check-'.now()->format('Ymd-His').'.json';
        File::put($path, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return $path;
    }
}
