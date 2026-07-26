<?php

namespace App\Services;

use App\Models\KpAssignment;
use App\Models\KpExam;
use App\Models\Lecturer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use InvalidArgumentException;

class KpDosenPortfolioEventFactory
{
    public function supervisorAssigned(KpAssignment $assignment, Lecturer $lecturer): array
    {
        return $this->envelope('kp.supervisor.assigned', 'KP-ASSIGNMENT-'.$assignment->id, (int) $assignment->integration_revision, [
            'lecturer_core_id' => $this->coreLecturerId($lecturer),
            'lecturer_role' => 'PEMBIMBING_DALAM',
            'student_id' => $assignment->student?->nim ?: (string) $assignment->student_id,
            'student_name' => $assignment->student?->user?->name,
            'program_name' => $assignment->student?->study_program ?: 'Farmasi',
            'academic_year' => $assignment->period?->academic_year,
            'semester' => $assignment->period?->semester,
            'assignment_id' => $assignment->id,
            'assigned_at' => $assignment->assigned_at?->toIso8601String() ?: now()->toIso8601String(),
            'action_url' => '/management/kp-assignments/'.$assignment->id,
        ]);
    }

    public function supervisorChanged(KpAssignment $assignment, Lecturer $oldLecturer, Lecturer $newLecturer, ?string $reason = null): array
    {
        return $this->envelope('kp.supervisor.changed', 'KP-ASSIGNMENT-'.$assignment->id, (int) $assignment->integration_revision, [
            'lecturer_core_id' => $this->coreLecturerId($newLecturer),
            'old_lecturer_core_id' => $this->coreLecturerId($oldLecturer),
            'new_lecturer_core_id' => $this->coreLecturerId($newLecturer),
            'lecturer_role' => 'PEMBIMBING_DALAM',
            'student_id' => $assignment->student?->nim ?: (string) $assignment->student_id,
            'student_name' => $assignment->student?->user?->name,
            'program_name' => $assignment->student?->study_program ?: 'Farmasi',
            'academic_year' => $assignment->period?->academic_year,
            'semester' => $assignment->period?->semester,
            'assignment_id' => $assignment->id,
            'changed_at' => now()->toIso8601String(),
            'reason' => $reason,
            'action_url' => '/management/kp-assignments/'.$assignment->id,
        ]);
    }

    public function examinerAssigned(KpExam $exam, Lecturer $lecturer, int $index): array
    {
        return $this->examEnvelope('kp.examiner.assigned', $exam, $lecturer, 'PENGUJI_'.($index + 1), [
            'assigned_at' => $exam->scheduled_at?->toIso8601String() ?: now()->toIso8601String(),
        ]);
    }

    public function examinerChanged(KpExam $exam, Lecturer $oldLecturer, Lecturer $newLecturer, int $index, ?string $reason = null): array
    {
        return $this->examEnvelope('kp.examiner.changed', $exam, $newLecturer, 'PENGUJI_'.($index + 1), [
            'old_lecturer_core_id' => $this->coreLecturerId($oldLecturer),
            'new_lecturer_core_id' => $this->coreLecturerId($newLecturer),
            'changed_at' => now()->toIso8601String(),
            'reason' => $reason,
        ]);
    }

    public function examScheduled(KpExam $exam, Lecturer $lecturer, string $role): array
    {
        return $this->examEnvelope('kp.exam.scheduled', $exam, $lecturer, $role, $this->schedulePayload($exam));
    }

    public function examRescheduled(KpExam $exam, Lecturer $lecturer, string $role): array
    {
        return $this->examEnvelope('kp.exam.rescheduled', $exam, $lecturer, $role, $this->schedulePayload($exam));
    }

    public function examCompleted(KpExam $exam, Lecturer $lecturer, string $role): array
    {
        return $this->examEnvelope('kp.exam.completed', $exam, $lecturer, $role, [
            'completed_at' => now()->toIso8601String(),
            'document_references' => [],
        ]);
    }

    public function examCancelled(KpExam $exam, Lecturer $lecturer, string $role, ?string $reason): array
    {
        return $this->examEnvelope('kp.exam.cancelled', $exam, $lecturer, $role, [
            'cancelled_at' => now()->toIso8601String(),
            'reason' => $reason,
        ]);
    }

    private function examEnvelope(string $eventType, KpExam $exam, Lecturer $lecturer, string $role, array $extra = []): array
    {
        $exam->loadMissing(['assignment.student.user', 'assignment.period']);

        return $this->envelope($eventType, 'KP-EXAM-'.$exam->id, (int) $exam->integration_revision, array_merge([
            'lecturer_core_id' => $this->coreLecturerId($lecturer),
            'lecturer_role' => $role,
            'exam_id' => $exam->id,
            'student_id' => $exam->assignment?->student?->nim ?: (string) $exam->assignment?->student_id,
            'student_name' => $exam->assignment?->student?->user?->name,
            'program_name' => $exam->assignment?->student?->study_program ?: 'Farmasi',
            'academic_year' => $exam->assignment?->period?->academic_year,
            'semester' => $exam->assignment?->period?->semester,
            'action_url' => '/management/exams/'.$exam->id,
        ], $extra));
    }

    private function schedulePayload(KpExam $exam): array
    {
        $date = Carbon::parse($exam->exam_date)->toDateString();

        return [
            'start_at' => Carbon::parse($date.' '.$exam->start_time)->toIso8601String(),
            'end_at' => Carbon::parse($date.' '.$exam->end_time)->toIso8601String(),
            'location' => $exam->room,
            'meeting_url' => $exam->meeting_link,
            'activity_type' => 'UJIAN_KP',
        ];
    }

    private function envelope(string $eventType, string $sourceRecordId, int $sourceRevision, array $payload): array
    {
        $this->assertPayloadIsSafe($payload);

        return [
            'event_id' => (string) Str::uuid(),
            'destination_app' => 'dosen-farmasi',
            'event_type' => $eventType,
            'event_version' => 1,
            'source_app' => 'kp-farmasi',
            'source_record_id' => $sourceRecordId,
            'source_revision' => max(1, $sourceRevision),
            'correlation_id' => null,
            'payload' => $payload,
            'status' => 'PENDING',
            'available_at' => now(),
        ];
    }

    private function coreLecturerId(Lecturer $lecturer): string
    {
        $lecturer->loadMissing('user');

        $id = $lecturer->core_lecturer_id ?: $lecturer->id;
        if (blank($id)) {
            throw new InvalidArgumentException('Dosen tidak memiliki identitas yang dapat dikirim.');
        }

        return (string) $id;
    }

    private function assertPayloadIsSafe(array $payload): void
    {
        foreach ($payload as $key => $value) {
            if (str_contains(strtolower((string) $key), 'password') || str_contains(strtolower((string) $key), 'token')) {
                throw new InvalidArgumentException('Payload integrasi tidak boleh berisi credential.');
            }

            if (is_string($value) && str_starts_with(strtolower(trim($value)), 'javascript:')) {
                throw new InvalidArgumentException('URL tidak aman tidak boleh dikirim.');
            }
        }
    }
}
