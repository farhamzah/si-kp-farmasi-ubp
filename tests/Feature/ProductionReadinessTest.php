<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProductionReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_env_example_contains_safe_production_relevant_placeholders(): void
    {
        $envExample = file_get_contents(base_path('.env.example'));

        $this->assertStringContainsString('APP_NAME="SI-KP Farmasi UBP"', $envExample);
        $this->assertStringContainsString('APP_ENV=local', $envExample);
        $this->assertStringContainsString('APP_DEBUG=true', $envExample);
        $this->assertStringContainsString('APP_URL=http://127.0.0.1:8000', $envExample);
        $this->assertStringContainsString('SESSION_DRIVER=file', $envExample);
        $this->assertStringContainsString('SESSION_SECURE_COOKIE=false', $envExample);
        $this->assertStringContainsString('SESSION_SAME_SITE=lax', $envExample);
        $this->assertStringContainsString('FILESYSTEM_DISK=local', $envExample);
        $this->assertStringContainsString('QUEUE_CONNECTION=sync', $envExample);
        $this->assertStringContainsString('CACHE_STORE=file', $envExample);
        $this->assertStringContainsString('MAIL_MAILER=log', $envExample);
    }

    public function test_error_pages_and_login_page_render_for_release_readiness(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Portal Kerja Praktek Farmasi UBP');

        $this->assertStringContainsString('Akses Ditolak', view('errors.403')->render());
        $this->assertStringContainsString('Halaman Tidak Ditemukan', view('errors.404')->render());
        $this->assertStringContainsString('Sesi Kedaluwarsa', view('errors.419')->render());
        $this->assertStringContainsString('Terjadi Kesalahan', view('errors.500')->render());
    }

    public function test_management_recaps_and_exports_remain_protected_from_student_role(): void
    {
        $this->seed(RoleSeeder::class);

        $student = User::create([
            'name' => 'Mahasiswa Readiness',
            'email' => 'readiness-student@test.local',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);
        $student->roles()->sync(Role::where('name', 'mahasiswa')->pluck('id'));

        $this->actingAs($student)->withSession(['active_role' => 'mahasiswa'])
            ->get('/management/recaps')
            ->assertForbidden();

        $this->actingAs($student)->withSession(['active_role' => 'mahasiswa'])
            ->get('/management/exports/students')
            ->assertForbidden();
    }
}
