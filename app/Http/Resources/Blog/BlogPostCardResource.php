<?php

namespace App\Http\Resources\Blog;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class BlogPostCardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'title'        => $this->title,
            'slug'         => $this->slug,
            'excerpt'      => $this->excerpt,
            'thumbnail_url'=> $this->thumbnail_path
                ? Storage::disk('public')->url($this->thumbnail_path)
                : null,
            'status'       => $this->status,
            'published_at' => $this->published_at?->toIso8601String(),
            'tags'         => $this->tags ?? [],
            'has_media'    => !is_null($this->mediable_id),
            'media_type'   => $this->mediable_type
                ? class_basename($this->mediable_type)
                : null,
            'like_count'   => $this->likes_count ?? $this->likes()->count(),
            'comment_count'=> $this->comments_count ?? $this->comments()->count(),
            'author'       => $this->whenLoaded('creator', fn () => [
                'id'   => $this->creator->id,
                'name' => $this->creator->name,
            ]),
            'created_at'   => $this->created_at?->toIso8601String(),
        ];
    }
}
