<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_course_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('course_online_id')->constrained('course_onlines')->cascadeOnDelete();
            $table->decimal('progress_percentage', 5, 2)->default(0);
            $table->enum('status', ['not_started', 'in_progress', 'completed'])->default('not_started');
            $table->unsignedInteger('total_content_items')->default(0);
            $table->unsignedInteger('completed_content_items')->default(0);
            $table->unsignedBigInteger('current_module_id')->nullable();
            $table->foreign('current_module_id')->references('id')->on('course_modules')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('last_accessed_at')->nullable();
            $table->unsignedBigInteger('last_session_id')->nullable();
            $table->foreign('last_session_id')->references('id')->on('learning_sessions')->nullOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'course_online_id']);
            $table->index(['course_online_id', 'status']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_course_progress');
    }
};
