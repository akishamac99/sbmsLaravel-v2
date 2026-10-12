<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_availability_requests', function (Blueprint $t) {
            $t->increments('requestID');
            $t->unsignedInteger('staffID');
            $t->json('schedule');
            $t->string('reason', 255);
            $t->string('status', 20)->default('pending');
            $t->unsignedInteger('reviewed_by')->nullable();
            $t->timestamp('reviewed_at')->nullable();
            $t->string('review_note', 255)->nullable();
            $t->timestamp('created_at')->useCurrent();

            $t->index(['staffID', 'status']);
            $t->foreign('staffID')->references('staffID')->on('salon_staff')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_availability_requests');
    }
};
