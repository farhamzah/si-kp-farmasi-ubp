<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaEligibilityEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_endpoint_requires_shared_token(): void
    {
        config()->set('ta_integration.status_token', 'shared-test-token');

        $this->getJson('/api/internal/v1/ta-eligibility/24416248201001')
            ->assertUnauthorized();
    }

    public function test_unknown_student_returns_not_eligible(): void
    {
        config()->set('ta_integration.status_token', 'shared-test-token');

        $this->withToken('shared-test-token')
            ->getJson('/api/internal/v1/ta-eligibility/24416248201001')
            ->assertOk()
            ->assertJsonPath('data.student_identifier', '24416248201001')
            ->assertJsonPath('data.eligible', false)
            ->assertJsonPath('data.reason', 'kp_assignment_not_found');
    }
}
