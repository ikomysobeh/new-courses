<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reporting_department_course_daily', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('department_id');
            $table->unsignedBigInteger('course_online_id');
            $table->date('report_date');
            $table->unsignedInteger('enrolled_users')->default(0);
            $table->integer('active_users')->default(0);
            $table->unsignedInteger('completed_users')->default(0);
            $table->decimal('avg_progress_percentage', 5, 2)->default(0);
            $table->unsignedInteger('total_active_seconds')->default(0);
            $table->timestamps();

            $table->unique(['department_id', 'course_online_id', 'report_date'], 'rdcd_dept_course_date_unique');
            $table->index(['report_date'],                                         'rdcd_date_idx');
            $table->index(['department_id', 'report_date'],                        'rdcd_dept_date_idx');
            $table->index(['course_online_id', 'report_date'],                     'rdcd_course_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reporting_department_course_daily');
    }
};
