<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')
                ->constrained('quizzes')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->integer('attempt_number');
            $table->timestamp('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->integer('score')->default(0);
            $table->integer('manual_score')->nullable();
            $table->integer('total_score')->nullable();
            $table->boolean('passed')->default(false);
            $table->boolean('submitted_after_deadline')->default(false);
            $table->timestamps();

            $table->unique(['quiz_id', 'user_id', 'attempt_number']);
            $table->index(['user_id', 'quiz_id']);
            $table->index(['quiz_id', 'completed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_attempts');
    }
};
