<?php

namespace Tests\Feature;

use App\Models\PostComment;
use App\Models\PodcastPost;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogCommentApiTest extends TestCase
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

    private function adminToken(): string
    {
        $this->seed(DatabaseSeeder::class);

        return (string) $this->postJson('/api/login', [
            'email'    => 'admin@newproject.test',
            'password' => env('ADMIN_INITIAL_PASSWORD', 'Admin@12345'),
        ])->json('data.token');
    }

    private function publishedPost(): PodcastPost
    {
        return PodcastPost::create([
            'title'        => 'Post',
            'slug'         => 'post',
            'status'       => 'published',
            'published_at' => now(),
            'created_by'   => User::where('role', 'admin')->first()->id,
        ]);
    }

    // ---- store ----

    public function test_authenticated_user_can_comment(): void
    {
        $user  = $this->seedAndGetUser();
        $token = $this->userToken($user);
        $post  = $this->publishedPost();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/blog-posts/{$post->id}/comments", [
                'body' => 'Great article!',
            ])
            ->assertCreated()
            ->assertJsonPath('data.body', 'Great article!');

        $this->assertDatabaseHas('post_comments', ['body' => 'Great article!']);
    }

    public function test_guest_cannot_comment(): void
    {
        $this->seed(DatabaseSeeder::class);
        $post = $this->publishedPost();

        $this->postJson("/api/blog-posts/{$post->id}/comments", [
            'body' => 'Sneaky guest',
        ])->assertUnauthorized();
    }

    public function test_comment_on_draft_returns_404(): void
    {
        $user  = $this->seedAndGetUser();
        $token = $this->userToken($user);

        $draft = PodcastPost::create([
            'title'      => 'Hidden',
            'slug'       => 'hidden',
            'status'     => 'draft',
            'created_by' => User::where('role', 'admin')->first()->id,
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/blog-posts/{$draft->id}/comments", [
                'body' => 'Should not work',
            ])
            ->assertNotFound();
    }

    public function test_comment_body_is_required(): void
    {
        $user  = $this->seedAndGetUser();
        $token = $this->userToken($user);
        $post  = $this->publishedPost();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/blog-posts/{$post->id}/comments", [])
            ->assertUnprocessable();
    }

    public function test_comment_body_max_length(): void
    {
        $user  = $this->seedAndGetUser();
        $token = $this->userToken($user);
        $post  = $this->publishedPost();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/blog-posts/{$post->id}/comments", [
                'body' => str_repeat('a', 2001),
            ])
            ->assertUnprocessable();
    }

    // ---- destroy ----

    public function test_owner_can_delete_own_comment(): void
    {
        $user  = $this->seedAndGetUser();
        $token = $this->userToken($user);
        $post  = $this->publishedPost();

        $comment = PostComment::create([
            'podcast_post_id' => $post->id,
            'user_id'         => $user->id,
            'body'            => 'My comment',
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/blog-comments/{$comment->id}")
            ->assertOk();

        $this->assertDatabaseMissing('post_comments', ['id' => $comment->id]);
    }

    public function test_admin_can_delete_any_comment(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user       = User::factory()->create(['role' => 'user']);
        $adminToken = (string) $this->postJson('/api/login', [
            'email'    => 'admin@newproject.test',
            'password' => env('ADMIN_INITIAL_PASSWORD', 'Admin@12345'),
        ])->json('data.token');
        $post = $this->publishedPost();

        $comment = PostComment::create([
            'podcast_post_id' => $post->id,
            'user_id'         => $user->id,
            'body'            => 'Someone else comment',
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $adminToken)
            ->deleteJson("/api/blog-comments/{$comment->id}")
            ->assertOk();
    }

    public function test_user_cannot_delete_another_users_comment(): void
    {
        $owner      = $this->seedAndGetUser();
        $otherUser  = User::factory()->create(['role' => 'user']);
        $post       = $this->publishedPost();

        $comment = PostComment::create([
            'podcast_post_id' => $post->id,
            'user_id'         => $owner->id,
            'body'            => 'Owner comment',
        ]);

        $otherToken = $this->userToken($otherUser);

        $this->withHeader('Authorization', 'Bearer ' . $otherToken)
            ->deleteJson("/api/blog-comments/{$comment->id}")
            ->assertForbidden();
    }

    public function test_guest_cannot_delete_comment(): void
    {
        $user = $this->seedAndGetUser();
        $post = $this->publishedPost();

        $comment = PostComment::create([
            'podcast_post_id' => $post->id,
            'user_id'         => $user->id,
            'body'            => 'Some comment',
        ]);

        $this->deleteJson("/api/blog-comments/{$comment->id}")->assertUnauthorized();
    }
}
