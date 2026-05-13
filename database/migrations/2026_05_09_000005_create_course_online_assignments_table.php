<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_online_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_online_id')->constrained('course_onlines')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('assigned_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('assigned_at');
            $table->dateTime('deadline')->nullable();
            $table->boolean('is_overdue')->default(false);
            $table->dateTime('deadline_notification_sent_at')->nullable();
            $table->timestamp('unassigned_at')->nullable();
            $table->foreignId('unassigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['course_online_id', 'user_id']);
            $table->index('course_online_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_online_assignments');
    }
};
