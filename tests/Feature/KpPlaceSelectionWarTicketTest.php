<?php

namespace Tests\Feature;

use App\Models\KpDocument;
use App\Models\KpDocumentRequirement;
use App\Models\KpPeriod;
use App\Models\KpPlace;
use App\Models\KpPlaceQuota;
use App\Models\KpPlaceSelection;
use App\Models\KpRegistration;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class KpPlaceSelectionWarTicketTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $koordinator;
    private User $mahasiswa;
    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->admin = $this->makeUser('admin@test.local', ['admin']);
        $this->koordinator = $this->makeUser('koordinator@test.local', ['koordinator_kp']);
        $this->mahasiswa = $this->makeUser('mahasiswa@test.local', ['mahasiswa']);
        $this->student = $this->makeStudent($this->mahasiswa, '2210631230001');
    }

    public function test_verified_student_can_open_place_selection_page(): void
    {
        [$registration] = $this->verifiedRegistration($this->student);

        $this->actingAs($this->mahasiswa)
            ->withSession(['active_role' => 'mahasiswa'])
            ->get('/mahasiswa/pemilihan-tempat/'.$registration->kp_period_id)
            ->assertOk()
            ->assertSee('Daftar Tempat KP');
    }

    public function test_unverified_student_cannot_select_place(): void
    {
        [$registration, $quota] = $this->draftRegistrationWithQuota($this->student);

        $this->actingAs($this->mahasiswa)
            ->withSession(['active_role' => 'mahasiswa'])
            ->post('/mahasiswa/pemilihan-tempat/'.$quota->id.'/pilih')
            ->assertSessionHasErrors('selection');

        $this->assertDatabaseMissing('kp_place_selections', ['kp_registration_id' => $registration->id]);
    }

    public function test_student_can_select_place_when_open_and_quota_available(): void
    {
        [$registration, $quota] = $this->verifiedRegistration($this->student);

        $this->actingAs($this->mahasiswa)
            ->withSession(['active_role' => 'mahasiswa'])
            ->post('/mahasiswa/pemilihan-tempat/'.$quota->id.'/pilih')
            ->assertRedirect();

        $this->assertDatabaseHas('kp_place_selections', [
            'kp_registration_id' => $registration->id,
            'kp_place_quota_id' => $quota->id,
            'status' => 'aktif',
        ]);
        $this->assertSame(1, $quota->fresh()->filledCount());
        $this->assertDatabaseHas('kp_selection_logs', ['action' => 'selection_success', 'status' => 'success']);
    }

    public function test_student_cannot_select_twice_or_outside_schedule_or_closed_quota(): void
    {
        [$registration, $quota] = $this->verifiedRegistration($this->student);

        $this->actingAs($this->mahasiswa)->withSession(['active_role' => 'mahasiswa'])->post('/mahasiswa/pemilihan-tempat/'.$quota->id.'/pilih')->assertRedirect();
        $this->actingAs($this->mahasiswa)->withSession(['active_role' => 'mahasiswa'])->post('/mahasiswa/pemilihan-tempat/'.$quota->id.'/pilih')->assertSessionHasErrors('selection');

        $other = $this->makeUser('other@student.test', ['mahasiswa']);
        $otherStudent = $this->makeStudent($other, '2210631230002');
        [$closedRegistration, $closedQuota] = $this->verifiedRegistration($otherStudent);
        $closedQuota->update(['is_open' => false]);
        $this->actingAs($other)->withSession(['active_role' => 'mahasiswa'])->post('/mahasiswa/pemilihan-tempat/'.$closedQuota->id.'/pilih')->assertSessionHasErrors('selection');

        $lateUser = $this->makeUser('late@student.test', ['mahasiswa']);
        $lateStudent = $this->makeStudent($lateUser, '2210631230003');
        [$lateRegistration, $lateQuota] = $this->verifiedRegistration($lateStudent);
        $lateRegistration->period->update(['selection_start_at' => now()->addDay(), 'selection_end_at' => now()->addDays(2)]);
        $this->actingAs($lateUser)->withSession(['active_role' => 'mahasiswa'])->post('/mahasiswa/pemilihan-tempat/'.$lateQuota->id.'/pilih')->assertSessionHasErrors('selection');

        $this->assertDatabaseHas('kp_selection_logs', ['action' => 'selection_failed_already_selected']);
        $this->assertDatabaseHas('kp_selection_logs', ['action' => 'selection_failed_quota_closed']);
        $this->assertDatabaseHas('kp_selection_logs', ['action' => 'selection_failed_not_open']);
    }

    public function test_full_quota_rejects_second_student_and_waiting_list_can_be_joined(): void
    {
        [$registration, $quota] = $this->verifiedRegistration($this->student, quota: 1);
        $secondUser = $this->makeUser('second@student.test', ['mahasiswa']);
        $secondStudent = $this->makeStudent($secondUser, '2210631230004');
        $secondRegistration = $this->verifiedRegistrationForPeriod($secondStudent, $registration->period, $quota->place);

        $this->actingAs($this->mahasiswa)->withSession(['active_role' => 'mahasiswa'])->post('/mahasiswa/pemilihan-tempat/'.$quota->id.'/pilih')->assertRedirect();
        $this->actingAs($secondUser)->withSession(['active_role' => 'mahasiswa'])->post('/mahasiswa/pemilihan-tempat/'.$quota->id.'/pilih')->assertSessionHasErrors('selection');

        $this->assertSame(1, KpPlaceSelection::where('kp_place_quota_id', $quota->id)->where('status', 'aktif')->count());
        $this->assertDatabaseHas('kp_waiting_lists', ['kp_registration_id' => $secondRegistration->id, 'status' => 'menunggu']);
        $this->assertDatabaseHas('kp_selection_logs', ['action' => 'selection_failed_quota_full']);
    }

    public function test_admin_and_koordinator_can_monitor_and_student_cannot(): void
    {
        $this->verifiedRegistration($this->student);

        $this->actingAs($this->admin)->withSession(['active_role' => 'admin'])->get('/management/place-selections')->assertOk()->assertSee('Monitoring Pemilihan');
        $this->actingAs($this->koordinator)->withSession(['active_role' => 'koordinator_kp'])->get('/management/place-selections')->assertOk();
        $this->actingAs($this->mahasiswa)->withSession(['active_role' => 'mahasiswa'])->get('/management/place-selections')->assertForbidden();
    }

    public function test_admin_can_cancel_and_move_selection(): void
    {
        [$registration, $quota] = $this->verifiedRegistration($this->student);
        $selection = KpPlaceSelection::create([
            'kp_period_id' => $registration->kp_period_id,
            'kp_registration_id' => $registration->id,
            'student_id' => $this->student->id,
            'kp_place_id' => $quota->kp_place_id,
            'kp_place_quota_id' => $quota->id,
            'selected_at' => now(),
            'selected_by' => $this->mahasiswa->id,
            'status' => 'aktif',
            'active_key' => $registration->kp_period_id.'-'.$this->student->id,
        ]);

        $this->actingAs($this->admin)->withSession(['active_role' => 'admin'])->post('/management/place-selections/'.$selection->id.'/cancel', ['reason' => 'Data perlu diperbaiki.'])->assertRedirect();
        $this->assertDatabaseHas('kp_place_selections', ['id' => $selection->id, 'status' => 'dibatalkan', 'active_key' => null]);
        $this->assertSame(0, $quota->fresh()->filledCount());

        $newPlace = KpPlace::create(['name' => 'RS Baru', 'type' => 'rumah_sakit', 'status' => 'aktif']);
        $newQuota = KpPlaceQuota::create(['kp_period_id' => $registration->kp_period_id, 'kp_place_id' => $newPlace->id, 'quota' => 5, 'is_open' => true]);
        $selection2 = KpPlaceSelection::create([
            'kp_period_id' => $registration->kp_period_id,
            'kp_registration_id' => $registration->id,
            'student_id' => $this->student->id,
            'kp_place_id' => $quota->kp_place_id,
            'kp_place_quota_id' => $quota->id,
            'selected_at' => now(),
            'selected_by' => $this->mahasiswa->id,
            'status' => 'aktif',
            'active_key' => $registration->kp_period_id.'-'.$this->student->id,
        ]);

        $this->actingAs($this->admin)->withSession(['active_role' => 'admin'])->post('/management/place-selections/'.$selection2->id.'/move', [
            'kp_place_quota_id' => $newQuota->id,
            'reason' => 'Pindah manual.',
        ])->assertRedirect();

        $this->assertDatabaseHas('kp_place_selections', ['id' => $selection2->id, 'status' => 'dipindahkan']);
        $this->assertDatabaseHas('kp_place_selections', ['kp_place_quota_id' => $newQuota->id, 'status' => 'aktif']);
        $this->assertDatabaseHas('kp_selection_logs', ['action' => 'selection_moved_by_admin']);
    }

    private function verifiedRegistration(Student $student, int $quota = 5): array
    {
        $period = KpPeriod::create([
            'name' => 'KP Genap 2026',
            'registration_start_at' => now()->subDays(10),
            'registration_end_at' => now()->subDays(2),
            'selection_start_at' => now()->subHour(),
            'selection_end_at' => now()->addDay(),
            'status' => 'dibuka',
        ]);
        $place = KpPlace::create(['name' => 'Apotek Sehat', 'type' => 'apotek', 'status' => 'aktif']);
        $quotaModel = KpPlaceQuota::create(['kp_period_id' => $period->id, 'kp_place_id' => $place->id, 'quota' => $quota, 'is_open' => true]);
        $registration = $this->verifiedRegistrationForPeriod($student, $period, $place);

        return [$registration, $quotaModel];
    }

    private function verifiedRegistrationForPeriod(Student $student, KpPeriod $period, KpPlace $place): KpRegistration
    {
        $requirement = KpDocumentRequirement::firstOrCreate(['kp_period_id' => $period->id, 'name' => 'KRS'], ['is_required' => true, 'status' => 'aktif']);
        $registration = KpRegistration::create(['kp_period_id' => $period->id, 'student_id' => $student->id, 'status' => 'terverifikasi']);
        KpDocument::create([
            'kp_registration_id' => $registration->id,
            'kp_document_requirement_id' => $requirement->id,
            'original_filename' => 'krs.pdf',
            'file_path' => 'kp-documents/test/krs.pdf',
            'status' => 'disetujui',
        ]);

        return $registration;
    }

    private function draftRegistrationWithQuota(Student $student): array
    {
        [$registration, $quota] = $this->verifiedRegistration($student);
        $registration->update(['status' => 'draft']);

        return [$registration, $quota];
    }

    private function makeStudent(User $user, string $nim): Student
    {
        $user->forceFill(['profile_completed' => true])->save();

        return Student::create([
            'user_id' => $user->id,
            'nim' => $nim,
            'study_program' => 'Farmasi',
            'semester' => 6,
            'phone' => '081234567890',
            'status' => 'active',
        ]);
    }

    private function makeUser(string $email, array $roles): User
    {
        $user = User::create([
            'name' => 'User Test',
            'email' => $email,
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);

        $user->roles()->sync(Role::whereIn('name', $roles)->pluck('id'));

        return $user;
    }
}
