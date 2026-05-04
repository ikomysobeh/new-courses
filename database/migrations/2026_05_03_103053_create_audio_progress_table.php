<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('audio_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('audio_id')
                ->constrained('audios')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->decimal('current_time', 10, 2)->default(0);
            $table->unsignedInteger('total_listened_time')->default(0);
            $table->boolean('is_completed')->default(false);
            $table->decimal('completion_percentage', 5, 2)->default(0);
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'audio_id']);
            $table->index('is_completed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audio_progress');
    }
};
