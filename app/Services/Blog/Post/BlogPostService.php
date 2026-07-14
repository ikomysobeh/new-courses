<?php

namespace App\Services\Blog\Post;

use App\Models\PodcastPost;
use App\Services\Blog\Media\BlogMediaService;
use App\Support\Filtering\FilterableQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BlogPostService
{
    use FilterableQuery;

    public function __construct(
        private readonly BlogMediaService $mediaService,
    ) {}

    public function getPublicFeed(int $perPage = 15): LengthAwarePaginator
    {
        return PodcastPost::query()
            ->published()
            ->with(['creator:id,name'])
            ->orderByDesc('published_at')
            ->paginate($perPage);
    }

    public function getPostBySlug(string $slug): PodcastPost
    {
        return PodcastPost::query()
            ->published()
            ->where('slug', $slug)
            ->with(['creator:id,name', 'comments.user:id,name', 'likes', 'mediable'])
            ->firstOrFail();
    }

    public function getRelatedPosts(PodcastPost $post, int $limit = 4): \Illuminate\Database\Eloquent\Collection
    {
        return PodcastPost::query()
            ->published()
            ->where('id', '!=', $post->id)
            ->when($post->tags, function ($query) use ($post) {
                // match posts that share at least one tag
                foreach ((array) $post->tags as $tag) {
                    $query->orWhereJsonContains('tags', $tag);
                }
            })
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get();
    }

    public function getAllForAdmin(array $params = []): LengthAwarePaginator
    {
        $query = PodcastPost::query()
            ->with(['creator:id,name'])
            ->withCount(['likes', 'comments']);

        return $this->applyFilters($query, $params, [
            'searchable'  => ['title', 'excerpt', 'creator.name'],
            'filters'     => ['status' => 'exact'],
            'dateColumn'  => 'created_at',
            'sortable'    => ['title', 'created_at', 'published_at'],
            'defaultSort' => ['created_at', 'desc'],
            'perPage'     => 15,
        ]);
    }

    public function getByIdForAdmin(int $id): PodcastPost
    {
        return PodcastPost::query()
            ->with(['creator:id,name', 'comments.user:id,name', 'likes', 'mediable'])
            ->withCount(['likes', 'comments'])
            ->findOrFail($id);
    }

    public function createPost(array $data, int $createdBy): PodcastPost
    {
        return DB::transaction(function () use ($data, $createdBy) {
            $this->mediaService->validateMediableSelection(
                $data['mediable_type'] ?? null,
                isset($data['mediable_id']) ? (int) $data['mediable_id'] : null
            );

            $slug = $data['slug'] ?? PodcastPost::generateUniqueSlug($data['title']);

            $thumbnailPath = null;
            if (!empty($data['thumbnail'])) {
                $thumbnailPath = $data['thumbnail']->store('blog/thumbnails', 'public');
            }

            $post = PodcastPost::create([
                'title'         => $data['title'],
                'slug'          => $slug,
                'excerpt'       => $data['excerpt'] ?? null,
                'description'   => $data['description'] ?? null,
                'mediable_type' => $data['mediable_type'] ?? null,
                'mediable_id'   => $data['mediable_id'] ?? null,
                'thumbnail_path'=> $thumbnailPath,
                'status'        => $data['status'] ?? 'draft',
                'published_at'  => ($data['status'] ?? 'draft') === 'published' ? now() : null,
                'tags'          => $data['tags'] ?? null,
                'created_by'    => $createdBy,
            ]);

            return $post;
        });
    }

    public function updatePost(PodcastPost $post, array $data): PodcastPost
    {
        return DB::transaction(function () use ($post, $data) {
            $this->mediaService->validateMediableSelection(
                $data['mediable_type'] ?? $post->mediable_type,
                isset($data['mediable_id']) ? (int) $data['mediable_id'] : $post->mediable_id
            );

            if (isset($data['slug']) && $data['slug'] !== $post->slug) {
                $data['slug'] = PodcastPost::generateUniqueSlug($data['slug'], $post->id);
            }

            if (!empty($data['thumbnail'])) {
                if ($post->thumbnail_path) {
                    Storage::disk('public')->delete($post->thumbnail_path);
                }
                $data['thumbnail_path'] = $data['thumbnail']->store('blog/thumbnails', 'public');
            }
            unset($data['thumbnail']);

            // Set published_at only on first publish
            if (isset($data['status']) && $data['status'] === 'published' && !$post->published_at) {
                $data['published_at'] = now();
            }

            $post->update($data);

            return $post->fresh();
        });
    }

    public function deletePost(PodcastPost $post): void
    {
        DB::transaction(function () use ($post) {
            if ($post->thumbnail_path) {
                Storage::disk('public')->delete($post->thumbnail_path);
            }
            $post->comments()->delete();
            $post->likes()->delete();
            $post->delete();
        });
    }

    public function publishPost(PodcastPost $post): PodcastPost
    {
        $post->update([
            'status'       => 'published',
            'published_at' => $post->published_at ?? now(),
        ]);

        return $post->fresh();
    }

    public function draftPost(PodcastPost $post): PodcastPost
    {
        $post->update(['status' => 'draft']);

        return $post->fresh();
    }
}
