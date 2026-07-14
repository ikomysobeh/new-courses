<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reporting_user_course_progress', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->enum('course_type', ['traditional', 'online']);
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('department_id')->nullable();

            // Denormalized labels for fast export (no joins needed at read time)
            $table->string('user_name')->nullable();
            $table->string('department_name')->nullable();
            $table->string('course_name')->nullable();

            $table->date('course_beginning_date')->nullable();
            $table->string('status', 20);                 // completed / in_progress / not_started
            $table->string('completion_status', 20);      // Completed / In Progress / Not Started
            $table->boolean('is_completed')->default(false);
            $table->integer('days_overdue')->nullable();
            $table->decimal('progress_percentage', 5, 2)->default(0);
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('deadline')->nullable();

            $table->decimal('attention_score', 5, 2)->default(0);
            $table->decimal('quiz_score', 5, 2)->default(0);
            $table->unsignedTinyInteger('completion_rate')->default(0);
            $table->decimal('learning_score', 5, 2)->default(0);
            $table->string('score_band', 20);             // Excellent / Good / Needs Attention
            $table->string('compliance_status', 20);      // Compliant / At Risk / Non-Compliant

            $table->date('snapshot_date');
            $table->timestamps();

            $table->unique(['user_id', 'course_type', 'course_id'], 'rucp_user_type_course_unique');
            $table->index(['is_completed', 'department_id'], 'rucp_completed_dept_idx');
            $table->index(['course_type', 'status'], 'rucp_type_status_idx');
            $table->index('department_id', 'rucp_dept_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reporting_user_course_progress');
    }
};
