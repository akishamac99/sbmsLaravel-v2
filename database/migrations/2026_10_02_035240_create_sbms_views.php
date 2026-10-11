<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private function createView(string $name, string $sql): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement("DROP VIEW IF EXISTS {$name}");
            $sql = preg_replace('/^CREATE\s+OR\s+REPLACE\s+VIEW\s+\S+/i', "CREATE VIEW {$name}", $sql);
            $sql = str_replace(' WITH (security_invoker = true)', '', $sql);
            $sql = str_replace('ORDER BY SUM(b.total_amount) DESC NULLS LAST', 'ORDER BY SUM(b.total_amount) DESC', $sql);
        }

        DB::statement($sql);
    }

    public function up(): void
    {
        $this->createView('view_booking_summary', <<<'SQL'
CREATE OR REPLACE VIEW view_booking_summary WITH (security_invoker = true) AS
SELECT CAST(created_at AS date) AS booking_date,
       COUNT(*) AS total_bookings,
       SUM(CASE WHEN "bookingStatus" = 'pending' THEN 1 ELSE 0 END) AS pending,
       SUM(CASE WHEN "bookingStatus" = 'approved' THEN 1 ELSE 0 END) AS approved,
       SUM(CASE WHEN "bookingStatus" = 'completed' THEN 1 ELSE 0 END) AS completed,
       SUM(CASE WHEN "bookingStatus" = 'cancelled' THEN 1 ELSE 0 END) AS cancelled,
       SUM(downpayment_amount) AS total_downpayment,
       SUM(total_amount) AS total_amount
FROM booking
GROUP BY CAST(created_at AS date)
SQL);

        $this->createView('view_customer_summary', <<<'SQL'
CREATE OR REPLACE VIEW view_customer_summary WITH (security_invoker = true) AS
SELECT c."customerID" AS "customerID",
       CONCAT(c."firstName", ' ', c."lastName") AS customer_name,
       c."contactNumber" AS "contactNumber",
       c.email AS email,
       COUNT(b."bookingID") AS total_bookings,
       SUM(CASE WHEN b."bookingStatus" = 'completed' THEN 1 ELSE 0 END) AS completed,
       SUM(CASE WHEN b."bookingStatus" = 'cancelled' THEN 1 ELSE 0 END) AS cancelled,
       SUM(b.total_amount) AS total_spent,
       MAX(b."bookingDate") AS last_visit
FROM customer c
LEFT JOIN booking b ON c."customerID" = b."customerID"
WHERE c."accountStatus" = 'active'
GROUP BY c."customerID"
ORDER BY SUM(b.total_amount) DESC NULLS LAST
SQL);

        $this->createView('view_revenue_by_service', <<<'SQL'
CREATE OR REPLACE VIEW view_revenue_by_service WITH (security_invoker = true) AS
SELECT s."serviceName" AS "serviceName",
       c.category_name AS category,
       COUNT(b."bookingID") AS bookings_count,
       SUM(b.downpayment_amount) AS total_downpayment,
       SUM(b.total_amount) AS total_revenue
FROM booking b
JOIN service s ON b."serviceID" = s."serviceID"
LEFT JOIN service_categories c ON s."catID" = c."catID"
WHERE b."bookingStatus" <> 'cancelled'
GROUP BY s."serviceID", c.category_name
ORDER BY SUM(b.total_amount) DESC
SQL);

        $this->createView('view_staff_performance', <<<'SQL'
CREATE OR REPLACE VIEW view_staff_performance WITH (security_invoker = true) AS
SELECT s."staffID" AS "staffID",
       CONCAT(s."firstName", ' ', s."lastName") AS staff_name,
       s.role AS role,
       s.specialization AS specialization,
       COALESCE(b.total_bookings, 0) AS total_bookings,
       COALESCE(b.completed, 0) AS completed,
       f.avg_rating AS avg_rating,
       COALESCE(f.feedback_count, 0) AS feedback_count
FROM salon_staff s
LEFT JOIN (
    SELECT "staffID",
           COUNT(*) AS total_bookings,
           SUM(CASE WHEN "bookingStatus" = 'completed' THEN 1 ELSE 0 END) AS completed
    FROM booking
    GROUP BY "staffID"
) b ON b."staffID" = s."staffID"
LEFT JOIN (
    SELECT "staffID", AVG(rating) AS avg_rating, COUNT(*) AS feedback_count
    FROM feedback
    GROUP BY "staffID"
) f ON f."staffID" = s."staffID"
WHERE s.is_active = 1
ORDER BY COALESCE(b.completed, 0) DESC
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS view_staff_performance');
        DB::statement('DROP VIEW IF EXISTS view_revenue_by_service');
        DB::statement('DROP VIEW IF EXISTS view_customer_summary');
        DB::statement('DROP VIEW IF EXISTS view_booking_summary');
    }
};
