<?php

namespace Tests\Feature;

use App\Models\PostLike;
use App\Models\PodcastPost;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogLikeApiTest extends TestCase
{
    use RefreshDatabase;

    private function seedAndGetUser(): User
    {
        $this->seed(DatabaseSeeder::class);

        return User::factory()->create(['role' => 'user']);
    }

    private function userToken(User $user): string
    {
        return (string) $this->postJson('/api/login', [
            'email'    => $user->email,
            'password' => 'password',
        ])->json('data.token');
    }

    private function publishedPost(): PodcastPost
    {
        return PodcastPost::create([
            'title'        => 'Likeable Post',
            'slug'         => 'likeable-post',
            'status'       => 'published',
            'published_at' => now(),
            'created_by'   => User::where('role', 'admin')->first()->id,
        ]);
    }

    // ---- like ----

    public function test_authenticated_user_can_like_post(): void
    {
        $user  = $this->seedAndGetUser();
        $token = $this->userToken($user);
        $post  = $this->publishedPost();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/blog-posts/{$post->id}/like")
            ->assertOk()
            ->assertJsonPath('is_liked', true);

        $this->assertDatabaseHas('post_likes', [
            'podcast_post_id' => $post->id,
            'user_id'         => $user->id,
        ]);
    }

    public function test_liking_twice_is_idempotent(): void
    {
        $user  = $this->seedAndGetUser();
        $token = $this->userToken($user);
        $post  = $this->publishedPost();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/blog-posts/{$post->id}/like");

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/blog-posts/{$post->id}/like")
            ->assertOk();

        $this->assertDatabaseCount('post_likes', 1);
    }

    public function test_guest_cannot_like(): void
    {
        $this->seed(DatabaseSeeder::class);
        $post = $this->publishedPost();

        $this->postJson("/api/blog-posts/{$post->id}/like")->assertUnauthorized();
    }

    // ---- unlike ----

    public function test_authenticated_user_can_unlike_post(): void
    {
        $user  = $this->seedAndGetUser();
        $token = $this->userToken($user);
        $post  = $this->publishedPost();

        PostLike::create([
            'podcast_post_id' => $post->id,
            'user_id'         => $user->id,
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/blog-posts/{$post->id}/like")
            ->assertOk()
            ->assertJsonPath('is_liked', false);

        $this->assertDatabaseMissing('post_likes', [
            'podcast_post_id' => $post->id,
            'user_id'         => $user->id,
        ]);
    }

    public function test_unliking_when_not_liked_is_idempotent(): void
    {
        $user  = $this->seedAndGetUser();
        $token = $this->userToken($user);
        $post  = $this->publishedPost();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/blog-posts/{$post->id}/like")
            ->assertOk()
            ->assertJsonPath('is_liked', false);
    }

    public function test_guest_cannot_unlike(): void
    {
        $this->seed(DatabaseSeeder::class);
        $post = $this->publishedPost();

        $this->deleteJson("/api/blog-posts/{$post->id}/like")->assertUnauthorized();
    }

    // ---- like count integrity ----

    public function test_like_count_increments(): void
    {
        $user  = $this->seedAndGetUser();
        $token = $this->userToken($user);
        $post  = $this->publishedPost();

        $before = $post->likes()->count();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/blog-posts/{$post->id}/like");

        $this->assertSame($before + 1, $post->fresh()->likes()->count());
    }

    public function test_like_count_decrements_on_unlike(): void
    {
        $user  = $this->seedAndGetUser();
        $token = $this->userToken($user);
        $post  = $this->publishedPost();

        PostLike::create(['podcast_post_id' => $post->id, 'user_id' => $user->id]);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/blog-posts/{$post->id}/like");

        $this->assertSame(0, $post->fresh()->likes()->count());
    }
}
