<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('module_contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained('course_modules')->restrictOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('content_type', ['video', 'pdf', 'audio', 'text']);
            $table->unsignedInteger('order_number');
            $table->unsignedBigInteger('content_id')->nullable(); // FK to video/audio/etc (polymorphic by content_type)
            $table->text('text_body')->nullable(); // for content_type = text
            $table->unsignedInteger('estimated_duration')->nullable(); // minutes
            $table->timestamps();

            $table->unique(['module_id', 'order_number']);
            $table->index('module_id');
            $table->index('content_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_contents');
    }
};
