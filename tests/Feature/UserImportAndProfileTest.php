<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Services\UserImportService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserImportAndProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_import_validation_rejects_duplicate_email(): void
    {
        User::create([
            'name' => 'Existing',
            'email' => 'existing@test.local',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);

        $service = app(UserImportService::class);
        $preview = $service->preview('mahasiswa', [
            ['nim' => '2211', 'name' => 'A', 'email' => 'existing@test.local'],
            ['nim' => '2212', 'name' => 'B', 'email' => 'new@test.local'],
            ['nim' => '2213', 'name' => 'C', 'email' => 'new@test.local'],
        ]);

        $this->assertFalse($preview[0]['valid']);
        $this->assertTrue($preview[1]['valid']);
        $this->assertFalse($preview[2]['valid']);
    }

    public function test_user_can_open_and_update_own_profile(): void
    {
        $user = User::create([
            'name' => 'Mahasiswa',
            'email' => 'mhs@test.local',
            'password' => Hash::make('password'),
            'status' => 'active',
            'profile_completed' => false,
        ]);
        $user->roles()->sync([Role::where('name', 'mahasiswa')->first()->id]);
        $user->student()->create(['nim' => '221010001']);

        $this->actingAs($user)
            ->withSession(['active_role' => 'mahasiswa'])
            ->get('/profil-saya')
            ->assertOk()
            ->assertSee('Profil Saya');

        $response = $this->actingAs($user)
            ->withSession(['active_role' => 'mahasiswa'])
            ->put('/profile', [
                'phone' => '081234567890',
                'study_program' => 'Farmasi',
                'semester' => 6,
                'class_name' => 'A',
                'address' => 'Karawang',
            ]);

        $response->assertRedirect('/profil-saya');
        $this->assertTrue($user->fresh()->profile_completed);
        $this->assertDatabaseHas('students', ['nim' => '221010001', 'phone' => '081234567890']);
    }

    public function test_admin_can_open_import_page(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.local',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);
        $admin->roles()->sync([Role::where('name', 'admin')->first()->id]);

        $this->actingAs($admin)
            ->withSession(['active_role' => 'admin'])
            ->get('/admin/import-users')
            ->assertOk()
            ->assertSee('Import User');
    }
}
