<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
            $table->enum('course_type', ['regular', 'online']);
            $table->unsignedBigInteger('course_id')->nullable();
            $table->unsignedBigInteger('course_online_id')->nullable();
            $table->unsignedInteger('total_score')->default(0);
            $table->tinyInteger('performance_level')->unsigned()->nullable();
            $table->integer('performance_points_min')->nullable();
            $table->integer('performance_points_max')->nullable();
            $table->timestamps();

            $table->foreign('course_id')->references('id')->on('courses')->nullOnDelete();
            $table->foreign('course_online_id')->references('id')->on('course_onlines')->nullOnDelete();

            $table->index('user_id');
            $table->index('department_id');
            $table->index('course_type');
            $table->index('performance_level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluations');
    }
};
