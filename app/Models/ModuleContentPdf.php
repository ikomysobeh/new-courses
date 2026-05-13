<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModuleContentPdf extends Model
{
    use HasFactory;

    protected $fillable = [
        'module_content_id',
        'file_path',
        'pdf_page_count',
    ];

    protected function casts(): array
    {
        return [
            'pdf_page_count' => 'integer',
        ];
    }

    public function content(): BelongsTo
    {
        return $this->belongsTo(ModuleContent::class, 'module_content_id');
    }
}
