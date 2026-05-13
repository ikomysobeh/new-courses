<?php

namespace App\Http\Resources\OnlineCourse;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ModuleContentPdfResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'file_path'      => $this->file_path,
            'pdf_page_count' => $this->pdf_page_count,
        ];
    }
}
