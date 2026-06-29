<?php

namespace Tests\Feature;

use App\Models\KpFinalScore;
use App\Models\KpPeriod;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DemoEndToEndSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StabilizationDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_opens_with_no_cache_headers(): void
    {
        $response = $this->get('/login')
            ->assertOk()
            ->assertSee('Portal Kerja Praktek Farmasi UBP')
            ->assertSee('toggle-password')
            ->assertSee('Tampilkan kata sandi');

        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-cache', $response->headers->get('Cache-Control'));
    }

    public function test_koordinator_can_login_select_role_logout_and_login_again(): void
    {
        $this->seed(RoleSeeder::class);
        $user = User::create([
            'name' => 'Koordinator Test',
            'email' => 'koordinator-login@test.local',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);
        $user->roles()->sync(Role::whereIn('name', ['koordinator_kp', 'pembimbing_dalam'])->pluck('id'));

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/pilih-role');

        $this->assertAuthenticatedAs($user);

        $this->post('/set-role/koordinator_kp')
            ->assertRedirect('/koordinator/dashboard');
        $this->assertSame('koordinator_kp', session('active_role'));

        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/pilih-role');
        $this->assertAuthenticatedAs($user);
    }

    public function test_demo_end_to_end_seeder_is_idempotent_and_creates_demo_lifecycle(): void
    {
        $this->seed(DemoEndToEndSeeder::class);
        $this->seed(DemoEndToEndSeeder::class);

        $this->assertSame(1, User::where('email', 'mahasiswa@sikp.test')->count());
        $this->assertSame(1, User::where('email', 'mahasiswa2@sikp.test')->count());
        $this->assertSame(1, KpPeriod::where('name', 'KP Farmasi Demo 2026')->count());

        $finalScore = KpFinalScore::whereHas('assignment.student.user', fn ($query) => $query->where('email', 'mahasiswa@sikp.test'))->first();
        $this->assertNotNull($finalScore);
        $this->assertSame('published', $finalScore->status);
        $this->assertSame('A', $finalScore->final_grade);
    }

    public function test_demo_users_can_open_recaps_dashboards_and_score_pages(): void
    {
        $this->seed(DemoEndToEndSeeder::class);

        $admin = User::where('email', 'admin@sikp.test')->firstOrFail();
        $studentA = User::where('email', 'mahasiswa@sikp.test')->firstOrFail();
        $studentB = User::where('email', 'mahasiswa2@sikp.test')->firstOrFail();

        $this->actingAs($admin)->withSession(['active_role' => 'admin'])
            ->get('/management/recaps')
            ->assertOk()
            ->assertSee('Rekap, Monitoring, dan Export KP');

        $this->actingAs($studentA)->withSession(['active_role' => 'mahasiswa'])
            ->get('/mahasiswa/dashboard')
            ->assertOk()
            ->assertSee('Dashboard Mahasiswa');

        $this->actingAs($studentA)->withSession(['active_role' => 'mahasiswa'])
            ->get('/mahasiswa/nilai')
            ->assertOk()
            ->assertSee('Nilai Akhir KP')
            ->assertSee('A');

        $this->actingAs($studentB)->withSession(['active_role' => 'mahasiswa'])
            ->get('/mahasiswa/nilai')
            ->assertOk()
            ->assertSee('Nilai sedang diproses');
    }

    public function test_friendly_error_pages_and_management_permission(): void
    {
        $this->seed(DemoEndToEndSeeder::class);
        $student = User::where('email', 'mahasiswa@sikp.test')->firstOrFail();

        $this->actingAs($student)->withSession(['active_role' => 'mahasiswa'])
            ->get('/management/recaps')
            ->assertForbidden()
            ->assertSee('Akses Ditolak');

        $this->get('/route-yang-tidak-ada')
            ->assertNotFound()
            ->assertSee('Halaman Tidak Ditemukan');

        $this->assertStringContainsString('Sesi Kedaluwarsa', view('errors.419')->render());
        $this->assertStringContainsString('Terjadi Kesalahan', view('errors.500')->render());
    }
}
