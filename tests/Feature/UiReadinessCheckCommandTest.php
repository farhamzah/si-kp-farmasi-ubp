<?php

namespace Tests\Feature;

use Tests\TestCase;

class UiReadinessCheckCommandTest extends TestCase
{
    public function test_ui_readiness_check_reports_required_sections_without_blockers(): void
    {
        $this->artisan('kp:ui-readiness-check')
            ->expectsOutputToContain('KP UI readiness check')
            ->expectsOutputToContain('Read-only: yes')
            ->expectsOutputToContain('Blockers: 0')
            ->expectsOutputToContain('Warnings: 0')
            ->expectsOutputToContain('Ready for UI/UX UAT: yes')
            ->assertSuccessful();
    }
}
