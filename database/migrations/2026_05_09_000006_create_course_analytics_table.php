<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_online_id')->unique()->constrained('course_onlines')->cascadeOnDelete();
            $table->unsignedInteger('total_enrollments')->default(0);
            $table->unsignedInteger('active_learners')->default(0);
            $table->unsignedInteger('completed_learners')->default(0);
            $table->decimal('completion_rate', 5, 2)->default(0);
            $table->decimal('dropout_rate', 5, 2)->default(0);
            $table->unsignedInteger('average_session_duration_minutes')->default(0);
            $table->decimal('average_video_completion_rate', 5, 2)->default(0);
            $table->unsignedInteger('cheating_incidents_count')->default(0);
            $table->timestamp('last_calculated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_analytics');
    }
};
