<?php

namespace App\Http\Resources\User\OnlineCourse;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ContentViewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = $this->resource;

        return [
            'content_id'      => $data['content']->id,
            'content_type'    => $data['content']->content_type,
            'title'           => $data['content']->title,
            'duration_seconds' => $data['content']->duration,
            'thumbnail_path'  => (function () use ($data) {
                $content = $data['content'];
                $path = $content->thumbnail_path
                    ?? ($content->content_type === 'video' && $content->relationLoaded('video') && $content->video
                        ? $content->video->thumbnail_path
                        : null);
                return $path ? Storage::disk('public')->url($path) : null;
            })(),
            'attachment_path' => $data['content']->attachment_path
                ? Storage::disk('public')->url($data['content']->attachment_path)
                : null,
            'attachment_name' => $data['content']->attachment_name,
            'media_url'       => $data['media_url'],
            'pdf_total_pages' => $data['pdf_total_pages'] ?? null,
            'progress'        => $data['progress'] ? [
                'playback_position'     => $data['progress']->playback_position,
                'completion_percentage' => $data['progress']->completion_percentage,
                'is_completed'          => $data['progress']->is_completed,
            ] : [
                'playback_position'     => 0,
                'completion_percentage' => 0,
                'is_completed'          => false,
            ],
            'next_content' => $data['next_content'] ? [
                'id'           => $data['next_content']->id,
                'title'        => $data['next_content']->title,
                'content_type' => $data['next_content']->content_type,
            ] : null,
            'prev_content' => $data['prev_content'] ? [
                'id'           => $data['prev_content']->id,
                'title'        => $data['prev_content']->title,
                'content_type' => $data['prev_content']->content_type,
            ] : null,
        ];
    }
}
