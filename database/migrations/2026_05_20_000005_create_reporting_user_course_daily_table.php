<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reporting_user_course_daily', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('course_online_id');
            $table->unsignedBigInteger('department_id')->nullable();
            $table->date('report_date');
            $table->unsignedInteger('sessions_count')->default(0);
            $table->unsignedInteger('active_playback_time')->default(0);
            $table->unsignedInteger('content_items_completed')->default(0);
            $table->decimal('course_progress_pct', 5, 2)->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'course_online_id', 'report_date'], 'rucd_user_course_date_unique');
            $table->index(['course_online_id', 'report_date'],              'rucd_course_date_idx');
            $table->index(['department_id', 'report_date'],                 'rucd_dept_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reporting_user_course_daily');
    }
};
