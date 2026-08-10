<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attention_score_recalculation_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attention_score_config_id')
                ->constrained('attention_score_configs', 'id', 'asr_jobs_config_id_fk')
                ->cascadeOnDelete();
            $table->enum('status', ['queued', 'running', 'done', 'failed'])->default('queued');
            $table->unsignedInteger('total_sessions')->default(0);
            $table->unsignedInteger('processed_sessions')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attention_score_recalculation_jobs');
    }
};
