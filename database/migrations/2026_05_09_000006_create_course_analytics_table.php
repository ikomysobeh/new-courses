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
            $table->unsignedInteger('total_completions')->default(0);
            $table->unsignedInteger('total_modules')->default(0);
            $table->unsignedInteger('total_contents')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_analytics');
    }
};
