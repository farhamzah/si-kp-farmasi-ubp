<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthMultiRoleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_active_single_role_user_can_login_and_is_redirected_to_role_dashboard(): void
    {
        $user = $this->createUser('mahasiswa@sikp.test', ['mahasiswa']);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/mahasiswa/dashboard');
        $this->assertAuthenticatedAs($user);
        $this->assertSame('mahasiswa', session('active_role'));
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = $this->createUser('inactive@sikp.test', ['mahasiswa'], ['status' => 'inactive']);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_multi_role_user_is_redirected_to_role_selection(): void
    {
        $user = $this->createUser('dosen@sikp.test', ['pembimbing_dalam', 'penguji']);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/pilih-role');
        $this->assertAuthenticatedAs($user);
        $this->assertNull(session('active_role'));
    }

    public function test_user_cannot_access_other_role_dashboard(): void
    {
        $user = $this->createUser('mahasiswa@sikp.test', ['mahasiswa']);

        $response = $this->actingAs($user)
            ->withSession(['active_role' => 'mahasiswa'])
            ->get('/admin/dashboard');

        $response->assertForbidden();
    }

    public function test_user_without_role_cannot_enter_dashboard(): void
    {
        $user = User::create([
            'name' => 'User Tanpa Role',
            'email' => 'norole@sikp.test',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    private function createUser(string $email, array $roles, array $attributes = []): User
    {
        $user = User::create(array_merge([
            'name' => 'Demo User',
            'email' => $email,
            'password' => Hash::make('password'),
            'status' => 'active',
            'must_change_password' => true,
            'profile_completed' => false,
        ], $attributes));

        $roleIds = Role::whereIn('name', $roles)->pluck('id');
        $user->roles()->sync($roleIds);

        return $user;
    }
}
