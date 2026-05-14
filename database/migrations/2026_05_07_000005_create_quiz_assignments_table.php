<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('quiz_id')
                ->constrained('quizzes')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('assigned_by')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->timestamp('assigned_at');
            $table->boolean('notification_sent')->default(false);
            $table->timestamps();
            
            $table->unique(['user_id', 'quiz_id']);
            $table->index(['quiz_id', 'user_id']);
            $table->index('notification_sent');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_assignments');
    }
};
