<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('module_contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained('course_modules')->restrictOnDelete();
            $table->enum('content_type', ['video', 'pdf']);
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('order_number');
            $table->foreignId('video_id')->nullable()->constrained('videos')->restrictOnDelete();
            $table->unsignedInteger('duration')->nullable(); // seconds
            $table->string('thumbnail_path')->nullable();
            $table->boolean('is_required')->default(true);
            $table->boolean('is_active')->default(true);
            $table->string('attachment_path')->nullable();
            $table->string('attachment_name')->nullable();
            $table->string('attachment_extension')->nullable();
            $table->timestamps();

            $table->unique(['module_id', 'order_number']);
            $table->index('module_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_contents');
    }
};
