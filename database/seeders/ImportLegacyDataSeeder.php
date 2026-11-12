<?php

namespace Database\Seeders;

use App\Support\PhoneNumber;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * One-time import of the real records found in database/seeders/data/sbms.sql:
 * customers, the booking and its payment, promos and notifications.
 *
 * Safe to run more than once: every row is matched first (customer by email,
 * booking by reference, promo by code, payment by reference, notification by
 * its content) and skipped when it already exists.
 *
 *   php artisan db:seed --class=ImportLegacyDataSeeder
 */
class ImportLegacyDataSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/sbms.sql');

        if (! is_file($path)) {
            $this->command->error("Missing file: {$path}");

            return;
        }

        $sql = file_get_contents($path);
        $parser = new CatalogSeeder;
        $rows = fn (string $table) => $parser->parseInsert($sql, $table);

        $counts = DB::transaction(function () use ($rows) {
            $counts = ['customers' => 0, 'bookings' => 0, 'payments' => 0, 'promos' => 0, 'notifications' => 0];

            // ---- customers ------------------------------------------------
            $customerMap = [];
            foreach ($rows('customer') as $c) {
                $email = strtolower(trim((string) $c['email']));
                $existing = DB::table('customer')->whereRaw('LOWER(email) = ?', [$email])->value('customerID');

                if ($existing) {
                    $customerMap[(int) $c['customerID']] = (int) $existing;

                    continue;
                }

                $phone = PhoneNumber::normalize($c['contactNumber'] ?? null);
                if ($phone && DB::table('customer')->where('contactNumber', $phone)->exists()) {
                    $phone = null; // number already used by another customer
                }

                $password = (string) $c['password'];
                if (! preg_match('/^\$2[aby]\$/', $password)) {
                    $password = Hash::make($password); // legacy plain-text or other format
                }

                $customerMap[(int) $c['customerID']] = (int) DB::table('customer')->insertGetId([
                    'firstName' => $c['firstName'],
                    'middleName' => $c['middleName'],
                    'lastName' => $c['lastName'],
                    'email' => $email,
                    'password' => $password,
                    'contactNumber' => $phone,
                    'home_address' => $c['home_address'],
                    'accountStatus' => $c['accountStatus'] ?: 'active',
                    'is_verified' => (int) $c['is_verified'],
                    'createdAt' => $c['createdAt'],
                ], 'customerID');
                $counts['customers']++;
            }

            // ---- staff + service lookups (old ID -> current ID) -------------
            $staffMap = [];
            foreach ($rows('salon_staff') as $s) {
                $id = DB::table('salon_staff')->whereRaw('LOWER(email) = ?', [strtolower($s['email'])])->value('staffID');
                if ($id) {
                    $staffMap[(int) $s['staffID']] = (int) $id;
                }
            }

            $serviceMap = [];
            foreach ($rows('service') as $s) {
                $id = DB::table('service')->where('serviceName', $s['serviceName'])->value('serviceID')
                    ?? (DB::table('service')->where('serviceID', $s['serviceID'])->exists() ? (int) $s['serviceID'] : null);
                if ($id) {
                    $serviceMap[(int) $s['serviceID']] = (int) $id;
                }
            }

            // ---- bookings -------------------------------------------------
            $bookingMap = [];
            foreach ($rows('booking') as $b) {
                $existing = DB::table('booking')->where('bookingRef', $b['bookingRef'])->value('bookingID');
                if ($existing) {
                    $bookingMap[(int) $b['bookingID']] = (int) $existing;

                    continue;
                }

                $customerId = $customerMap[(int) $b['customerID']] ?? null;
                $serviceId = $serviceMap[(int) $b['serviceID']] ?? null;
                if (! $customerId || ! $serviceId) {
                    $this->command->warn("Booking {$b['bookingRef']} skipped: customer or service not found.");

                    continue;
                }

                $bookingMap[(int) $b['bookingID']] = (int) DB::table('booking')->insertGetId([
                    'bookingRef' => $b['bookingRef'],
                    'customerID' => $customerId,
                    'serviceID' => $serviceId,
                    'service' => $b['service'],
                    'staffID' => $staffMap[(int) $b['staffID']] ?? null,
                    'bookingDate' => $b['bookingDate'],
                    'bookingTime' => $b['bookingTime'],
                    'bookingStatus' => $b['bookingStatus'],
                    'paymentStatus' => $b['paymentStatus'],
                    'payment_type' => $b['payment_type'],
                    'total_amount' => $b['total_amount'],
                    'downpayment_amount' => $b['downpayment_amount'],
                    'discount_amount' => $b['discount_amount'] ?? 0,
                    'discount_reason' => $b['discount_reason'],
                    'service_type' => $b['service_type'],
                    'home_address' => $b['home_address'],
                    'special_request' => $b['special_request'],
                    'attendance_status' => $b['attendance_status'],
                    'reschedule_count' => (int) $b['reschedule_count'],
                    'cancellation_reason' => $b['cancellation_reason'],
                    // The old proof image file is not part of this project, so the
                    // broken path is not kept (it would only give a 404).
                    'payment_proof' => null,
                    'payment_proof_name' => $b['payment_proof_name'],
                    'created_at' => $b['created_at'],
                    'updated_at' => $b['updated_at'],
                ], 'bookingID');
                $counts['bookings']++;
            }

            // ---- payments -------------------------------------------------
            foreach ($rows('payment') as $p) {
                $bookingId = $bookingMap[(int) $p['bookingID']] ?? null;
                if (! $bookingId) {
                    continue;
                }

                $exists = DB::table('payment')->where('bookingID', $bookingId)
                    ->where('referenceNo', $p['referenceNo'])->exists();
                if ($exists) {
                    continue;
                }

                DB::table('payment')->insert([
                    'bookingID' => $bookingId,
                    'payType' => $p['payType'],
                    'amount' => $p['amount'],
                    'payStatus' => $p['payStatus'],
                    'referenceNo' => $p['referenceNo'],
                    'payment_proof' => null,
                    'payDate' => $p['payDate'],
                ]);
                $counts['payments']++;
            }

            // ---- promos ---------------------------------------------------
            foreach ($rows('promos') as $p) {
                if (DB::table('promos')->where('promo_code', $p['promo_code'])->exists()) {
                    continue;
                }

                DB::table('promos')->insert([
                    'promo_code' => $p['promo_code'],
                    'promo_name' => $p['promo_name'],
                    'discount_type' => $p['discount_type'],
                    'discount_value' => $p['discount_value'],
                    'min_spend' => $p['min_spend'],
                    'max_discount' => $p['max_discount'],
                    'valid_from' => $p['valid_from'],
                    'valid_until' => $p['valid_until'],
                    'is_active' => (int) $p['is_active'],
                    'usage_limit' => $p['usage_limit'],
                    'used_count' => (int) ($p['used_count'] ?? 0),
                    'created_at' => $p['created_at'],
                ]);
                $counts['promos']++;
            }

            // ---- notifications --------------------------------------------
            foreach ($rows('notifications') as $n) {
                $oldId = (int) $n['userID'];
                $userId = $n['user_type'] === 'customer'
                    ? ($customerMap[$oldId] ?? null)
                    : ($staffMap[$oldId] ?? null);

                if (! $userId) {
                    continue;
                }

                $exists = DB::table('notifications')->where([
                    'user_type' => $n['user_type'],
                    'userID' => $userId,
                    'title' => $n['title'],
                    'message' => $n['message'],
                ])->exists();
                if ($exists) {
                    continue;
                }

                DB::table('notifications')->insert([
                    'user_type' => $n['user_type'],
                    'userID' => $userId,
                    'title' => $n['title'],
                    'message' => $n['message'],
                    'is_read' => (int) $n['is_read'],
                    'created_at' => $n['created_at'],
                ]);
                $counts['notifications']++;
            }

            return $counts;
        });

        foreach ($counts as $what => $n) {
            $this->command->info("{$n} {$what} imported.");
        }
    }
}
