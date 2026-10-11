<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_categories', function (Blueprint $t) {
            $t->increments('catID');
            $t->string('category_name', 100);
            $t->text('description')->nullable();
            $t->tinyInteger('is_event_type')->default(0);
            $t->timestamp('created_at')->useCurrent();
        });

        Schema::create('salon_staff', function (Blueprint $t) {
            $t->increments('staffID');
            $t->string('firstName', 50);
            $t->string('middleName', 50)->nullable();
            $t->string('lastName', 50);
            $t->string('username', 50)->unique();
            $t->string('password', 255);
            $t->string('email', 100)->unique();
            $t->enum('role', ['Owner', 'Manager', 'Staff'])->default('Staff');
            $t->string('specialization', 100)->nullable();
            $t->string('contactNumber', 20)->nullable();
            $t->string('emergency_contact_name', 100)->nullable();
            $t->string('emergency_contact_number', 20)->nullable();
            $t->text('schedule')->nullable();
            $t->tinyInteger('is_active')->default(1);
            $t->tinyInteger('is_archived')->default(0);
            $t->timestamp('created_at')->useCurrent();
        });

        Schema::create('customer', function (Blueprint $t) {
            $t->increments('customerID');
            $t->string('firstName', 50);
            $t->string('middleName', 50)->nullable();
            $t->string('lastName', 50);
            $t->string('email', 100)->unique();
            $t->string('password', 255);
            $t->string('contactNumber', 20)->unique();
            $t->text('home_address')->nullable();
            $t->string('reset_token', 100)->nullable();
            $t->dateTime('reset_token_expiry')->nullable();
            $t->string('otp_code', 10)->nullable();
            $t->dateTime('otp_expiry')->nullable();
            $t->enum('accountStatus', ['active', 'inactive', 'blocked', 'pending'])->default('pending');
            $t->tinyInteger('is_verified')->default(0);
            $t->timestamp('createdAt')->useCurrent();
        });

        Schema::create('service', function (Blueprint $t) {
            $t->increments('serviceID');
            $t->string('serviceName', 100);
            $t->unsignedInteger('catID')->nullable();
            $t->decimal('servicePrice', 10, 2);
            $t->time('duration');
            $t->text('description')->nullable();
            $t->string('image', 500)->nullable();
            $t->tinyInteger('is_home_service')->default(0);
            $t->tinyInteger('is_active')->default(1);
            $t->timestamp('created_at')->useCurrent();

            $t->foreign('catID')->references('catID')->on('service_categories')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service');
        Schema::dropIfExists('customer');
        Schema::dropIfExists('salon_staff');
        Schema::dropIfExists('service_categories');
    }
};
