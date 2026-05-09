<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_attempt_id')
                ->constrained('quiz_attempts')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('quiz_question_id')
                ->constrained('quiz_questions')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->text('answer');
            $table->tinyInteger('is_correct')->nullable();
            $table->integer('points_earned')->nullable();
            $table->timestamps();

            $table->index(['quiz_attempt_id', 'quiz_question_id']);
            $table->index('quiz_question_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_answers');
    }
};
