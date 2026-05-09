<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_qualities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_id')
                ->constrained('videos')
                ->cascadeOnDelete();
            $table->string('quality', 20);
            $table->string('file_path');
            $table->unsignedBigInteger('file_size')->nullable();
            $table->timestamps();

            $table->unique(['video_id', 'quality']);
            $table->index('video_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_qualities');
    }
};
