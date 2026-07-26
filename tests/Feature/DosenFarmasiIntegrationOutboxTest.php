<?php

namespace Tests\Feature;

use App\Jobs\DeliverIntegrationOutboxEvent;
use App\Models\FieldSupervisor;
use App\Models\IntegrationOutboxEvent;
use App\Models\KpAssignment;
use App\Models\KpDocument;
use App\Models\KpDocumentRequirement;
use App\Models\KpExamRequest;
use App\Models\KpFinalReport;
use App\Models\KpLogbook;
use App\Models\KpPeriod;
use App\Models\KpPlace;
use App\Models\KpPlaceQuota;
use App\Models\KpPlaceSelection;
use App\Models\KpRegistration;
use App\Models\KpReportGuidanceLog;
use App\Models\Lecturer;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use App\Services\DosenFarmasiIntegrationClient;
use App\Services\KpAssignmentService;
use App\Services\KpExamService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class DosenFarmasiIntegrationOutboxTest extends TestCase
{
    use RefreshDatabase;

    public function test_supervisor_assignment_writes_outbox_inside_business_transaction(): void
    {
        $this->seed(RoleSeeder::class);
        [$admin, $student, $lecturer, $field] = $this->fixturePeople();
        $assignment = $this->assignment(null, $field, $student, $admin);

        app(KpAssignmentService::class)->assignInternalSupervisor($admin, $assignment, $lecturer, 'Pilot M6');

        $assignment->refresh();
        $this->assertSame(1, (int) $assignment->integration_revision);
        $event = IntegrationOutboxEvent::query()->where('event_type', 'kp.supervisor.assigned')->firstOrFail();
        $this->assertSame('kp-farmasi', $event->source_app);
        $this->assertSame('KP-ASSIGNMENT-'.$assignment->id, $event->source_record_id);
        $this->assertSame((string) $lecturer->core_lecturer_id, $event->payload['lecturer_core_id']);
        $this->assertArrayNotHasKey('password', $event->payload);
    }

    public function test_business_rollback_rolls_back_outbox(): void
    {
        $this->seed(RoleSeeder::class);
        [$admin, $student, $lecturer, $field] = $this->fixturePeople();
        $assignment = $this->assignment(null, $field, $student, $admin);

        try {
            DB::transaction(function () use ($admin, $assignment, $lecturer): void {
                app(KpAssignmentService::class)->assignInternalSupervisor($admin, $assignment, $lecturer, 'Rollback');
                throw new RuntimeException('force rollback');
            });
        } catch (RuntimeException) {
            //
        }

        $this->assertSame(0, IntegrationOutboxEvent::query()->count());
    }

    public function test_exam_lifecycle_writes_assignment_schedule_reschedule_complete_and_cancel_events(): void
    {
        $this->seed(RoleSeeder::class);
        [$admin, $student, $supervisor, $field] = $this->fixturePeople();
        $examinerA = $this->lecturer('penguji-a@example.test', ['penguji'], '2001');
        $examinerB = $this->lecturer('penguji-b@example.test', ['penguji'], '2002');
        $assignment = $this->assignment($supervisor, $field, $student, $admin);
        $request = $this->examRequest($assignment, $student->user);

        $exam = app(KpExamService::class)->scheduleExam($admin, $request, [
            'examiner_ids' => [$examinerA->id, $examinerB->id],
            'exam_date' => now()->addWeek()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'mode' => 'hybrid',
            'room' => 'Ruang Sidang 1',
            'meeting_link' => 'https://meet.example.test/kp',
        ]);

        $this->assertSame(1, (int) $exam->fresh()->integration_revision);
        $this->assertSame(2, IntegrationOutboxEvent::query()->where('event_type', 'kp.examiner.assigned')->count());
        $this->assertSame(3, IntegrationOutboxEvent::query()->where('event_type', 'kp.exam.scheduled')->count());

        app(KpExamService::class)->rescheduleExam($admin, $exam, [
            'examiner_ids' => [$examinerA->id, $examinerB->id],
            'exam_date' => now()->addWeeks(2)->toDateString(),
            'start_time' => '13:00',
            'end_time' => '14:00',
            'mode' => 'offline',
            'room' => 'Ruang Sidang 2',
        ]);
        $this->assertSame(2, (int) $exam->fresh()->integration_revision);
        $this->assertSame(3, IntegrationOutboxEvent::query()->where('event_type', 'kp.exam.rescheduled')->count());

        app(KpExamService::class)->completeExam($admin, $exam, 'Selesai');
        $this->assertSame(3, (int) $exam->fresh()->integration_revision);
        $this->assertSame(3, IntegrationOutboxEvent::query()->where('event_type', 'kp.exam.completed')->count());

        $exam->update(['status' => 'dijadwalkan']);
        app(KpExamService::class)->cancelExam($admin, $exam, 'Batal');
        $this->assertSame(4, (int) $exam->fresh()->integration_revision);
        $this->assertSame(3, IntegrationOutboxEvent::query()->where('event_type', 'kp.exam.cancelled')->count());
    }

    public function test_delivery_job_classifies_success_duplicate_permanent_and_retryable_failures(): void
    {
        config([
            'dosen_farmasi.integration.base_url' => 'https://dosen.example.test',
            'dosen_farmasi.integration.token' => 'test-token',
        ]);
        Http::fakeSequence()
            ->push(['status' => 'accepted'], 202)
            ->push(['status' => 'duplicate'], 200)
            ->push(['message' => 'No'], 401)
            ->push(['message' => 'Try later'], 500)
            ->push(['message' => 'Rate limited'], 429);

        $accepted = $this->outboxEvent('kp.supervisor.assigned');
        (new DeliverIntegrationOutboxEvent($accepted->id))->handle(app(DosenFarmasiIntegrationClient::class));
        $this->assertSame(IntegrationOutboxEvent::STATUS_SENT, $accepted->fresh()->status);

        $duplicate = $this->outboxEvent('kp.supervisor.assigned');
        (new DeliverIntegrationOutboxEvent($duplicate->id))->handle(app(DosenFarmasiIntegrationClient::class));
        $this->assertSame(IntegrationOutboxEvent::STATUS_SENT, $duplicate->fresh()->status);

        $unauthorized = $this->outboxEvent('kp.supervisor.assigned');
        (new DeliverIntegrationOutboxEvent($unauthorized->id))->handle(app(DosenFarmasiIntegrationClient::class));
        $this->assertSame(IntegrationOutboxEvent::STATUS_FAILED, $unauthorized->fresh()->status);
        $this->assertSame('AUTHORIZATION_FAILED', $unauthorized->fresh()->last_error_code);

        $temporary = $this->outboxEvent('kp.supervisor.assigned');
        (new DeliverIntegrationOutboxEvent($temporary->id))->handle(app(DosenFarmasiIntegrationClient::class));
        $this->assertSame(IntegrationOutboxEvent::STATUS_PENDING, $temporary->fresh()->status);
        $this->assertSame('TEMPORARY_HTTP_500', $temporary->fresh()->last_error_code);

        $rateLimited = $this->outboxEvent('kp.supervisor.assigned');
        (new DeliverIntegrationOutboxEvent($rateLimited->id))->handle(app(DosenFarmasiIntegrationClient::class));
        $this->assertSame(IntegrationOutboxEvent::STATUS_PENDING, $rateLimited->fresh()->status);
        $this->assertSame('TEMPORARY_HTTP_429', $rateLimited->fresh()->last_error_code);
    }

    public function test_delivery_command_dry_run_does_not_mutate_events(): void
    {
        $event = $this->outboxEvent('kp.supervisor.assigned');

        $this->artisan('kp:deliver-integration-outbox --dry-run')
            ->expectsOutput('Eligible events: 1')
            ->assertSuccessful();

        $this->assertSame(IntegrationOutboxEvent::STATUS_PENDING, $event->fresh()->status);
        $this->assertSame(0, (int) $event->fresh()->attempt_count);
    }

    public function test_prune_outbox_defaults_to_dry_run_and_retains_pending_failed_events(): void
    {
        $sent = $this->outboxEvent('kp.supervisor.assigned');
        $pending = $this->outboxEvent('kp.exam.scheduled');
        $failed = $this->outboxEvent('kp.exam.completed');

        IntegrationOutboxEvent::query()->whereKey($sent->id)->update([
            'status' => IntegrationOutboxEvent::STATUS_SENT,
            'created_at' => now()->subDays(120),
            'updated_at' => now()->subDays(120),
            'sent_at' => now()->subDays(120),
        ]);
        IntegrationOutboxEvent::query()->whereKey($failed->id)->update([
            'status' => IntegrationOutboxEvent::STATUS_FAILED,
            'created_at' => now()->subDays(120),
            'updated_at' => now()->subDays(120),
        ]);

        $this->artisan('kp:prune-integration-outbox')
            ->expectsOutputToContain('Mode: dry-run')
            ->expectsOutputToContain('Terminal rows eligible for prune: 1')
            ->expectsOutputToContain('No changes applied.')
            ->assertSuccessful();

        $this->assertDatabaseHas('integration_outbox_events', ['id' => $sent->id]);
        $this->assertDatabaseHas('integration_outbox_events', ['id' => $pending->id, 'status' => IntegrationOutboxEvent::STATUS_PENDING]);
        $this->assertDatabaseHas('integration_outbox_events', ['id' => $failed->id, 'status' => IntegrationOutboxEvent::STATUS_FAILED]);
    }

    public function test_prune_outbox_requires_confirmation_and_can_recover_orphans(): void
    {
        $sent = $this->outboxEvent('kp.supervisor.assigned');
        $processing = $this->outboxEvent('kp.exam.scheduled');

        IntegrationOutboxEvent::query()->whereKey($sent->id)->update([
            'status' => IntegrationOutboxEvent::STATUS_SENT,
            'created_at' => now()->subDays(120),
            'updated_at' => now()->subDays(120),
            'sent_at' => now()->subDays(120),
        ]);
        IntegrationOutboxEvent::query()->whereKey($processing->id)->update([
            'status' => IntegrationOutboxEvent::STATUS_PROCESSING,
            'locked_at' => now()->subHour(),
        ]);

        $this->artisan('kp:prune-integration-outbox --execute')
            ->expectsOutputToContain('Execute refused: missing --confirm-execute.')
            ->assertFailed();

        $this->artisan('kp:prune-integration-outbox --execute --confirm-execute --recover-orphans')
            ->expectsOutputToContain('Terminal rows pruned: 1')
            ->expectsOutputToContain('Orphan rows recovered: 1')
            ->assertSuccessful();

        $this->assertDatabaseMissing('integration_outbox_events', ['id' => $sent->id]);
        $this->assertDatabaseHas('integration_outbox_events', [
            'id' => $processing->id,
            'status' => IntegrationOutboxEvent::STATUS_PENDING,
            'last_error_code' => 'ORPHAN_RECOVERED',
        ]);
    }

    private function outboxEvent(string $type): IntegrationOutboxEvent
    {
        return IntegrationOutboxEvent::query()->create([
            'event_id' => (string) Str::uuid(),
            'destination_app' => 'dosen-farmasi',
            'event_type' => $type,
            'event_version' => 1,
            'source_app' => 'kp-farmasi',
            'source_record_id' => 'KP-TEST-1',
            'source_revision' => 1,
            'payload' => ['lecturer_core_id' => 'CORE-DOSEN-1', 'student_name' => 'Mahasiswa Test'],
            'status' => IntegrationOutboxEvent::STATUS_PENDING,
            'available_at' => now(),
        ]);
    }

    private function fixturePeople(): array
    {
        $admin = $this->user('admin@example.test', ['admin', 'koordinator_kp']);
        $studentUser = $this->user('student@example.test', ['mahasiswa']);
        $student = Student::query()->create(['user_id' => $studentUser->id, 'nim' => '2210631230001', 'study_program' => 'Farmasi', 'semester' => 7, 'status' => 'active']);
        $lecturer = $this->lecturer('supervisor@example.test', ['pembimbing_dalam'], '1001');
        $fieldUser = $this->user('field@example.test', ['pembimbing_lapangan']);
        $field = FieldSupervisor::query()->create(['user_id' => $fieldUser->id, 'institution_name' => 'Apotek Test', 'position' => 'Supervisor', 'status' => 'active']);

        return [$admin, $student, $lecturer, $field];
    }

    private function assignment(?Lecturer $lecturer, FieldSupervisor $field, Student $student, User $admin): KpAssignment
    {
        $period = KpPeriod::query()->create(['name' => 'KP 2026', 'academic_year' => '2026/2027', 'semester' => 'ganjil', 'status' => 'dibuka']);
        $place = KpPlace::query()->create(['name' => 'Apotek Test', 'type' => 'apotek', 'status' => 'aktif']);
        $quota = KpPlaceQuota::query()->create(['kp_period_id' => $period->id, 'kp_place_id' => $place->id, 'quota' => 5, 'is_open' => true]);
        $requirement = KpDocumentRequirement::query()->create(['kp_period_id' => $period->id, 'name' => 'KRS', 'is_required' => true, 'status' => 'aktif']);
        $registration = KpRegistration::query()->create(['kp_period_id' => $period->id, 'student_id' => $student->id, 'status' => 'terverifikasi']);
        KpDocument::query()->create(['kp_registration_id' => $registration->id, 'kp_document_requirement_id' => $requirement->id, 'file_path' => 'x.pdf', 'status' => 'disetujui']);
        $selection = KpPlaceSelection::query()->create([
            'kp_period_id' => $period->id,
            'kp_registration_id' => $registration->id,
            'student_id' => $student->id,
            'kp_place_id' => $place->id,
            'kp_place_quota_id' => $quota->id,
            'selected_at' => now(),
            'selected_by' => $student->user_id,
            'status' => 'aktif',
            'active_key' => $period->id.'-'.$student->id,
        ]);

        return KpAssignment::query()->create([
            'kp_period_id' => $period->id,
            'kp_registration_id' => $registration->id,
            'kp_place_selection_id' => $selection->id,
            'student_id' => $student->id,
            'kp_place_id' => $place->id,
            'internal_supervisor_id' => $lecturer?->id,
            'field_supervisor_id' => $field->id,
            'status' => $lecturer ? 'aktif' : 'menunggu_pembimbing',
            'assigned_by' => $admin->id,
            'assigned_at' => now(),
            'active_key' => $period->id.'-'.$student->id,
        ]);
    }

    private function examRequest(KpAssignment $assignment, User $studentUser): KpExamRequest
    {
        KpLogbook::query()->create([
            'kp_assignment_id' => $assignment->id,
            'activity_date' => now()->toDateString(),
            'activity_title' => 'Kegiatan',
            'start_time' => '08:00',
            'end_time' => '12:00',
            'activity_description' => 'Kegiatan KP',
            'learning_outcome' => 'Belajar',
            'status' => 'disetujui',
        ]);
        for ($i = 1; $i <= 8; $i++) {
            KpReportGuidanceLog::query()->create(['kp_assignment_id' => $assignment->id, 'reviewer_type' => KpReportGuidanceLog::REVIEWER_INTERNAL, 'guidance_date' => now()->subDays($i)->toDateString(), 'topic' => 'Bimbingan Dalam '.$i, 'status' => 'disetujui']);
            KpReportGuidanceLog::query()->create(['kp_assignment_id' => $assignment->id, 'reviewer_type' => KpReportGuidanceLog::REVIEWER_FIELD, 'guidance_date' => now()->subDays($i)->toDateString(), 'topic' => 'Bimbingan Lapangan '.$i, 'status' => 'disetujui']);
        }
        KpFinalReport::query()->create([
            'kp_assignment_id' => $assignment->id,
            'current_version' => 1,
            'status' => 'disetujui',
            'final_document_url' => 'https://drive.google.com/file/d/kp-final-report/view',
            'final_document_label' => 'Laporan final siap sidang',
            'internal_review_status' => 'disetujui',
            'field_review_status' => 'disetujui',
        ]);

        return KpExamRequest::query()->create(['kp_assignment_id' => $assignment->id, 'requested_by' => $studentUser->id, 'status' => 'disetujui', 'submitted_at' => now()]);
    }

    private function lecturer(string $email, array $roles, string $coreLecturerId): Lecturer
    {
        $user = $this->user($email, $roles);

        return Lecturer::query()->create(['user_id' => $user->id, 'nidn_nip' => $coreLecturerId, 'status' => 'active', 'core_lecturer_id' => $coreLecturerId]);
    }

    private function user(string $email, array $roles): User
    {
        $user = User::query()->create(['name' => 'User Test', 'email' => $email, 'password' => Hash::make('password'), 'status' => 'active']);
        $user->roles()->sync(Role::query()->whereIn('name', $roles)->pluck('id'));

        return $user;
    }
}
