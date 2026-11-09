<?php

namespace App\Actions\Services;

use App\Exceptions\BusinessRuleViolation;
use App\Models\Booking;
use App\Models\Service;
use App\Models\Staff;
use App\Models\SystemLog;
use App\Services\BookingService;
use Illuminate\Support\Facades\DB;

/**
 * UC25 Delete Service (safe delete, as written in the SDD).
 * The service is set inactive so it can no longer be booked, and it is taken out of customer carts.
 * Past bookings keep their link to it.
 */
final class DeleteService
{
    public function upcomingBookingCount(Service $service): int
    {
        return Booking::where('serviceID', $service->getKey())
            ->whereIn('bookingStatus', ['pending', 'approved'])
            ->whereDate('bookingDate', '>=', now()->toDateString())
            ->count();
    }

    public function handle(Staff $actor, Service $service): void
    {
        // The home-service fee is a system line item, not a bookable service.
        if ($service->serviceName === BookingService::HOME_FEE_NAME) {
            throw new BusinessRuleViolation('The home service fee is used by the booking system and cannot be deleted.');
        }

        DB::transaction(function () use ($service): void {
            $service->update(['is_active' => 0]);
            DB::table('booking_cart')->where('serviceID', $service->getKey())->delete();
        });

        SystemLog::record($actor->role_slug, (int) $actor->getKey(), 'delete_service', "Deleted (set inactive) service {$service->serviceName}");
    }
}
