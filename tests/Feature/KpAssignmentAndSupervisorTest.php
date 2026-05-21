<?php

namespace Tests\Feature;

use App\Models\FieldSupervisor;
use App\Models\KpAssignment;
use App\Models\KpDocument;
use App\Models\KpDocumentRequirement;
use App\Models\KpPeriod;
use App\Models\KpPlace;
use App\Models\KpPlaceQuota;
use App\Models\KpPlaceSelection;
use App\Models\KpRegistration;
use App\Models\Lecturer;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class KpAssignmentAndSupervisorTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $koordinator;
    private User $mahasiswa;
    private Student $student;
    private User $lecturerUser;
    private Lecturer $lecturer;
    private User $fieldUser;
    private FieldSupervisor $fieldSupervisor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->admin = $this->makeUser('admin@test.local', ['admin']);
        $this->koordinator = $this->makeUser('koordinator@test.local', ['koordinator_kp']);
        $this->mahasiswa = $this->makeUser('mahasiswa@test.local', ['mahasiswa']);
        $this->student = $this->makeStudent($this->mahasiswa, '2210631230001');
        $this->lecturerUser = $this->makeUser('dosen@test.local', ['pembimbing_dalam']);
        $this->lecturer = Lecturer::create(['user_id' => $this->lecturerUser->id, 'nidn_nip' => '001122', 'status' => 'active']);
        $this->fieldUser = $this->makeUser('lapangan@test.local', ['pembimbing_lapangan']);
        $this->fieldSupervisor = FieldSupervisor::create(['user_id' => $this->fieldUser->id, 'institution_name' => 'Apotek Sehat', 'position' => 'Supervisor', 'status' => 'active']);
    }

    public function test_admin_and_koordinator_can_open_assignment_page_but_mahasiswa_cannot(): void
    {
        $this->actingAs($this->admin)->withSession(['active_role' => 'admin'])->get('/management/kp-assignments')->assertOk()->assertSee('Penempatan KP');
        $this->actingAs($this->koordinator)->withSession(['active_role' => 'koordinator_kp'])->get('/management/kp-assignments')->assertOk();
        $this->actingAs($this->mahasiswa)->withSession(['active_role' => 'mahasiswa'])->get('/management/kp-assignments')->assertForbidden();
    }

    public function test_admin_can_create_assignment_from_active_selection_and_duplicate_is_rejected(): void
    {
        $selection = $this->activeSelection($this->student);

        $this->actingAs($this->admin)->withSession(['active_role' => 'admin'])->post('/management/kp-assignments', [
            'kp_place_selection_id' => $selection->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('kp_assignments', ['kp_place_selection_id' => $selection->id, 'status' => 'menunggu_pembimbing']);
        $this->assertDatabaseHas('kp_assignment_logs', ['action' => 'assignment_created']);

        $this->actingAs($this->admin)->withSession(['active_role' => 'admin'])->post('/management/kp-assignments', [
            'kp_place_selection_id' => $selection->id,
        ])->assertSessionHasErrors('selection');
    }

    public function test_assignment_from_cancelled_selection_is_rejected(): void
    {
        $selection = $this->activeSelection($this->student);
        $selection->update(['status' => 'dibatalkan', 'active_key' => null]);

        $this->actingAs($this->admin)->withSession(['active_role' => 'admin'])->post('/management/kp-assignments', [
            'kp_place_selection_id' => $selection->id,
        ])->assertSessionHasErrors('selection');
    }

    public function test_supervisors_can_be_assigned_and_status_becomes_active(): void
    {
        $assignment = $this->assignment();

        $this->actingAs($this->koordinator)->withSession(['active_role' => 'koordinator_kp'])->post('/management/kp-assignments/'.$assignment->id.'/assign-internal-supervisor', [
            'internal_supervisor_id' => $this->lecturer->id,
        ])->assertRedirect();

        $this->actingAs($this->admin)->withSession(['active_role' => 'admin'])->post('/management/kp-assignments/'.$assignment->id.'/assign-field-supervisor', [
            'field_supervisor_id' => $this->fieldSupervisor->id,
        ])->assertRedirect();

        $assignment->refresh();
        $this->assertSame('aktif', $assignment->status);
        $this->assertDatabaseHas('kp_place_field_supervisors', ['kp_place_id' => $assignment->kp_place_id, 'field_supervisor_id' => $this->fieldSupervisor->id]);
    }

    public function test_student_and_supervisors_only_see_their_own_assignments_and_cancel_logs(): void
    {
        $assignment = $this->assignment($this->lecturer, $this->fieldSupervisor);

        $this->actingAs($this->mahasiswa)->withSession(['active_role' => 'mahasiswa'])->get('/mahasiswa/penempatan-kp')->assertOk()->assertSee($assignment->place->name);
        $this->actingAs($this->lecturerUser)->withSession(['active_role' => 'pembimbing_dalam'])->get('/pembimbing-dalam/mahasiswa-bimbingan')->assertOk()->assertSee($this->student->user->name);
        $this->actingAs($this->fieldUser)->withSession(['active_role' => 'pembimbing_lapangan'])->get('/pembimbing-lapangan/mahasiswa-kp')->assertOk()->assertSee($this->student->user->name);

        $otherLecturerUser = $this->makeUser('other-dosen@test.local', ['pembimbing_dalam']);
        Lecturer::create(['user_id' => $otherLecturerUser->id, 'nidn_nip' => '0099', 'status' => 'active']);
        $this->actingAs($otherLecturerUser)->withSession(['active_role' => 'pembimbing_dalam'])->get('/pembimbing-dalam/mahasiswa-bimbingan/'.$assignment->id)->assertForbidden();

        $this->actingAs($this->admin)->withSession(['active_role' => 'admin'])->post('/management/kp-assignments/'.$assignment->id.'/cancel', [
            'reason' => 'Dibatalkan untuk test.',
        ])->assertRedirect();

        $this->assertDatabaseHas('kp_assignment_logs', ['kp_assignment_id' => $assignment->id, 'action' => 'assignment_cancelled']);
    }

    private function assignment(?Lecturer $lecturer = null, ?FieldSupervisor $fieldSupervisor = null): KpAssignment
    {
        $selection = $this->activeSelection($this->student);

        return KpAssignment::create([
            'kp_period_id' => $selection->kp_period_id,
            'kp_registration_id' => $selection->kp_registration_id,
            'kp_place_selection_id' => $selection->id,
            'student_id' => $selection->student_id,
            'kp_place_id' => $selection->kp_place_id,
            'internal_supervisor_id' => $lecturer?->id,
            'field_supervisor_id' => $fieldSupervisor?->id,
            'status' => ($lecturer && $fieldSupervisor) ? 'aktif' : 'menunggu_pembimbing',
            'assigned_by' => $this->admin->id,
            'assigned_at' => now(),
            'active_key' => $selection->kp_period_id.'-'.$selection->student_id,
        ]);
    }

    private function activeSelection(Student $student): KpPlaceSelection
    {
        $period = KpPeriod::create(['name' => 'KP Genap 2026', 'status' => 'dibuka']);
        $place = KpPlace::create(['name' => 'Apotek Sehat', 'type' => 'apotek', 'status' => 'aktif']);
        $quota = KpPlaceQuota::create(['kp_period_id' => $period->id, 'kp_place_id' => $place->id, 'quota' => 5, 'is_open' => true]);
        $requirement = KpDocumentRequirement::create(['kp_period_id' => $period->id, 'name' => 'KRS', 'is_required' => true, 'status' => 'aktif']);
        $registration = KpRegistration::create(['kp_period_id' => $period->id, 'student_id' => $student->id, 'status' => 'terverifikasi']);
        KpDocument::create(['kp_registration_id' => $registration->id, 'kp_document_requirement_id' => $requirement->id, 'file_path' => 'x.pdf', 'status' => 'disetujui']);

        return KpPlaceSelection::create([
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
