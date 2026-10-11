<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking', function (Blueprint $t) {
            $t->increments('bookingID');
            $t->string('bookingRef', 20)->unique();
            $t->unsignedInteger('customerID');
            $t->unsignedInteger('serviceID');
            $t->text('service')->nullable();
            $t->unsignedInteger('staffID')->nullable();
            $t->date('bookingDate');
            $t->time('bookingTime');
            $t->enum('bookingStatus', ['pending', 'approved', 'completed', 'declined', 'cancelled', 'no-show'])->default('pending');
            $t->enum('paymentStatus', ['pending', 'proof-submitted', 'confirmed', 'paid', 'rejected', 'cancelled'])->default('pending');
            $t->enum('payment_type', ['full', 'partial'])->default('full');
            $t->decimal('total_amount', 10, 2)->default(0);
            $t->decimal('downpayment_amount', 10, 2)->default(0);
            $t->decimal('discount_amount', 10, 2)->default(0);
            $t->string('discount_reason', 255)->nullable();
            $t->enum('service_type', ['salon', 'home_service'])->default('salon');
            $t->text('home_address')->nullable();
            $t->text('special_request')->nullable();
            $t->enum('attendance_status', ['pending', 'present', 'no-show'])->default('pending');
            $t->integer('reschedule_count')->default(0);
            $t->text('cancellation_reason')->nullable();
            $t->string('payment_proof', 500)->nullable();
            $t->string('payment_proof_name', 255)->nullable();
            $t->timestamp('created_at')->useCurrent();
            $t->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $t->foreign('customerID')->references('customerID')->on('customer')->cascadeOnDelete();
            $t->foreign('serviceID')->references('serviceID')->on('service')->cascadeOnDelete();
            $t->foreign('staffID')->references('staffID')->on('salon_staff')->nullOnDelete();
        });

        Schema::create('booking_cart', function (Blueprint $t) {
            $t->increments('cartID');
            $t->unsignedInteger('customerID');
            $t->string('session_id', 100);
            $t->unsignedInteger('serviceID');
            $t->unsignedInteger('staffID')->nullable();
            $t->date('bookingDate')->nullable();
            $t->time('bookingTime')->nullable();
            $t->text('notes')->nullable();
            $t->text('special_request')->nullable();
            $t->timestamp('created_at')->useCurrent();

            $t->foreign('customerID')->references('customerID')->on('customer')->cascadeOnDelete();
            $t->foreign('serviceID')->references('serviceID')->on('service')->cascadeOnDelete();
            $t->foreign('staffID')->references('staffID')->on('salon_staff')->nullOnDelete();
        });

        Schema::create('payment', function (Blueprint $t) {
            $t->increments('payID');
            $t->unsignedInteger('bookingID');
            $t->enum('payType', ['cash', 'gcash', 'bank_transfer'])->default('cash');
            $t->decimal('amount', 10, 2);
            $t->enum('payStatus', ['pending', 'proof-submitted', 'confirmed', 'paid', 'failed', 'cancelled', 'refunded'])->default('pending');
            $t->string('referenceNo', 50)->nullable();
            $t->string('payment_proof', 500)->nullable();
            $t->dateTime('payDate')->useCurrent();

            $t->foreign('bookingID')->references('bookingID')->on('booking')->cascadeOnDelete();
        });

        Schema::create('feedback', function (Blueprint $t) {
            $t->increments('feedbackID');
            $t->unsignedInteger('bookingID');
            $t->unsignedInteger('customerID');
            $t->unsignedInteger('staffID');
            $t->unsignedInteger('serviceID');
            $t->tinyInteger('rating')->nullable();
            $t->tinyInteger('service_rating')->nullable();
            $t->tinyInteger('staff_rating')->nullable();
            $t->text('comment')->nullable();
            $t->timestamp('created_at')->useCurrent();

            $t->foreign('bookingID')->references('bookingID')->on('booking')->cascadeOnDelete();
            $t->foreign('customerID')->references('customerID')->on('customer')->cascadeOnDelete();
            $t->foreign('staffID')->references('staffID')->on('salon_staff')->cascadeOnDelete();
            $t->foreign('serviceID')->references('serviceID')->on('service')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback');
        Schema::dropIfExists('payment');
        Schema::dropIfExists('booking_cart');
        Schema::dropIfExists('booking');
    }
};
