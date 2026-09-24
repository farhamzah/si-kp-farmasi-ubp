<?php

namespace Tests\Feature;

use App\Models\FieldSupervisor;
use App\Models\KpAssessmentComponent;
use App\Models\KpAssignment;
use App\Models\KpExam;
use App\Models\KpDocumentSignature;
use App\Models\KpExamMinute;
use App\Models\KpExamRequest;
use App\Models\KpFinalReport;
use App\Models\KpFinalScore;
use App\Models\KpLogbook;
use App\Models\KpPeriod;
use App\Models\KpPlace;
use App\Models\KpPostExamReport;
use App\Models\KpRegistration;
use App\Models\KpReportGuidanceLog;
use App\Models\KpScore;
use App\Models\Lecturer;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use App\Services\KpAssessmentService;
use App\Services\KpExamMinuteService;
use App\Support\KpScoreCalculator;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class KpAssessmentAndFinalScoreTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $koordinator;

    private User $mahasiswa;

    private User $supervisorUser;

    private User $fieldUser;

    private User $examinerUser;

    private Lecturer $supervisor;

    private Lecturer $examiner;

    private FieldSupervisor $field;

    private KpAssignment $assignment;

    private KpExam $exam;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->admin = $this->makeUser('admin-score@test.local', ['admin']);
        $this->koordinator = $this->makeUser('koor-score@test.local', ['koordinator_kp']);
        $this->mahasiswa = $this->makeUser('student-score@test.local', ['mahasiswa']);
        $student = Student::create(['user_id' => $this->mahasiswa->id, 'nim' => '2210631231001', 'study_program' => 'Farmasi', 'semester' => 6, 'phone' => '081234567890', 'status' => 'active']);
        $this->mahasiswa->forceFill(['profile_completed' => true])->save();
        $this->supervisorUser = $this->makeUser('supervisor-score@test.local', ['pembimbing_dalam']);
        $this->supervisor = Lecturer::create(['user_id' => $this->supervisorUser->id, 'nidn_nip' => '881101', 'status' => 'active']);
        $this->fieldUser = $this->makeUser('field-score@test.local', ['pembimbing_lapangan']);
        $this->field = FieldSupervisor::create(['user_id' => $this->fieldUser->id, 'institution_name' => 'Apotek Sehat', 'position' => 'Supervisor', 'status' => 'active']);
        $this->examinerUser = $this->makeUser('examiner-score@test.local', ['penguji']);
        $this->examiner = Lecturer::create(['user_id' => $this->examinerUser->id, 'nidn_nip' => '881102', 'status' => 'active']);
        $this->assignment = $this->makeAssignment($student);
        $request = KpExamRequest::create([
            'kp_assignment_id' => $this->assignment->id,
            'requested_by' => $this->mahasiswa->id,
            'status' => 'dijadwalkan',
            'submitted_at' => now(),
            'payment_proof_url' => 'https://drive.google.com/example-payment-proof',
            'payment_proof_status' => KpExamRequest::PAYMENT_PROOF_APPROVED,
            'payment_proof_reviewed_by' => $this->koordinator->id,
            'payment_proof_reviewed_at' => now(),
        ]);
        $this->exam = KpExam::create(['kp_exam_request_id' => $request->id, 'kp_assignment_id' => $this->assignment->id, 'supervisor_id' => $this->supervisor->id, 'examiner_id' => $this->examiner->id, 'exam_date' => now()->toDateString(), 'start_time' => '09:00', 'end_time' => '10:00', 'mode' => 'offline', 'room' => 'R1', 'status' => 'dijadwalkan']);
        KpPostExamReport::create([
            'kp_assignment_id' => $this->assignment->id,
            'status' => KpPostExamReport::STATUS_APPROVED,
            'document_url' => 'https://drive.google.com/example-post-exam-report',
            'submitted_at' => now(),
            'reviewed_by' => $this->koordinator->id,
            'reviewed_at' => now(),
            'approved_at' => now(),
        ]);
    }

    public function test_admin_and_koordinator_can_manage_assessment_components(): void
    {
        $period = $this->assignment->period;

        $this->actingAs($this->admin)->withSession(['active_role' => 'admin'])
            ->get('/management/assessment-components')
            ->assertOk();

        $this->actingAs($this->koordinator)->withSession(['active_role' => 'koordinator_kp'])
            ->post('/management/assessment-components', [
                'kp_period_id' => $period->id,
                'assessor_type' => 'pembimbing_dalam',
                'component_name' => 'Kualitas laporan',
                'weight' => 30,
                'max_score' => 100,
                'status' => 'aktif',
                'is_required' => 1,
            ])->assertRedirect();

        $this->assertDatabaseHas('kp_assessment_components', ['component_name' => 'Kualitas laporan']);
        $this->assertDatabaseHas('kp_score_logs', ['action' => 'component_created']);
    }

    public function test_default_assessment_rubric_is_created_per_period(): void
    {
        app(KpAssessmentService::class)->ensureDefaultComponents($this->assignment->period, $this->koordinator);

        $this->assertDatabaseHas('kp_assessment_components', ['kp_period_id' => $this->assignment->kp_period_id, 'assessor_type' => 'pembimbing_lapangan', 'component_name' => 'Komunikasi dan Kerjasama', 'weight' => 30]);
        $this->assertDatabaseHas('kp_assessment_components', ['kp_period_id' => $this->assignment->kp_period_id, 'assessor_type' => 'pembimbing_dalam', 'component_name' => 'Laporan KP', 'weight' => 50]);
        $this->assertDatabaseHas('kp_assessment_components', ['kp_period_id' => $this->assignment->kp_period_id, 'assessor_type' => 'penguji', 'component_name' => 'Penguasaan Materi KP', 'weight' => 50]);
    }

    public function test_coordinator_can_open_score_monitoring_detail(): void
    {
        $this->actingAs($this->koordinator)
            ->withSession(['active_role' => 'koordinator_kp'])
            ->get('/management/scores/'.$this->assignment->id)
            ->assertOk()
            ->assertSee($this->mahasiswa->name)
            ->assertSee('Koreksi Nilai Koordinator');
    }

    public function test_each_assessor_can_score_only_their_own_assignment_and_invalid_score_is_rejected(): void
    {
        [$internal] = $this->components();
        $this->makeReadyForInternalAssessment($this->assignment);
        $otherUser = $this->makeUser('other-supervisor-score@test.local', ['pembimbing_dalam']);
        Lecturer::create(['user_id' => $otherUser->id, 'nidn_nip' => '881103', 'status' => 'active']);

        $payload = ['scores' => [['component_id' => $internal->id, 'score' => 90, 'note' => 'Baik']]];
        $this->actingAs($this->supervisorUser)->withSession(['active_role' => 'pembimbing_dalam'])
            ->post('/pembimbing-dalam/penilaian/'.$this->assignment->id.'/save', $payload)
            ->assertRedirect();

        $this->assertDatabaseHas('kp_scores', ['kp_assignment_id' => $this->assignment->id, 'score' => 90, 'weighted_score' => 27]);

        $this->actingAs($otherUser)->withSession(['active_role' => 'pembimbing_dalam'])
            ->post('/pembimbing-dalam/penilaian/'.$this->assignment->id.'/save', $payload)
            ->assertForbidden();

        $this->actingAs($this->supervisorUser)->withSession(['active_role' => 'pembimbing_dalam'])
            ->post('/pembimbing-dalam/penilaian/'.$this->assignment->id.'/save', ['scores' => [['component_id' => $internal->id, 'score' => 120]]])
            ->assertSessionHasErrors('scores.0.score');
    }

    public function test_field_supervisor_and_examiner_can_score_assigned_records_only(): void
    {
        [, $fieldComponent, $examinerComponent] = $this->components();
        $this->makeReadyForFieldAssessment($this->assignment);
        $otherStudentUser = $this->makeUser('other-field-score-student@test.local', ['mahasiswa']);
        $otherStudentUser->update(['name' => 'Mahasiswa Nilai Lain']);
        $otherStudent = Student::create(['user_id' => $otherStudentUser->id, 'nim' => '2210631231888', 'study_program' => 'Farmasi', 'semester' => 6, 'phone' => '081234567890', 'status' => 'active']);
        $otherStudentUser->forceFill(['profile_completed' => true])->save();
        $otherAssignment = $this->makeAssignment($otherStudent);
        $otherAssignment->update(['field_supervisor_id' => null, 'status' => 'menunggu_pembimbing']);

        $this->actingAs($this->fieldUser)
            ->withSession(['active_role' => 'pembimbing_lapangan'])
            ->get('/pembimbing-lapangan/penilaian')
            ->assertOk()
            ->assertSee($this->mahasiswa->name)
            ->assertDontSee('Mahasiswa Nilai Lain');

        $this->actingAs($this->fieldUser)->withSession(['active_role' => 'pembimbing_lapangan'])
            ->post('/pembimbing-lapangan/penilaian/'.$this->assignment->id.'/save', ['scores' => [['component_id' => $fieldComponent->id, 'score' => 80]]])
            ->assertRedirect();

        $this->actingAs($this->examinerUser)->withSession(['active_role' => 'penguji'])
            ->post('/penguji/penilaian/'.$this->exam->id.'/save', ['scores' => [['component_id' => $examinerComponent->id, 'score' => 85]]])
            ->assertRedirect();

        $this->assertDatabaseHas('kp_scores', ['assessor_type' => 'pembimbing_lapangan', 'score' => 80]);
        $this->assertDatabaseHas('kp_scores', ['assessor_type' => 'penguji', 'score' => 85]);

        $otherExaminer = $this->makeUser('other-examiner-score@test.local', ['penguji']);
        Lecturer::create(['user_id' => $otherExaminer->id, 'nidn_nip' => '881104', 'status' => 'active']);
        $this->actingAs($otherExaminer)->withSession(['active_role' => 'penguji'])
            ->post('/penguji/penilaian/'.$this->exam->id.'/save', ['scores' => [['component_id' => $examinerComponent->id, 'score' => 90]]])
            ->assertForbidden();
    }

    public function test_exam_chair_completes_score_and_minutes_from_the_same_assessment_page(): void
    {
        [, , $examinerComponent] = $this->components();
        $this->exam->update([
            'chair_lecturer_id' => $this->examiner->id,
            'minutes_sequence' => 1,
            'minutes_number' => '001/BA-SKP/FF-UBP/IX/2026',
        ]);

        $this->actingAs($this->examinerUser)->withSession(['active_role' => 'penguji'])
            ->get('/penguji/penilaian/'.$this->exam->id)
            ->assertOk()
            ->assertSee('Submit Nilai dan Isi Berita Acara')
            ->assertSee('Submit nilai terlebih dahulu');

        $this->actingAs($this->examinerUser)->withSession(['active_role' => 'penguji'])
            ->post('/penguji/penilaian/'.$this->exam->id.'/save', [
                'scores' => [['component_id' => $examinerComponent->id, 'score' => 88]],
            ])
            ->assertRedirect();

        $this->actingAs($this->examinerUser)->withSession(['active_role' => 'penguji'])
            ->post('/penguji/penilaian/'.$this->exam->id.'/submit')
            ->assertRedirect('/penguji/penilaian/'.$this->exam->id.'#berita-acara');

        $this->actingAs($this->examinerUser)->withSession(['active_role' => 'penguji'])
            ->get('/penguji/penilaian/'.$this->exam->id)
            ->assertOk()
            ->assertSee('Tutup Sidang dan Buat Berita Acara')
            ->assertDontSee('Submit nilai terlebih dahulu');

        $this->actingAs($this->examinerUser)->withSession(['active_role' => 'penguji'])
            ->post('/penguji/jadwal-sidang/'.$this->exam->id.'/tutup', [
                'result' => 'lulus_revisi',
                'actual_start_time' => '09:05',
                'actual_end_time' => '10:10',
                'revision_deadline' => now()->addWeek()->toDateString(),
                'notes' => 'Perbaiki format laporan.',
                'attendance' => ['mahasiswa', 'ketua_sidang', 'tim_penguji'],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('kp_exam_minutes', [
            'kp_exam_id' => $this->exam->id,
            'chair_lecturer_id' => $this->examiner->id,
            'result' => 'lulus_revisi',
        ]);
    }

    public function test_chair_without_examiner_assignment_can_open_minutes_section_without_score_form(): void
    {
        $this->supervisorUser->roles()->syncWithoutDetaching(Role::where('name', 'penguji')->value('id'));
        $this->exam->update(['chair_lecturer_id' => $this->supervisor->id]);

        $this->actingAs($this->supervisorUser)->withSession(['active_role' => 'penguji'])
            ->get('/penguji/penilaian')
            ->assertOk()
            ->assertSee($this->mahasiswa->name)
            ->assertSee('Ketua Sidang');

        $this->actingAs($this->supervisorUser)->withSession(['active_role' => 'penguji'])
            ->get('/penguji/penilaian/'.$this->exam->id)
            ->assertOk()
            ->assertSee('tidak tercatat sebagai anggota tim penguji')
            ->assertSee('Tutup Sidang dan Buat Berita Acara')
            ->assertDontSee('Simpan Draft');
    }

    public function test_multiple_assigned_examiners_can_score_and_finalization_waits_for_each_examiner(): void
    {
        [, $field, $examiner] = $this->components();
        $secondExaminerUser = $this->makeUser('second-examiner-score@test.local', ['penguji']);
        $secondExaminer = Lecturer::create(['user_id' => $secondExaminerUser->id, 'nidn_nip' => '881105', 'status' => 'active']);
        $this->exam->examiners()->sync([
            $this->examiner->id => ['sort_order' => 1],
            $secondExaminer->id => ['sort_order' => 2],
        ]);

        $this->saveAndSubmit($this->supervisorUser, 'pembimbing-dalam', $this->assignment->id, $this->components()[0], 90);
        $this->saveAndSubmit($this->fieldUser, 'pembimbing-lapangan', $this->assignment->id, $field, 80);
        KpLogbook::create([
            'kp_assignment_id' => $this->assignment->id,
            'activity_date' => now()->toDateString(),
            'activity_title' => 'Kegiatan KP',
            'activity_description' => 'Kegiatan harian.',
            'status' => 'disetujui',
            'submitted_at' => now(),
            'validated_by' => $this->fieldUser->id,
            'validated_at' => now(),
        ]);

        $this->actingAs($this->examinerUser)->withSession(['active_role' => 'penguji'])
            ->post('/penguji/penilaian/'.$this->exam->id.'/save', ['scores' => [['component_id' => $examiner->id, 'score' => 80]]])
            ->assertRedirect();
        $this->actingAs($this->examinerUser)->withSession(['active_role' => 'penguji'])
            ->post('/penguji/penilaian/'.$this->exam->id.'/submit')
            ->assertRedirect();

        $this->actingAs($this->koordinator)->withSession(['active_role' => 'koordinator_kp'])
            ->post('/management/scores/'.$this->assignment->id.'/finalize')
            ->assertSessionHasErrors('final_score');

        $this->actingAs($secondExaminerUser)->withSession(['active_role' => 'penguji'])
            ->post('/penguji/penilaian/'.$this->exam->id.'/save', ['scores' => [['component_id' => $examiner->id, 'score' => 100]]])
            ->assertRedirect();
        $this->actingAs($secondExaminerUser)->withSession(['active_role' => 'penguji'])
            ->post('/penguji/penilaian/'.$this->exam->id.'/submit')
            ->assertRedirect();

        $this->assertDatabaseHas('kp_scores', ['assessor_type' => 'penguji', 'assessor_user_id' => $this->examinerUser->id, 'score' => 80]);
        $this->assertDatabaseHas('kp_scores', ['assessor_type' => 'penguji', 'assessor_user_id' => $secondExaminerUser->id, 'score' => 100]);

        $this->actingAs($this->koordinator)->withSession(['active_role' => 'koordinator_kp'])
            ->post('/management/scores/'.$this->assignment->id.'/finalize')
            ->assertRedirect();
    }

    public function test_final_score_requires_complete_submitted_scores_then_can_be_finalized_and_published(): void
    {
        [$internal, $field, $examiner] = $this->components();

        $this->actingAs($this->koordinator)->withSession(['active_role' => 'koordinator_kp'])
            ->post('/management/scores/'.$this->assignment->id.'/finalize')
            ->assertSessionHasErrors('final_score');

        $this->saveAndSubmit($this->supervisorUser, 'pembimbing-dalam', $this->assignment->id, $internal, 90);
        $this->saveAndSubmit($this->fieldUser, 'pembimbing-lapangan', $this->assignment->id, $field, 80);
        KpLogbook::create([
            'kp_assignment_id' => $this->assignment->id,
            'activity_date' => now()->toDateString(),
            'activity_title' => 'Kegiatan KP',
            'activity_description' => 'Kegiatan harian.',
            'status' => 'disetujui',
            'submitted_at' => now(),
            'validated_by' => $this->fieldUser->id,
            'validated_at' => now(),
        ]);
        $this->actingAs($this->examinerUser)->withSession(['active_role' => 'penguji'])
            ->post('/penguji/penilaian/'.$this->exam->id.'/save', ['scores' => [['component_id' => $examiner->id, 'score' => 85]]])
            ->assertRedirect();
        $this->actingAs($this->examinerUser)->withSession(['active_role' => 'penguji'])
            ->post('/penguji/penilaian/'.$this->exam->id.'/submit')
            ->assertRedirect();

        $this->actingAs($this->koordinator)->withSession(['active_role' => 'koordinator_kp'])
            ->post('/management/scores/'.$this->assignment->id.'/finalize', ['note' => 'Final.'])
            ->assertRedirect();

        $final = KpFinalScore::firstOrFail();
        $this->assertSame('locked', $final->status);
        $this->assertSame('A', $final->final_grade);
        $this->assertSame('86.50', (string) $final->final_score);

        $this->actingAs($this->koordinator)->withSession(['active_role' => 'koordinator_kp'])
            ->post('/management/final-scores/'.$final->id.'/publish')
            ->assertRedirect();

        $this->assignment->period->update(['score_visible_to_students' => true]);
        KpFinalReport::updateOrCreate(
            ['kp_assignment_id' => $this->assignment->id],
            [
                'status' => 'disetujui',
                'internal_review_status' => 'disetujui',
                'internal_guidance_completed_by' => $this->supervisorUser->id,
                'internal_guidance_completed_at' => now(),
                'internal_guidance_completion_note' => 'Bimbingan dalam selesai untuk tampilan nilai.',
                'field_review_status' => 'disetujui',
                'final_document_url' => 'https://drive.google.com/example-final-report',
                'submitted_at' => now(),
                'approved_at' => now(),
            ]
        );
        $this->exam->update(['status' => 'selesai']);

        $this->actingAs($this->mahasiswa)->withSession(['active_role' => 'mahasiswa'])
            ->get('/mahasiswa/nilai')
            ->assertOk()
            ->assertSee('Nilai Akhir KP')
            ->assertSee('A');
    }

    public function test_attendance_score_uses_assignment_work_period_and_workday_pattern(): void
    {
        $this->assignment->update([
            'started_at' => '2026-07-01',
            'ended_at' => '2026-07-07',
            'workday_pattern' => 'senin_sabtu',
        ]);

        foreach (['2026-07-01', '2026-07-02', '2026-07-04'] as $date) {
            KpLogbook::create([
                'kp_assignment_id' => $this->assignment->id,
                'activity_date' => $date,
                'activity_title' => 'Kegiatan KP '.$date,
                'activity_description' => 'Kegiatan harian.',
                'status' => 'disetujui',
                'submitted_at' => now(),
                'validated_by' => $this->fieldUser->id,
                'validated_at' => now(),
            ]);
        }

        $breakdown = app(KpScoreCalculator::class)->breakdown($this->assignment->fresh());

        $this->assertSame(6, $breakdown['sections']['kehadiran']['meta']['workdays']);
        $this->assertSame(3, $breakdown['sections']['kehadiran']['meta']['approved_logbook_days']);
        $this->assertSame(50.0, $breakdown['sections']['kehadiran']['score']);
    }

    public function test_published_score_cannot_be_changed_by_assessor_and_can_be_unlocked_by_management(): void
    {
        [$internal] = $this->components();
        $this->makeReadyForInternalAssessment($this->assignment);
        KpFinalScore::create(['kp_assignment_id' => $this->assignment->id, 'final_score' => 90, 'final_grade' => 'A', 'status' => 'published', 'published_at' => now()]);

        $this->actingAs($this->supervisorUser)->withSession(['active_role' => 'pembimbing_dalam'])
            ->post('/pembimbing-dalam/penilaian/'.$this->assignment->id.'/save', ['scores' => [['component_id' => $internal->id, 'score' => 95]]])
            ->assertSessionHasErrors('final_score');

        $final = KpFinalScore::first();
        $this->actingAs($this->admin)->withSession(['active_role' => 'admin'])
            ->post('/management/final-scores/'.$final->id.'/unlock', ['reason' => 'Koreksi nilai.'])
            ->assertRedirect();

        $this->assertSame('calculated', $final->fresh()->status);
        $this->assertDatabaseHas('kp_score_logs', ['action' => 'final_score_unlocked']);
    }

    public function test_coordinator_can_correct_wrong_examiner_after_published_minutes(): void
    {
        [, , $component] = $this->components();
        $retainedUser = $this->makeUser('retained-examiner@test.local', ['penguji']);
        $retained = Lecturer::create(['user_id' => $retainedUser->id, 'nidn_nip' => '881120', 'status' => 'active']);
        $replacementUser = $this->makeUser('replacement-examiner@test.local', ['penguji']);
        $replacement = Lecturer::create(['user_id' => $replacementUser->id, 'nidn_nip' => '881121', 'status' => 'active']);
        $this->exam->examiners()->sync([
            $this->examiner->id => ['sort_order' => 1],
            $retained->id => ['sort_order' => 2],
        ]);

        $wrongScore = KpScore::create([
            'kp_assignment_id' => $this->assignment->id,
            'kp_exam_id' => $this->exam->id,
            'kp_assessment_component_id' => $component->id,
            'assessor_user_id' => $this->examinerUser->id,
            'assessor_type' => 'penguji',
            'score' => 70,
            'weighted_score' => 70,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
        $retainedScore = KpScore::create([
            'kp_assignment_id' => $this->assignment->id,
            'kp_exam_id' => $this->exam->id,
            'kp_assessment_component_id' => $component->id,
            'assessor_user_id' => $retainedUser->id,
            'assessor_type' => 'penguji',
            'score' => 90,
            'weighted_score' => 90,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
        $this->exam->update([
            'chair_lecturer_id' => $this->supervisor->id,
            'minutes_sequence' => 1,
            'minutes_number' => '001/BA-SKP/FF-UBP/IX/2026',
            'status' => 'selesai',
        ]);
        $minute = KpExamMinute::create([
            'kp_exam_id' => $this->exam->id,
            'minutes_number' => $this->exam->minutes_number,
            'chair_lecturer_id' => $this->supervisor->id,
            'status' => 'terbit',
            'result' => 'lulus',
            'actual_start_time' => '09:00',
            'actual_end_time' => '10:00',
            'verification_code' => 'WRONGEXAMINER001',
            'closed_by' => $this->supervisorUser->id,
            'closed_at' => now(),
            'published_by' => $this->koordinator->id,
            'published_at' => now(),
        ]);
        KpFinalScore::create([
            'kp_assignment_id' => $this->assignment->id,
            'final_score' => 82,
            'final_grade' => 'B',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->actingAs($this->koordinator)->withSession(['active_role' => 'koordinator_kp'])
            ->post('/management/exams/'.$this->exam->id.'/correct-examiners', [
                'examiner_ids' => [$retained->id, $replacement->id],
                'reason' => 'Penguji pertama salah diplot oleh koordinator.',
            ])->assertRedirect('/management/exams/'.$this->exam->id);

        $this->exam->refresh();
        $this->assertSame('dijadwalkan', $this->exam->status);
        $this->assertEqualsCanonicalizing([$retained->id, $replacement->id], $this->exam->examinerIds());
        $this->assertDatabaseMissing('kp_scores', ['id' => $wrongScore->id]);
        $this->assertDatabaseHas('kp_scores', ['id' => $retainedScore->id, 'status' => 'submitted']);
        $this->assertDatabaseMissing('kp_exam_minutes', ['id' => $minute->id]);
        $this->assertDatabaseHas('kp_score_logs', ['action' => 'examiner_score_voided', 'user_id' => $this->koordinator->id]);
        $this->assertDatabaseHas('kp_score_logs', ['action' => 'final_score_reopened_for_examiner_correction']);
        $this->assertDatabaseHas('kp_exam_logs', ['action' => 'examiner_assignment_corrected']);
        $final = KpFinalScore::where('kp_assignment_id', $this->assignment->id)->firstOrFail();
        $this->assertSame('draft', $final->status);
        $this->assertNull($final->final_score);

        $this->get('/berita-acara-sidang/verifikasi/WRONGEXAMINER001')
            ->assertOk()
            ->assertSee('Dokumen Dibatalkan')
            ->assertSee('Penguji pertama salah diplot oleh koordinator.');
        $this->actingAs($replacementUser)->withSession(['active_role' => 'penguji'])
            ->get('/penguji/penilaian/'.$this->exam->id)
            ->assertOk();
        $this->actingAs($this->examinerUser)->withSession(['active_role' => 'penguji'])
            ->get('/penguji/penilaian/'.$this->exam->id)
            ->assertForbidden();
    }

    public function test_coordinator_can_rebuild_published_minutes_with_individual_signatures(): void
    {
        $this->exam->update([
            'chair_lecturer_id' => $this->supervisor->id,
            'minutes_sequence' => 2,
            'minutes_number' => '002/BA-SKP/FF-UBP/IX/2026',
            'status' => 'selesai',
        ]);
        $this->exam->examiners()->sync([$this->examiner->id => ['sort_order' => 1]]);
        $minute = KpExamMinute::create([
            'kp_exam_id' => $this->exam->id,
            'minutes_number' => $this->exam->minutes_number,
            'chair_lecturer_id' => $this->supervisor->id,
            'status' => 'terbit',
            'result' => 'lulus',
            'actual_start_time' => '09:00',
            'actual_end_time' => '10:00',
            'verification_code' => 'REBUILDMINUTE001',
            'closed_by' => $this->supervisorUser->id,
            'closed_at' => now(),
            'published_by' => $this->koordinator->id,
            'published_at' => now(),
        ]);

        app(KpExamMinuteService::class)->rebuild($minute, $this->koordinator, 'Perbaikan QR penandatangan.');

        $this->assertSame(2, $minute->fresh()->document_version);
        $this->assertDatabaseHas('kp_document_signatures', [
            'document_type' => KpDocumentSignature::DOCUMENT_MINUTE,
            'document_id' => $minute->id,
            'signer_key' => 'lecturer_'.$this->supervisor->id,
            'role_label' => 'Ketua Sidang',
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('kp_document_signatures', [
            'document_type' => KpDocumentSignature::DOCUMENT_MINUTE,
            'document_id' => $minute->id,
            'signer_key' => 'lecturer_'.$this->examiner->id,
            'role_label' => 'Anggota Penguji',
            'status' => 'active',
        ]);
    }

    public function test_management_can_override_scores_before_finalization(): void
    {
        [$internal, $field, $examiner] = $this->components();

        $this->actingAs($this->koordinator)->withSession(['active_role' => 'koordinator_kp'])
            ->post('/management/scores/'.$this->assignment->id.'/override', [
                'attendance_score' => 100,
                'attendance_note' => 'Kehadiran lengkap.',
                'scores' => [
                    ['component_id' => $internal->id, 'score' => 90, 'note' => 'Override pembimbing dalam.'],
                    ['component_id' => $field->id, 'score' => 80, 'note' => 'Override lapangan.'],
                    ['component_id' => $examiner->id, 'score' => 85, 'note' => 'Override penguji.'],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('kp_final_scores', ['kp_assignment_id' => $this->assignment->id, 'attendance_score_override' => 100]);
        $this->assertDatabaseHas('kp_scores', ['kp_assignment_id' => $this->assignment->id, 'assessor_type' => 'pembimbing_dalam', 'score' => 90, 'status' => 'submitted']);
        $this->assertSame('86.50', (string) KpFinalScore::where('kp_assignment_id', $this->assignment->id)->firstOrFail()->final_score);
    }

    public function test_supervisor_assessments_are_blocked_until_previous_review_steps_are_complete(): void
    {
        [$internal, $field] = $this->components();

        $this->actingAs($this->supervisorUser)->withSession(['active_role' => 'pembimbing_dalam'])
            ->post('/pembimbing-dalam/penilaian/'.$this->assignment->id.'/save', ['scores' => [['component_id' => $internal->id, 'score' => 90]]])
            ->assertSessionHasErrors('assessment');

        $this->actingAs($this->fieldUser)->withSession(['active_role' => 'pembimbing_lapangan'])
            ->post('/pembimbing-lapangan/penilaian/'.$this->assignment->id.'/save', ['scores' => [['component_id' => $field->id, 'score' => 80]]])
            ->assertSessionHasErrors('assessment');

        $this->makeReadyForInternalAssessment($this->assignment);
        $this->makeReadyForFieldAssessment($this->assignment);

        $this->actingAs($this->supervisorUser)->withSession(['active_role' => 'pembimbing_dalam'])
            ->post('/pembimbing-dalam/penilaian/'.$this->assignment->id.'/save', ['scores' => [['component_id' => $internal->id, 'score' => 90]]])
            ->assertRedirect();

        $this->actingAs($this->fieldUser)->withSession(['active_role' => 'pembimbing_lapangan'])
            ->post('/pembimbing-lapangan/penilaian/'.$this->assignment->id.'/save', ['scores' => [['component_id' => $field->id, 'score' => 80]]])
            ->assertRedirect();
    }

    private function components(): array
    {
        return [
            KpAssessmentComponent::firstOrCreate(['kp_period_id' => $this->assignment->kp_period_id, 'assessor_type' => 'pembimbing_dalam', 'component_name' => 'Kualitas laporan'], ['weight' => 30, 'max_score' => 100, 'status' => 'aktif', 'is_required' => true]),
            KpAssessmentComponent::firstOrCreate(['kp_period_id' => $this->assignment->kp_period_id, 'assessor_type' => 'pembimbing_lapangan', 'component_name' => 'Kedisiplinan'], ['weight' => 30, 'max_score' => 100, 'status' => 'aktif', 'is_required' => true]),
            KpAssessmentComponent::firstOrCreate(['kp_period_id' => $this->assignment->kp_period_id, 'assessor_type' => 'penguji', 'component_name' => 'Presentasi'], ['weight' => 40, 'max_score' => 100, 'status' => 'aktif', 'is_required' => true]),
        ];
    }

    private function saveAndSubmit(User $user, string $prefix, int $assignmentId, KpAssessmentComponent $component, int $score): void
    {
        $role = $prefix === 'pembimbing-dalam' ? 'pembimbing_dalam' : 'pembimbing_lapangan';
        $assignment = KpAssignment::findOrFail($assignmentId);

        if ($role === 'pembimbing_dalam') {
            $this->makeReadyForInternalAssessment($assignment);
        }

        if ($role === 'pembimbing_lapangan') {
            $this->makeReadyForFieldAssessment($assignment);
        }

        $this->actingAs($user)->withSession(['active_role' => $role])
            ->post("/{$prefix}/penilaian/{$assignmentId}/save", ['scores' => [['component_id' => $component->id, 'score' => $score]]])
            ->assertRedirect();
        $this->actingAs($user)->withSession(['active_role' => $role])
            ->post("/{$prefix}/penilaian/{$assignmentId}/submit")
            ->assertRedirect();
    }

    private function makeAssignment(Student $student): KpAssignment
    {
        $period = KpPeriod::create(['name' => 'KP Genap 2026', 'status' => 'dibuka']);
        $place = KpPlace::create(['name' => 'Apotek Sehat', 'type' => 'apotek', 'status' => 'aktif']);
        $registration = KpRegistration::create(['kp_period_id' => $period->id, 'student_id' => $student->id, 'status' => 'terverifikasi']);

        return KpAssignment::create(['kp_period_id' => $period->id, 'kp_registration_id' => $registration->id, 'student_id' => $student->id, 'kp_place_id' => $place->id, 'internal_supervisor_id' => $this->supervisor->id, 'field_supervisor_id' => $this->field->id, 'status' => 'aktif', 'assigned_by' => $this->admin->id, 'assigned_at' => now(), 'active_key' => $period->id.'-'.$student->id]);
    }

    private function makeReadyForInternalAssessment(KpAssignment $assignment): void
    {
        KpFinalReport::updateOrCreate(
            ['kp_assignment_id' => $assignment->id],
            [
                'student_id' => $assignment->student_id,
                'status' => 'submitted',
                'internal_review_status' => 'disetujui',
                'internal_guidance_completed_by' => $this->supervisorUser->id,
                'internal_guidance_completed_at' => now(),
                'internal_guidance_completion_note' => 'Bimbingan dalam selesai untuk penilaian.',
                'field_review_status' => $assignment->finalReport?->field_review_status ?? 'menunggu_review',
                'final_document_url' => 'https://drive.google.com/example-report',
            ]
        );

        for ($i = 1; $i <= 8; $i++) {
            KpReportGuidanceLog::firstOrCreate(
                [
                    'kp_assignment_id' => $assignment->id,
                    'reviewer_type' => KpReportGuidanceLog::REVIEWER_INTERNAL,
                    'guidance_date' => now()->subDays($i)->toDateString(),
                    'topic' => 'Bimbingan Internal '.$i,
                ],
                [
                    'status' => 'disetujui',
                    'submitted_at' => now(),
                    'validated_by' => $this->supervisorUser->id,
                    'validated_at' => now(),
                ]
            );
        }
    }

    private function makeReadyForFieldAssessment(KpAssignment $assignment): void
    {
        KpFinalReport::updateOrCreate(
            ['kp_assignment_id' => $assignment->id],
            [
                'student_id' => $assignment->student_id,
                'status' => 'submitted',
                'internal_review_status' => $assignment->finalReport?->internal_review_status ?? 'menunggu_review',
                'field_review_status' => 'disetujui',
                'field_guidance_completed_by' => $this->fieldUser->id,
                'field_guidance_completed_at' => now(),
                'field_guidance_completion_note' => 'Bimbingan lapangan selesai untuk penilaian.',
                'final_document_url' => 'https://drive.google.com/example-report',
            ]
        );

        KpLogbook::firstOrCreate(
            [
                'kp_assignment_id' => $assignment->id,
                'activity_date' => now()->subDay()->toDateString(),
            ],
            [
                'activity_title' => 'Kegiatan KP',
                'activity_description' => 'Kegiatan harian.',
                'status' => 'disetujui',
                'submitted_at' => now(),
                'validated_by' => $this->fieldUser->id,
                'validated_at' => now(),
            ]
        );

        for ($i = 1; $i <= 8; $i++) {
            KpReportGuidanceLog::firstOrCreate(
                [
                    'kp_assignment_id' => $assignment->id,
                    'reviewer_type' => KpReportGuidanceLog::REVIEWER_FIELD,
                    'guidance_date' => now()->subDays($i)->toDateString(),
                    'topic' => 'Review Laporan Lapangan '.$i,
                ],
                [
                    'status' => 'disetujui',
                    'submitted_at' => now(),
                    'validated_by' => $this->fieldUser->id,
                    'validated_at' => now(),
                ]
            );
        }
    }

    private function makeUser(string $email, array $roles): User
    {
        $user = User::create(['name' => 'User Test', 'email' => $email, 'password' => Hash::make('password'), 'status' => 'active']);
        $user->roles()->sync(Role::whereIn('name', $roles)->pluck('id'));

        return $user;
    }
}
