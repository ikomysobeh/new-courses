<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_manager', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('manager_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'manager_id']);
            $table->index('user_id');
            $table->index('manager_id');
        });

        // Backfill existing single-manager relationships (users.report_to) into the pivot.
        $now = now();
        DB::table('users')
            ->whereNotNull('report_to')
            ->orderBy('id')
            ->select('id', 'report_to')
            ->chunk(500, function ($rows) use ($now) {
                $payload = $rows->map(fn ($r) => [
                    'user_id'    => $r->id,
                    'manager_id' => $r->report_to,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all();

                if (! empty($payload)) {
                    DB::table('user_manager')->insertOrIgnore($payload);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_manager');
    }
};
