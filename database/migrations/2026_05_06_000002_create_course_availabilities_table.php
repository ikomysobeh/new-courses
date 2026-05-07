<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')
                ->constrained('courses')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->unsignedInteger('capacity')->default(20);
            $table->unsignedInteger('sessions')->default(1);
            $table->unsignedInteger('duration_weeks')->nullable();
            $table->enum('status', ['active', 'closed'])->default('active');
            $table->text('notes')->nullable();
            $table->string('days_of_week')->nullable();
            $table->time('session_time_shift_1')->nullable();
            $table->time('session_time_shift_2')->nullable();
            $table->time('session_time_shift_3')->nullable();
            $table->unsignedInteger('session_duration_minutes')->default(60);
            $table->timestamps();

            $table->index('course_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_availabilities');
    }
};
