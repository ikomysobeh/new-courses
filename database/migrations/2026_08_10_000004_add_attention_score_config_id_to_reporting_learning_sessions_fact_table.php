<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reporting_learning_sessions_fact', function (Blueprint $table) {
            $table->foreignId('attention_score_config_id')
                ->nullable()
                ->after('attention_score')
                ->constrained('attention_score_configs', 'id', 'reporting_fact_config_id_fk')
                ->nullOnDelete();

            $table->index('attention_score_config_id');
        });
    }

    public function down(): void
    {
        Schema::table('reporting_learning_sessions_fact', function (Blueprint $table) {
            $table->dropForeign('reporting_fact_config_id_fk');
            $table->dropColumn('attention_score_config_id');
        });
    }
};
