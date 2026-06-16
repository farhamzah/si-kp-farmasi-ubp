<?php

namespace Tests\Feature;

use App\Models\KpDocument;
use App\Models\KpDocumentRequirement;
use App\Models\KpPeriod;
use App\Models\KpRegistration;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegistrationStatusReconcileCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_reconcile_defaults_to_dry_run_without_writes(): void
    {
        $registration = $this->approvedDraftRegistration();

        $this->artisan('kp:registration-status-reconcile --show-rows')
            ->expectsOutputToContain('dry-run only; no writes performed')
            ->expectsOutputToContain('Would update: 1')
            ->assertSuccessful();

        $this->assertSame('draft', $registration->fresh()->status);
        $this->assertNull($registration->fresh()->submitted_at);
    }

    public function test_reconcile_execute_requires_confirmation(): void
    {
        $this->approvedDraftRegistration();

        $this->artisan('kp:registration-status-reconcile --execute')
            ->expectsOutputToContain('Execute refused: missing --confirm-execute.')
            ->assertFailed();
    }

    public function test_reconcile_execute_promotes_only_fully_approved_local_registrations(): void
    {
        $registration = $this->approvedDraftRegistration('ready@student.test');
        $incomplete = $this->approvedDraftRegistration('pending@student.test');
        $incomplete->documents()->first()->update(['status' => 'menunggu']);

        $this->artisan('kp:registration-status-reconcile --execute --confirm-execute')
            ->expectsOutputToContain('execute local KP updates')
            ->expectsOutputToContain('Updated: 1')
            ->expectsOutputToContain('Skipped: 1')
            ->assertSuccessful();

        $registration->refresh();
        $incomplete->refresh();

        $this->assertSame('menunggu_verifikasi', $registration->status);
        $this->assertNotNull($registration->submitted_at);
        $this->assertNotNull($registration->registration_number);
        $this->assertSame('draft', $incomplete->status);
        $this->assertDatabaseHas('kp_registration_logs', [
            'kp_registration_id' => $registration->id,
            'action' => 'registration_status_reconciled',
            'old_status' => 'draft',
            'new_status' => 'menunggu_verifikasi',
        ]);
    }

    private function approvedDraftRegistration(string $email = 'student@test.local'): KpRegistration
    {
        $this->seed(RoleSeeder::class);
        $user = User::create([
            'name' => 'Student Test',
            'email' => $email,
            'password' => Hash::make('password'),
            'status' => 'active',
            'profile_completed' => true,
        ]);
        $user->roles()->sync(Role::where('name', 'mahasiswa')->pluck('id'));

        $student = Student::create([
            'user_id' => $user->id,
            'nim' => fake()->unique()->numerify('221063#######'),
            'study_program' => 'Farmasi',
            'semester' => 6,
            'phone' => '081234567890',
            'status' => 'active',
        ]);

        $period = KpPeriod::create([
            'name' => 'KP TA 2026_2027',
            'academic_year' => '2026/2027',
            'semester' => 'ganjil',
            'registration_start_at' => now()->subDay(),
            'registration_end_at' => now()->addDays(7),
            'status' => 'dibuka',
        ]);

        $requirement = KpDocumentRequirement::create([
            'kp_period_id' => $period->id,
            'name' => 'Bukti pembayaran UKT C10',
            'is_required' => true,
            'allowed_file_types' => 'pdf,jpg,jpeg,png',
            'max_file_size_mb' => 5,
            'status' => 'aktif',
        ]);

        $registration = KpRegistration::create([
            'kp_period_id' => $period->id,
            'student_id' => $student->id,
            'status' => 'draft',
        ]);

        KpDocument::create([
            'kp_registration_id' => $registration->id,
            'kp_document_requirement_id' => $requirement->id,
            'original_filename' => 'bukti-ukt.pdf',
            'file_path' => 'kp-documents/test/bukti-ukt.pdf',
            'file_disk' => 'local',
            'status' => 'disetujui',
            'uploaded_at' => now(),
        ]);

        return $registration;
    }
}
