<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportingSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function loginUserAndGetToken(): string
    {
        $user = \App\Models\User::factory()->create(['role' => 'user']);

        return $user->createToken('test')->plainTextToken;
    }

    public function test_unauthenticated_cannot_access_kpi_overview(): void
    {
        $this->getJson('/api/admin/reporting/kpi/overview')
            ->assertUnauthorized();
    }

    public function test_unauthenticated_cannot_access_datasets(): void
    {
        $this->getJson('/api/admin/reporting/datasets/user-course-daily')
            ->assertUnauthorized();
    }

    public function test_unauthenticated_cannot_trigger_refresh(): void
    {
        $this->postJson('/api/admin/reporting/refresh/daily')
            ->assertUnauthorized();
    }

    public function test_unauthenticated_cannot_access_export(): void
    {
        $this->getJson('/api/admin/reporting/export/user-course-daily?export_type=csv')
            ->assertUnauthorized();
    }

    public function test_non_admin_user_cannot_access_kpi(): void
    {
        $token = $this->loginUserAndGetToken();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/reporting/kpi/overview')
            ->assertForbidden();
    }

    public function test_non_admin_user_cannot_trigger_refresh(): void
    {
        $token = $this->loginUserAndGetToken();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/reporting/refresh/full')
            ->assertForbidden();
    }

    public function test_non_admin_user_cannot_access_export(): void
    {
        $token = $this->loginUserAndGetToken();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/reporting/export/user-course-daily?export_type=csv')
            ->assertForbidden();
    }
}
