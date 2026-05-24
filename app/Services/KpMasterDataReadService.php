<?php

namespace App\Services;

use App\Data\LecturerDisplayData;
use App\Data\StudentDisplayData;
use App\Exceptions\CoreMasterDataUnavailableException;
use App\Models\Core\CoreLecturer;
use App\Models\Core\CoreStudent;
use App\Models\Lecturer;
use App\Models\Student;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class KpMasterDataReadService
{
    private array $studentDisplayCache = [];

    private array $lecturerDisplayCache = [];

    public function getStudentDisplayData(Student $legacyStudent, ?string $mode = null): StudentDisplayData
    {
        $mode = $this->mode($mode);
        $cacheKey = "{$mode}:{$legacyStudent->getKey()}:{$legacyStudent->core_student_id}";

        if (isset($this->studentDisplayCache[$cacheKey])) {
            return $this->studentDisplayCache[$cacheKey];
        }

        if ($mode === 'legacy') {
            return $this->studentDisplayCache[$cacheKey] = $this->legacyStudentDisplay($legacyStudent);
        }

        $coreStudent = $this->coreStudentFor($legacyStudent);
        if ($coreStudent) {
            return $this->studentDisplayCache[$cacheKey] = $this->coreStudentDisplay($legacyStudent, $coreStudent);
        }

        if ($mode === 'core_preferred') {
            Log::warning('Falling back to legacy student display because Core student was unavailable.', [
                'legacy_student_id' => $legacyStudent->id,
                'core_student_id' => $legacyStudent->core_student_id,
            ]);

            return $this->studentDisplayCache[$cacheKey] = $this->legacyStudentDisplay($legacyStudent, 'Core student unavailable; using legacy fallback.');
        }

        throw new CoreMasterDataUnavailableException("Core student data is required for legacy student {$legacyStudent->id}.");
    }

    public function getLecturerDisplayData(Lecturer $legacyLecturer, ?string $mode = null): LecturerDisplayData
    {
        $mode = $this->mode($mode);
        $cacheKey = "{$mode}:{$legacyLecturer->getKey()}:{$legacyLecturer->core_lecturer_id}";

        if (isset($this->lecturerDisplayCache[$cacheKey])) {
            return $this->lecturerDisplayCache[$cacheKey];
        }

        if ($mode === 'legacy') {
            return $this->lecturerDisplayCache[$cacheKey] = $this->legacyLecturerDisplay($legacyLecturer);
        }

        $coreLecturer = $this->coreLecturerFor($legacyLecturer);
        if ($coreLecturer) {
            return $this->lecturerDisplayCache[$cacheKey] = $this->coreLecturerDisplay($legacyLecturer, $coreLecturer);
        }

        if ($mode === 'core_preferred') {
            Log::warning('Falling back to legacy lecturer display because Core lecturer was unavailable.', [
                'legacy_lecturer_id' => $legacyLecturer->id,
                'core_lecturer_id' => $legacyLecturer->core_lecturer_id,
            ]);

            return $this->lecturerDisplayCache[$cacheKey] = $this->legacyLecturerDisplay($legacyLecturer, 'Core lecturer unavailable; using legacy fallback.');
        }

        throw new CoreMasterDataUnavailableException("Core lecturer data is required for legacy lecturer {$legacyLecturer->id}.");
    }

    public function findStudentDisplayByLegacyId(int $id, ?string $mode = null): ?StudentDisplayData
    {
        $student = Student::query()->with('user')->find($id);

        return $student ? $this->getStudentDisplayData($student, $mode) : null;
    }

    public function findLecturerDisplayByLegacyId(int $id, ?string $mode = null): ?LecturerDisplayData
    {
        $lecturer = Lecturer::query()->with('user')->find($id);

        return $lecturer ? $this->getLecturerDisplayData($lecturer, $mode) : null;
    }

    public function listStudentsForSelect(?string $search = null, int $limit = 50, ?string $mode = null): Collection
    {
        return Student::query()
            ->with('user')
            ->when($search, function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('nim', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
                });
            })
            ->orderBy('nim')
            ->limit($limit)
            ->get()
            ->map(fn (Student $student) => $this->getStudentDisplayData($student, $mode));
    }

    public function listLecturersForSelect(?string $search = null, int $limit = 50, ?string $mode = null): Collection
    {
        return Lecturer::query()
            ->with('user')
            ->when($search, function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('nidn_nip', 'like', "%{$search}%")
                        ->orWhere('employee_number', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
                });
            })
            ->orderBy('nidn_nip')
            ->orderBy('employee_number')
            ->limit($limit)
            ->get()
            ->map(fn (Lecturer $lecturer) => $this->getLecturerDisplayData($lecturer, $mode));
    }

    public function mode(?string $mode = null): string
    {
        $mode ??= config('kp_master_data.read_mode', 'legacy');
        $allowed = config('kp_master_data.allowed_modes', ['legacy']);

        return in_array($mode, $allowed, true) ? $mode : 'legacy';
    }

    private function legacyStudentDisplay(Student $student, ?string $error = null): StudentDisplayData
    {
        $student->loadMissing('user');

        return new StudentDisplayData(
            source: 'legacy',
            legacyStudentId: $student->id,
            coreStudentId: $student->core_student_id,
            name: (string) ($student->user?->name ?? $student->nim),
            studentNumber: (string) $student->nim,
            email: (string) ($student->user?->email ?? ''),
            phone: $student->phone,
            studyProgramName: $student->study_program,
            className: $student->class_name,
            semester: $student->semester !== null ? (int) $student->semester : null,
            status: $student->status,
            error: $error,
        );
    }

    private function coreStudentDisplay(Student $legacyStudent, CoreStudent $coreStudent): StudentDisplayData
    {
        $coreStudent->loadMissing(['user', 'studyProgram']);

        return new StudentDisplayData(
            source: 'core',
            legacyStudentId: $legacyStudent->id,
            coreStudentId: $coreStudent->id,
            name: (string) $coreStudent->name,
            studentNumber: (string) $coreStudent->student_number,
            email: (string) $coreStudent->email,
            phone: $legacyStudent->phone,
            studyProgramName: $coreStudent->studyProgram?->name,
            className: $legacyStudent->class_name,
            semester: $legacyStudent->semester !== null ? (int) $legacyStudent->semester : null,
            status: $coreStudent->active ? 'active' : 'inactive',
        );
    }

    private function legacyLecturerDisplay(Lecturer $lecturer, ?string $error = null): LecturerDisplayData
    {
        $lecturer->loadMissing('user');

        return new LecturerDisplayData(
            source: 'legacy',
            legacyLecturerId: $lecturer->id,
            coreLecturerId: $lecturer->core_lecturer_id,
            name: (string) ($lecturer->user?->name ?? $lecturer->nidn_nip ?? $lecturer->employee_number),
            lecturerNumber: (string) ($lecturer->nidn_nip ?: $lecturer->employee_number),
            email: (string) ($lecturer->user?->email ?? ''),
            phone: $lecturer->phone,
            studyProgramName: $lecturer->study_program,
            departmentName: $lecturer->department,
            expertise: $lecturer->expertise,
            status: $lecturer->status,
            error: $error,
        );
    }

    private function coreLecturerDisplay(Lecturer $legacyLecturer, CoreLecturer $coreLecturer): LecturerDisplayData
    {
        $coreLecturer->loadMissing(['user', 'studyProgram', 'department']);

        return new LecturerDisplayData(
            source: 'core',
            legacyLecturerId: $legacyLecturer->id,
            coreLecturerId: $coreLecturer->id,
            name: (string) $coreLecturer->name,
            lecturerNumber: (string) $coreLecturer->lecturer_number,
            email: (string) $coreLecturer->email,
            phone: $coreLecturer->phone ?: $legacyLecturer->phone,
            studyProgramName: $coreLecturer->studyProgram?->name,
            departmentName: $coreLecturer->department?->name,
            expertise: $coreLecturer->notes ?: $legacyLecturer->expertise,
            status: $coreLecturer->active ? 'active' : 'inactive',
        );
    }

    private function coreStudentFor(Student $student): ?CoreStudent
    {
        if (! $student->core_student_id) {
            return null;
        }

        return CoreStudent::query()->with(['user', 'studyProgram'])->find($student->core_student_id);
    }

    private function coreLecturerFor(Lecturer $lecturer): ?CoreLecturer
    {
        if (! $lecturer->core_lecturer_id) {
            return null;
        }

        return CoreLecturer::query()->with(['user', 'studyProgram', 'department'])->find($lecturer->core_lecturer_id);
    }
}
