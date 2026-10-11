<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\SystemPolicy;
use App\Services\BookingService;
use App\Support\TimeSlots;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

// Replaces customer/bookings.php and customer/booking_details.php.
class BookingController extends Controller
{
    public function __construct(private BookingService $bookings) {}

    public function index()
    {
        $bookings = Booking::with(['serviceItem', 'staff'])
            ->ownedBy($this->customerId())
            ->latest('created_at')
            ->latest('bookingID')
            ->get();

        return view('customer.bookings', ['bookings' => $bookings]);
    }

    public function show(string $ref)
    {
        $booking = $this->find($ref);

        if (! $booking) {
            return redirect()->route('customer.bookings')->with('error', 'Booking not found.');
        }

        return view('customer.booking-details', ['booking' => $booking]);
    }

    // A POST, not a link: a link that cancels something can be triggered by accident or by another website.
    public function cancel(string $ref)
    {
        $booking = $this->find($ref);

        if (! $booking) {
            return redirect()->route('customer.bookings')->with('error', 'Booking not found.');
        }

        try {
            $this->bookings->cancelByCustomer($booking, $this->customerId());
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('customer.bookings')->with('status', 'Booking cancelled.');
    }

    public function reschedule(string $ref)
    {
        $booking = $this->find($ref);

        if (! $booking) {
            return redirect()->route('customer.bookings')->with('error', 'Booking not found.');
        }

        if (! $booking->can_reschedule) {
            return redirect()->route('customer.bookings.show', $booking->bookingRef)
                ->with('error', $booking->reschedules_left <= 0 && $booking->is_changeable_by_customer
                    ? 'This booking has already been rescheduled the maximum number of times.'
                    : 'This booking can no longer be rescheduled online. Please call the salon.');
        }

        return view('customer.reschedule', [
            'booking' => $booking,
            'timeSlots' => TimeSlots::all(),
            'minDate' => $this->earliestDate()->toDateString(),
            'maxDate' => today()->addDays(30)->toDateString(),
            'windowHours' => SystemPolicy::cancellationWindowHours(),
        ]);
    }

    public function rescheduleStore(Request $request, string $ref)
    {
        $booking = $this->find($ref);

        if (! $booking) {
            return redirect()->route('customer.bookings')->with('error', 'Booking not found.');
        }

        $data = $request->validate([
            'booking_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.$this->earliestDate()->toDateString(), 'before_or_equal:'.today()->addDays(30)->toDateString()],
            'booking_time' => ['required', Rule::in(TimeSlots::values())],
            'staff_id' => ['required', 'integer', 'exists:salon_staff,staffID'],
        ], [
            'booking_date.after_or_equal' => 'Choose a date at least '.SystemPolicy::cancellationWindowHours().' hours from now.',
            'booking_date.before_or_equal' => 'Bookings can be made up to 30 days ahead.',
            'booking_time.in' => 'Choose one of the listed times.',
            'staff_id.required' => 'Choose a staff member who is free at that time.',
        ]);

        try {
            $this->bookings->rescheduleByCustomer($booking, $this->customerId(), $data['booking_date'], $data['booking_time'], (int) $data['staff_id']);
        } catch (DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('customer.bookings.show', $booking->bookingRef)
            ->with('status', 'Booking moved. The salon will confirm the new time.');
    }

    /**
     * The first day a booking can be moved to: the cancellation window counts from now.
     */
    private function earliestDate(): Carbon
    {
        return now()->addHours(SystemPolicy::cancellationWindowHours())->startOfDay();
    }

    private function find(string $ref): ?Booking
    {
        return Booking::with(['serviceItem', 'staff'])
            ->ownedBy($this->customerId())
            ->where('bookingRef', $ref)
            ->first();
    }

    private function customerId(): int
    {
        return (int) Auth::guard('customer')->id();
    }
}
