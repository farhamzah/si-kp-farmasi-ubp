<?php

namespace Tests\Feature;

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
use Illuminate\Support\Str;
use Tests\TestCase;

class KpInternalSupervisorWorkloadReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_internal_supervisor_workload_report_with_totals(): void
    {
        $this->seed(RoleSeeder::class);
        $admin = $this->makeUser('admin@test.local', ['admin']);
        $lecturer = $this->lecturer('apt. Dosen Beban, M.Farm.');
        $otherLecturer = $this->lecturer('Dosen Lain');
        $period = KpPeriod::create(['name' => 'KP TA 2026_2027', 'status' => 'dibuka']);

        $this->assignment($period, $lecturer, 'RSUD Karawang', 'rumah_sakit', 'aktif');
        $this->assignment($period, $lecturer, 'Apotik Hafizh', 'apotek', 'aktif');
        $this->assignment($period, $lecturer, 'PT Glow', 'industri', 'berjalan');
        $this->assignment($period, $lecturer, 'Klinik Pratama', 'klinik', 'selesai');
        $this->assignment($period, $lecturer, 'RS Lama', 'rumah_sakit', 'dibatalkan');
        $this->assignment($period, $otherLecturer, 'Apotek Sehat', 'apotek', 'aktif');

        $this->actingAs($admin)
            ->withSession(['active_role' => 'admin'])
            ->get('/management/internal-supervisor-workload?q=Beban')
            ->assertOk()
            ->assertSee('Report Pembimbing Dalam')
            ->assertSee('apt. Dosen Beban, M.Farm.')
            ->assertSee('TOTAL')
            ->assertSeeInOrder(['apt. Dosen Beban, M.Farm.', '1', '1', '1', '1', '4'])
            ->assertDontSee('Dosen Lain');
    }

    public function test_report_preview_print_and_downloads_are_available(): void
    {
        $this->seed(RoleSeeder::class);
        $admin = $this->makeUser('admin-report@test.local', ['admin']);
        $lecturer = $this->lecturer('Dosen Export');
        $period = KpPeriod::create(['name' => 'KP TA 2026_2027', 'status' => 'dibuka']);
        $this->assignment($period, $lecturer, 'RS Hermina', 'rumah_sakit', 'aktif');

        $this->actingAs($admin)
            ->withSession(['active_role' => 'admin'])
            ->get('/management/internal-supervisor-workload/preview?print=1')
            ->assertOk()
            ->assertSee('onload="window.print()"', false)
            ->assertSee('Dosen Export');

        $this->actingAs($admin)
            ->withSession(['active_role' => 'admin'])
            ->get('/management/internal-supervisor-workload/download/word')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/msword; charset=UTF-8');

        $this->actingAs($admin)
            ->withSession(['active_role' => 'admin'])
            ->get('/management/internal-supervisor-workload/download/pdf')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $excelResponse = $this->actingAs($admin)
            ->withSession(['active_role' => 'admin'])
            ->get('/management/internal-supervisor-workload/download/excel')
            ->assertOk();

        $this->assertStringContainsString('.xlsx', (string) $excelResponse->headers->get('Content-Disposition'));
    }

    private function lecturer(string $name): Lecturer
    {
        $user = $this->makeUser(Str::slug($name).'@test.local', ['pembimbing_dalam']);
        $user->update(['name' => $name]);

        return Lecturer::create(['user_id' => $user->id, 'nidn_nip' => (string) random_int(100000, 999999), 'status' => 'active']);
    }

    private function assignment(KpPeriod $period, Lecturer $lecturer, string $placeName, string $placeType, string $status): KpAssignment
    {
        $studentUser = $this->makeUser(uniqid('student', true).'@test.local', ['mahasiswa']);
        $student = Student::create(['user_id' => $studentUser->id, 'nim' => (string) random_int(2210000000000, 2219999999999), 'study_program' => 'Farmasi', 'semester' => 6, 'phone' => '081234567890', 'status' => 'active']);
        $place = KpPlace::create(['name' => $placeName, 'type' => $placeType, 'status' => 'aktif']);
        $quota = KpPlaceQuota::create(['kp_period_id' => $period->id, 'kp_place_id' => $place->id, 'quota' => 5, 'is_open' => true]);
        $requirement = KpDocumentRequirement::create(['kp_period_id' => $period->id, 'name' => 'KRS', 'is_required' => true, 'status' => 'aktif']);
        $registration = KpRegistration::create(['kp_period_id' => $period->id, 'student_id' => $student->id, 'status' => 'terverifikasi']);
        KpDocument::create(['kp_registration_id' => $registration->id, 'kp_document_requirement_id' => $requirement->id, 'file_path' => 'x.pdf', 'status' => 'disetujui']);
        $selection = KpPlaceSelection::create([
            'kp_period_id' => $period->id,
            'kp_registration_id' => $registration->id,
            'student_id' => $student->id,
            'kp_place_id' => $place->id,
            'kp_place_quota_id' => $quota->id,
            'selected_at' => now(),
            'selected_by' => $studentUser->id,
            'status' => $status === 'dibatalkan' ? 'dibatalkan' : 'aktif',
            'active_key' => $status === 'dibatalkan' ? null : $period->id.'-'.$student->id,
        ]);

        return KpAssignment::create([
            'kp_period_id' => $period->id,
            'kp_registration_id' => $registration->id,
            'kp_place_selection_id' => $selection->id,
            'student_id' => $student->id,
            'kp_place_id' => $place->id,
            'internal_supervisor_id' => $lecturer->id,
            'status' => $status,
            'assigned_by' => $lecturer->user_id,
            'assigned_at' => now(),
            'active_key' => $status === 'dibatalkan' ? null : $period->id.'-'.$student->id,
        ]);
    }

    private function makeUser(string $email, array $roles): User
    {
        $user = User::create(['name' => 'User Test', 'email' => $email, 'password' => Hash::make('password'), 'status' => 'active']);
        $user->roles()->sync(Role::whereIn('name', $roles)->pluck('id'));

        return $user;
    }
}
