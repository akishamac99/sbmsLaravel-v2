<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SDD Table 7.5 (booking.serviceID): "on delete restrict: services are deactivated, never deleted".
 * The original migration cascaded, so deleting a service would have deleted its bookings.
 */
return new class extends Migration
{
    public function up(): void
    {
        // The SQLite test database is rebuilt from the original migrations; this fix is for PostgreSQL.
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        Schema::table('booking', function (Blueprint $t) {
            $t->dropForeign(['serviceID']);
            $t->foreign('serviceID')->references('serviceID')->on('service')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        Schema::table('booking', function (Blueprint $t) {
            $t->dropForeign(['serviceID']);
            $t->foreign('serviceID')->references('serviceID')->on('service')->cascadeOnDelete();
        });
    }
};
