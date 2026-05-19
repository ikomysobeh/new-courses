<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('type', ['suggestion', 'improvement', 'feature_request', 'general']);
            $table->string('title');
            $table->text('description');
            $table->enum('status', ['pending', 'under_review', 'approved', 'rejected'])->default('pending');
            $table->text('admin_response')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_feedback');
    }
};
