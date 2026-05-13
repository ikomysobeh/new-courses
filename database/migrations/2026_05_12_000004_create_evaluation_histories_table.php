<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluation_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_id')
                  ->constrained('evaluations')
                  ->cascadeOnDelete();
            $table->unsignedBigInteger('course_online_id')->nullable();
            // Snapshot strings — no FK to config tables so history is immutable
            $table->string('config_name');
            $table->string('type_name');
            $table->unsignedInteger('score_given');
            $table->unsignedInteger('max_score');
            $table->timestamps();

            $table->foreign('course_online_id')->references('id')->on('course_onlines')->nullOnDelete();

            $table->index('evaluation_id');
            $table->index('course_online_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_histories');
    }
};
