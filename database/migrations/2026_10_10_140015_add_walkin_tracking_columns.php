<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Walk-ins become a first-class booking type instead of being guessed from text.
 *
 *  - booking.booking_source: "online" (customer portal) or "walkin" (front desk).
 *  - customer.is_walkin: a record created at the front desk, without a login.
 *  - customer.email becomes nullable, so walk-ins no longer get a fake
 *    "walkin-<uuid>@invalid.local" address. Existing fake addresses are cleared.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking', function (Blueprint $table) {
            $table->string('booking_source', 20)->default('online');
        });

        Schema::table('customer', function (Blueprint $table) {
            $table->tinyInteger('is_walkin')->default(0);
        });

        $this->withoutSqliteViews(function (): void {
            Schema::table('customer', function (Blueprint $table) {
                $table->string('email', 100)->nullable()->change();
            });
        });

        DB::table('booking')
            ->where('special_request', 'Walk-in booking')
            ->update(['booking_source' => 'walkin', 'special_request' => null]);

        DB::table('customer')
            ->where('email', 'like', 'walkin-%@invalid.local')
            ->update(['is_walkin' => 1, 'email' => null]);
    }

    public function down(): void
    {
        DB::table('booking')
            ->where('booking_source', 'walkin')
            ->whereNull('special_request')
            ->update(['special_request' => 'Walk-in booking']);

        foreach (DB::table('customer')->whereNull('email')->pluck('customerID') as $id) {
            DB::table('customer')->where('customerID', $id)->update(['email' => "walkin-{$id}@invalid.local"]);
        }

        $this->withoutSqliteViews(function (): void {
            Schema::table('customer', function (Blueprint $table) {
                $table->string('email', 100)->nullable(false)->change();
            });
        });

        Schema::table('customer', function (Blueprint $table) {
            $table->dropColumn('is_walkin');
        });

        Schema::table('booking', function (Blueprint $table) {
            $table->dropColumn('booking_source');
        });
    }

    /**
     * SQLite rebuilds a table to change a column, which fails while a view points at it.
     * The reporting views are dropped first and recreated afterwards (SQLite only).
     */
    private function withoutSqliteViews(callable $callback): void
    {
        if (! in_array(DB::getDriverName(), ['sqlite', 'pgsql'], true)) {
            $callback();

            return;
        }

        $views = require database_path('migrations/2026_10_02_035240_create_sbms_views.php');
        $views->down();
        $callback();
        $views->up();
    }
};
