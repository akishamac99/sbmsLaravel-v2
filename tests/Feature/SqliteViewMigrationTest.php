<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SqliteViewMigrationTest extends TestCase
{
    public function test_sqlite_views_migration_runs_without_sqlite_unsupported_syntax(): void
    {
        $this->artisan('migrate:fresh', ['--seed' => true, '--force' => true])->assertSuccessful();

        $viewNames = [
            'view_booking_summary',
            'view_customer_summary',
            'view_revenue_by_service',
            'view_staff_performance',
        ];

        $existingViews = DB::table('sqlite_master')
            ->where('type', 'view')
            ->whereIn('name', $viewNames)
            ->pluck('name')
            ->all();

        $this->assertEqualsCanonicalizing($viewNames, $existingViews);
    }
}
