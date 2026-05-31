<?php

namespace Tests\Feature;

use App\Models\KpAssignment;
use App\Models\KpPeriod;
use App\Models\KpPlace;
use App\Models\KpRegistration;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TuDocumentPayloadPreviewCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_tu_document_payload_preview_is_dry_run_and_read_only(): void
    {
        $this->createAssignment();

        $before = [
            'assignments' => DB::table('kp_assignments')->count(),
            'users' => DB::table('users')->count(),
        ];

        $exitCode = Artisan::call('kp:tu-document-payload-preview --limit=1');
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('"dry_run": true', $output);
        $this->assertStringContainsString('"external_request_sent": false', $output);
        $this->assertStringContainsString('"service_code": "KP_PLACEMENT_LETTER"', $output);
        $this->assertStringContainsString('"unchanged": true', $output);

        $this->assertSame($before['assignments'], DB::table('kp_assignments')->count());
        $this->assertSame($before['users'], DB::table('users')->count());
    }

    private function createAssignment(): KpAssignment
    {
        $studentUser = User::create([
            'name' => 'Alya Farmasi',
            'email' => 'alya@test.local',
            'password' => 'hash',
            'status' => 'active',
        ]);

        $student = Student::create([
            'user_id' => $studentUser->id,
            'nim' => '221063120001',
            'study_program' => 'Farmasi',
            'semester' => 7,
            'class_name' => 'A',
            'status' => 'active',
        ]);

        $period = KpPeriod::create([
            'name' => 'KP Farmasi Test',
            'academic_year' => '2025/2026',
            'semester' => 'genap',
            'registration_start_at' => now()->subDay(),
            'registration_end_at' => now()->addDay(),
            'selection_start_at' => now()->subDay(),
            'selection_end_at' => now()->addDay(),
            'kp_start_date' => now()->toDateString(),
            'kp_end_date' => now()->addMonth()->toDateString(),
            'status' => 'dibuka',
        ]);

        $place = KpPlace::create([
            'name' => 'Apotek Test',
            'type' => 'apotek',
            'city' => 'Karawang',
            'province' => 'Jawa Barat',
            'status' => 'aktif',
        ]);

        $registration = KpRegistration::create([
            'kp_period_id' => $period->id,
            'student_id' => $student->id,
            'registration_number' => 'KP-TEST-001',
            'status' => 'terverifikasi',
        ]);

        return KpAssignment::create([
            'kp_period_id' => $period->id,
            'kp_registration_id' => $registration->id,
            'student_id' => $student->id,
            'kp_place_id' => $place->id,
            'status' => 'aktif',
            'active_key' => $period->id.'-'.$student->id,
        ]);
    }
}
