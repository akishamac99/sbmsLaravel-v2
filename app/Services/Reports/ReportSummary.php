<?php

namespace App\Services\Reports;

use App\Models\Booking;
use App\Models\Feedback;
use Illuminate\Database\Eloquent\Builder;

/**
 * The one place that works out the numbers of the operational report.
 * The Reports page, Download Report and Print Report all use it, so they always agree.
 */
final class ReportSummary
{
    public const STATUSES = ['pending', 'approved', 'completed', 'declined', 'cancelled', 'no-show'];

    /** @param array<string, mixed> $filters */
    public function filteredBookings(array $filters): Builder
    {
        $from = $filters['from'] ?? null;
        $to = $filters['to'] ?? null;

        if ($from && $to && $from > $to) {
            [$from, $to] = [$to, $from];
        }

        $search = trim((string) ($filters['q'] ?? ''));

        return Booking::query()
            ->when($from, fn ($q) => $q->whereDate('booking.bookingDate', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('booking.bookingDate', '<=', $to))
            ->when(isset($filters['status']), fn ($q) => $q->where('booking.bookingStatus', $filters['status']))
            ->when(isset($filters['type']), fn ($q) => $q->where('booking.booking_source', $filters['type']))
            ->when(isset($filters['staff_id']), fn ($q) => $q->where('booking.staffID', (int) $filters['staff_id']))
            ->when(isset($filters['service_id']), fn ($q) => $q->where('booking.serviceID', (int) $filters['service_id']))
            ->when($search !== '', function ($q) use ($search) {
                $like = '%'.mb_strtolower($search).'%';
                $q->where(fn ($inner) => $inner
                    ->whereRaw('LOWER(booking."bookingRef") LIKE ?', [$like])
                    ->orWhereRaw('LOWER(booking.service) LIKE ?', [$like])
                    ->orWhereHas('customer', fn ($customer) => $customer
                        ->whereRaw('LOWER("firstName" || \' \' || "lastName") LIKE ?', [$like])
                        ->orWhere('contactNumber', 'like', '%'.$search.'%')));
            });
    }

    /** @param array<string, mixed> $filters */
    public function build(Builder $filtered, array $filters = []): array
    {
        $totals = (clone $filtered)->toBase()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN booking."bookingStatus" = \'pending\' THEN 1 ELSE 0 END) as pending')
            ->selectRaw('SUM(CASE WHEN booking."bookingStatus" = \'completed\' THEN 1 ELSE 0 END) as completed')
            ->selectRaw('SUM(CASE WHEN booking."bookingStatus" = \'completed\' THEN booking.total_amount ELSE 0 END) as revenue')
            ->first();

        $byService = (clone $filtered)->toBase()
            ->whereNotIn('booking.bookingStatus', ['cancelled', 'declined', 'no-show'])
            ->join('service as s', 'booking.serviceID', '=', 's.serviceID')
            ->leftJoin('service_categories as c', 's.catID', '=', 'c.catID')
            ->groupBy('s.serviceID', 's.serviceName', 'c.category_name')
            ->select('s.serviceName', 'c.category_name as category')
            ->selectRaw('COUNT(*) as bookings_count')
            ->selectRaw('SUM(booking.total_amount) as total_revenue')
            ->orderByRaw('SUM(booking.total_amount) DESC')
            ->limit(10)
            ->get();

        $staffPerformance = (clone $filtered)->toBase()
            ->join('salon_staff as st', 'booking.staffID', '=', 'st.staffID')
            ->leftJoin('feedback as f', 'f.bookingID', '=', 'booking.bookingID')
            ->groupBy('st.staffID', 'st.firstName', 'st.lastName')
            ->select('st.firstName', 'st.lastName')
            ->selectRaw('SUM(CASE WHEN booking."bookingStatus" = \'completed\' THEN 1 ELSE 0 END) as completed')
            ->selectRaw('COUNT(f."feedbackID") as feedback_count')
            ->selectRaw('AVG(f.rating) as avg_rating')
            ->orderByRaw('SUM(CASE WHEN booking."bookingStatus" = \'completed\' THEN 1 ELSE 0 END) DESC')
            ->limit(10)
            ->get()
            ->map(fn ($row) => (object) [
                'staff_name' => trim($row->firstName.' '.$row->lastName),
                'completed' => (int) $row->completed,
                'feedback_count' => (int) $row->feedback_count,
                'avg_rating' => $row->avg_rating,
            ]);

        return [
            'generatedAt' => now()->format('F j, Y g:i A'),
            'filterSummary' => $this->describe($filters),
            'totalBookings' => (int) ($totals->total ?? 0),
            'pendingBookings' => (int) ($totals->pending ?? 0),
            'completedBookings' => (int) ($totals->completed ?? 0),
            'completedRevenue' => (float) ($totals->revenue ?? 0),
            'feedbackCount' => Feedback::whereIn('bookingID', (clone $filtered)->select('booking.bookingID'))->count(),
            'byService' => $byService,
            'staffPerformance' => $staffPerformance,
        ];
    }

    /** A short sentence such as "Status: completed; From: 2026-11-01". */
    private function describe(array $filters): string
    {
        $labels = ['from' => 'From', 'to' => 'To', 'status' => 'Status', 'type' => 'Type', 'staff_id' => 'Staff ID', 'service_id' => 'Service ID', 'q' => 'Search'];
        $parts = [];

        foreach ($labels as $key => $label) {
            if (isset($filters[$key])) {
                $parts[] = "{$label}: {$filters[$key]}";
            }
        }

        return $parts ? implode('; ', $parts) : 'All bookings';
    }
}
