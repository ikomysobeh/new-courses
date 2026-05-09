<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('file_path');
            $table->unsignedBigInteger('file_size')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->string('thumbnail_path')->nullable();
            $table->string('subtitle_vtt_path')->nullable();
            $table->foreignId('video_category_id')
                ->constrained('video_categories')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->enum('transcode_status', ['pending', 'processing', 'completed', 'failed'])
                ->default('pending');
            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('video_category_id');
            $table->index('transcode_status');
            $table->index('created_by');
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};
