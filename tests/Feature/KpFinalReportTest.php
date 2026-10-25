<?php

namespace Tests\Feature;

use App\Models\FieldSupervisor;
use App\Models\KpAssignment;
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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class KpFinalReportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $koordinator;
    private User $mahasiswa;
    private Student $student;
    private User $lecturerUser;
    private Lecturer $lecturer;
    private User $fieldUser;
    private FieldSupervisor $field;
    private KpAssignment $assignment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Storage::fake('local');

        $this->admin = $this->makeUser('admin-final@test.local', ['admin']);
        $this->koordinator = $this->makeUser('koordinator-final@test.local', ['koordinator_kp']);
        $this->mahasiswa = $this->makeUser('mahasiswa-final@test.local', ['mahasiswa']);
        $this->student = $this->makeStudent($this->mahasiswa, '2210631230201');
        $this->lecturerUser = $this->makeUser('dosen-final@test.local', ['pembimbing_dalam']);
        $this->lecturer = Lecturer::create(['user_id' => $this->lecturerUser->id, 'nidn_nip' => '552211', 'status' => 'active']);
        $this->fieldUser = $this->makeUser('field-final@test.local', ['pembimbing_lapangan']);
        $this->field = FieldSupervisor::create(['user_id' => $this->fieldUser->id, 'institution_name' => 'Apotek Sehat', 'position' => 'Supervisor', 'status' => 'active']);
        $this->assignment = $this->makeAssignment($this->student, $this->lecturer, $this->field);
    }

    public function test_student_with_active_assignment_can_open_final_report_and_without_assignment_sees_empty_state(): void
    {
        $this->actingAs($this->mahasiswa)->withSession(['active_role' => 'mahasiswa'])
            ->get('/mahasiswa/laporan-akhir')
            ->assertOk()
            ->assertSee('Laporan Akhir')
            ->assertSee('Buka Folder Drive Resmi')
            ->assertSee('2210631230201_USER_TEST_LAPORAN_AKHIR_KP_KP_GENAP_2026.pdf');

        $other = $this->makeUser('no-final-assignment@test.local', ['mahasiswa']);
        $this->makeStudent($other, '2210631230202');

        $this->actingAs($other)->withSession(['active_role' => 'mahasiswa'])
            ->get('/mahasiswa/laporan-akhir')
            ->assertOk()
            ->assertSee('Anda belum memiliki penempatan KP aktif.');
    }

    public function test_student_can_upload_submit_revision_and_version_increments(): void
    {
        $this->actingAs($this->mahasiswa)->withSession(['active_role' => 'mahasiswa'])
            ->post('/mahasiswa/laporan-akhir/upload', [
                'report_file' => UploadedFile::fake()->create('laporan.pdf', 256, 'application/pdf'),
                'note' => 'Upload awal',
            ])->assertRedirect();

        $report = KpFinalReport::first();
        $this->assertSame(1, $report->current_version);
        $this->assertDatabaseHas('kp_final_report_logs', ['action' => 'uploaded']);

        $this->actingAs($this->mahasiswa)->withSession(['active_role' => 'mahasiswa'])
            ->post('/mahasiswa/laporan-akhir/submit')
            ->assertRedirect();
        $this->assertSame('menunggu_review', $report->fresh()->status);

        $this->actingAs($this->lecturerUser)->withSession(['active_role' => 'pembimbing_dalam'])
            ->post('/pembimbing-dalam/laporan-akhir/'.$report->id.'/revision', ['review_note' => 'Perbaiki format.'])
            ->assertRedirect();

        $this->actingAs($this->mahasiswa)->withSession(['active_role' => 'mahasiswa'])
            ->post('/mahasiswa/laporan-akhir/upload', [
                'report_file' => UploadedFile::fake()->create('laporan-revisi.docx', 256, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
            ])->assertRedirect();

        $report->refresh();
        $this->assertSame(2, $report->current_version);
        $this->assertSame('draft', $report->status);
        $this->assertDatabaseHas('kp_final_report_logs', ['kp_final_report_id' => $report->id, 'action' => 'revision_uploaded']);
    }

    public function test_student_cannot_submit_without_file_or_upload_after_approved_and_invalid_file_is_rejected(): void
    {
        $this->actingAs($this->mahasiswa)->withSession(['active_role' => 'mahasiswa'])
            ->post('/mahasiswa/laporan-akhir/submit')
            ->assertSessionHasErrors('file');

        $report = KpFinalReport::first();
        $report->update(['status' => 'disetujui']);

        $this->actingAs($this->mahasiswa)->withSession(['active_role' => 'mahasiswa'])
            ->post('/mahasiswa/laporan-akhir/upload', ['report_file' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')])
            ->assertSessionHasErrors('report');

        $report->update(['status' => 'draft']);
        $this->actingAs($this->mahasiswa)->withSession(['active_role' => 'mahasiswa'])
            ->post('/mahasiswa/laporan-akhir/upload', ['report_file' => UploadedFile::fake()->create('x.exe', 10, 'application/octet-stream')])
            ->assertSessionHasErrors('report_file');
    }

    public function test_internal_supervisor_can_review_only_own_student_report(): void
    {
        $report = $this->submittedReport();
        $otherLecturerUser = $this->makeUser('other-final-lecturer@test.local', ['pembimbing_dalam']);
        Lecturer::create(['user_id' => $otherLecturerUser->id, 'nidn_nip' => '778811', 'status' => 'active']);

        $this->actingAs($otherLecturerUser)->withSession(['active_role' => 'pembimbing_dalam'])
            ->get('/pembimbing-dalam/laporan-akhir/'.$report->id)
            ->assertForbidden();

        $this->actingAs($this->lecturerUser)->withSession(['active_role' => 'pembimbing_dalam'])
            ->post('/pembimbing-dalam/laporan-akhir/'.$report->id.'/approve', ['review_note' => 'Layak.'])
            ->assertRedirect();

        $report->refresh();
        $this->assertSame('menunggu_review', $report->status);
        $this->assertSame('disetujui', $report->internal_review_status);
        $this->assertNull($report->approved_at);

        $this->actingAs($this->fieldUser)->withSession(['active_role' => 'pembimbing_lapangan'])
            ->post('/pembimbing-lapangan/laporan-akhir/'.$report->id.'/approve', ['review_note' => 'Sesuai lapangan.'])
            ->assertRedirect();

        $report->refresh();
        $this->assertSame('disetujui', $report->status);
        $this->assertSame('disetujui', $report->field_review_status);
        $this->assertNotNull($report->approved_at);
        $this->assertDatabaseHas('kp_final_report_logs', ['kp_final_report_id' => $report->id, 'action' => 'internal_approved']);
        $this->assertDatabaseHas('kp_final_report_logs', ['kp_final_report_id' => $report->id, 'action' => 'field_approved']);
    }

    public function test_student_can_save_final_link_and_internal_supervisor_validates_guidance_logs(): void
    {
        $this->actingAs($this->mahasiswa)->withSession(['active_role' => 'mahasiswa'])
            ->post('/mahasiswa/laporan-akhir/link-final', [
                'report_title' => 'Evaluasi Pelayanan Kefarmasian di Apotek Sehat',
                'final_document_url' => 'https://docs.google.com/document/d/final-report',
                'final_document_label' => 'Laporan Final KP',
            ])->assertRedirect();

        $report = KpFinalReport::first();
        $this->assertSame('Evaluasi Pelayanan Kefarmasian di Apotek Sehat', $report->report_title);
        $this->assertSame('https://docs.google.com/document/d/final-report', $report->final_document_url);

        $this->actingAs($this->mahasiswa)->withSession(['active_role' => 'mahasiswa'])
            ->post('/mahasiswa/laporan-akhir/bimbingan', [
                'reviewer_type' => KpReportGuidanceLog::REVIEWER_INTERNAL,
                'guidance_date' => now()->toDateString(),
                'topic' => 'Review bab hasil',
                'student_note' => 'Sudah revisi pembahasan.',
                'document_url' => ' docs.google.com/document/d/draft-report ',
                'document_label' => ' Draft Bab Hasil ',
            ])->assertRedirect();

        $guidance = $this->assignment->reportGuidanceLogs()->first();
        $this->assertSame('menunggu_validasi', $guidance->status);
        $this->assertSame(KpReportGuidanceLog::REVIEWER_INTERNAL, $guidance->reviewer_type);
        $this->assertSame('https://docs.google.com/document/d/draft-report', $guidance->document_url);
        $this->assertSame('Draft Bab Hasil', $guidance->document_label);

        $this->actingAs($this->mahasiswa)->withSession(['active_role' => 'mahasiswa'])
            ->post('/mahasiswa/laporan-akhir/bimbingan', [
                'reviewer_type' => KpReportGuidanceLog::REVIEWER_INTERNAL,
                'guidance_date' => now()->addDay()->toDateString(),
                'topic' => 'Review lanjutan sebelum diputuskan',
                'student_note' => 'Saya mencoba mengirim sesi berikutnya sebelum sesi sebelumnya divalidasi.',
            ])->assertSessionHasErrors('guidance');

        $this->actingAs($this->mahasiswa)->withSession(['active_role' => 'mahasiswa'])
            ->post('/mahasiswa/laporan-akhir/bimbingan', [
                'reviewer_type' => KpReportGuidanceLog::REVIEWER_FIELD,
                'guidance_date' => now()->addDay()->toDateString(),
                'topic' => 'Review lapangan sebelum bimbingan dalam diputuskan',
                'student_note' => 'Saya mencoba mengirim ke pembimbing lain saat masih ada sesi pending.',
            ])->assertRedirect();

        $this->actingAs($this->mahasiswa)->withSession(['active_role' => 'mahasiswa'])
            ->post('/mahasiswa/laporan-akhir/bimbingan', [
                'reviewer_type' => KpReportGuidanceLog::REVIEWER_FIELD,
                'guidance_date' => now()->addDays(2)->toDateString(),
                'topic' => 'Review lapangan kedua sebelum sesi lapangan diputuskan',
                'student_note' => 'Saya mencoba mengirim sesi lapangan kedua sebelum sesi lapangan sebelumnya divalidasi.',
            ])->assertSessionHasErrors('guidance');

        $this->assertSame(2, $this->assignment->reportGuidanceLogs()->count());

        $this->actingAs($this->lecturerUser)->withSession(['active_role' => 'pembimbing_dalam'])
            ->get('/pembimbing-dalam/laporan-akhir/'.$report->id)
            ->assertOk()
            ->assertSee('Draft Bab Hasil')
            ->assertSee('Preview Dokumen');

        $this->actingAs($this->fieldUser)->withSession(['active_role' => 'pembimbing_lapangan'])
            ->get('/pembimbing-lapangan/laporan-akhir/'.$report->id)
            ->assertOk()
            ->assertDontSee('Draft Bab Hasil')
            ->assertSee('Review lapangan sebelum bimbingan dalam diputuskan');

        $this->actingAs($this->lecturerUser)->withSession(['active_role' => 'pembimbing_dalam'])
            ->post('/pembimbing-dalam/laporan-akhir/'.$report->id.'/bimbingan/'.$guidance->id.'/approve', ['review_note' => 'OK.'])
            ->assertRedirect();

        $this->assertSame('disetujui', $guidance->fresh()->status);

        $this->actingAs($this->mahasiswa)->withSession(['active_role' => 'mahasiswa'])
            ->post('/mahasiswa/laporan-akhir/bimbingan', [
                'reviewer_type' => KpReportGuidanceLog::REVIEWER_INTERNAL,
                'guidance_date' => now()->addDay()->toDateString(),
                'topic' => 'Review lanjutan setelah diputuskan',
                'student_note' => 'Sesi sebelumnya sudah diputuskan, jadi saya bisa mengajukan lagi.',
            ])->assertRedirect();

        $this->assertSame(3, $this->assignment->reportGuidanceLogs()->count());

        $this->actingAs($this->mahasiswa)->withSession(['active_role' => 'mahasiswa'])
            ->get('/mahasiswa/laporan-akhir')
            ->assertOk()
            ->assertSee('Riwayat Bimbingan Pembimbing Dalam')
            ->assertSee('Riwayat Bimbingan Pembimbing Lapangan')
            ->assertSee('Yang diajukan mahasiswa')
            ->assertSee('Sudah revisi pembahasan.')
            ->assertSee('Review lapangan sebelum bimbingan dalam diputuskan')
            ->assertSee('Catatan Pembimbing Dalam')
            ->assertSee('OK.');
    }

    public function test_field_supervisor_validates_field_guidance_and_exam_eligibility_needs_both_guidance_tracks(): void
    {
        config()->set('kp_final_report.allow_unreviewed_exam_scheduling', false);
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
        KpLogbook::create([
            'kp_assignment_id' => $this->assignment->id,
            'activity_date' => now()->subDay()->toDateString(),
            'activity_title' => 'Tanggal logbook salah',
            'start_time' => '08:00',
            'end_time' => '12:00',
            'activity_description' => 'Logbook ditolak karena tanggal salah.',
            'learning_outcome' => 'Tidak dihitung sebagai absen.',
            'status' => 'ditolak',
            'submitted_at' => now(),
            'validated_by' => $this->fieldUser->id,
            'validated_at' => now(),
            'validation_note' => 'Tanggal kegiatan tidak sesuai periode KP.',
        ]);

        $report = KpFinalReport::create([
            'kp_assignment_id' => $this->assignment->id,
            'current_version' => 1,
            'status' => 'disetujui',
            'final_document_url' => 'https://docs.google.com/document/d/final',
            'internal_review_status' => 'disetujui',
            'internal_reviewed_by' => $this->lecturerUser->id,
            'internal_reviewed_at' => now(),
            'field_review_status' => 'disetujui',
            'field_reviewed_by' => $this->fieldUser->id,
            'field_reviewed_at' => now(),
            'approved_at' => now(),
        ]);

        for ($i = 1; $i <= 8; $i++) {
            KpReportGuidanceLog::create([
                'kp_assignment_id' => $this->assignment->id,
                'reviewer_type' => KpReportGuidanceLog::REVIEWER_INTERNAL,
                'guidance_date' => now()->subDays($i)->toDateString(),
                'topic' => 'Bimbingan dalam '.$i,
                'status' => $i === 8 ? 'revisi' : 'disetujui',
                'submitted_at' => now()->subDays($i),
                'validated_by' => $this->lecturerUser->id,
                'validated_at' => now()->subDays($i),
                'validation_note' => $i === 8 ? 'Revisi tetap tercatat sebagai sesi bimbingan.' : null,
            ]);
        }

        $this->assertFalse($this->assignment->fresh()->isEligibleForExamRequest());
        $internalGuidanceItem = collect($this->assignment->fresh()->examEligibility()['items'])
            ->firstWhere('key', 'internal_report_guidance_completed');
        $this->assertTrue($internalGuidanceItem['ready']);
        $this->assertSame('8/8 sesi direview, otomatis selesai karena laporan disetujui', $internalGuidanceItem['description']);

        $this->actingAs($this->lecturerUser)->withSession(['active_role' => 'pembimbing_dalam'])
            ->post('/pembimbing-dalam/laporan-akhir/'.$report->id.'/bimbingan-selesai', [
                'review_note' => 'Delapan sesi pembimbing dalam sudah tercatat.',
            ])
            ->assertRedirect();

        $report->refresh();
        $this->assertNotNull($report->internal_guidance_completed_at);
        $this->assertSame($this->lecturerUser->id, $report->internal_guidance_completed_by);
        $this->assertDatabaseHas('kp_final_report_logs', [
            'kp_final_report_id' => $report->id,
            'action' => 'internal_guidance_completed',
        ]);

        $this->assertFalse($this->assignment->fresh()->isEligibleForExamRequest());

        $this->actingAs($this->mahasiswa)->withSession(['active_role' => 'mahasiswa'])
            ->post('/mahasiswa/laporan-akhir/bimbingan', [
                'reviewer_type' => KpReportGuidanceLog::REVIEWER_FIELD,
                'guidance_date' => now()->toDateString(),
                'topic' => 'Review laporan lapangan',
                'student_note' => 'Mohon validasi hasil revisi laporan berdasarkan masukan lapangan.',
                'document_url' => 'https://docs.google.com/document/d/lapangan',
            ])->assertRedirect();

        $fieldGuidance = $this->assignment->reportGuidanceLogs()->where('reviewer_type', KpReportGuidanceLog::REVIEWER_FIELD)->first();

        $this->actingAs($this->lecturerUser)->withSession(['active_role' => 'pembimbing_dalam'])
            ->post('/pembimbing-dalam/laporan-akhir/'.$report->id.'/bimbingan/'.$fieldGuidance->id.'/approve')
            ->assertForbidden();

        $this->actingAs($this->fieldUser)->withSession(['active_role' => 'pembimbing_lapangan'])
            ->post('/pembimbing-lapangan/laporan-akhir/'.$report->id.'/bimbingan/'.$fieldGuidance->id.'/revision', ['review_note' => 'Tambahkan satu contoh kasus pelayanan resep.'])
            ->assertRedirect();

        $this->actingAs($this->mahasiswa)->withSession(['active_role' => 'mahasiswa'])
            ->get('/mahasiswa/laporan-akhir')
            ->assertOk()
            ->assertSee('Riwayat Bimbingan Pembimbing Dalam')
            ->assertSee('Riwayat Bimbingan Pembimbing Lapangan')
            ->assertSee('Bimbingan dalam 1')
            ->assertSee('Review laporan lapangan')
            ->assertSee('Tambahkan satu contoh kasus pelayanan resep.');

        $this->assertTrue($this->assignment->fresh()->isEligibleForExamRequest());

        $this->actingAs($this->fieldUser)->withSession(['active_role' => 'pembimbing_lapangan'])
            ->post('/pembimbing-lapangan/laporan-akhir/'.$report->id.'/bimbingan-selesai', [
                'review_note' => 'Bimbingan lapangan cukup untuk lanjut sidang.',
            ])
            ->assertRedirect();

        $report->refresh();
        $this->assertNotNull($report->field_guidance_completed_at);
        $this->assertSame($this->fieldUser->id, $report->field_guidance_completed_by);
        $this->assertDatabaseHas('kp_final_report_logs', [
            'kp_final_report_id' => $report->id,
            'action' => 'field_guidance_completed',
        ]);

        $this->assertTrue($this->assignment->fresh()->isEligibleForExamRequest());
        $logbookItem = collect($this->assignment->fresh()->examEligibility()['items'])
            ->firstWhere('key', 'field_logbook_validated');
        $this->assertTrue($logbookItem['ready']);
        $this->assertSame('1 disetujui, 1 direview tidak dihitung absen, 0 menunggu validasi', $logbookItem['description']);
    }

    public function test_report_and_guidance_links_must_be_safe_http_urls(): void
    {
        $this->actingAs($this->mahasiswa)->withSession(['active_role' => 'mahasiswa'])
            ->post('/mahasiswa/laporan-akhir/link-final', [
                'final_document_url' => 'javascript:alert(1)',
                'final_document_label' => 'Unsafe',
            ])->assertSessionHasErrors('final_document_url');

        $this->actingAs($this->mahasiswa)->withSession(['active_role' => 'mahasiswa'])
            ->post('/mahasiswa/laporan-akhir/bimbingan', [
                'reviewer_type' => KpReportGuidanceLog::REVIEWER_INTERNAL,
                'guidance_date' => now()->toDateString(),
                'topic' => 'Review link tidak aman',
                'document_url' => 'javascript:alert(1)',
            ])->assertSessionHasErrors('document_url');
    }

    public function test_final_report_link_must_be_google_file_not_folder(): void
    {
        $this->actingAs($this->mahasiswa)->withSession(['active_role' => 'mahasiswa'])
            ->post('/mahasiswa/laporan-akhir/link-final', [
                'final_document_url' => 'https://example.com/laporan-final.pdf',
            ])->assertSessionHasErrors('final_document_url');

        $this->actingAs($this->mahasiswa)->withSession(['active_role' => 'mahasiswa'])
            ->post('/mahasiswa/laporan-akhir/link-final', [
                'final_document_url' => 'https://drive.google.com/drive/folders/1EwAC9_tEl1DJmKx8eG1313nVnl89fbWv',
            ])->assertSessionHasErrors('final_document_url');

        $this->actingAs($this->mahasiswa)->withSession(['active_role' => 'mahasiswa'])
            ->post('/mahasiswa/laporan-akhir/link-final', [
                'final_document_url' => 'drive.google.com/file/d/final-report/view',
                'final_document_label' => 'Laporan Final KP',
            ])->assertRedirect();

        $this->assertSame('https://drive.google.com/file/d/final-report/view', KpFinalReport::first()->final_document_url);
    }

    public function test_internal_supervisor_can_reject_with_note_and_admin_koordinator_can_monitor(): void
    {
        $report = $this->submittedReport();

        $this->actingAs($this->lecturerUser)->withSession(['active_role' => 'pembimbing_dalam'])
            ->post('/pembimbing-dalam/laporan-akhir/'.$report->id.'/reject', ['review_note' => 'Tidak sesuai.'])
            ->assertRedirect();

        $this->assertSame('ditolak', $report->fresh()->status);

        $this->actingAs($this->admin)->withSession(['active_role' => 'admin'])->get('/management/final-reports')->assertOk()->assertSee('Monitoring Laporan');
        $this->actingAs($this->koordinator)->withSession(['active_role' => 'koordinator_kp'])->get('/management/final-report-logs')->assertOk();
        $this->actingAs($this->mahasiswa)->withSession(['active_role' => 'mahasiswa'])->get('/management/final-reports')->assertForbidden();
    }

    public function test_management_monitor_shows_and_filters_each_supervisor_completion_separately(): void
    {
        $report = $this->submittedReport();
        $report->update([
            'internal_guidance_completed_at' => now(),
            'internal_review_status' => 'disetujui',
            'field_review_status' => 'pending',
        ]);

        $this->actingAs($this->koordinator)->withSession(['active_role' => 'koordinator_kp'])
            ->get('/management/final-reports')
            ->assertOk()
            ->assertSee('Pembimbing Dalam Lengkap')
            ->assertSee('Pembimbing Lapangan')
            ->assertSee('Bimbingan: Selesai')
            ->assertSee('Laporan: Disetujui')
            ->assertSee('Bimbingan: Belum')
            ->assertSee('Laporan: Belum Review');

        $this->actingAs($this->koordinator)->withSession(['active_role' => 'koordinator_kp'])
            ->get('/management/final-reports?internal_status=completed')
            ->assertOk()
            ->assertSee('2210631230201');

        $this->actingAs($this->koordinator)->withSession(['active_role' => 'koordinator_kp'])
            ->get('/management/final-reports?field_status=completed')
            ->assertOk()
            ->assertDontSee('2210631230201');

        $this->actingAs($this->koordinator)->withSession(['active_role' => 'koordinator_kp'])
            ->get('/management/final-reports?sort=student')
            ->assertOk()
            ->assertSee('2210631230201');
    }

    public function test_coordinator_can_approve_submitted_report_on_behalf_of_each_supervisor_with_audit(): void
    {
        $report = $this->submittedReport();
        $base = '/management/final-reports/'.$report->id;

        $this->actingAs($this->koordinator)->withSession(['active_role' => 'koordinator_kp'])
            ->get('/management/final-reports?internal_status=report_pending')
            ->assertOk()->assertSee('2210631230201');
        $this->post($base.'/approve/internal', ['reason' => 'Dokumen telah diperiksa koordinator.'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('menunggu_review', $report->fresh()->status);
        $this->assertSame($this->koordinator->id, $report->fresh()->internal_reviewed_by);
        $this->assertDatabaseHas('kp_final_report_logs', ['kp_final_report_id' => $report->id, 'user_id' => $this->koordinator->id, 'action' => 'coordinator_internal_approved']);

        $this->post($base.'/approve/field', ['reason' => 'Dokumen lapangan telah diperiksa.'])->assertRedirect();
        $this->assertSame('disetujui', $report->fresh()->status);
        $this->assertSame($this->koordinator->id, $report->fresh()->field_reviewed_by);
        $this->get('/management/final-reports?internal_status=report_pending')->assertDontSee('2210631230201');
        $this->post($base.'/approve/field', ['reason' => 'Upaya approval ulang oleh koordinator.'])->assertSessionHasErrors('report');
    }

    public function test_coordinator_override_requires_reason_and_cannot_bypass_draft_revision_or_role(): void
    {
        $report = $this->submittedReport();
        $url = '/management/final-reports/'.$report->id.'/approve/internal';
        $this->actingAs($this->admin)->withSession(['active_role' => 'admin'])
            ->post($url, ['reason' => 'Dokumen sudah diperiksa.'])->assertForbidden();
        $this->actingAs($this->koordinator)->withSession(['active_role' => 'koordinator_kp'])
            ->post($url, ['reason' => 'pendek'])->assertSessionHasErrors('reason');
        $report->update(['status' => 'revisi']);
        $this->post($url, ['reason' => 'Dokumen sudah diperiksa.'])->assertSessionHasErrors('report');
        $this->assertNull($report->fresh()->internal_reviewed_by);
    }

    public function test_coordinator_can_validate_pending_guidance_and_complete_only_after_minimum(): void
    {
        $report = $this->submittedReport();
        $guidance = $this->assignment->reportGuidanceLogs()->create([
            'reviewer_type' => 'internal', 'guidance_date' => today(), 'topic' => 'Perbaikan laporan',
            'status' => 'menunggu_validasi', 'submitted_at' => now(),
        ]);
        $base = '/management/final-reports/'.$report->id;
        $this->actingAs($this->koordinator)->withSession(['active_role' => 'koordinator_kp'])
            ->get($base)->assertOk()->assertSee('Validasi Sesi');
        $this->post($base.'/guidance/internal/complete', ['reason' => 'Semua sesi sudah saya periksa.'])->assertSessionHasErrors('guidance');
        $this->post($base.'/guidance/'.$guidance->id.'/approve', ['reason' => 'Sesi dan dokumen telah diperiksa.'])->assertRedirect();
        $this->assertSame($this->koordinator->id, $guidance->fresh()->validated_by);
        $this->assertDatabaseHas('kp_final_report_logs', ['action' => 'coordinator_guidance_approved', 'user_id' => $this->koordinator->id]);
        $this->post($base.'/guidance/internal/complete', ['reason' => 'Semua sesi sudah saya periksa.'])->assertSessionHasErrors('guidance');
        for ($i = 1; $i < 8; $i++) {
            $this->assignment->reportGuidanceLogs()->create([
                'reviewer_type' => 'internal', 'guidance_date' => today(), 'topic' => 'Sesi '.$i,
                'status' => 'disetujui', 'submitted_at' => now(), 'validated_at' => now(), 'validated_by' => $this->lecturerUser->id,
            ]);
        }
        $this->post($base.'/guidance/internal/complete', ['reason' => 'Delapan sesi telah diverifikasi.'])->assertRedirect();
        $this->assertSame($this->koordinator->id, $report->fresh()->internal_guidance_completed_by);
        $this->assertDatabaseHas('kp_final_report_logs', ['action' => 'coordinator_internal_guidance_completed']);
    }

    public function test_management_can_preview_print_and_download_filtered_report_in_all_formats(): void
    {
        $this->submittedReport();
        $other = $this->makeUser('other-report@test.local', ['mahasiswa']);
        $otherStudent = $this->makeStudent($other, '2210631230999');
        $otherAssignment = $this->makeAssignment($otherStudent, $this->lecturer, $this->field);
        KpFinalReport::create(['kp_assignment_id' => $otherAssignment->id, 'current_version' => 1, 'status' => 'draft']);

        $query = '?q=2210631230201&status=menunggu_review';
        $this->actingAs($this->koordinator)->withSession(['active_role' => 'koordinator_kp'])
            ->get('/management/final-reports'.$query)
            ->assertOk()
            ->assertSee('Preview')
            ->assertSee('Excel');

        $this->actingAs($this->koordinator)->withSession(['active_role' => 'koordinator_kp'])
            ->get('/management/final-reports/report/preview'.$query)
            ->assertOk()
            ->assertSee('2210631230201')
            ->assertDontSee('2210631230999')
            ->assertSee('Total data:');

        $this->actingAs($this->koordinator)->withSession(['active_role' => 'koordinator_kp'])
            ->get('/management/final-reports/report/preview?print=1&q=2210631230201&status=menunggu_review')
            ->assertOk()
            ->assertSee('window.print()');

        $pdf = $this->actingAs($this->koordinator)->withSession(['active_role' => 'koordinator_kp'])
            ->get('/management/final-reports/report/download/pdf'.$query)
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $pdf->getContent());
        $this->assertStringNotContainsString('2210631230999', $pdf->getContent());

        $this->actingAs($this->koordinator)->withSession(['active_role' => 'koordinator_kp'])
            ->get('/management/final-reports/report/download/word'.$query)
            ->assertOk()
            ->assertHeader('Content-Type', 'application/msword; charset=UTF-8')
            ->assertSee('2210631230201')
            ->assertDontSee('2210631230999');

        $excel = $this->actingAs($this->koordinator)->withSession(['active_role' => 'koordinator_kp'])
            ->get('/management/final-reports/report/download/excel'.$query)
            ->assertOk();
        $this->assertMatchesRegularExpression('/^attachment; filename=monitoring-laporan-kp-\d{8}-\d{6}\.xlsx$/', $excel->headers->get('content-disposition'));

        $this->actingAs($this->koordinator)->withSession(['active_role' => 'koordinator_kp'])
            ->get('/management/final-reports/report/preview?q=tidak-ada')
            ->assertOk()
            ->assertSee('Belum ada data sesuai filter.');
        $this->actingAs($this->koordinator)->withSession(['active_role' => 'koordinator_kp'])
            ->get('/management/final-reports/report/download/excel?q=tidak-ada')
            ->assertOk();

        $this->actingAs($this->mahasiswa)->withSession(['active_role' => 'mahasiswa'])
            ->get('/management/final-reports/report/preview')->assertForbidden();
        $this->actingAs($this->mahasiswa)->withSession(['active_role' => 'mahasiswa'])
            ->get('/management/final-reports/report/download/pdf')->assertForbidden();
    }

    public function test_download_file_is_protected_by_ownership(): void
    {
        $report = $this->submittedReport();
        $file = $report->files()->first();
        Storage::disk('local')->put($file->file_path, 'dummy');

        $other = $this->makeUser('other-student-final@test.local', ['mahasiswa']);
        $this->makeStudent($other, '2210631230203');

        $this->actingAs($other)->withSession(['active_role' => 'mahasiswa'])
            ->get('/mahasiswa/laporan-akhir/files/'.$file->id.'/download')
            ->assertForbidden();

        $this->actingAs($this->mahasiswa)->withSession(['active_role' => 'mahasiswa'])
            ->get('/mahasiswa/laporan-akhir/files/'.$file->id.'/download')
            ->assertOk();
    }

    private function submittedReport(): KpFinalReport
    {
        $report = KpFinalReport::create(['kp_assignment_id' => $this->assignment->id, 'current_version' => 1, 'status' => 'menunggu_review', 'submitted_at' => now()]);
        $report->files()->create([
            'version' => 1,
            'original_filename' => 'laporan.pdf',
            'file_path' => 'kp-final-reports/laporan.pdf',
            'file_disk' => 'local',
            'file_mime' => 'application/pdf',
            'file_size' => 100,
            'uploaded_by' => $this->mahasiswa->id,
            'uploaded_at' => now(),
        ]);

        return $report;
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
