<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_content_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('content_id');
            $table->foreign('content_id')->references('id')->on('module_contents')->cascadeOnDelete();
            $table->foreignId('course_online_id')->constrained('course_onlines')->cascadeOnDelete();
            $table->foreignId('module_id')->constrained('course_modules')->cascadeOnDelete();
            $table->enum('content_type', ['video', 'pdf'])->default('video');
            $table->unsignedInteger('watch_time')->default(0);
            $table->unsignedInteger('pdf_pages_viewed')->default(0);
            $table->decimal('completion_percentage', 5, 2)->default(0);
            $table->tinyInteger('is_completed')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('last_accessed_at')->nullable();
            $table->decimal('playback_position', 8, 2)->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'content_id']);
            $table->index(['user_id', 'course_online_id']);
            $table->index(['content_id', 'is_completed']);
            $table->index(['module_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_content_progress');
    }
};
