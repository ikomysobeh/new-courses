<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('course_online_id')->constrained('course_onlines')->cascadeOnDelete();
            $table->unsignedBigInteger('content_id')->nullable();
            $table->foreign('content_id')->references('id')->on('module_contents')->nullOnDelete();
            $table->enum('content_type', ['video', 'pdf'])->default('video');
            $table->timestamp('session_start');
            $table->timestamp('session_end')->nullable();
            $table->timestamp('last_progress_at')->nullable();
            $table->unsignedInteger('active_playback_time')->default(0);
            $table->unsignedInteger('wall_clock_seconds')->nullable();
            $table->unsignedInteger('skip_count')->default(0);
            $table->unsignedInteger('seek_count')->default(0);
            $table->unsignedInteger('replay_count')->default(0);
            $table->unsignedInteger('pause_count')->default(0);
            $table->unsignedInteger('speed_changes')->default(0);
            $table->unsignedInteger('fullscreen_count')->default(0);
            $table->decimal('video_completion_percentage', 5, 2)->default(0);
            $table->unsignedTinyInteger('attention_score')->nullable();
            $table->tinyInteger('is_suspicious')->default(0);
            $table->json('events_log')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'course_online_id', 'session_start']);
            $table->index(['course_online_id', 'session_start']);
            $table->index(['content_id', 'session_start']);
            $table->index(['is_suspicious', 'attention_score']);
            $table->index('last_progress_at');
            $table->index('session_end');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_sessions');
    }
};
