<?php

namespace App\Http\Resources\Video;

use Illuminate\Http\Request;
use App\Http\Resources\BaseResource;

class VideoQualityResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'video_id'  => $this->video_id,
            'quality'   => $this->quality,
            'file_path' => $this->file_path,
            'file_size' => $this->file_size,
        ];
    }
}
