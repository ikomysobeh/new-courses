<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_online_id')->constrained('course_onlines')->restrictOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('order_number');
            $table->unsignedInteger('estimated_duration')->nullable(); // minutes
            $table->boolean('has_quiz')->default(false);
            $table->boolean('quiz_required')->default(true);
            $table->timestamps();

            $table->unique(['course_online_id', 'order_number']);
            $table->index('course_online_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_modules');
    }
};
