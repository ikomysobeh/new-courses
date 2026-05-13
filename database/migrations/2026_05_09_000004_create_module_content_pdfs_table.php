<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('module_content_pdfs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_content_id')->unique()->constrained('module_contents')->cascadeOnDelete();
            $table->string('file_path', 500);
            $table->unsignedInteger('pdf_page_count')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_content_pdfs');
    }
};
