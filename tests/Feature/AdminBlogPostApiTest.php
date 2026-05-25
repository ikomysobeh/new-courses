<?php

namespace Tests\Feature;

use App\Models\Audio;
use App\Models\PodcastPost;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminBlogPostApiTest extends TestCase
{
    use RefreshDatabase;

    private function adminToken(): string
    {
        $this->seed(DatabaseSeeder::class);

        return (string) $this->postJson('/api/login', [
            'email'    => 'admin@newproject.test',
            'password' => env('ADMIN_INITIAL_PASSWORD', 'Admin@12345'),
        ])->json('data.token');
    }

    private function adminUser(): User
    {
        return User::where('role', 'admin')->first();
    }

    private function createPost(array $overrides = []): PodcastPost
    {
        return PodcastPost::create(array_merge([
            'title'      => 'Test Post',
            'slug'       => 'test-post',
            'status'     => 'draft',
            'created_by' => $this->adminUser()->id,
        ], $overrides));
    }

    // ---- getAll ----

    public function test_admin_can_list_all_posts(): void
    {
        $token = $this->adminToken();
        $this->createPost();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/blog-posts')
            ->assertOk()
            ->assertJsonStructure(['data']);
    }

    public function test_guest_cannot_list_admin_posts(): void
    {
        $this->getJson('/api/admin/blog-posts')->assertUnauthorized();
    }

    // ---- create ----

    public function test_admin_can_create_post(): void
    {
        Storage::fake('public');
        $token = $this->adminToken();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/blog-posts', [
                'title'  => 'Hello World',
                'status' => 'draft',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.title', 'Hello World')
            ->assertJsonPath('data.status', 'draft');

        $this->assertDatabaseHas('podcast_posts', ['title' => 'Hello World']);
    }

    public function test_create_auto_generates_slug(): void
    {
        Storage::fake('public');
        $token = $this->adminToken();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/blog-posts', [
                'title'  => 'Auto Slug Post',
                'status' => 'draft',
            ]);

        $response->assertCreated();
        $this->assertDatabaseHas('podcast_posts', ['slug' => 'auto-slug-post']);
    }

    public function test_create_rejects_duplicate_slug(): void
    {
        $token = $this->adminToken();
        $this->createPost(['slug' => 'existing-slug']);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/blog-posts', [
                'title'  => 'Another Post',
                'slug'   => 'existing-slug',
                'status' => 'draft',
            ])
            ->assertUnprocessable();
    }

    public function test_create_requires_title(): void
    {
        $token = $this->adminToken();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/blog-posts', [])
            ->assertUnprocessable();
    }

    public function test_create_with_thumbnail(): void
    {
        Storage::fake('public');
        $token = $this->adminToken();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/blog-posts', [
                'title'     => 'Post With Image',
                'status'    => 'draft',
                'thumbnail' => UploadedFile::fake()->image('cover.jpg'),
            ]);

        $response->assertCreated();
        $post = PodcastPost::where('title', 'Post With Image')->first();
        $this->assertNotNull($post->thumbnail_path);
    }

    // ---- getById ----

    public function test_admin_can_get_post_by_id(): void
    {
        $token = $this->adminToken();
        $post  = $this->createPost();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/blog-posts/' . $post->id)
            ->assertOk()
            ->assertJsonPath('data.id', $post->id);
    }

    public function test_getById_returns_404_for_missing_post(): void
    {
        $token = $this->adminToken();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/blog-posts/99999')
            ->assertNotFound();
    }

    // ---- update ----

    public function test_admin_can_update_post(): void
    {
        Storage::fake('public');
        $token = $this->adminToken();
        $post  = $this->createPost();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/admin/blog-posts/' . $post->id, [
                'title' => 'Updated Title',
            ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Updated Title');
    }

    public function test_update_sets_published_at_on_first_publish(): void
    {
        Storage::fake('public');
        $token = $this->adminToken();
        $post  = $this->createPost(['status' => 'draft']);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/admin/blog-posts/' . $post->id, [
                'status' => 'published',
            ])
            ->assertOk();

        $this->assertNotNull($post->fresh()->published_at);
    }

    // ---- delete ----

    public function test_admin_can_delete_post(): void
    {
        $token = $this->adminToken();
        $post  = $this->createPost();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson('/api/admin/blog-posts/' . $post->id)
            ->assertOk();

        $this->assertDatabaseMissing('podcast_posts', ['id' => $post->id]);
    }

    // ---- available media ----

    public function test_admin_can_list_available_videos(): void
    {
        $token = $this->adminToken();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/blog-posts/available-videos')
            ->assertOk()
            ->assertJsonStructure(['data']);
    }

    public function test_admin_can_list_available_audios(): void
    {
        $token = $this->adminToken();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/blog-posts/available-audios')
            ->assertOk()
            ->assertJsonStructure(['data']);
    }

    // ---- security ----

    public function test_regular_user_cannot_access_admin_blog_endpoints(): void
    {
        $token = $this->adminToken(); // seeds DB

        $user = \App\Models\User::factory()->create(['role' => 'user']);

        $userToken = (string) $this->postJson('/api/login', [
            'email'    => $user->email,
            'password' => 'password',
        ])->json('data.token');

        $this->withHeader('Authorization', 'Bearer ' . $userToken)
            ->getJson('/api/admin/blog-posts')
            ->assertForbidden();
    }
}
