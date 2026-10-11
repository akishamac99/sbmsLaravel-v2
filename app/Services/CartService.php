<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingCart;
use App\Models\Service;
use App\Models\Staff;
use App\Models\SystemPolicy;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

// The customer's booking cart, plus the rules a service/staff/date/time choice must pass.
// Ported from SalonSystem::getCartItems(), addToCart(), updateCartItem(), validateCartSelection() and friends.
class CartService
{
    public function __construct(private StaffMatcher $matcher) {}

    public function items(int $customerId): Collection
    {
        return BookingCart::with(['service.category', 'staff'])
            ->where('customerID', $customerId)
            ->latest('created_at')
            ->latest('cartID')
            ->get();
    }

    public function count(int $customerId): int
    {
        return BookingCart::where('customerID', $customerId)->count();
    }

    // Returns null when the item was added, or a message explaining why it was refused.
    public function add(int $customerId, string $sessionId, int $serviceId, ?int $staffId, ?string $date, ?string $time, ?string $notes = null, ?string $specialRequest = null): ?string
    {
        if ($error = $this->validate($serviceId, $staffId, $date, $time)) {
            return $error;
        }

        BookingCart::create([
            'customerID' => $customerId,
            'session_id' => $sessionId,
            'serviceID' => $serviceId,
            'staffID' => $staffId,
            'bookingDate' => $date,
            'bookingTime' => Carbon::parse($time)->format('H:i:s'),
            'notes' => $notes,
            'special_request' => $specialRequest,
        ]);

        return null;
    }

    // Returns null when the item was updated, or a message explaining why it was refused.
    public function update(int $cartId, int $customerId, ?int $staffId, ?string $date, ?string $time, ?string $notes = null, ?string $specialRequest = null): ?string
    {
        $item = BookingCart::where('cartID', $cartId)->where('customerID', $customerId)->first();

        if (! $item) {
            return 'That cart item no longer exists.';
        }

        if ($error = $this->validate($item->serviceID, $staffId, $date, $time)) {
            return $error;
        }

        $item->update([
            'staffID' => $staffId,
            'bookingDate' => $date,
            'bookingTime' => Carbon::parse($time)->format('H:i:s'),
            'notes' => $notes,
            'special_request' => $specialRequest,
        ]);

        return null;
    }

    public function remove(int $cartId, int $customerId): bool
    {
        return BookingCart::where('cartID', $cartId)->where('customerID', $customerId)->delete() > 0;
    }

    public function clear(int $customerId): void
    {
        BookingCart::where('customerID', $customerId)->delete();
    }

    // How many bookings already sit on this exact date and time (cancelled, declined and no-show don't count).
    public function slotBookingCount(string $date, string $time, ?int $excludeBookingId = null): int
    {
        return Booking::whereDate('bookingDate', $date)
            ->where('bookingTime', Carbon::parse($time)->format('H:i:s'))
            ->whereNotIn('bookingStatus', ['cancelled', 'declined', 'no-show'])
            ->when($excludeBookingId, fn ($q) => $q->where('bookingID', '<>', $excludeBookingId))
            ->count();
    }

    // The rules a choice must pass. Returns null if it is fine, or a message if it is not.
    public function validate(int $serviceId, ?int $staffId, ?string $date, ?string $time, ?int $excludeBookingId = null): ?string
    {
        if (! $date || ! $time) {
            return 'Please select a booking date and time.';
        }

        $maxPerSlot = (int) SystemPolicy::value('max_bookings_per_slot', 10);

        if ($this->slotBookingCount($date, $time, $excludeBookingId) >= $maxPerSlot) {
            return 'This time slot has reached the booking limit. Please choose another time.';
        }

        $service = Service::with('category')->find($serviceId);

        if (! $service || ! $service->is_active) {
            return 'The selected service is no longer available.';
        }

        if (! $staffId) {
            return 'Please select an available staff member for this service.';
        }

        $staff = Staff::find($staffId);

        if (! $staff || ! $this->matcher->isQualified($staff, $service)) {
            return 'The selected staff member is not qualified for that service.';
        }

        if (! $this->matcher->isAvailable($staffId, $date, $time, $serviceId, $excludeBookingId)) {
            return 'The selected staff member is not available for that date and time.';
        }

        return null;
    }
}
