<?php

namespace App\Console\Commands\LegacyImport;

use App\Models\PodcastPost;
use App\Models\User;
use App\Models\Video;

class ImportPodcastPosts extends LegacyImportCommand
{
    protected $signature = 'legacy:import-podcast-posts';

    protected $description = "Import podcast_posts. tags decoded from old longtext (single-encoded JSON, unlike quiz_questions.options) into the new json column. mediable_id remapped when mediable_type=App\Models\Video (the only type seen in current data) via videos.legacy_id.";

    protected array $userMap = [];

    protected array $videoMap = [];

    protected function legacyTable(): string
    {
        return 'podcast_posts';
    }

    protected function newModel(): string
    {
        return PodcastPost::class;
    }

    protected function beforeImport(): void
    {
        $this->userMap = User::query()->whereNotNull('legacy_id')->pluck('id', 'legacy_id')->all();
        $this->videoMap = Video::query()->whereNotNull('legacy_id')->pluck('id', 'legacy_id')->all();
    }

    protected function mapRow(array $old): ?array
    {
        $newCreatedBy = $this->userMap[$old['created_by']] ?? null;

        if ($newCreatedBy === null) {
            $this->error("No imported User for legacy created_by={$old['created_by']} (podcast_post legacy_id={$old['id']})");

            return null;
        }

        $newMediableId = $old['mediable_id'];

        if ($old['mediable_type'] === 'App\\Models\\Video' && $old['mediable_id'] !== null) {
            $newMediableId = $this->videoMap[$old['mediable_id']] ?? null;

            if ($newMediableId === null) {
                $this->error("No imported Video for legacy mediable_id={$old['mediable_id']} (podcast_post legacy_id={$old['id']})");

                return null;
            }
        }

        return [
            'legacy_id' => $old['id'],
            'title' => $old['title'],
            'slug' => $old['slug'],
            'excerpt' => $old['excerpt'],
            'description' => $old['description'],
            'mediable_type' => $old['mediable_type'],
            'mediable_id' => $newMediableId,
            'thumbnail_path' => $old['thumbnail_path'],
            'status' => $old['status'],
            'published_at' => $old['published_at'],
            'tags' => $old['tags'] !== null && $old['tags'] !== '' ? json_decode($old['tags'], true) : null,
            'created_by' => $newCreatedBy,
            'created_at' => $old['created_at'],
            'updated_at' => $old['updated_at'],
        ];
    }
}
