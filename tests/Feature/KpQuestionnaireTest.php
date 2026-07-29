<?php

namespace Tests\Feature;

use App\Models\FieldSupervisor;
use App\Models\KpAssignment;
use App\Models\KpPeriod;
use App\Models\KpPlace;
use App\Models\KpQuestionnaire;
use App\Models\KpQuestionnaireQuestion;
use App\Models\KpQuestionnaireResponse;
use App\Models\KpRegistration;
use App\Models\Lecturer;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class KpQuestionnaireTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $koordinator;
    private User $mahasiswa;
    private Student $student;
    private User $fieldUser;
    private FieldSupervisor $fieldSupervisor;
    private KpAssignment $assignment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->admin = $this->makeUser('admin-questionnaire@test.local', ['admin']);
        $this->koordinator = $this->makeUser('koordinator-questionnaire@test.local', ['koordinator_kp']);
        $this->mahasiswa = $this->makeUser('student-questionnaire@test.local', ['mahasiswa']);
        $this->student = $this->makeStudent($this->mahasiswa, '2441624820999');
        $lecturerUser = $this->makeUser('lecturer-questionnaire@test.local', ['pembimbing_dalam']);
        $lecturer = Lecturer::create(['user_id' => $lecturerUser->id, 'nidn_nip' => '001122', 'status' => 'active']);
        $this->fieldUser = $this->makeUser('field-questionnaire@test.local', ['pembimbing_lapangan']);
        $this->fieldSupervisor = FieldSupervisor::create(['user_id' => $this->fieldUser->id, 'institution_name' => 'Apotek Sehat', 'status' => 'active']);
        $this->assignment = $this->makeAssignment($this->student, $lecturer, $this->fieldSupervisor);
    }

    public function test_management_builder_creates_default_questionnaires(): void
    {
        $this->actingAs($this->koordinator)
            ->withSession(['active_role' => 'koordinator_kp'])
            ->get('/management/questionnaires')
            ->assertOk()
            ->assertSee('Kuisioner Kepuasan Mahasiswa KP')
            ->assertSee('Kuisioner Kepuasan Tempat KP');

        $this->assertDatabaseHas('kp_questionnaires', ['audience' => KpQuestionnaire::AUDIENCE_STUDENT]);
        $this->assertDatabaseHas('kp_questionnaires', ['audience' => KpQuestionnaire::AUDIENCE_FIELD_SUPERVISOR]);
        $this->assertGreaterThanOrEqual(40, KpQuestionnaireQuestion::count());
    }

    public function test_student_can_submit_questionnaire_and_management_can_view_result(): void
    {
        $questionnaire = $this->makeQuestionnaire(KpQuestionnaire::AUDIENCE_STUDENT);
        $question = $questionnaire->questions()->firstOrFail();

        $this->actingAs($this->mahasiswa)
            ->withSession(['active_role' => 'mahasiswa'])
            ->get('/mahasiswa/kuisioner')
            ->assertOk()
            ->assertSee('Kuisioner singkat');

        $this->actingAs($this->mahasiswa)
            ->withSession(['active_role' => 'mahasiswa'])
            ->post('/mahasiswa/kuisioner/'.$questionnaire->id, [
                'answers' => [$question->id => 5],
            ])
            ->assertRedirect(route('student.questionnaires.index'));

        $this->assertDatabaseHas('kp_questionnaire_responses', [
            'kp_questionnaire_id' => $questionnaire->id,
            'kp_assignment_id' => $this->assignment->id,
            'respondent_user_id' => $this->mahasiswa->id,
            'status' => 'submitted',
        ]);

        $this->actingAs($this->admin)
            ->withSession(['active_role' => 'admin'])
            ->get('/management/questionnaire-results')
            ->assertOk()
            ->assertSee($this->mahasiswa->name)
            ->assertSee('Kuisioner singkat')
            ->assertSee('Rata-rata')
            ->assertSee('Sangat baik')
            ->assertSee('Kepuasan sangat kuat');

        $response = \App\Models\KpQuestionnaireResponse::where('kp_questionnaire_id', $questionnaire->id)->firstOrFail();

        $this->actingAs($this->admin)
            ->withSession(['active_role' => 'admin'])
            ->get('/management/questionnaire-results/'.$response->id)
            ->assertOk()
            ->assertSee('Skor Respons')
            ->assertSee('Kesimpulan Individual')
            ->assertSee('5');
    }

    public function test_field_supervisor_can_submit_only_for_own_assignment(): void
    {
        $questionnaire = $this->makeQuestionnaire(KpQuestionnaire::AUDIENCE_FIELD_SUPERVISOR);
        $question = $questionnaire->questions()->firstOrFail();
        $otherFieldUser = $this->makeUser('other-field-questionnaire@test.local', ['pembimbing_lapangan']);
        FieldSupervisor::create(['user_id' => $otherFieldUser->id, 'institution_name' => 'RS Lain', 'status' => 'active']);

        $this->actingAs($otherFieldUser)
            ->withSession(['active_role' => 'pembimbing_lapangan'])
            ->get('/pembimbing-lapangan/kuisioner/'.$this->assignment->id.'/'.$questionnaire->id)
            ->assertForbidden();

        $this->actingAs($this->fieldUser)
            ->withSession(['active_role' => 'pembimbing_lapangan'])
            ->post('/pembimbing-lapangan/kuisioner/'.$this->assignment->id.'/'.$questionnaire->id, [
                'answers' => [$question->id => 4],
            ])
            ->assertRedirect(route('field-supervisor.questionnaires.index'));

        $this->assertDatabaseHas('kp_questionnaire_responses', [
            'kp_questionnaire_id' => $questionnaire->id,
            'kp_assignment_id' => null,
            'kp_place_id' => $this->assignment->kp_place_id,
            'kp_period_id' => $this->assignment->kp_period_id,
            'respondent_user_id' => $this->fieldUser->id,
            'respondent_role' => 'pembimbing_lapangan',
            'status' => 'submitted',
        ]);
    }

    public function test_field_supervisor_questionnaire_is_one_response_per_place_and_period(): void
    {
        $questionnaire = $this->makeQuestionnaire(KpQuestionnaire::AUDIENCE_FIELD_SUPERVISOR);
        $question = $questionnaire->questions()->firstOrFail();
        $otherStudentUser = $this->makeUser('second-student-questionnaire@test.local', ['mahasiswa']);
        $otherStudent = $this->makeStudent($otherStudentUser, '2441624820888');
        $registration = KpRegistration::create([
            'kp_period_id' => $this->assignment->kp_period_id,
            'student_id' => $otherStudent->id,
            'status' => 'terverifikasi',
        ]);
        $secondAssignment = KpAssignment::create([
            'kp_period_id' => $this->assignment->kp_period_id,
            'kp_registration_id' => $registration->id,
            'student_id' => $otherStudent->id,
            'kp_place_id' => $this->assignment->kp_place_id,
            'internal_supervisor_id' => $this->assignment->internal_supervisor_id,
            'field_supervisor_id' => $this->fieldSupervisor->id,
            'status' => 'aktif',
            'assigned_by' => $this->admin->id,
            'assigned_at' => now()->addMinute(),
            'active_key' => $this->assignment->kp_period_id.'-'.$otherStudent->id,
        ]);

        $this->actingAs($this->fieldUser)
            ->withSession(['active_role' => 'pembimbing_lapangan'])
            ->get('/pembimbing-lapangan/kuisioner')
            ->assertOk()
            ->assertSee('1 tempat')
            ->assertSee('2 mahasiswa')
            ->assertSee($this->assignment->place->name);

        $this->actingAs($this->fieldUser)
            ->withSession(['active_role' => 'pembimbing_lapangan'])
            ->post('/pembimbing-lapangan/kuisioner/'.$this->assignment->id.'/'.$questionnaire->id, [
                'answers' => [$question->id => 4],
            ])
            ->assertRedirect(route('field-supervisor.questionnaires.index'));

        $this->actingAs($this->fieldUser)
            ->withSession(['active_role' => 'pembimbing_lapangan'])
            ->post('/pembimbing-lapangan/kuisioner/'.$secondAssignment->id.'/'.$questionnaire->id, [
                'answers' => [$question->id => 5],
            ])
            ->assertRedirect(route('field-supervisor.questionnaires.index'));

        $responses = KpQuestionnaireResponse::query()
            ->where('kp_questionnaire_id', $questionnaire->id)
            ->where('respondent_user_id', $this->fieldUser->id)
            ->where('kp_place_id', $this->assignment->kp_place_id)
            ->where('kp_period_id', $this->assignment->kp_period_id)
            ->get();

        $this->assertCount(1, $responses);
        $this->assertSame('5', $responses->first()->answerMap()[$question->id]);
    }

    private function makeQuestionnaire(string $audience): KpQuestionnaire
    {
        $questionnaire = KpQuestionnaire::create([
            'audience' => $audience,
            'title' => 'Kuisioner singkat',
            'description' => 'Untuk test.',
            'status' => 'aktif',
            'created_by' => $this->admin->id,
        ]);

        $questionnaire->questions()->create([
            'section' => 'Kepuasan',
            'question_text' => 'Layanan KP berjalan baik.',
            'answer_type' => KpQuestionnaireQuestion::TYPE_SCALE,
            'is_required' => true,
            'sort_order' => 1,
            'status' => 'aktif',
        ]);

        return $questionnaire;
    }

    private function makeAssignment(Student $student, Lecturer $lecturer, FieldSupervisor $fieldSupervisor): KpAssignment
    {
        $period = KpPeriod::create(['name' => 'KP TA 2026_2027', 'status' => 'dibuka']);
        $place = KpPlace::create(['name' => 'Apotek Sehat', 'type' => 'apotek', 'status' => 'aktif']);
        $registration = KpRegistration::create(['kp_period_id' => $period->id, 'student_id' => $student->id, 'status' => 'terverifikasi']);

        return KpAssignment::create([
            'kp_period_id' => $period->id,
            'kp_registration_id' => $registration->id,
            'student_id' => $student->id,
            'kp_place_id' => $place->id,
            'internal_supervisor_id' => $lecturer->id,
            'field_supervisor_id' => $fieldSupervisor->id,
            'status' => 'aktif',
            'assigned_by' => $this->admin->id,
            'assigned_at' => now(),
            'active_key' => $period->id.'-'.$student->id,
        ]);
    }

    private function makeStudent(User $user, string $nim): Student
    {
        $user->forceFill(['profile_completed' => true])->save();

        return Student::create(['user_id' => $user->id, 'nim' => $nim, 'study_program' => 'Farmasi', 'semester' => 7, 'status' => 'active']);
    }

    private function makeUser(string $email, array $roles): User
    {
        $user = User::create(['name' => 'User Kuisioner', 'email' => $email, 'password' => Hash::make('password'), 'status' => 'active']);
        $user->roles()->sync(Role::whereIn('name', $roles)->pluck('id'));

        return $user;
    }
}
