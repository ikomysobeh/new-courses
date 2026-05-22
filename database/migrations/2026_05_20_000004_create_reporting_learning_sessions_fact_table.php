<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reporting_learning_sessions_fact', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('learning_sessions')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('course_online_id');
            $table->unsignedBigInteger('content_id')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->date('session_date');
            $table->unsignedInteger('active_playback_time');
            $table->unsignedInteger('wall_clock_seconds')->nullable();
            $table->decimal('completion_percentage', 5, 2);
            $table->unsignedTinyInteger('attention_score')->nullable();
            $table->tinyInteger('is_suspicious')->default(0);
            $table->unsignedInteger('skip_count');
            $table->unsignedInteger('seek_count');
            $table->unsignedInteger('replay_count');
            $table->unsignedInteger('pause_count');
            $table->tinyInteger('content_completed')->default(0);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'session_date'],          'rls_user_date_idx');
            $table->index(['course_online_id', 'session_date'],  'rls_course_date_idx');
            $table->index(['department_id', 'session_date'],     'rls_dept_date_idx');
            $table->index('is_suspicious',                       'rls_suspicious_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reporting_learning_sessions_fact');
    }
};
