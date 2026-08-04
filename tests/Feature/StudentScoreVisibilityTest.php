<?php

namespace Tests\Feature;

use App\Models\KpAssignment;
use App\Models\KpFinalReport;
use App\Models\KpFinalScore;
use App\Models\KpPeriod;
use App\Models\KpPlace;
use App\Models\KpQuestionnaire;
use App\Models\KpQuestionnaireResponse;
use App\Models\KpRegistration;
use App\Models\KpScoreVisibilityOverride;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StudentScoreVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private User $koordinator;
    private User $mahasiswa;
    private Student $student;
    private KpPeriod $period;
    private KpAssignment $assignment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->koordinator = $this->makeUser('koor-visibility@test.local', ['koordinator_kp']);
        $this->mahasiswa = $this->makeUser('student-visibility@test.local', ['mahasiswa']);
        $this->student = Student::create([
            'user_id' => $this->mahasiswa->id,
            'nim' => '2441624820199',
            'study_program' => 'Farmasi',
            'semester' => 7,
            'phone' => '081234567890',
            'status' => 'active',
        ]);
        $this->mahasiswa->forceFill(['profile_completed' => true])->save();

        $this->period = KpPeriod::create([
            'name' => 'KP TA 2026_2027',
            'status' => 'dibuka',
            'score_visible_to_students' => false,
        ]);
        $place = KpPlace::create(['name' => 'RS UAT', 'type' => 'rumah_sakit', 'status' => 'aktif']);
        $registration = KpRegistration::create([
            'kp_period_id' => $this->period->id,
            'student_id' => $this->student->id,
            'status' => 'terverifikasi',
        ]);

        $this->assignment = KpAssignment::create([
            'kp_period_id' => $this->period->id,
            'kp_registration_id' => $registration->id,
            'student_id' => $this->student->id,
            'kp_place_id' => $place->id,
            'status' => 'aktif',
            'assigned_by' => $this->koordinator->id,
            'assigned_at' => now(),
            'active_key' => $this->period->id.'-'.$this->student->id,
        ]);

        KpFinalScore::create([
            'kp_assignment_id' => $this->assignment->id,
            'final_score' => 88,
            'final_grade' => 'A',
            'status' => 'published',
            'published_at' => now(),
            'calculated_at' => now(),
        ]);

        KpFinalReport::create([
            'kp_assignment_id' => $this->assignment->id,
            'status' => 'disetujui',
            'internal_review_status' => 'disetujui',
            'field_review_status' => 'disetujui',
            'final_document_url' => 'https://drive.google.com/example-final-report',
            'submitted_at' => now(),
            'approved_at' => now(),
        ]);
    }

    public function test_student_score_is_hidden_until_period_visibility_is_opened(): void
    {
        $this->actingAs($this->mahasiswa)->withSession(['active_role' => 'mahasiswa'])
            ->get('/mahasiswa/nilai')
            ->assertOk()
            ->assertSee('Nilai belum dapat dibuka')
            ->assertSee('Akses nilai periode ini belum dibuka oleh Koordinator KP.')
            ->assertDontSee('Nilai Akhir KP');

        $this->period->update(['score_visible_to_students' => true]);

        $this->actingAs($this->mahasiswa)->withSession(['active_role' => 'mahasiswa'])
            ->get('/mahasiswa/nilai')
            ->assertOk()
            ->assertSee('Nilai Akhir KP')
            ->assertSee('88')
            ->assertSee('A');
    }

    public function test_student_specific_override_can_allow_or_block_score_visibility(): void
    {
        KpScoreVisibilityOverride::create([
            'kp_period_id' => $this->period->id,
            'student_id' => $this->student->id,
            'can_view' => true,
            'note' => 'Boleh lihat lebih awal.',
            'created_by' => $this->koordinator->id,
            'updated_by' => $this->koordinator->id,
        ]);

        $this->actingAs($this->mahasiswa)->withSession(['active_role' => 'mahasiswa'])
            ->get('/mahasiswa/nilai')
            ->assertOk()
            ->assertSee('Nilai Akhir KP');

        $this->period->update(['score_visible_to_students' => true]);
        KpScoreVisibilityOverride::query()->update(['can_view' => false, 'note' => 'Ditahan khusus.']);

        $this->actingAs($this->mahasiswa)->withSession(['active_role' => 'mahasiswa'])
            ->get('/mahasiswa/nilai')
            ->assertOk()
            ->assertSee('Akses nilai Anda ditahan oleh Koordinator KP.')
            ->assertDontSee('Nilai Akhir KP');
    }

    public function test_active_student_questionnaire_must_be_submitted_before_score_is_visible(): void
    {
        $this->period->update(['score_visible_to_students' => true]);
        $questionnaire = KpQuestionnaire::create([
            'kp_period_id' => $this->period->id,
            'audience' => KpQuestionnaire::AUDIENCE_STUDENT,
            'title' => 'Kuisioner Mahasiswa',
            'status' => 'aktif',
            'created_by' => $this->koordinator->id,
            'updated_by' => $this->koordinator->id,
        ]);

        $this->actingAs($this->mahasiswa)->withSession(['active_role' => 'mahasiswa'])
            ->get('/mahasiswa/nilai')
            ->assertOk()
            ->assertSee('Nilai belum bisa dibuka karena syarat akhir belum lengkap.')
            ->assertSee('Kuisioner KP mahasiswa sudah diisi')
            ->assertDontSee('Nilai Akhir KP');

        KpQuestionnaireResponse::create([
            'kp_questionnaire_id' => $questionnaire->id,
            'kp_assignment_id' => $this->assignment->id,
            'kp_place_id' => $this->assignment->kp_place_id,
            'kp_period_id' => $this->period->id,
            'respondent_user_id' => $this->mahasiswa->id,
            'respondent_role' => 'mahasiswa',
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $this->actingAs($this->mahasiswa)->withSession(['active_role' => 'mahasiswa'])
            ->get('/mahasiswa/nilai')
            ->assertOk()
            ->assertSee('Nilai Akhir KP');
    }

    public function test_koordinator_can_update_period_visibility_and_student_override(): void
    {
        $this->actingAs($this->koordinator)->withSession(['active_role' => 'koordinator_kp'])
            ->patch('/management/scores/periods/'.$this->period->id.'/visibility', [
                'score_visible_to_students' => '1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('kp_periods', [
            'id' => $this->period->id,
            'score_visible_to_students' => true,
        ]);

        $this->actingAs($this->koordinator)->withSession(['active_role' => 'koordinator_kp'])
            ->patch('/management/scores/'.$this->assignment->id.'/visibility-override', [
                'visibility_override' => 'deny',
                'visibility_note' => 'Tahan sampai revisi selesai.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('kp_score_visibility_overrides', [
            'kp_period_id' => $this->period->id,
            'student_id' => $this->student->id,
            'can_view' => false,
        ]);

        $this->actingAs($this->koordinator)->withSession(['active_role' => 'koordinator_kp'])
            ->patch('/management/scores/'.$this->assignment->id.'/visibility-override', [
                'visibility_override' => 'inherit',
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('kp_score_visibility_overrides', [
            'kp_period_id' => $this->period->id,
            'student_id' => $this->student->id,
        ]);
    }

    private function makeUser(string $email, array $roles): User
    {
        $user = User::create([
            'name' => 'User Visibility',
            'email' => $email,
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);
        $user->roles()->sync(Role::whereIn('name', $roles)->pluck('id'));

        return $user;
    }
}
