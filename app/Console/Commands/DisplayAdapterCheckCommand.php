<?php

namespace App\Console\Commands;

use App\Exceptions\CoreMasterDataUnavailableException;
use App\Models\Lecturer;
use App\Models\Student;
use App\Services\KpMasterDataReadService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class DisplayAdapterCheckCommand extends Command
{
    protected $signature = 'kp:display-adapter-check
        {--mode= : Display mode to test: legacy, core_preferred, core_only}
        {--show-samples : Show sample labels}
        {--report-json : Save diagnostic report as JSON}';

    protected $description = 'Read-only diagnostic for Core-backed KP safe display labels';

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
        $student = Student::query()->with('user')->orderBy('id')->first();
        $lecturer = Lecturer::query()->with('user')->orderBy('id')->first();
        $warnings = [];
        $failures = [];
        $studentData = null;
        $lecturerData = null;

        $before = $this->counts();

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

        $after = $this->counts();

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
            'student_sample' => [
                'legacy_id' => $student?->id,
                'core_id' => $student?->core_student_id,
                'select_value' => $student?->id,
                'select_value_uses_legacy_id' => $student ? $student->id === $student->getKey() : null,
                'label' => $studentData?->label(),
                'source' => $studentData?->source,
            ],
            'lecturer_sample' => [
                'legacy_id' => $lecturer?->id,
                'core_id' => $lecturer?->core_lecturer_id,
                'select_value' => $lecturer?->id,
                'select_value_uses_legacy_id' => $lecturer ? $lecturer->id === $lecturer->getKey() : null,
                'label' => $lecturerData?->label(),
                'source' => $lecturerData?->source,
            ],
            'read_only' => [
                'before' => $before,
                'after' => $after,
                'unchanged' => $before === $after,
            ],
            'warnings' => array_values(array_unique($warnings)),
            'failures' => array_values(array_unique($failures)),
        ];
    }

    private function counts(): array
    {
        return [
            'kp_students' => Student::query()->count(),
            'kp_lecturers' => Lecturer::query()->count(),
            'core_students' => $this->safeCoreCount('students'),
            'core_lecturers' => $this->safeCoreCount('lecturers'),
        ];
    }

    private function safeCoreCount(string $table): ?int
    {
        try {
            return DB::connection('core')->table($table)->count();
        } catch (\Throwable) {
            return null;
        }
    }

    private function renderReport(array $report): void
    {
        $this->info('KP display adapter diagnostic');
        $this->line('Current read mode: '.$report['current_read_mode']);
        $this->line('Tested mode: '.$report['tested_mode']);
        $this->line('Student select value uses legacy ID: '.($report['student_sample']['select_value_uses_legacy_id'] ? 'yes' : 'n/a'));
        $this->line('Lecturer select value uses legacy ID: '.($report['lecturer_sample']['select_value_uses_legacy_id'] ? 'yes' : 'n/a'));
        $this->line('Read-only counts unchanged: '.($report['read_only']['unchanged'] ? 'yes' : 'no'));

        if ($this->option('show-samples')) {
            $this->newLine();
            $this->line('Samples:');
            $this->line('  student: '.json_encode($report['student_sample'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            $this->line('  lecturer: '.json_encode($report['lecturer_sample'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
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

        $path = $directory.'/kp-display-adapter-check-'.now()->format('Ymd-His').'.json';
        File::put($path, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return $path;
    }
}
