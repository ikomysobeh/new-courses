<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')
                ->constrained('courses')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('assigned_by')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('course_availability_id')
                ->nullable()
                ->constrained('course_availabilities')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->timestamp('assigned_at');
            $table->timestamps();

            $table->index('course_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_assignments');
    }
};
