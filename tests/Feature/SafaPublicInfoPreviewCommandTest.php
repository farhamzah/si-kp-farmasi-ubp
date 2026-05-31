<?php

namespace Tests\Feature;

use App\Models\KpDocumentRequirement;
use App\Models\KpPeriod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SafaPublicInfoPreviewCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_safa_public_info_preview_is_public_safe_and_read_only(): void
    {
        $period = KpPeriod::create([
            'name' => 'KP Farmasi Public',
            'academic_year' => '2025/2026',
            'semester' => 'genap',
            'registration_start_at' => now()->subDay(),
            'registration_end_at' => now()->addDay(),
            'document_verification_start_at' => now()->subDay(),
            'document_verification_end_at' => now()->addDays(2),
            'selection_start_at' => now()->subDay(),
            'selection_end_at' => now()->addDays(3),
            'kp_start_date' => now()->addWeek()->toDateString(),
            'kp_end_date' => now()->addMonth()->toDateString(),
            'status' => 'dibuka',
            'description' => 'Pengumuman umum KP.',
        ]);
        KpDocumentRequirement::create([
            'kp_period_id' => $period->id,
            'name' => 'KRS',
            'description' => 'Dokumen KRS aktif.',
            'is_required' => true,
            'allowed_file_types' => 'pdf,jpg',
            'max_file_size_mb' => 5,
            'sort_order' => 1,
            'status' => 'aktif',
        ]);

        $before = [
            'periods' => DB::table('kp_periods')->count(),
            'requirements' => DB::table('kp_document_requirements')->count(),
        ];

        Artisan::call('kp:safa-public-info-preview');
        $output = Artisan::output();

        $this->assertStringContainsString('"dry_run": true', $output);
        $this->assertStringContainsString('"external_request_sent": false', $output);
        $this->assertStringContainsString('"requirements"', $output);
        $this->assertStringContainsString('"unchanged": true', $output);
        $this->assertStringNotContainsString('"final_score"', $output);
        $this->assertStringNotContainsString('"student_documents"', $output);
        $this->assertStringNotContainsString('"individual_registration_status"', $output);

        $this->assertSame($before['periods'], DB::table('kp_periods')->count());
        $this->assertSame($before['requirements'], DB::table('kp_document_requirements')->count());
    }
}

