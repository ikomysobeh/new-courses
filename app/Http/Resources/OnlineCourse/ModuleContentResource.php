<?php

namespace App\Http\Resources\OnlineCourse;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ModuleContentResource extends JsonResource
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
            'thumbnail_path'       => $this->thumbnail_path
                ? Storage::disk('public')->url($this->thumbnail_path)
                : null,
            'is_required'          => $this->is_required,
            'is_active'            => $this->is_active,
            'attachment_path'      => $this->attachment_path,
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
