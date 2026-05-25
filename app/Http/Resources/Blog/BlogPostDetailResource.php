<?php

namespace App\Http\Resources\Blog;

use App\Services\Blog\Media\BlogMediaService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class BlogPostDetailResource extends JsonResource
{
    public function __construct($resource, private readonly ?string $streamUrl = null, private readonly ?bool $isLiked = null)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        $user = $request->user();

        return [
            'id'           => $this->id,
            'title'        => $this->title,
            'slug'         => $this->slug,
            'excerpt'      => $this->excerpt,
            'description'  => $this->description,
            'thumbnail_url'=> $this->thumbnail_path
                ? Storage::disk('public')->url($this->thumbnail_path)
                : null,
            'status'       => $this->status,
            'published_at' => $this->published_at?->toIso8601String(),
            'tags'         => $this->tags ?? [],
            'author'       => $this->whenLoaded('creator', fn () => [
                'id'   => $this->creator->id,
                'name' => $this->creator->name,
            ]),
            'media'        => $this->mediable_id
                ? $this->buildMediaPayload()
                : null,
            'like_count'   => $this->likes()->count(),
            'is_liked'     => $this->isLiked,
            'comments'     => BlogCommentResource::collection(
                $this->whenLoaded('comments')
            ),
            'created_at'   => $this->created_at?->toIso8601String(),
            'updated_at'   => $this->updated_at?->toIso8601String(),
        ];
    }

    private function buildMediaPayload(): array
    {
        $mediaType = class_basename($this->mediable_type ?? '');

        if ($mediaType === 'Video') {
            $media = $this->mediable;
            return [
                'type'             => 'video',
                'id'               => $media?->id,
                'name'             => $media?->name,
                'duration_seconds' => $media?->duration_seconds,
                'thumbnail_url'    => $media?->thumbnail_path
                    ? Storage::disk('public')->url($media->thumbnail_path)
                    : null,
                'stream_url'       => $this->streamUrl,
            ];
        }

        if ($mediaType === 'Audio') {
            $media = $this->mediable;
            return [
                'type'       => 'audio',
                'id'         => $media?->id,
                'name'       => $media?->name,
                'duration'   => $media?->duration,
                'thumbnail_url' => $media?->thumbnail_path
                    ? Storage::disk('public')->url($media->thumbnail_path)
                    : null,
                'stream_url' => $this->streamUrl,
            ];
        }

        return [];
    }
}
