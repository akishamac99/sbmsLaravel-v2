<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Feedback;
use App\Models\SystemLog;
use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

// Replaces customer/feedback.php: rate completed appointments, and read your past ratings.
class FeedbackController extends Controller
{
    public function index(Request $request)
    {
        $customerId = $this->customerId();

        // Completed bookings that have a staff member and no rating yet.
        $pending = Booking::with(['serviceItem', 'staff'])
            ->ownedBy($customerId)
            ->where('bookingStatus', 'completed')
            ->whereNotNull('staffID')
            ->whereDoesntHave('feedback')
            ->orderByDesc('bookingDate')
            ->get();

        $history = Feedback::with(['service', 'staff'])
            ->where('customerID', $customerId)
            ->latest('created_at')
            ->latest('feedbackID')
            ->get();

        return view('customer.feedback', [
            'pending' => $pending,
            'history' => $history,
            'highlight' => (string) $request->query('booking', ''),
        ]);
    }

    // The "Rate Service" link in My Bookings: show the feedback page with that booking highlighted.
    public function focus(string $ref)
    {
        return redirect()->route('customer.feedback', ['booking' => $ref]);
    }

    public function store(Request $request, string $ref)
    {
        $data = $request->validate([
            'service_rating' => ['required', 'integer', 'between:1,5'],
            'staff_rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['required', 'string', 'max:1000'],
        ], [
            'service_rating.required' => 'Please rate the service.',
            'staff_rating.required' => 'Please rate the staff.',
            'service_rating.between' => 'Ratings must be from 1 to 5 stars.',
            'staff_rating.between' => 'Ratings must be from 1 to 5 stars.',
            'comment.required' => 'Please write a short comment.',
        ]);

        $customerId = $this->customerId();

        // The booking row is locked while we check, so double-clicking Submit cannot save two ratings.
        // Who the staff member and service are comes from the booking itself, never from the form.
        $problem = DB::transaction(function () use ($ref, $customerId, $data) {
            $booking = Booking::ownedBy($customerId)->where('bookingRef', $ref)->lockForUpdate()->first();

            if (! $booking) {
                return 'Booking not found.';
            }

            if ($booking->bookingStatus !== 'completed') {
                return 'You can only rate completed appointments.';
            }

            if (! $booking->staffID) {
                return 'This booking has no staff member recorded, so it cannot be rated.';
            }

            if (Feedback::where('bookingID', $booking->bookingID)->exists()) {
                return 'You already sent feedback for this appointment.';
            }

            Feedback::create([
                'bookingID' => $booking->bookingID,
                'customerID' => $customerId,
                'staffID' => $booking->staffID,
                'serviceID' => $booking->serviceID,
                'rating' => (int) round(($data['service_rating'] + $data['staff_rating']) / 2),
                'service_rating' => $data['service_rating'],
                'staff_rating' => $data['staff_rating'],
                'comment' => trim($data['comment']),
            ]);

            UserNotification::send('customer', $customerId, 'Feedback Submitted', 'Thank you for your feedback.');

            return null;
        });

        if ($problem) {
            return redirect()->route('customer.feedback')->with('error', $problem);
        }

        SystemLog::record('customer', $customerId, 'submit_feedback', "Submitted feedback for #{$ref}");

        return redirect()->route('customer.feedback')->with('status', 'Thank you for your feedback.');
    }

    private function customerId(): int
    {
        return (int) Auth::guard('customer')->id();
    }
}
