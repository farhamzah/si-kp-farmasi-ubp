<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReleaseCandidateGateCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_release_candidate_gate_reports_local_blockers_without_writes(): void
    {
        $this->artisan('kp:release-candidate-gate')
            ->expectsOutputToContain('KP release candidate gate')
            ->expectsOutputToContain('Dry run: yes')
            ->expectsOutputToContain('External request sent: no')
            ->expectsOutputToContain('Write to Core/TU/SAFA: no/no/no')
            ->expectsOutputToContain('Ready for release candidate: no')
            ->expectsOutputToContain('Ready for runtime TU bridge: no')
            ->assertFailed();
    }

    public function test_release_candidate_gate_can_pass_required_production_checks(): void
    {
        config()->set('app.env', 'production');
        config()->set('app.debug', false);
        config()->set('app.url', 'https://kp-farmasi.example.test');
        config()->set('session.secure', true);
        config()->set('queue.default', 'database');
        config()->set('cache.default', 'array');
        config()->set('mail.default', 'smtp');
        config()->set('services.tu_farmasi.endpoint', null);
        config()->set('services.safa.endpoint', null);

        $this->artisan('kp:release-candidate-gate')
            ->expectsOutputToContain('Ready for release candidate: yes')
            ->expectsOutputToContain('Ready for runtime TU bridge: no')
            ->expectsOutputToContain('Read-only counts unchanged: yes')
            ->assertSuccessful();
    }
}
