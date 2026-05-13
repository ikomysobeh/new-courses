<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluation_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_config_id')
                  ->constrained('evaluation_configs')
                  ->cascadeOnDelete();
            $table->string('type_name');
            $table->unsignedInteger('score_value');
            $table->timestamps();

            $table->index('evaluation_config_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_types');
    }
};
