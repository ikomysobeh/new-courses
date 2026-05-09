<?php

namespace App\Http\Resources\OnlineCourse;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ModuleContentPdfResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'module_content_id' => $this->module_content_id,
            'file_path'         => $this->file_path,
            'original_filename' => $this->original_filename,
            'file_size'         => $this->file_size,
            'page_count'        => $this->page_count,
            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,
        ];
    }
}
