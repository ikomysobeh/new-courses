<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_onlines', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('image_path', 500)->nullable();
            $table->string('level')->nullable(); // beginner, intermediate, advanced
            $table->unsignedInteger('estimated_duration')->nullable(); // minutes
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->boolean('is_active')->default(true);
            $table->dateTime('deadline')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('created_by');
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_onlines');
    }
};
