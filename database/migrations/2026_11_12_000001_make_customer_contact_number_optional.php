<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * customer.contactNumber becomes optional. The unique rule stays: PostgreSQL lets many rows have no number.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE customer ALTER COLUMN "contactNumber" DROP NOT NULL');

            return;
        }

        Schema::table('customer', function (Blueprint $t) {
            $t->string('contactNumber', 20)->nullable()->change();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('UPDATE customer SET "contactNumber" = \'unknown-\' || "customerID" WHERE "contactNumber" IS NULL');
            DB::statement('ALTER TABLE customer ALTER COLUMN "contactNumber" SET NOT NULL');
        }
    }
};
