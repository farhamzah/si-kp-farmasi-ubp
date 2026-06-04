<?php

namespace Tests\Feature;

use App\Models\Lecturer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademicUnitCleanupCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_academic_unit_cleanup_dry_run_does_not_write(): void
    {
        $lecturer = $this->lecturerWithDepartment('Fakultas Farmasi');

        $this->artisan('kp:academic-unit-cleanup --show-rows')
            ->expectsOutputToContain('KP local academic unit cleanup')
            ->expectsOutputToContain('Mode: dry-run only; no writes performed')
            ->expectsOutputToContain('Planned updates: 1')
            ->assertSuccessful();

        $this->assertSame('Fakultas Farmasi', $lecturer->refresh()->department);
    }

    public function test_academic_unit_cleanup_execute_requires_confirmation(): void
    {
        $this->lecturerWithDepartment('Fakultas Farmasi');

        $this->artisan('kp:academic-unit-cleanup --execute')
            ->expectsOutputToContain('Execute refused: missing --confirm-execute.')
            ->assertFailed();
    }

    public function test_academic_unit_cleanup_execute_updates_local_kp_only(): void
    {
        $lecturer = $this->lecturerWithDepartment('Fakultas Farmasi');

        $this->artisan('kp:academic-unit-cleanup --execute --confirm-execute')
            ->expectsOutputToContain('Planned updates: 1')
            ->expectsOutputToContain('Local KP academic unit cleanup applied.')
            ->assertSuccessful();

        $this->assertSame('Farmakologi dan Farmasi Klinik', $lecturer->refresh()->department);
    }

    private function lecturerWithDepartment(string $department): Lecturer
    {
        $user = User::create([
            'name' => 'Dosen KP',
            'email' => 'dosen-cleanup@sikp.test',
            'password' => 'hash',
            'status' => 'active',
        ]);

        return Lecturer::create([
            'user_id' => $user->id,
            'nidn_nip' => '0012345601',
            'study_program' => 'Farmasi',
            'department' => $department,
            'status' => 'active',
        ]);
    }
}
