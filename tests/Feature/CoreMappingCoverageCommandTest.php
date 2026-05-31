<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CoreMappingCoverageCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_core_mapping_coverage_command_is_read_only(): void
    {
        $admin = User::create([
            'name' => 'Admin KP',
            'email' => 'admin@sikp.test',
            'password' => 'hash',
            'status' => 'active',
            'core_user_id' => 10,
        ]);
        $student = User::create([
            'name' => 'Student KP',
            'email' => 'student@sikp.test',
            'password' => 'hash',
            'status' => 'active',
        ]);

        $adminRole = Role::create(['name' => 'admin', 'label' => 'Admin']);
        $studentRole = Role::create(['name' => 'mahasiswa', 'label' => 'Mahasiswa']);
        $admin->roles()->sync([$adminRole->id]);
        $student->roles()->sync([$studentRole->id]);

        $before = [
            'users' => DB::table('users')->count(),
            'roles' => DB::table('roles')->count(),
            'user_roles' => DB::table('user_roles')->count(),
        ];

        $this->artisan('kp:core-mapping-coverage --show-users')
            ->expectsOutputToContain('KP Core mapping coverage')
            ->expectsOutputToContain('Total user: 2')
            ->expectsOutputToContain('Mapped user: 1')
            ->expectsOutputToContain('Unmapped user: 1')
            ->expectsOutputToContain('Read-only counts unchanged: yes')
            ->assertSuccessful();

        $this->assertSame($before['users'], DB::table('users')->count());
        $this->assertSame($before['roles'], DB::table('roles')->count());
        $this->assertSame($before['user_roles'], DB::table('user_roles')->count());
    }
}

