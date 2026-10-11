<?php

use App\Models\Customer;
use App\Models\Staff;

return [

    /*
    |--------------------------------------------------------------------------
    | SBMS has two kinds of accounts in two tables, so it has two guards:
    |   customer -> the `customer` table
    |   staff    -> the `salon_staff` table (Owner, Manager and Staff roles)
    |--------------------------------------------------------------------------
    */

    'defaults' => [
        'guard' => 'customer',
        'passwords' => 'customers',
    ],

    'guards' => [
        'customer' => [
            'driver' => 'session',
            'provider' => 'customers',
        ],
        'staff' => [
            'driver' => 'session',
            'provider' => 'staffs',
        ],
    ],

    'providers' => [
        'customers' => [
            'driver' => 'eloquent',
            'model' => Customer::class,
        ],
        'staffs' => [
            'driver' => 'eloquent',
            'model' => Staff::class,
        ],
    ],

    // Each kind of account keeps its reset tokens in its own table.
    'passwords' => [
        'customers' => [
            'provider' => 'customers',
            'table' => 'customer_password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
        'staff' => [
            'provider' => 'staffs',
            'table' => 'staff_password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];
