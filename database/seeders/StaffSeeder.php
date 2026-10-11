<?php

namespace Database\Seeders;

use App\Models\Staff;
use Illuminate\Database\Seeder;

class StaffSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['Janet', 'Arkias', 'janet', 'janet@salon.com', 'Owner', 'owner123', 'Owner / Physical Logbook Records'],
            ['Janah', 'Arkias', 'janah', 'janah@salon.com', 'Owner', 'owner123', 'Owner/ Overall Handler'],
            ['Hace Mae', 'Mendoza', 'hace', 'hace@salon.com', 'Manager', 'manager123', 'Nail Tech / Lash Tech / Brows Tech / Booking Records and Operational Reports Coordinator'],
            ['Aurelia', 'Alcantara', 'aurelia', 'aurelia@salon.com', 'Staff', 'staff123', 'All-Around Salon Services / Operations'],
            ['Pau', 'Badilla', 'pau', 'pau@salon.com', 'Staff', 'staff123', 'All-Around Salon Services / Operations'],
            ['Charls', 'Baguitan', 'charls', 'charls@salon.com', 'Staff', 'staff123', 'Barbering Services'],
            ['Robert', 'Ramos', 'robert', 'robert@salon.com', 'Staff', 'staff123', 'Barbering Services'],
            ['Jean', 'Abrera', 'jean', 'jean@salon.com', 'Staff', 'staff123', 'Hair and Makeup Services'],
            ['Krishna', 'Arkias', 'krishna', 'krishna@salon.com', 'Staff', 'staff123', 'Hair and Makeup Services'],
            ['Xena', 'Arkias', 'xena', 'xena@salon.com', 'Staff', 'staff123', 'Hair and Makeup Services'],
        ];

        foreach ($rows as [$first, $last, $username, $email, $role, $password, $specialization]) {
            // firstOrCreate: running the seeder again never resets a password someone has changed.
            Staff::firstOrCreate(['email' => $email], [
                'firstName' => $first,
                'lastName' => $last,
                'username' => $username,
                'password' => $password,   // hashed automatically by the model
                'role' => $role,
                'specialization' => $specialization,
                'is_active' => 1,
                'is_archived' => 0,
            ]);
        }
    }
}
