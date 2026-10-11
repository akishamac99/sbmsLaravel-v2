<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

// Test customers for your own computer only. Password for all three: customer123
class DemoCustomerSeeder extends Seeder
{
    public function run(): void
    {
        $customers = [
            ['Maria', 'Santos', 'maria@gmail.com', '09171234569'],
            ['John', 'Dela Cruz', 'john@gmail.com', '09171234570'],
            ['Lara', 'Mendoza', 'lara@gmail.com', '09171234571'],
        ];

        foreach ($customers as [$first, $last, $email, $phone]) {
            Customer::firstOrCreate(['email' => $email], [
                'firstName' => $first,
                'lastName' => $last,
                'contactNumber' => $phone,
                'password' => 'customer123',
                'accountStatus' => 'active',
                'is_verified' => 1,
            ]);
        }
    }
}
