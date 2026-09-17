<?php

namespace Tests\Feature;

use App\Models\FieldSupervisor;
use App\Models\KpAssignment;
use App\Models\KpExam;
use App\Models\KpExamInvitation;
use App\Models\KpExamInvitationSignatory;
use App\Models\KpExamRequest;
use App\Models\KpFinalReport;
use App\Models\KpLogbook;
use App\Models\KpReportGuidanceLog;
use App\Models\KpPeriod;
use App\Models\KpPlace;
use App\Models\KpRegistration;
use App\Models\Lecturer;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class KpExamSchedulingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $koordinator;
    private User $mahasiswa;
    private Student $student;
    private User $supervisorUser;
    private Lecturer $supervisor;
    private User $examinerUser;
    private Lecturer $examiner;
    private User $secondExaminerUser;
    private Lecturer $secondExaminer;
    private Lecturer $nonExaminer;
    private User $fieldUser;
    private KpAssignment $assignment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->admin = $this->makeUser('admin-exam@test.local', ['admin']);
        $this->koordinator = $this->makeUser('koordinator-exam@test.local', ['koordinator_kp']);
        $this->mahasiswa = $this->makeUser('mahasiswa-exam@test.local', ['mahasiswa']);
        $this->student = $this->makeStudent($this->mahasiswa, '2210631230901');
        $this->supervisorUser = $this->makeUser('supervisor-exam@test.local', ['pembimbing_dalam']);
        $this->supervisor = Lecturer::create(['user_id' => $this->supervisorUser->id, 'nidn_nip' => '991101', 'status' => 'active']);
        $this->examinerUser = $this->makeUser('examiner-exam@test.local', ['penguji']);
        $this->examiner = Lecturer::create(['user_id' => $this->examinerUser->id, 'nidn_nip' => '991102', 'status' => 'active']);
        $this->secondExaminerUser = $this->makeUser('second-examiner-exam@test.local', ['penguji']);
        $this->secondExaminer = Lecturer::create(['user_id' => $this->secondExaminerUser->id, 'nidn_nip' => '991106', 'status' => 'active']);
        $nonExaminerUser = $this->makeUser('not-examiner@test.local', ['pembimbing_dalam']);
        $this->nonExaminer = Lecturer::create(['user_id' => $nonExaminerUser->id, 'nidn_nip' => '991103', 'status' => 'active']);
        $this->fieldUser = $this->makeUser('field-exam@test.local', ['pembimbing_lapangan']);
        $field = FieldSupervisor::create(['user_id' => $this->fieldUser->id, 'institution_name' => 'Apotek Sehat', 'position' => 'Supervisor', 'status' => 'active']);
        $this->assignment = $this->makeAssignment($this->student, $this->supervisor, $field);
    }

    public function test_login_page_opens_and_student_registration_sidebar_has_single_active_item(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Portal Kerja Praktek Farmasi UBP');

        $response = $this->actingAs($this->mahasiswa)->withSession(['active_role' => 'mahasiswa'])
            ->get('/mahasiswa/pendaftaran-kp');

        $response->assertOk()->assertSee('Pendaftaran KP')->assertSee('Berkas KP');
        $this->assertSame(1, substr_count($response->getContent(), 'bg-cyan-700 text-white'));
    }

    public function test_student_can_only_submit_exam_request_after_final_report_is_approved_and_cannot_duplicate(): void
    {
        $this->actingAs($this->mahasiswa)->withSession(['active_role' => 'mahasiswa'])
            ->post('/mahasiswa/sidang/ajukan', [
                'request_note' => 'Mohon dijadwalkan.',
                'payment_proof_url' => 'https://drive.google.com/file/d/payment-proof/view',
            ])
            ->assertSessionHasErrors('exam');

        $this->approvedFinalReport();

        $this->actingAs($this->mahasiswa)->withSession(['active_role' => 'mahasiswa'])
            ->get('/mahasiswa/sidang')
            ->assertOk()
            ->assertSee('Ajukan Sidang');

        $this->actingAs($this->mahasiswa)->withSession(['active_role' => 'mahasiswa'])
            ->post('/mahasiswa/sidang/ajukan', [
                'request_note' => 'Siap sidang.',
                'payment_proof_url' => 'https://drive.google.com/file/d/payment-proof/view',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('kp_exam_requests', [
            'kp_assignment_id' => $this->assignment->id,
            'status' => 'diajukan',
            'payment_proof_url' => 'https://drive.google.com/file/d/payment-proof/view',
            'payment_proof_status' => KpExamRequest::PAYMENT_PROOF_PENDING,
        ]);
        $this->assertDatabaseHas('kp_exam_logs', ['action' => 'request_submitted']);

        $this->actingAs($this->mahasiswa)->withSession(['active_role' => 'mahasiswa'])
            ->post('/mahasiswa/sidang/ajukan', [
                'payment_proof_url' => 'https://drive.google.com/file/d/payment-proof/view',
            ])
            ->assertSessionHasErrors('exam');
    }

    public function test_student_can_submit_exam_request_without_payment_proof(): void
    {
        $this->approvedFinalReport();

        $this->actingAs($this->mahasiswa)->withSession(['active_role' => 'mahasiswa'])
            ->post('/mahasiswa/sidang/ajukan', ['request_note' => 'Siap sidang.'])
            ->assertRedirect();

        $request = KpExamRequest::firstOrFail();
        $this->assertSame('diajukan', $request->status);
        $this->assertFalse($request->hasPaymentProof());
        $this->assertSame('belum_upload', $request->paymentProofStatus());

        $this->actingAs($this->mahasiswa)->withSession(['active_role' => 'mahasiswa'])
            ->get('/mahasiswa/sidang')
            ->assertOk()
            ->assertSee('Syarat membuka nilai')
            ->assertSee('Unggah bukti pembayaran KP');
    }

    public function test_student_can_upload_exam_payment_proof_and_management_can_preview_it(): void
    {
        Storage::fake('local');
        $this->approvedFinalReport();

        $this->actingAs($this->mahasiswa)->withSession(['active_role' => 'mahasiswa'])
            ->post('/mahasiswa/sidang/ajukan', [
                'request_note' => 'Siap sidang.',
                'payment_proof' => UploadedFile::fake()->create('bukti-pembayaran-kp.pdf', 128, 'application/pdf'),
                'payment_proof_label' => 'Bukti pembayaran KP',
            ])
            ->assertRedirect();

        $request = KpExamRequest::firstOrFail();
        Storage::disk('local')->assertExists($request->payment_proof_path);
        $this->assertSame(KpExamRequest::PAYMENT_PROOF_PENDING, $request->payment_proof_status);

        $this->actingAs($this->koordinator)->withSession(['active_role' => 'koordinator_kp'])
            ->get('/management/exam-requests/'.$request->id)
            ->assertOk()
            ->assertSee('Bukti pembayaran KP')
            ->assertSee('Menunggu validasi')
            ->assertSee('Preview File')
            ->assertSee('Download');

        $this->actingAs($this->koordinator)->withSession(['active_role' => 'koordinator_kp'])
            ->get('/management/exam-requests/'.$request->id.'/payment-proof/preview')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_student_can_preview_and_replace_payment_proof_before_request_is_approved(): void
    {
        Storage::fake('local');
        $this->approvedFinalReport();

        $this->actingAs($this->mahasiswa)->withSession(['active_role' => 'mahasiswa'])
            ->post('/mahasiswa/sidang/ajukan', [
                'payment_proof' => UploadedFile::fake()->create('bukti-salah.pdf', 128, 'application/pdf'),
                'payment_proof_label' => 'Bukti salah',
            ])
            ->assertRedirect();

        $request = KpExamRequest::firstOrFail();
        $oldPath = $request->payment_proof_path;

        $this->actingAs($this->mahasiswa)->withSession(['active_role' => 'mahasiswa'])
            ->get('/mahasiswa/sidang')
            ->assertOk()
            ->assertSee('Ganti bukti pembayaran KP')
            ->assertSee('Preview File');

        $this->actingAs($this->mahasiswa)->withSession(['active_role' => 'mahasiswa'])
            ->get('/mahasiswa/sidang/bukti-pembayaran/preview')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->actingAs($this->mahasiswa)->withSession(['active_role' => 'mahasiswa'])
            ->post('/mahasiswa/sidang/bukti-pembayaran', [
                'payment_proof' => UploadedFile::fake()->create('bukti-benar.pdf', 128, 'application/pdf'),
                'payment_proof_label' => 'Bukti benar',
            ])
            ->assertRedirect();

        $request->refresh();
        Storage::disk('local')->assertMissing($oldPath);
        Storage::disk('local')->assertExists($request->payment_proof_path);
        $this->assertSame('bukti-benar.pdf', $request->payment_proof_original_filename);
        $this->assertSame(KpExamRequest::PAYMENT_PROOF_PENDING, $request->payment_proof_status);
        $this->assertSame('diajukan', $request->status);
    }

    public function test_coordinator_can_return_payment_proof_and_student_can_replace_it_again(): void
    {
        $this->approvedFinalReport();

        $this->actingAs($this->mahasiswa)->withSession(['active_role' => 'mahasiswa'])
            ->post('/mahasiswa/sidang/ajukan', [
                'payment_proof_url' => 'https://drive.google.com/file/d/wrong-payment-proof/view',
                'payment_proof_label' => 'Bukti yang salah',
            ])
            ->assertRedirect();

        $request = KpExamRequest::firstOrFail();

        $this->actingAs($this->koordinator)->withSession(['active_role' => 'koordinator_kp'])
            ->post('/management/exam-requests/'.$request->id.'/payment-proof/revision', [
                'payment_proof_review_note' => 'Bukti pembayaran bukan untuk KP.',
            ])
            ->assertRedirect();

        $request->refresh();
        $this->assertSame('diajukan', $request->status);
        $this->assertSame(KpExamRequest::PAYMENT_PROOF_REVISION, $request->payment_proof_status);
        $this->assertSame('Bukti pembayaran bukan untuk KP.', $request->payment_proof_review_note);

        $this->actingAs($this->mahasiswa)->withSession(['active_role' => 'mahasiswa'])
            ->get('/mahasiswa/sidang')
            ->assertOk()
            ->assertSee('Perlu diganti')
            ->assertSee('Bukti pembayaran bukan untuk KP.');

        $this->actingAs($this->mahasiswa)->withSession(['active_role' => 'mahasiswa'])
            ->post('/mahasiswa/sidang/bukti-pembayaran', [
                'payment_proof_url' => 'https://drive.google.com/file/d/correct-payment-proof/view',
                'payment_proof_label' => 'Bukti pembayaran benar',
            ])
            ->assertRedirect();

        $request->refresh();
        $this->assertSame('diajukan', $request->status);
        $this->assertSame(KpExamRequest::PAYMENT_PROOF_PENDING, $request->payment_proof_status);
        $this->assertSame('https://drive.google.com/file/d/correct-payment-proof/view', $request->payment_proof_url);
        $this->assertNull($request->payment_proof_review_note);
    }

    public function test_coordinator_can_approve_and_schedule_exam_without_payment_proof(): void
    {
        $this->approvedFinalReport();

        $this->actingAs($this->mahasiswa)->withSession(['active_role' => 'mahasiswa'])
            ->post('/mahasiswa/sidang/ajukan')
            ->assertRedirect();

        $request = KpExamRequest::firstOrFail();

        $this->actingAs($this->koordinator)->withSession(['active_role' => 'koordinator_kp'])
            ->post('/management/exam-requests/'.$request->id.'/approve', [
                'review_note' => 'Syarat lengkap.',
            ])
            ->assertRedirect();

        $request->refresh();
        $this->assertSame('disetujui', $request->status);
        $this->assertFalse($request->hasPaymentProof());

        $this->actingAs($this->koordinator)->withSession(['active_role' => 'koordinator_kp'])
            ->post('/management/exam-requests/'.$request->id.'/schedule', $this->validSchedulePayload())
            ->assertRedirect();

        $this->assertSame('dijadwalkan', $request->fresh()->status);
        $this->assertDatabaseHas('kp_exams', ['kp_exam_request_id' => $request->id]);
    }

    public function test_payment_proof_can_be_uploaded_and_returned_after_exam_is_scheduled(): void
    {
        $request = $this->approvedExamRequest();

        $this->actingAs($this->koordinator)->withSession(['active_role' => 'koordinator_kp'])
            ->post('/management/exam-requests/'.$request->id.'/schedule', $this->validSchedulePayload())
            ->assertRedirect();

        $request->update([
            'payment_proof_url' => null,
            'payment_proof_status' => null,
            'payment_proof_reviewed_by' => null,
            'payment_proof_reviewed_at' => null,
        ]);

        $this->actingAs($this->mahasiswa)->withSession(['active_role' => 'mahasiswa'])
            ->post('/mahasiswa/sidang/bukti-pembayaran', [
                'payment_proof_url' => 'https://drive.google.com/file/d/payment-proof-after-schedule/view',
                'payment_proof_label' => 'Bukti pembayaran setelah jadwal',
            ])
            ->assertRedirect();

        $request->refresh();
        $this->assertSame('dijadwalkan', $request->status);
        $this->assertSame(KpExamRequest::PAYMENT_PROOF_PENDING, $request->payment_proof_status);

        $this->actingAs($this->koordinator)->withSession(['active_role' => 'koordinator_kp'])
            ->post('/management/exam-requests/'.$request->id.'/payment-proof/revision', [
                'payment_proof_review_note' => 'Mohon unggah bukti yang lebih jelas.',
            ])
            ->assertRedirect();

        $request->refresh();
        $this->assertSame('dijadwalkan', $request->status);
        $this->assertSame(KpExamRequest::PAYMENT_PROOF_REVISION, $request->payment_proof_status);
    }

    public function test_admin_and_koordinator_can_monitor_exam_requests_but_field_supervisor_cannot(): void
    {
        $this->approvedFinalReport();
        $this->submittedExamRequest();

        $this->actingAs($this->admin)->withSession(['active_role' => 'admin'])
            ->get('/management/exam-requests')
            ->assertOk()
            ->assertSee('Antrian Validasi Sidang')
            ->assertSee('Validasi kandidat sebelum penjadwalan');

        $this->actingAs($this->koordinator)->withSession(['active_role' => 'koordinator_kp'])
            ->get('/management/exam-requests')
            ->assertOk();

        $this->actingAs($this->fieldUser)->withSession(['active_role' => 'pembimbing_lapangan'])
            ->get('/management/exam-requests')
            ->assertForbidden();
    }

    public function test_pending_exam_request_must_be_approved_before_scheduling(): void
    {
        $request = $this->submittedExamRequest();

        $this->actingAs($this->koordinator)->withSession(['active_role' => 'koordinator_kp'])
            ->get('/management/exam-requests/'.$request->id.'/schedule')
            ->assertRedirect('/management/exam-requests/'.$request->id)
            ->assertSessionHasErrors('request');

        $this->assertFalse($request->fresh()->canBeScheduled());
    }

    public function test_koordinator_can_schedule_exam_and_student_supervisor_examiner_can_see_it(): void
    {
        $request = $this->approvedExamRequest();

        $this->actingAs($this->koordinator)->withSession(['active_role' => 'koordinator_kp'])
            ->get('/management/exam-requests/'.$request->id.'/schedule')
            ->assertOk()
            ->assertSee('Kandidat sidang')
            ->assertSee('Pilih 2 sampai 3 penguji');

        $this->actingAs($this->koordinator)->withSession(['active_role' => 'koordinator_kp'])
            ->post('/management/exam-requests/'.$request->id.'/schedule', $this->validSchedulePayload())
            ->assertRedirect();

        $exam = KpExam::first();
        $this->assertSame('dijadwalkan', $request->fresh()->status);
        $this->assertEqualsCanonicalizing([$this->examiner->id, $this->secondExaminer->id], $exam->examiners()->pluck('lecturers.id')->all());
        $this->assertSame($this->examiner->id, $exam->examiner_id);
        $this->assertSame($this->supervisor->id, $exam->chair_lecturer_id);
        $this->assertNotNull($exam->minutes_number);
        $this->assertDatabaseHas('kp_exam_logs', ['action' => 'exam_scheduled']);

        $this->actingAs($this->mahasiswa)->withSession(['active_role' => 'mahasiswa'])
            ->get('/mahasiswa/sidang')
            ->assertOk()
            ->assertSee('Jadwal Sidang');

        $this->actingAs($this->supervisorUser)->withSession(['active_role' => 'pembimbing_dalam'])
            ->get('/pembimbing-dalam/jadwal-sidang/'.$exam->id)
            ->assertOk()
            ->assertSee('Input nilai sidang akan tersedia pada tahap berikutnya.');

        $this->actingAs($this->examinerUser)->withSession(['active_role' => 'penguji'])
            ->get('/penguji/jadwal-sidang/'.$exam->id)
            ->assertOk()
            ->assertSee('Input nilai penguji akan tersedia pada tahap berikutnya.');

        $this->actingAs($this->secondExaminerUser)->withSession(['active_role' => 'penguji'])
            ->get('/penguji/jadwal-sidang/'.$exam->id)
            ->assertOk();
    }

    public function test_exam_invitation_inbox_is_scoped_to_the_active_role(): void
    {
        $exam = $this->scheduledExam();

        $this->actingAs($this->mahasiswa)->withSession(['active_role' => 'mahasiswa'])
            ->get('/undangan-sidang')
            ->assertOk()
            ->assertSee('Undangan sidang')
            ->assertSee('Ruang Sidang 1')
            ->assertSee('Apotek Sehat');

        $this->actingAs($this->supervisorUser)->withSession(['active_role' => 'pembimbing_dalam'])
            ->get('/undangan-sidang')
            ->assertOk()
            ->assertSee('Undangan sidang')
            ->assertSee('Ruang Sidang 1');

        $this->actingAs($this->fieldUser)->withSession(['active_role' => 'pembimbing_lapangan'])
            ->get('/undangan-sidang')
            ->assertOk()
            ->assertSee('Undangan sidang')
            ->assertSee('Apotek Sehat');

        $this->actingAs($this->examinerUser)->withSession(['active_role' => 'penguji'])
            ->get('/undangan-sidang')
            ->assertOk()
            ->assertSee('Undangan sidang')
            ->assertSee('Ruang Sidang 1');

        $this->actingAs($this->koordinator)->withSession(['active_role' => 'koordinator_kp'])
            ->get('/undangan-sidang')
            ->assertOk()
            ->assertSee('Undangan sidang')
            ->assertSee('Ruang Sidang 1');

        $otherStudentUser = $this->makeUser('other-student-exam@test.local', ['mahasiswa']);
        $this->makeStudent($otherStudentUser, '2210631230999');

        $this->actingAs($otherStudentUser)->withSession(['active_role' => 'mahasiswa'])
            ->get('/undangan-sidang')
            ->assertOk()
            ->assertSee('Belum ada data pada bagian ini.')
            ->assertDontSee('Ruang Sidang 1');

        $this->assertSame('dijadwalkan', $exam->fresh()->status);
    }

    public function test_koordinator_can_publish_official_exam_invitation_letter(): void
    {
        $exam = $this->scheduledExam();

        $this->actingAs($this->koordinator)->withSession(['active_role' => 'koordinator_kp'])
            ->post('/management/exams/invitations/signatory', [
                'coordinator_name' => 'Farhamzah',
                'coordinator_nuptk' => '123456',
                'head_program_name' => 'Kaprodi Farmasi',
                'head_program_nuptk' => '654321',
                'dean_name' => 'Dekan Fakultas Farmasi',
                'dean_nuptk' => '987654',
                'effective_start_date' => now()->toDateString(),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('kp_exam_invitation_signatories', [
            'coordinator_name' => 'Farhamzah',
            'head_program_name' => 'Kaprodi Farmasi',
            'dean_name' => 'Dekan Fakultas Farmasi',
            'is_active' => true,
        ]);

        $this->actingAs($this->koordinator)->withSession(['active_role' => 'koordinator_kp'])
            ->post('/management/exams/'.$exam->id.'/invitation')
            ->assertRedirect();

        $invitation = KpExamInvitation::firstOrFail();

        $this->assertDatabaseHas('kp_exam_invitations', [
            'kp_exam_id' => $exam->id,
            'coordinator_name' => 'Farhamzah',
            'head_program_name' => 'Kaprodi Farmasi',
            'dean_name' => 'Dekan Fakultas Farmasi',
        ]);

        $this->actingAs($this->mahasiswa)->withSession(['active_role' => 'mahasiswa'])
            ->get('/undangan-sidang/surat/'.$invitation->id)
            ->assertOk()
            ->assertSee('UNDANGAN SIDANG KERJA PRAKTIK')
            ->assertSee('Logo UBP Karawang')
            ->assertSee($invitation->letter_number);

        $pdfResponse = $this->actingAs($this->mahasiswa)->withSession(['active_role' => 'mahasiswa'])
            ->get('/undangan-sidang/surat/'.$invitation->id.'/pdf')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertDownload('undangan-sidang-kp-'.$exam->id.'.pdf');

        $this->assertStringStartsWith('%PDF-', $pdfResponse->getContent());

        $otherStudentUser = $this->makeUser('other-letter-exam@test.local', ['mahasiswa']);
        $this->makeStudent($otherStudentUser, '2210631230888');

        $this->actingAs($otherStudentUser)->withSession(['active_role' => 'mahasiswa'])
            ->get('/undangan-sidang/surat/'.$invitation->id)
            ->assertForbidden();
    }

    public function test_koordinator_can_publish_exam_invitations_in_bulk_with_active_signatory(): void
    {
        $firstExam = $this->scheduledExam();
        $secondStudentUser = $this->makeUser('bulk-student-exam@test.local', ['mahasiswa']);
        $secondStudent = $this->makeStudent($secondStudentUser, '2210631230777');
        $field = FieldSupervisor::where('user_id', $this->fieldUser->id)->firstOrFail();
        $secondAssignment = $this->makeAssignment($secondStudent, $this->supervisor, $field);

        $secondRequest = KpExamRequest::create([
            'kp_assignment_id' => $secondAssignment->id,
            'requested_by' => $secondStudentUser->id,
            'status' => 'dijadwalkan',
            'submitted_at' => now(),
        ]);

        $secondExam = KpExam::create([
            'kp_exam_request_id' => $secondRequest->id,
            'kp_assignment_id' => $secondAssignment->id,
            'supervisor_id' => $this->supervisor->id,
            'examiner_id' => $this->examiner->id,
            'exam_date' => now()->addDays(8)->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
            'mode' => 'offline',
            'room' => 'Ruang Sidang 2',
            'status' => 'dijadwalkan',
            'scheduled_by' => $this->admin->id,
            'scheduled_at' => now(),
        ]);

        KpExamInvitationSignatory::create([
            'coordinator_name' => 'Koordinator Aktif',
            'head_program_name' => 'Kaprodi Aktif',
            'dean_name' => 'Dekan Aktif',
            'is_active' => true,
            'updated_by' => $this->koordinator->id,
        ]);

        $this->actingAs($this->koordinator)->withSession(['active_role' => 'koordinator_kp'])
            ->post('/management/exams/invitations/bulk', [
                'exam_ids' => [$firstExam->id, $secondExam->id],
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('kp_exam_invitations', 2);
        $this->assertDatabaseHas('kp_exam_invitations', ['kp_exam_id' => $firstExam->id, 'coordinator_name' => 'Koordinator Aktif']);
        $this->assertDatabaseHas('kp_exam_invitations', ['kp_exam_id' => $secondExam->id, 'coordinator_name' => 'Koordinator Aktif']);
    }

    public function test_schedule_validation_rejects_invalid_examiner_time_room_and_link(): void
    {
        $request = $this->approvedExamRequest();

        $this->actingAs($this->koordinator)->withSession(['active_role' => 'koordinator_kp'])
            ->post('/management/exam-requests/'.$request->id.'/schedule', $this->validSchedulePayload(['examiner_ids' => [$this->examiner->id]]))
            ->assertSessionHasErrors('examiner_ids');

        $this->actingAs($this->koordinator)->withSession(['active_role' => 'koordinator_kp'])
            ->post('/management/exam-requests/'.$request->id.'/schedule', $this->validSchedulePayload(['examiner_ids' => [$this->examiner->id, $this->nonExaminer->id]]))
            ->assertSessionHasErrors('examiner_ids');

        $this->actingAs($this->koordinator)->withSession(['active_role' => 'koordinator_kp'])
            ->post('/management/exam-requests/'.$request->id.'/schedule', $this->validSchedulePayload(['chair_lecturer_id' => $this->nonExaminer->id]))
            ->assertSessionHasErrors('chair_lecturer_id');

        $this->actingAs($this->koordinator)->withSession(['active_role' => 'koordinator_kp'])
            ->post('/management/exam-requests/'.$request->id.'/schedule', $this->validSchedulePayload(['end_time' => '08:00']))
            ->assertSessionHasErrors('end_time');

        $this->actingAs($this->koordinator)->withSession(['active_role' => 'koordinator_kp'])
            ->post('/management/exam-requests/'.$request->id.'/schedule', $this->validSchedulePayload(['mode' => 'offline', 'room' => null]))
            ->assertSessionHasErrors('room');

        $this->actingAs($this->koordinator)->withSession(['active_role' => 'koordinator_kp'])
            ->post('/management/exam-requests/'.$request->id.'/schedule', $this->validSchedulePayload(['mode' => 'online', 'room' => null, 'meeting_link' => null]))
            ->assertSessionHasErrors('meeting_link');
    }

    public function test_backdated_schedule_requires_confirmation_and_reason(): void
    {
        $request = $this->approvedExamRequest();

        $this->actingAs($this->koordinator)->withSession(['active_role' => 'koordinator_kp'])
            ->post('/management/exam-requests/'.$request->id.'/schedule', $this->validSchedulePayload([
                'exam_date' => now()->subWeek()->toDateString(),
            ]))
            ->assertSessionHasErrors(['allow_backdate', 'backdate_reason']);

        $this->assertDatabaseCount('kp_exams', 0);
    }

    public function test_coordinator_can_record_exceptional_backdated_schedule_without_bypassing_flow(): void
    {
        $request = $this->approvedExamRequest();
        $reason = 'Tempat KP meminta sidang dilaksanakan lebih awal.';

        $this->actingAs($this->koordinator)->withSession(['active_role' => 'koordinator_kp'])
            ->post('/management/exam-requests/'.$request->id.'/schedule', $this->validSchedulePayload([
                'exam_date' => now()->subWeek()->toDateString(),
                'allow_backdate' => '1',
                'backdate_reason' => $reason,
            ]))
            ->assertRedirect();

        $exam = KpExam::firstOrFail();
        $this->assertSame($reason, $exam->backdate_reason);
        $this->assertSame('dijadwalkan', $request->fresh()->status);
        $this->assertDatabaseHas('kp_exam_logs', [
            'kp_exam_id' => $exam->id,
            'action' => 'exam_scheduled',
        ]);
    }

    public function test_coordinator_can_preview_print_and_download_filtered_exam_schedule(): void
    {
        $exam = $this->scheduledExam();
        $query = ['status' => 'dijadwalkan', 'date_from' => now()->toDateString()];

        $this->actingAs($this->koordinator)->withSession(['active_role' => 'koordinator_kp'])
            ->get('/management/exams/report/preview?'.http_build_query($query))
            ->assertOk()
            ->assertSee('DAFTAR JADWAL SIDANG KERJA PRAKTIK')
            ->assertSee($this->mahasiswa->name)
            ->assertSee('Download PDF')
            ->assertSee('Print');

        $this->actingAs($this->koordinator)->withSession(['active_role' => 'koordinator_kp'])
            ->get('/management/exams/report/preview?'.http_build_query($query + ['print' => 1]))
            ->assertOk()
            ->assertSee('onload="window.print()"', false);

        $pdfResponse = $this->actingAs($this->koordinator)->withSession(['active_role' => 'koordinator_kp'])
            ->get('/management/exams/report/pdf?'.http_build_query($query))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertDownload('jadwal-sidang-kp.pdf');

        $this->assertStringStartsWith('%PDF-', $pdfResponse->getContent());
        $this->assertDatabaseHas('kp_exams', ['id' => $exam->id]);
    }

    public function test_supervisor_and_examiner_can_only_open_their_own_exam_schedule(): void
    {
        $exam = $this->scheduledExam();
        $otherSupervisorUser = $this->makeUser('other-supervisor-exam@test.local', ['pembimbing_dalam']);
        Lecturer::create(['user_id' => $otherSupervisorUser->id, 'nidn_nip' => '991104', 'status' => 'active']);
        $otherExaminerUser = $this->makeUser('other-examiner-exam@test.local', ['penguji']);
        Lecturer::create(['user_id' => $otherExaminerUser->id, 'nidn_nip' => '991105', 'status' => 'active']);

        $this->actingAs($otherSupervisorUser)->withSession(['active_role' => 'pembimbing_dalam'])
            ->get('/pembimbing-dalam/jadwal-sidang/'.$exam->id)
            ->assertForbidden();

        $this->actingAs($otherExaminerUser)->withSession(['active_role' => 'penguji'])
            ->get('/penguji/jadwal-sidang/'.$exam->id)
            ->assertForbidden();
    }

    public function test_internal_supervisor_can_also_be_examiner_when_they_have_penguji_role(): void
    {
        $this->supervisorUser->roles()->syncWithoutDetaching(Role::where('name', 'penguji')->value('id'));
        $request = $this->approvedExamRequest();

        $this->actingAs($this->koordinator)->withSession(['active_role' => 'koordinator_kp'])
            ->post('/management/exam-requests/'.$request->id.'/schedule', $this->validSchedulePayload([
                'examiner_ids' => [$this->supervisor->id, $this->examiner->id],
                'chair_lecturer_id' => $this->supervisor->id,
            ]))
            ->assertRedirect();

        $exam = KpExam::firstOrFail();
        $this->assertEqualsCanonicalizing([$this->supervisor->id, $this->examiner->id], $exam->examiners()->pluck('lecturers.id')->all());
    }

    public function test_only_chair_closes_exam_and_minutes_wait_for_late_scores(): void
    {
        $exam = $this->scheduledExam();
        $exam->update(['exam_date' => now()->toDateString()]);
        $exam->examiners()->sync([
            $this->examiner->id => ['sort_order' => 1],
            $this->secondExaminer->id => ['sort_order' => 2],
        ]);

        $payload = [
            'result' => 'lulus_revisi',
            'actual_start_time' => '09:05',
            'actual_end_time' => '10:10',
            'revision_deadline' => now()->addWeek()->toDateString(),
            'notes' => 'Perbaiki format laporan final.',
            'attendance' => ['mahasiswa', 'ketua_sidang', 'tim_penguji'],
        ];

        $this->actingAs($this->secondExaminerUser)->withSession(['active_role' => 'penguji'])
            ->post('/penguji/jadwal-sidang/'.$exam->id.'/tutup', $payload)
            ->assertForbidden();

        $this->actingAs($this->supervisorUser)->withSession(['active_role' => 'pembimbing_dalam'])
            ->post('/pembimbing-dalam/jadwal-sidang/'.$exam->id.'/tutup', $payload)
            ->assertRedirect();

        $this->assertDatabaseHas('kp_exam_minutes', [
            'kp_exam_id' => $exam->id,
            'minutes_number' => $exam->minutes_number,
            'status' => 'menunggu_nilai',
            'result' => 'lulus_revisi',
            'closed_by' => $this->supervisorUser->id,
        ]);
        $this->assertSame('selesai', $exam->fresh()->status);

        $minute = $exam->fresh()->minutes;
        $this->actingAs($this->supervisorUser)->withSession(['active_role' => 'pembimbing_dalam'])
            ->get('/berita-acara-sidang/'.$minute->id)
            ->assertOk()
            ->assertSee('DRAFT - MENUNGGU NILAI');
        $this->actingAs($this->supervisorUser)->withSession(['active_role' => 'pembimbing_dalam'])
            ->get('/berita-acara-sidang/'.$minute->id.'/pdf')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->actingAs($this->koordinator)->withSession(['active_role' => 'koordinator_kp'])
            ->post('/management/exam-minutes/'.$minute->id.'/publish')
            ->assertSessionHasErrors('minutes');
    }

    public function test_admin_can_cancel_exam_with_log(): void
    {
        $exam = $this->scheduledExam();

        $this->actingAs($this->admin)->withSession(['active_role' => 'admin'])
            ->post('/management/exams/'.$exam->id.'/cancel', ['reason' => 'Jadwal bentrok.'])
            ->assertRedirect();

        $this->assertSame('dibatalkan', $exam->fresh()->status);
        $this->assertDatabaseHas('kp_exam_logs', ['action' => 'exam_cancelled']);

    }

    private function validSchedulePayload(array $overrides = []): array
    {
        return array_merge([
            'examiner_ids' => [$this->examiner->id, $this->secondExaminer->id],
            'exam_date' => now()->addWeek()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'mode' => 'hybrid',
            'room' => 'Ruang Sidang 1',
            'meeting_link' => 'https://meet.example.test/sidang-kp',
            'note' => 'Sidang tahap awal.',
        ], $overrides);
    }

    private function submittedExamRequest(): KpExamRequest
    {
        $this->approvedFinalReport();

        return KpExamRequest::firstOrCreate(
            ['kp_assignment_id' => $this->assignment->id],
            [
                'requested_by' => $this->mahasiswa->id,
                'status' => 'diajukan',
                'payment_proof_url' => 'https://drive.google.com/file/d/payment-proof/view',
                'payment_proof_label' => 'Bukti pembayaran KP',
                'payment_proof_status' => KpExamRequest::PAYMENT_PROOF_APPROVED,
                'payment_proof_reviewed_by' => $this->koordinator->id,
                'payment_proof_reviewed_at' => now(),
                'submitted_at' => now(),
            ]
        );
    }

    private function approvedExamRequest(): KpExamRequest
    {
        $request = $this->submittedExamRequest();

        $request->forceFill([
            'status' => 'disetujui',
            'reviewed_by' => $this->koordinator->id,
            'reviewed_at' => now(),
            'review_note' => 'Syarat sidang lengkap.',
        ])->save();

        return $request->fresh();
    }

    private function scheduledExam(): KpExam
    {
        $request = $this->approvedExamRequest();
        $examDate = now()->addWeek();
        $romanMonth = [1 => 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'][$examDate->month];

        return KpExam::create([
            'kp_exam_request_id' => $request->id,
            'kp_assignment_id' => $this->assignment->id,
            'supervisor_id' => $this->supervisor->id,
            'examiner_id' => $this->examiner->id,
            'chair_lecturer_id' => $this->supervisor->id,
            'minutes_sequence' => 1,
            'minutes_number' => '001/BA-SKP/FF-UBP/'.$romanMonth.'/'.$examDate->year,
            'exam_date' => $examDate->toDateString(),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'mode' => 'offline',
            'room' => 'Ruang Sidang 1',
            'status' => 'dijadwalkan',
            'scheduled_by' => $this->admin->id,
            'scheduled_at' => now(),
        ]);
    }

    private function approvedFinalReport(): KpFinalReport
    {
        if (! $this->assignment->logbooks()->where('status', 'disetujui')->exists()) {
            KpLogbook::create([
                'kp_assignment_id' => $this->assignment->id,
                'activity_date' => now()->toDateString(),
                'activity_title' => 'Kegiatan KP',
                'start_time' => '08:00',
                'end_time' => '12:00',
                'activity_description' => 'Kegiatan lapangan.',
                'learning_outcome' => 'Memahami kegiatan lapangan.',
                'status' => 'disetujui',
                'submitted_at' => now(),
                'validated_by' => $this->fieldUser->id,
                'validated_at' => now(),
            ]);
        }

        for ($i = 1; $i <= 8; $i++) {
            KpReportGuidanceLog::firstOrCreate(
                [
                    'kp_assignment_id' => $this->assignment->id,
                    'reviewer_type' => KpReportGuidanceLog::REVIEWER_INTERNAL,
                    'guidance_date' => now()->subDays($i)->toDateString(),
                    'topic' => 'Bimbingan laporan dalam '.$i,
                ],
                [
                    'status' => 'disetujui',
                    'submitted_at' => now()->subDays($i),
                    'validated_by' => $this->supervisorUser->id,
                    'validated_at' => now()->subDays($i),
                ]
            );

            KpReportGuidanceLog::firstOrCreate(
                [
                    'kp_assignment_id' => $this->assignment->id,
                    'reviewer_type' => KpReportGuidanceLog::REVIEWER_FIELD,
                    'guidance_date' => now()->subDays($i)->toDateString(),
                    'topic' => 'Bimbingan laporan lapangan '.$i,
                ],
                [
                    'status' => 'disetujui',
                    'submitted_at' => now()->subDays($i),
                    'validated_by' => $this->fieldUser->id,
                    'validated_at' => now()->subDays($i),
                ]
            );
        }

        return KpFinalReport::updateOrCreate(
            ['kp_assignment_id' => $this->assignment->id],
            [
                'current_version' => 1,
                'status' => 'disetujui',
                'final_document_url' => 'https://docs.google.com/document/d/final',
                'internal_review_status' => 'disetujui',
                'internal_reviewed_by' => $this->supervisorUser->id,
                'internal_reviewed_at' => now(),
                'internal_guidance_completed_by' => $this->supervisorUser->id,
                'internal_guidance_completed_at' => now(),
                'internal_guidance_completion_note' => 'Bimbingan dalam selesai untuk sidang.',
                'field_review_status' => 'disetujui',
                'field_reviewed_by' => $this->fieldUser->id,
                'field_reviewed_at' => now(),
                'field_guidance_completed_by' => $this->fieldUser->id,
                'field_guidance_completed_at' => now(),
                'field_guidance_completion_note' => 'Bimbingan lapangan selesai untuk sidang.',
                'approved_at' => now(),
            ]
        );
    }

    private function makeAssignment(Student $student, Lecturer $lecturer, FieldSupervisor $field): KpAssignment
    {
        $period = KpPeriod::create(['name' => 'KP Genap 2026', 'status' => 'dibuka']);
        $place = KpPlace::create(['name' => 'Apotek Sehat', 'type' => 'apotek', 'status' => 'aktif']);
        $registration = KpRegistration::create(['kp_period_id' => $period->id, 'student_id' => $student->id, 'status' => 'terverifikasi']);

        return KpAssignment::create([
            'kp_period_id' => $period->id,
            'kp_registration_id' => $registration->id,
            'student_id' => $student->id,
            'kp_place_id' => $place->id,
            'internal_supervisor_id' => $lecturer->id,
            'field_supervisor_id' => $field->id,
            'status' => 'aktif',
            'assigned_by' => $this->admin->id,
            'assigned_at' => now(),
            'active_key' => $period->id.'-'.$student->id,
        ]);
    }

    private function makeStudent(User $user, string $nim): Student
    {
        $user->forceFill(['profile_completed' => true])->save();

        return Student::create(['user_id' => $user->id, 'nim' => $nim, 'study_program' => 'Farmasi', 'semester' => 6, 'phone' => '081234567890', 'status' => 'active']);
    }

    private function makeUser(string $email, array $roles): User
    {
        $user = User::create(['name' => 'User Test', 'email' => $email, 'password' => Hash::make('password'), 'status' => 'active']);
        $user->roles()->sync(Role::whereIn('name', $roles)->pluck('id'));

        return $user;
    }
}
