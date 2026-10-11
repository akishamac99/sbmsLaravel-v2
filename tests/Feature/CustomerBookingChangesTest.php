<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Staff;
use App\Models\StaffAvailability;
use App\Models\SystemPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CustomerBookingChangesTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    private Staff $owner;

    private Staff $stylist;

    private Staff $otherStylist;

    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(today()->setTime(9, 0));

        SystemPolicy::create(['policy_key' => 'cancellation_window_hours', 'policy_value' => '24']);
        SystemPolicy::create(['policy_key' => 'max_reschedule_count', 'policy_value' => '1']);

        $this->owner = $this->makeStaff('owner', 'Owner');
        $this->stylist = $this->makeStaff('stylist', 'Staff');
        $this->otherStylist = $this->makeStaff('other', 'Staff');
        StaffAvailability::openAllWeek((int) $this->stylist->getKey());
        StaffAvailability::openAllWeek((int) $this->otherStylist->getKey());

        $this->service = Service::create(['serviceName' => 'Classic Facial', 'servicePrice' => 1000, 'duration' => '01:00:00', 'is_active' => 1]);
        $this->customer = Customer::create([
            'firstName' => 'Maria', 'lastName' => 'Santos', 'email' => 'maria@example.com', 'password' => 'secret123',
            'contactNumber' => '09170000001', 'accountStatus' => 'active', 'is_verified' => 1,
        ]);
    }

    public function test_customer_can_cancel_an_approved_booking_before_the_window(): void
    {
        $booking = $this->booking(now()->addDays(3), 'approved');

        $this->actingAs($this->customer, 'customer')
            ->post(route('customer.bookings.cancel', $booking->bookingRef))
            ->assertRedirect(route('customer.bookings'))
            ->assertSessionHas('status');

        $this->assertSame('cancelled', $booking->fresh()->bookingStatus);
        $this->assertDatabaseHas('notifications', ['user_type' => 'staff', 'userID' => $this->owner->getKey(), 'title' => 'Booking Cancelled']);
    }

    public function test_customer_cannot_cancel_inside_the_cancellation_window(): void
    {
        $booking = $this->booking(now()->addHours(10), 'approved');

        $this->actingAs($this->customer, 'customer')
            ->from(route('customer.bookings.show', $booking->bookingRef))
            ->post(route('customer.bookings.cancel', $booking->bookingRef))
            ->assertSessionHas('error', 'Bookings can only be cancelled up to 24 hours before the appointment. Please call the salon instead.');

        $this->assertSame('approved', $booking->fresh()->bookingStatus);
        $this->assertFalse($booking->fresh()->can_cancel);
    }

    public function test_window_of_zero_hours_allows_changes_until_the_start_time(): void
    {
        SystemPolicy::where('policy_key', 'cancellation_window_hours')->update(['policy_value' => '0']);
        $booking = $this->booking(now()->addHour(), 'pending');

        $this->actingAs($this->customer, 'customer')
            ->post(route('customer.bookings.cancel', $booking->bookingRef))
            ->assertSessionHas('status');
    }

    public function test_reschedule_moves_the_booking_and_sends_it_back_for_approval(): void
    {
        $booking = $this->booking(now()->addDays(3)->setTime(10, 0), 'approved');
        $newDate = now()->addDays(5)->toDateString();

        $this->actingAs($this->customer, 'customer')
            ->get(route('customer.bookings.reschedule', $booking->bookingRef))
            ->assertOk()
            ->assertSee('Reschedules left');

        $this->actingAs($this->customer, 'customer')
            ->post(route('customer.bookings.reschedule.store', $booking->bookingRef), [
                'booking_date' => $newDate,
                'booking_time' => '14:00:00',
                'staff_id' => $this->otherStylist->getKey(),
            ])
            ->assertRedirect(route('customer.bookings.show', $booking->bookingRef))
            ->assertSessionHas('status');

        $booking->refresh();
        $this->assertSame($newDate, $booking->bookingDate->toDateString());
        $this->assertSame('14:00:00', $booking->bookingTime);
        $this->assertSame((int) $this->otherStylist->getKey(), (int) $booking->staffID);
        $this->assertSame('pending', $booking->bookingStatus);
        $this->assertSame(1, (int) $booking->reschedule_count);
        $this->assertDatabaseHas('notifications', ['user_type' => 'staff', 'userID' => $this->owner->getKey(), 'title' => 'Booking Rescheduled']);
    }

    public function test_reschedule_limit_is_enforced(): void
    {
        $booking = $this->booking(now()->addDays(3), 'pending', ['reschedule_count' => 1]);

        $this->assertFalse($booking->can_reschedule);

        $this->actingAs($this->customer, 'customer')
            ->get(route('customer.bookings.reschedule', $booking->bookingRef))
            ->assertRedirect(route('customer.bookings.show', $booking->bookingRef))
            ->assertSessionHas('error', 'This booking has already been rescheduled the maximum number of times.');

        $this->actingAs($this->customer, 'customer')
            ->post(route('customer.bookings.reschedule.store', $booking->bookingRef), [
                'booking_date' => now()->addDays(5)->toDateString(),
                'booking_time' => '14:00:00',
                'staff_id' => $this->stylist->getKey(),
            ])
            ->assertSessionHas('error');

        $this->assertSame(1, (int) $booking->fresh()->reschedule_count);
    }

    public function test_new_time_must_also_be_outside_the_window(): void
    {
        $booking = $this->booking(now()->addDays(3), 'pending');

        $this->actingAs($this->customer, 'customer')
            ->post(route('customer.bookings.reschedule.store', $booking->bookingRef), [
                'booking_date' => now()->addDay()->toDateString(),
                'booking_time' => '09:00:00',
                'staff_id' => $this->stylist->getKey(),
            ])
            ->assertSessionHas('error', 'The new time must be at least 24 hours from now.');
    }

    public function test_cannot_reschedule_into_a_slot_the_staff_member_already_has(): void
    {
        $booking = $this->booking(now()->addDays(3)->setTime(10, 0), 'pending');
        $this->booking(now()->addDays(4)->setTime(11, 0), 'approved', ['bookingRef' => 'JA-OTHER', 'staffID' => $this->otherStylist->getKey()]);

        $this->actingAs($this->customer, 'customer')
            ->post(route('customer.bookings.reschedule.store', $booking->bookingRef), [
                'booking_date' => now()->addDays(4)->toDateString(),
                'booking_time' => '11:00:00',
                'staff_id' => $this->otherStylist->getKey(),
            ])
            ->assertSessionHas('error', 'The selected staff member is not available for that date and time.');
    }

    public function test_availability_check_ignores_the_booking_being_moved(): void
    {
        $booking = $this->booking(now()->addDays(3)->setTime(10, 0), 'pending');
        $query = ['date' => $booking->bookingDate->toDateString(), 'time' => '10:00:00', 'service_id' => $this->service->getKey()];

        $without = $this->actingAs($this->customer, 'customer')->getJson(route('customer.staff-availability', $query))->json('staff');
        $with = $this->actingAs($this->customer, 'customer')->getJson(route('customer.staff-availability', $query + ['booking' => $booking->bookingRef]))->json('staff');

        $this->assertNotContains((int) $this->stylist->getKey(), array_column($without, 'staffID'));
        $this->assertContains((int) $this->stylist->getKey(), array_column($with, 'staffID'));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function booking(Carbon $at, string $status, array $overrides = []): Booking
    {
        return Booking::create(array_merge([
            'bookingRef' => 'JA-TEST-'.random_int(100, 999),
            'customerID' => $this->customer->getKey(),
            'serviceID' => $this->service->getKey(),
            'service' => $this->service->serviceName,
            'staffID' => $this->stylist->getKey(),
            'bookingDate' => $at->toDateString(),
            'bookingTime' => $at->format('H:i:s'),
            'bookingStatus' => $status,
            'paymentStatus' => 'proof-submitted',
            'total_amount' => 1000,
            'downpayment_amount' => 200,
            'payment_type' => 'partial',
        ], $overrides))->fresh();
    }

    private function makeStaff(string $username, string $role): Staff
    {
        return Staff::create([
            'firstName' => ucfirst($username), 'lastName' => 'Test', 'username' => $username, 'password' => 'password',
            'email' => $username.'@salon.test', 'role' => $role, 'specialization' => 'All-Around Salon Services',
            'is_active' => 1, 'is_archived' => 0,
        ]);
    }
}
