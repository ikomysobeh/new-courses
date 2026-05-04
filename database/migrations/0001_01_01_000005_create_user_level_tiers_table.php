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
        Schema::create('user_level_tiers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_level_id');
            $table->string('tier_name');
            $table->integer('tier_order');
            $table->timestamps();

            // Indexes & Foreign Keys
            $table->foreign('user_level_id')
                ->references('id')
                ->on('user_levels')
                ->onDelete('cascade');

            $table->unique(['user_level_id', 'tier_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_level_tiers');
    }
};
