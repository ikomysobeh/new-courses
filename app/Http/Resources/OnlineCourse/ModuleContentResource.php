<?php

namespace App\Http\Resources\OnlineCourse;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ModuleContentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'module_id'          => $this->module_id,
            'name'               => $this->name,
            'description'        => $this->description,
            'content_type'       => $this->content_type,
            'order_number'       => $this->order_number,
            'content_id'         => $this->content_id,
            'text_body'          => $this->text_body,
            'estimated_duration' => $this->estimated_duration,
            'pdf'                => $this->whenLoaded('pdf', fn () =>
                $this->pdf ? new ModuleContentPdfResource($this->pdf) : null
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
