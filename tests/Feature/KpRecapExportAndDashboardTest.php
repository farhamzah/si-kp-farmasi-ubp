<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class KpRecapExportAndDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $koordinator;
    private User $mahasiswa;
    private User $internal;
    private User $field;
    private User $examiner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->admin = $this->makeUser('admin-recap@test.local', ['admin']);
        $this->koordinator = $this->makeUser('koor-recap@test.local', ['koordinator_kp']);
        $this->mahasiswa = $this->makeUser('student-recap@test.local', ['mahasiswa']);
        $this->internal = $this->makeUser('internal-recap@test.local', ['pembimbing_dalam']);
        $this->field = $this->makeUser('field-recap@test.local', ['pembimbing_lapangan']);
        $this->examiner = $this->makeUser('examiner-recap@test.local', ['penguji']);
    }

    public function test_admin_and_koordinator_can_open_recaps_and_other_roles_cannot(): void
    {
        $this->actingAs($this->admin)->withSession(['active_role' => 'admin'])
            ->get('/management/recaps')
            ->assertOk()
            ->assertSee('Rekap, Monitoring, dan Export KP');

        $this->actingAs($this->koordinator)->withSession(['active_role' => 'koordinator_kp'])
            ->get('/management/recaps/students')
            ->assertOk()
            ->assertSee('Rekap Mahasiswa KP');

        $this->actingAs($this->mahasiswa)->withSession(['active_role' => 'mahasiswa'])
            ->get('/management/recaps')
            ->assertForbidden();
    }

    public function test_exports_can_be_downloaded_by_management_only(): void
    {
        $this->actingAs($this->admin)->withSession(['active_role' => 'admin'])
            ->get('/management/exports/students')
            ->assertOk()
            ->assertHeader('content-disposition');

        $this->actingAs($this->koordinator)->withSession(['active_role' => 'koordinator_kp'])
            ->get('/management/exports/scores')
            ->assertOk()
            ->assertHeader('content-disposition');

        $this->actingAs($this->mahasiswa)->withSession(['active_role' => 'mahasiswa'])
            ->get('/management/exports/scores')
            ->assertForbidden();
    }

    public function test_all_role_dashboards_can_be_opened(): void
    {
        $this->actingAs($this->mahasiswa)->withSession(['active_role' => 'mahasiswa'])->get('/mahasiswa/dashboard')->assertOk()->assertSee('Dashboard Mahasiswa');
        $this->actingAs($this->admin)->withSession(['active_role' => 'admin'])->get('/admin/dashboard')->assertOk()->assertSee('Dashboard Admin');
        $this->actingAs($this->koordinator)->withSession(['active_role' => 'koordinator_kp'])->get('/koordinator/dashboard')->assertOk()->assertSee('Dashboard Koordinator KP');
        $this->actingAs($this->internal)->withSession(['active_role' => 'pembimbing_dalam'])->get('/pembimbing-dalam/dashboard')->assertOk()->assertSee('Dashboard Pembimbing Dalam');
        $this->actingAs($this->field)->withSession(['active_role' => 'pembimbing_lapangan'])->get('/pembimbing-lapangan/dashboard')->assertOk()->assertSee('Dashboard Pembimbing Luar');
        $this->actingAs($this->examiner)->withSession(['active_role' => 'penguji'])->get('/penguji/dashboard')->assertOk()->assertSee('Dashboard Penguji');
    }

    private function makeUser(string $email, array $roles): User
    {
        $user = User::create(['name' => 'User Test', 'email' => $email, 'password' => Hash::make('password'), 'status' => 'active']);
        $user->roles()->sync(Role::whereIn('name', $roles)->pluck('id'));

        return $user;
    }
}
