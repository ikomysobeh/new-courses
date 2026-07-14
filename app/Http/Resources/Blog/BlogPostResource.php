<?php

namespace App\Http\Resources\Blog;

use Illuminate\Http\Request;
use App\Http\Resources\BaseResource;
use Illuminate\Support\Facades\Storage;

class BlogPostResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'title'         => $this->title,
            'slug'          => $this->slug,
            'excerpt'       => $this->excerpt,
            'description'   => $this->description,
            'thumbnail_url' => $this->thumbnail_path
                ? Storage::disk('public')->url($this->thumbnail_path)
                : null,
            'status'        => $this->status,
            'published_at'  => $this->published_at?->toIso8601String(),
            'tags'          => $this->tags ?? [],
            'media_type'    => $this->mediable_type
                ? class_basename($this->mediable_type)
                : null,
            'media'         => $this->when(
                $this->relationLoaded('mediable') && $this->mediable_id,
                function () {
                    $media     = $this->mediable;
                    $mediaType = class_basename($this->mediable_type ?? '');

                    if ($mediaType === 'Video') {
                        return [
                            'type'              => 'video',
                            'id'                => $media?->id,
                            'name'              => $media?->name,
                            'description'       => $media?->description,
                            'duration_seconds'  => $media?->duration_seconds,
                            'thumbnail_url'     => $media?->thumbnail_path
                                ? Storage::disk('public')->url($media->thumbnail_path)
                                : null,
                            'video_category_id' => $media?->video_category_id,
                        ];
                    }

                    if ($mediaType === 'Audio') {
                        return [
                            'type'              => 'audio',
                            'id'                => $media?->id,
                            'name'              => $media?->name,
                            'description'       => $media?->description,
                            'duration'          => $media?->duration,
                            'thumbnail_url'     => $media?->thumbnail_path
                                ? Storage::disk('public')->url($media->thumbnail_path)
                                : null,
                            'audio_category_id' => $media?->audio_category_id,
                        ];
                    }

                    return null;
                },
                fn () => $this->mediable_id
                    ? ['type' => class_basename($this->mediable_type ?? ''), 'id' => $this->mediable_id]
                    : null,
            ),
            'author'        => $this->whenLoaded('creator', fn () => [
                'id'   => $this->creator->id,
                'name' => $this->creator->name,
            ]),
            'like_count'    => $this->likes_count
                ?? ($this->relationLoaded('likes') ? $this->likes->count() : 0),
            'comment_count' => $this->comments_count
                ?? ($this->relationLoaded('comments') ? $this->comments->count() : 0),
            'comments'      => BlogCommentResource::collection($this->whenLoaded('comments')),
            'created_at'    => $this->created_at?->toIso8601String(),
            'updated_at'    => $this->updated_at?->toIso8601String(),
        ];
    }
}
