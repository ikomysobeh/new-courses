<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learning_sessions', function (Blueprint $table) {
            $table->foreignId('attention_score_config_id')
                ->nullable()
                ->after('is_suspicious')
                ->constrained('attention_score_configs')
                ->nullOnDelete();

            $table->json('watched_segments')->nullable()->after('events_log');
            $table->decimal('last_played_position', 10, 2)->default(0)->after('watched_segments');
            $table->decimal('unwatched_seconds_skipped', 10, 2)->default(0)->after('last_played_position');

            $table->index('attention_score_config_id');
        });
    }

    public function down(): void
    {
        Schema::table('learning_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('attention_score_config_id');
            $table->dropColumn(['watched_segments', 'last_played_position', 'unwatched_seconds_skipped']);
        });
    }
};
