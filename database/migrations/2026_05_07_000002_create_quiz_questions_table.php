<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')
                ->constrained('quizzes')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->text('question_text');
            $table->enum('type', ['radio', 'checkbox', 'text'])->default('radio');
            $table->integer('points')->nullable();
            $table->json('options')->nullable();
            $table->json('correct_answer')->nullable();
            $table->text('correct_answer_explanation')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->index(['quiz_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_questions');
    }
};
