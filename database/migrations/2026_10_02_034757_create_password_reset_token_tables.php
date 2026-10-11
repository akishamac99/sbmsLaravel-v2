<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['customer_password_reset_tokens', 'staff_password_reset_tokens'] as $table) {
            Schema::create($table, function (Blueprint $t) {
                $t->string('email')->primary();
                $t->string('token');
                $t->timestamp('created_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_password_reset_tokens');
        Schema::dropIfExists('customer_password_reset_tokens');
    }
};
