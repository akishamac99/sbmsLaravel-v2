<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $t) {
            $t->increments('notifID');
            $t->enum('user_type', ['customer', 'staff', 'owner', 'manager']);
            $t->unsignedInteger('userID');
            $t->string('title', 255);
            $t->text('message');
            $t->tinyInteger('is_read')->default(0);
            $t->timestamp('created_at')->useCurrent();
        });

        Schema::create('otp_storage', function (Blueprint $t) {
            $t->increments('id');
            $t->string('contact_number', 255);
            $t->string('otp_code', 10);
            $t->string('otp_type', 50);
            $t->dateTime('created_at')->useCurrent();
            $t->dateTime('expires_at');
            $t->tinyInteger('is_used')->default(0);
            $t->dateTime('used_at')->nullable();
            $t->integer('attempts')->default(0);
        });

        Schema::create('permissions', function (Blueprint $t) {
            $t->increments('permID');
            $t->string('role_name', 50);
            $t->string('permission_key', 100);
            $t->tinyInteger('can_view')->default(0);
            $t->tinyInteger('can_create')->default(0);
            $t->tinyInteger('can_edit')->default(0);
            $t->tinyInteger('can_delete')->default(0);

            $t->unique(['role_name', 'permission_key'], 'unique_role_permission');
        });

        Schema::create('promos', function (Blueprint $t) {
            $t->increments('promoID');
            $t->string('promo_code', 50)->unique();
            $t->string('promo_name', 100);
            $t->enum('discount_type', ['percentage', 'fixed'])->default('percentage');
            $t->decimal('discount_value', 10, 2);
            $t->decimal('min_spend', 10, 2)->default(0);
            $t->decimal('max_discount', 10, 2)->nullable();
            $t->date('valid_from');
            $t->date('valid_until');
            $t->tinyInteger('is_active')->default(1);
            $t->integer('usage_limit')->nullable();
            $t->integer('used_count')->default(0);
            $t->timestamp('created_at')->useCurrent();
        });

        Schema::create('staff_availability', function (Blueprint $t) {
            $t->increments('availID');
            $t->unsignedInteger('staffID');
            $t->enum('day_of_week', ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']);
            $t->time('start_time');
            $t->time('end_time');
            $t->tinyInteger('is_available')->default(1);

            $t->unique(['staffID', 'day_of_week'], 'unique_staff_day');
            $t->foreign('staffID')->references('staffID')->on('salon_staff')->cascadeOnDelete();
        });

        Schema::create('staff_unavailability', function (Blueprint $t) {
            $t->increments('unavailID');
            $t->unsignedInteger('staffID');
            $t->date('unavailable_date');
            $t->string('reason', 255)->nullable();

            $t->unique(['staffID', 'unavailable_date'], 'unique_staff_date');
            $t->foreign('staffID')->references('staffID')->on('salon_staff')->cascadeOnDelete();
        });

        Schema::create('system_logs', function (Blueprint $t) {
            $t->increments('logID');
            $t->enum('user_type', ['customer', 'staff', 'owner', 'manager']);
            $t->unsignedInteger('userID');
            $t->string('action', 100);
            $t->text('details')->nullable();
            $t->string('ip_address', 45)->nullable();
            $t->timestamp('created_at')->useCurrent();
        });

        Schema::create('system_policies', function (Blueprint $t) {
            $t->increments('id');
            $t->string('policy_key', 100)->unique();
            $t->text('policy_value')->nullable();
            $t->text('description')->nullable();
            $t->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        foreach ([
            'system_policies', 'system_logs', 'staff_unavailability', 'staff_availability',
            'promos', 'permissions', 'otp_storage', 'notifications',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
