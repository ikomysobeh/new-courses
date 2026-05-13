<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_sends', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('subject')->nullable();
            $table->text('message')->nullable();
            $table->json('recipient_ids')->nullable();
            $table->json('evaluation_ids')->nullable();
            $table->enum('status', ['sent', 'partial', 'failed'])->default('sent');
            $table->unsignedBigInteger('sent_by')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->foreign('sent_by')->references('id')->on('users')->nullOnDelete();
            $table->index('type');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_sends');
    }
};
