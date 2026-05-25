<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('podcast_post_id')
                ->constrained('podcast_posts')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->timestamps();

            $table->unique(['podcast_post_id', 'user_id']);
            $table->index('podcast_post_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_likes');
    }
};
