<?php

namespace App\Console\Commands;

use App\Support\CoreAcademicUnitMapper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class CoreAcademicUnitCheckCommand extends Command
{
    protected $signature = 'kp:core-academic-unit-check
        {--show-rows : Show local KP academic unit mapping rows}';

    protected $description = 'Read-only diagnostic for KP-Core faculty, study program, and department label alignment';

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

        return self::SUCCESS;
    }

    private function buildReport(): array
    {
        $core = $this->coreAcademicUnits();
        $studyProgramRows = $this->studyProgramRows($core['study_programs']);
        $departmentRows = $this->departmentRows($core['departments']);

        $studyProgramIssues = collect($studyProgramRows)->flatMap(fn (array $row) => $row['issues'])->countBy();
        $departmentIssues = collect($departmentRows)->flatMap(fn (array $row) => $row['issues'])->countBy();

        return [
            'generated_at' => now()->toIso8601String(),
            'hierarchy' => CoreAcademicUnitMapper::hierarchy(),
            'core' => $core,
            'summary' => [
                'kp_unique_study_programs' => count($studyProgramRows),
                'kp_study_program_mapped' => collect($studyProgramRows)->whereNotNull('canonical')->count(),
                'kp_study_program_unmapped' => $studyProgramIssues['unmapped_study_program'] ?? 0,
                'kp_unique_departments' => count($departmentRows),
                'kp_department_mapped' => collect($departmentRows)->whereNotNull('canonical')->count(),
                'kp_department_unmapped' => $departmentIssues['unmapped_department'] ?? 0,
                'faculty_label_used_as_department' => $departmentIssues['faculty_label_used_as_department'] ?? 0,
            ],
            'study_program_rows' => $studyProgramRows,
            'department_rows' => $departmentRows,
            'warnings' => array_values(array_unique(array_merge(
                $this->warningsFromRows($studyProgramRows),
                $this->warningsFromRows($departmentRows),
                $core['warnings'],
            ))),
        ];
    }

    private function studyProgramRows(array $coreStudyPrograms): array
    {
        return $this->localDistinctValues('students', 'study_program')
            ->merge($this->localDistinctValues('lecturers', 'study_program'))
            ->unique(fn (string $value) => CoreAcademicUnitMapper::normalize($value))
            ->values()
            ->map(function (string $value) use ($coreStudyPrograms): array {
                $canonical = CoreAcademicUnitMapper::mapStudyProgram($value);
                $issues = [];

                if (! $canonical) {
                    $issues[] = 'unmapped_study_program';
                } elseif ($coreStudyPrograms && ! in_array($canonical, $coreStudyPrograms, true)) {
                    $issues[] = 'canonical_study_program_missing_in_core';
                }

                return [
                    'source' => 'kp.study_program',
                    'local_value' => $value,
                    'canonical' => $canonical,
                    'issues' => $issues,
                ];
            })
            ->all();
    }

    private function departmentRows(array $coreDepartments): array
    {
        return $this->localDistinctValues('lecturers', 'department')
            ->unique(fn (string $value) => CoreAcademicUnitMapper::normalize($value))
            ->values()
            ->map(function (string $value) use ($coreDepartments): array {
                $canonical = CoreAcademicUnitMapper::mapDepartment($value);
                $issues = [];

                if (CoreAcademicUnitMapper::isFacultyLabel($value)) {
                    $issues[] = 'faculty_label_used_as_department';
                } elseif (! $canonical) {
                    $issues[] = 'unmapped_department';
                } elseif ($coreDepartments && ! in_array($canonical, $coreDepartments, true)) {
                    $issues[] = 'canonical_department_missing_in_core';
                }

                return [
                    'source' => 'kp.lecturers.department',
                    'local_value' => $value,
                    'canonical' => $canonical,
                    'issues' => $issues,
                ];
            })
            ->all();
    }

    private function coreAcademicUnits(): array
    {
        $warnings = [];

        try {
            return [
                'faculties' => $this->coreNames('faculties'),
                'study_programs' => $this->coreNames('study_programs'),
                'departments' => $this->coreNames('departments'),
                'warnings' => $warnings,
            ];
        } catch (Throwable $exception) {
            $warnings[] = 'Core academic unit tables unavailable: '.$exception->getMessage();
        }

        return [
            'faculties' => [],
            'study_programs' => [],
            'departments' => [],
            'warnings' => $warnings,
        ];
    }

    private function coreNames(string $table): array
    {
        if (! Schema::connection('core')->hasTable($table)) {
            return [];
        }

        return DB::connection('core')
            ->table($table)
            ->whereNotNull('name')
            ->orderBy('name')
            ->pluck('name')
            ->map(fn (mixed $name) => trim((string) $name))
            ->filter()
            ->values()
            ->all();
    }

    private function localDistinctValues(string $table, string $column)
    {
        return DB::table($table)
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->orderBy($column)
            ->pluck($column)
            ->map(fn (mixed $value) => trim((string) $value))
            ->filter();
    }

    private function warningsFromRows(array $rows): array
    {
        return collect($rows)
            ->filter(fn (array $row) => $row['issues'] !== [])
            ->map(fn (array $row) => $row['local_value'].': '.implode(', ', $row['issues']))
            ->values()
            ->all();
    }

    private function renderReport(array $report): void
    {
        $this->info('KP Core academic unit alignment');
        $this->line('Hierarchy: '.implode(' > ', $report['hierarchy']));
        $this->line('Core faculties: '.count($report['core']['faculties']));
        $this->line('Core study programs: '.count($report['core']['study_programs']));
        $this->line('Core departments: '.count($report['core']['departments']));

        $this->newLine();
        $this->line('KP study programs: '.$report['summary']['kp_unique_study_programs']);
        $this->line('Study program mapped: '.$report['summary']['kp_study_program_mapped']);
        $this->line('Study program unmapped: '.$report['summary']['kp_study_program_unmapped']);
        $this->line('KP departments: '.$report['summary']['kp_unique_departments']);
        $this->line('Department mapped: '.$report['summary']['kp_department_mapped']);
        $this->line('Department unmapped: '.$report['summary']['kp_department_unmapped']);
        $this->line('Faculty label used as department: '.$report['summary']['faculty_label_used_as_department']);
        $this->line('Read-only counts unchanged: '.($report['read_only_counts']['unchanged'] ? 'yes' : 'no'));

        if ($this->option('show-rows')) {
            $this->newLine();
            $this->line('Rows:');
            foreach (array_merge($report['study_program_rows'], $report['department_rows']) as $row) {
                $this->line('  '.json_encode($row, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            }
        }

        $this->newLine();
        $this->line('Warnings:');
        $report['warnings']
            ? collect($report['warnings'])->each(fn (string $warning) => $this->warn("  - {$warning}"))
            : $this->line('  none');
    }

    private function counts(): array
    {
        return [
            'students' => DB::table('students')->count(),
            'lecturers' => DB::table('lecturers')->count(),
        ];
    }
}
