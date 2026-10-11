<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('customerID');
            $table->unsignedInteger('staffID');
            $table->string('sender_type', 10); // customer or staff
            $table->text('body');
            $table->timestamp('read_at')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->foreign('customerID')->references('customerID')->on('customer')->cascadeOnDelete();
            $table->foreign('staffID')->references('staffID')->on('salon_staff')->cascadeOnDelete();
            $table->index(['customerID', 'staffID', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
