<?php

namespace App\Console\Commands\LegacyImport;

use App\Models\PodcastPost;
use App\Models\PostComment;
use App\Models\User;

class ImportPostComments extends LegacyImportCommand
{
    protected $signature = 'legacy:import-post-comments';

    protected $description = 'Import post_comments - identical schema, remaps podcast_post_id/user_id.';

    protected array $postMap = [];

    protected array $userMap = [];

    protected function legacyTable(): string
    {
        return 'post_comments';
    }

    protected function newModel(): string
    {
        return PostComment::class;
    }

    protected function beforeImport(): void
    {
        $this->postMap = PodcastPost::query()->whereNotNull('legacy_id')->pluck('id', 'legacy_id')->all();
        $this->userMap = User::query()->whereNotNull('legacy_id')->pluck('id', 'legacy_id')->all();
    }

    protected function mapRow(array $old): ?array
    {
        $newPostId = $this->postMap[$old['podcast_post_id']] ?? null;
        $newUserId = $this->userMap[$old['user_id']] ?? null;

        if ($newPostId === null || $newUserId === null) {
            $this->error("Unresolved mapping for post_comment legacy_id={$old['id']} (podcast_post_id={$old['podcast_post_id']}, user_id={$old['user_id']})");

            return null;
        }

        return [
            'legacy_id' => $old['id'],
            'podcast_post_id' => $newPostId,
            'user_id' => $newUserId,
            'body' => $old['body'],
            'created_at' => $old['created_at'],
            'updated_at' => $old['updated_at'],
        ];
    }
}
