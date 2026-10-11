<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Online payments through PayMongo.
 *
 *  - payment.gateway_ref: the PayMongo checkout session id (cs_...), used to match a webhook to its payment.
 *  - payment.payType may now be "paymongo" (PostgreSQL only: its enum is a CHECK constraint).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment', function (Blueprint $table) {
            $table->string('gateway_ref', 100)->nullable()->index();
        });

        if (DB::getDriverName() === 'pgsql') {
            $this->dropPayTypeCheck();
            DB::statement('ALTER TABLE payment ADD CONSTRAINT payment_paytype_allowed CHECK ("payType" IN (\'cash\', \'gcash\', \'bank_transfer\', \'paymongo\'))');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            $this->dropPayTypeCheck();
            DB::statement('UPDATE payment SET "payType" = \'gcash\' WHERE "payType" = \'paymongo\'');
            DB::statement('ALTER TABLE payment ADD CONSTRAINT payment_paytype_allowed CHECK ("payType" IN (\'cash\', \'gcash\', \'bank_transfer\'))');
        }

        Schema::table('payment', function (Blueprint $table) {
            $table->dropColumn('gateway_ref');
        });
    }

    private function dropPayTypeCheck(): void
    {
        $names = DB::select("select conname from pg_constraint where conrelid = 'payment'::regclass and contype = 'c' and pg_get_constraintdef(oid) ilike '%paytype%'");

        foreach ($names as $row) {
            DB::statement('ALTER TABLE payment DROP CONSTRAINT "'.$row->conname.'"');
        }
    }
};
