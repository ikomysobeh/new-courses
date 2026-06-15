<?php

namespace App\Http\Resources\Blog;

use Illuminate\Http\Request;
use App\Http\Resources\BaseResource;

class BlogLikeResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'like_count' => $this->resource['like_count'],
            'is_liked'   => $this->resource['is_liked'],
        ];
    }
}
