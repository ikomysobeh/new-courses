<?php

namespace App\Http\Resources\OnlineCourse;

use Illuminate\Http\Request;
use App\Http\Resources\BaseResource;
use Illuminate\Support\Facades\Storage;

class ModuleContentResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'module_id'            => $this->module_id,
            'content_type'         => $this->content_type,
            'title'                => $this->title,
            'description'          => $this->description,
            'order_number'         => $this->order_number,
            'duration'             => $this->duration,
            'thumbnail_path'       => (function () {
                $path = $this->thumbnail_path
                    ?? ($this->content_type === 'video' && $this->relationLoaded('video') && $this->video
                        ? $this->video->thumbnail_path
                        : null);
                return $path ? Storage::disk('public')->url($path) : null;
            })(),
            'is_required'          => $this->is_required,
            'is_active'            => $this->is_active,
            'attachment_path'      => $this->attachment_path
                ? Storage::disk('public')->url($this->attachment_path)
                : null,
            'attachment_name'      => $this->attachment_name,
            'attachment_extension' => $this->attachment_extension,
            'video'                => $this->whenLoaded('video', fn () =>
                $this->video ? [
                    'id'               => $this->video->id,
                    'name'             => $this->video->name,
                    'transcode_status' => $this->video->transcode_status,
                ] : null
            ),
            'pdf' => $this->whenLoaded('pdf', fn () =>
                $this->pdf ? new ModuleContentPdfResource($this->pdf) : null
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
