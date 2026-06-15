<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reporting_refresh_log', function (Blueprint $table) {
            $table->id();
            $table->string('report_table', 100);
            $table->date('report_date')->nullable();
            $table->timestamp('refreshed_at')->useCurrent();
            $table->integer('duration_seconds')->nullable();
            $table->integer('rows_written')->nullable();
            $table->enum('status', ['success', 'failed'])->default('success');
            $table->text('error_message')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['report_table', 'refreshed_at'], 'rrl_table_refreshed_idx');
            $table->index('status',                         'rrl_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reporting_refresh_log');
    }
};
