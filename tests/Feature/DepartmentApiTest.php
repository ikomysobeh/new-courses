<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentApiTest extends TestCase
{
    use RefreshDatabase;

    private function loginAdminAndGetToken(): string
    {
        $this->seed(DatabaseSeeder::class);

        $response = $this->postJson('/api/login', [
            'email' => 'admin@newproject.test',
            'password' => env('ADMIN_INITIAL_PASSWORD', 'Admin@12345'),
        ]);

        return (string) $response->json('data.token');
    }

    public function test_get_all_departments_returns_hierarchy(): void
    {
        $token = $this->loginAdminAndGetToken();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/departments/getAll');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'slug', 'parent_id', 'users', 'children'],
                ],
            ]);
    }

    public function test_admin_can_create_department(): void
    {
        $token = $this->loginAdminAndGetToken();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/departments/create', [
                'name' => 'Quality Assurance',
                'parent_id' => null,
                'sort_order' => 100,
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.name', 'Quality Assurance');

        $this->assertDatabaseHas('departments', [
            'name' => 'Quality Assurance',
            'slug' => 'quality-assurance',
        ]);
    }

    public function test_admin_can_update_department(): void
    {
        $token = $this->loginAdminAndGetToken();

        $create = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/departments/create', [
                'name' => 'QA Team',
                'sort_order' => 100,
            ]);

        $departmentId = $create->json('data.id');

        $update = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/admin/departments/update/' . $departmentId, [
                'name' => 'Quality Team',
                'sort_order' => 200,
            ]);

        $update
            ->assertOk()
            ->assertJsonPath('data.name', 'Quality Team')
            ->assertJsonPath('data.slug', 'quality-team');
    }

    public function test_admin_can_delete_department_without_children(): void
    {
        $token = $this->loginAdminAndGetToken();

        $create = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/departments/create', [
                'name' => 'Disposable Department',
            ]);

        $departmentId = $create->json('data.id');

        $delete = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson('/api/admin/departments/delete/' . $departmentId);

        $delete->assertOk();

        $this->assertSoftDeleted('departments', ['id' => $departmentId]);
    }

    public function test_department_routes_require_authentication(): void
    {
        $response = $this->getJson('/api/admin/departments/getAll');

        $response->assertStatus(401);
    }
}
