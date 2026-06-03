<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ReleaseSensitiveScanCommandTest extends TestCase
{
    public function test_release_sensitive_scan_passes_for_current_release_candidate(): void
    {
        $this->artisan('kp:release-sensitive-scan')
            ->expectsOutputToContain('KP release sensitive scan')
            ->expectsOutputToContain('Findings: 0')
            ->assertSuccessful();
    }

    public function test_release_sensitive_scan_blocks_unignored_secret_file(): void
    {
        $path = base_path('docs/release-secret-scan.md');
        $secretValue = 'super'.'-'.'secret'.'-'.'value'.'-'.'1234567890';

        File::put($path, "DB_PASSWORD={$secretValue}\n");

        try {
            $this->artisan('kp:release-sensitive-scan')
                ->expectsOutputToContain('secret_assignment')
                ->assertFailed();
        } finally {
            File::delete($path);
        }
    }
}
