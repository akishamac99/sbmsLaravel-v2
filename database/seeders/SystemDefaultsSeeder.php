<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Promo;
use App\Models\SystemPolicy;
use Illuminate\Database\Seeder;

class SystemDefaultsSeeder extends Seeder
{
    public function run(): void
    {
        $policies = [
            ['downpayment_percent', '20', 'Percentage of total amount required as downpayment'],
            ['cancellation_window_hours', '24', 'Hours before appointment to allow cancellation'],
            ['max_reschedule_count', '2', 'Maximum number of times a customer can reschedule'],
            ['max_bookings_per_slot', '10', 'Maximum number of bookings per time slot'],
            ['walkins_allowed', '1', 'Whether walk-in customers are allowed (1=yes, 0=no)'],
        ];

        foreach ($policies as [$key, $value, $description]) {
            SystemPolicy::firstOrCreate(
                ['policy_key' => $key],
                ['policy_value' => $value, 'description' => $description]
            );
        }

        // module => [view, create, edit, delete]
        $managerPermissions = [
            'bookings' => [1, 1, 1, 0],
            'payments' => [1, 0, 1, 0],
            'reports' => [1, 0, 0, 0],
            'attendance' => [1, 1, 1, 0],
            'staff' => [1, 0, 0, 0],
            'services' => [1, 0, 0, 0],
            'walkin' => [1, 1, 0, 0],
            'customers' => [1, 0, 0, 0],
        ];

        foreach ($managerPermissions as $module => [$view, $create, $edit, $delete]) {
            Permission::firstOrCreate(
                ['role_name' => 'Manager', 'permission_key' => $module],
                ['can_view' => $view, 'can_create' => $create, 'can_edit' => $edit, 'can_delete' => $delete]
            );
        }

        // Your original promos expired on 2026-06-01, so these run for one year from today.
        $from = now()->toDateString();
        $until = now()->addYear()->toDateString();

        Promo::firstOrCreate(['promo_code' => 'WELCOME10'], [
            'promo_name' => 'Welcome 10% Off',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'min_spend' => 500,
            'valid_from' => $from,
            'valid_until' => $until,
            'is_active' => 1,
        ]);

        Promo::firstOrCreate(['promo_code' => 'NEWYEAR20'], [
            'promo_name' => 'New Year Special',
            'discount_type' => 'percentage',
            'discount_value' => 20,
            'min_spend' => 1000,
            'valid_from' => $from,
            'valid_until' => $until,
            'is_active' => 1,
        ]);
    }
}
