<?php

namespace Tests\Feature;

use App\Models\PodcastPost;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogFeedApiTest extends TestCase
{
    use RefreshDatabase;

    private function adminId(): int
    {
        $this->seed(DatabaseSeeder::class);

        return User::where('role', 'admin')->first()->id;
    }

    private function makePublished(array $overrides = []): PodcastPost
    {
        return PodcastPost::create(array_merge([
            'title'        => 'Published Post',
            'slug'         => 'published-post',
            'status'       => 'published',
            'published_at' => now(),
            'created_by'   => $this->adminId(),
        ], $overrides));
    }

    private function makeDraft(array $overrides = []): PodcastPost
    {
        return PodcastPost::create(array_merge([
            'title'      => 'Draft Post',
            'slug'       => 'draft-post',
            'status'     => 'draft',
            'created_by' => $this->adminId(),
        ], $overrides));
    }

    // ---- index ----

    public function test_public_can_see_published_feed(): void
    {
        $this->makePublished();

        $this->getJson('/api/blog-posts')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta']);
    }

    public function test_feed_does_not_expose_drafts(): void
    {
        $this->makeDraft(['slug' => 'hidden-draft']);

        $response = $this->getJson('/api/blog-posts')->assertOk();

        $slugs = collect($response->json('data'))->pluck('slug')->toArray();
        $this->assertNotContains('hidden-draft', $slugs);
    }

    public function test_feed_returns_published_posts(): void
    {
        $this->makePublished(['slug' => 'visible-post', 'title' => 'Visible']);

        $response = $this->getJson('/api/blog-posts')->assertOk();

        $slugs = collect($response->json('data'))->pluck('slug')->toArray();
        $this->assertContains('visible-post', $slugs);
    }

    // ---- show ----

    public function test_public_can_read_published_post_by_slug(): void
    {
        $this->makePublished(['slug' => 'my-article', 'title' => 'My Article']);

        $this->getJson('/api/blog-posts/my-article')
            ->assertOk()
            ->assertJsonPath('data.slug', 'my-article');
    }

    public function test_show_returns_404_for_draft(): void
    {
        $this->makeDraft(['slug' => 'secret-draft']);

        $this->getJson('/api/blog-posts/secret-draft')->assertNotFound();
    }

    public function test_show_returns_404_for_nonexistent_slug(): void
    {
        $this->adminId(); // ensure DB is seeded

        $this->getJson('/api/blog-posts/no-such-slug')->assertNotFound();
    }

    public function test_authenticated_user_gets_is_liked_field(): void
    {
        $post = $this->makePublished(['slug' => 'liked-article']);
        $user  = User::factory()->create(['role' => 'user']);

        $token = (string) $this->postJson('/api/login', [
            'email'    => $user->email,
            'password' => 'password',
        ])->json('data.token');

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/blog-posts/liked-article')
            ->assertOk()
            ->assertJsonStructure(['data' => ['is_liked']]);
    }

    public function test_guest_gets_null_is_liked(): void
    {
        $this->makePublished(['slug' => 'guest-article']);

        $response = $this->getJson('/api/blog-posts/guest-article')->assertOk();

        $this->assertNull($response->json('data.is_liked'));
    }
}
