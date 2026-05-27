<?php

namespace App\Http\Resources\OnlineCourse;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ModuleContentPdfResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'file_path'      => $this->file_path
                ? Storage::disk('public')->url($this->file_path)
                : null,
            'pdf_page_count' => $this->pdf_page_count,
        ];
    }
}
