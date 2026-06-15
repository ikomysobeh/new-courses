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
            'mediable_type' => $this->mediable_type,
            'mediable_id'   => $this->mediable_id,
            'author'        => $this->whenLoaded('creator', fn () => [
                'id'   => $this->creator->id,
                'name' => $this->creator->name,
            ]),
            'like_count'    => $this->likes_count ?? 0,
            'comment_count' => $this->comments_count ?? 0,
            'created_at'    => $this->created_at?->toIso8601String(),
            'updated_at'    => $this->updated_at?->toIso8601String(),
        ];
    }
}
