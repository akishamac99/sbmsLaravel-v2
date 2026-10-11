<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\BookingService;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// Replaces customer/upload_payment.php: send a payment screenshot for a booking that already exists.
class PaymentProofController extends Controller
{
    public function __construct(private BookingService $bookings) {}

    public function create(string $ref)
    {
        $booking = $this->find($ref);

        if (! $booking) {
            return redirect()->route('customer.bookings')->with('error', 'Booking not found.');
        }

        if (! $booking->can_upload_proof) {
            return redirect()->route('customer.bookings.show', $booking->bookingRef)
                ->with('error', 'Payment proof can no longer be uploaded for this booking.');
        }

        return view('customer.upload-payment', [
            'booking' => $booking,
            'gcashNumber' => config('salon.gcash_number'),
            'gcashName' => config('salon.gcash_name'),
        ]);
    }

    public function store(Request $request, string $ref)
    {
        $booking = $this->find($ref);

        if (! $booking) {
            return redirect()->route('customer.bookings')->with('error', 'Booking not found.');
        }

        $request->validate([
            'payment_proof' => ['required', 'file', 'mimes:jpg,jpeg,png,gif,webp', 'max:10240'],
        ], CheckoutController::proofMessages());

        try {
            $this->bookings->submitProof($booking, Auth::guard('customer')->user(), $request->file('payment_proof'));
        } catch (DomainException $e) {
            return redirect()->route('customer.bookings.show', $booking->bookingRef)->with('error', $e->getMessage());
        }

        return redirect()->route('customer.bookings.show', $booking->bookingRef)
            ->with('status', 'Payment proof uploaded successfully. Our team will verify your payment.');
    }

    private function find(string $ref): ?Booking
    {
        return Booking::with(['serviceItem', 'staff'])
            ->ownedBy((int) Auth::guard('customer')->id())
            ->where('bookingRef', $ref)
            ->first();
    }
}
