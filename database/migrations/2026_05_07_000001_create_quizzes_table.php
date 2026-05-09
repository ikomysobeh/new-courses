<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')
                ->nullable()
                ->constrained('courses')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->unsignedBigInteger('course_online_id')->nullable();
            $table->unsignedBigInteger('module_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->boolean('required_to_proceed')->default(true);
            $table->integer('max_attempts')->default(3);
            $table->integer('retry_delay_hours')->default(0);
            $table->enum('show_correct_answers', ['never', 'after_pass', 'after_max_attempts', 'always'])->default('after_pass');
            $table->timestamp('deadline')->nullable();
            $table->integer('time_limit_minutes')->nullable();
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->integer('total_points')->default(0);
            $table->decimal('pass_threshold', 5, 2)->default(80.00);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'course_id']);
            $table->index(['status', 'course_online_id']);
            $table->index(['status', 'module_id']);
            $table->index('deadline');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quizzes');
    }
};
