<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_sends', function (Blueprint $table) {
            $table->json('employee_ids')->nullable()->after('recipient_ids');
        });
    }

    public function down(): void
    {
        Schema::table('notification_sends', function (Blueprint $table) {
            $table->dropColumn('employee_ids');
        });
    }
};
