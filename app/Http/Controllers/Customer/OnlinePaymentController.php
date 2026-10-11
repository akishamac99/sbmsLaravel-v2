<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\PayMongoService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Throwable;

// "Pay online": sends the customer to PayMongo's hosted checkout page and handles the return.
class OnlinePaymentController extends Controller
{
    public function __construct(private PayMongoService $paymongo)
    {
    }

    public function start(string $ref): RedirectResponse
    {
        $booking = $this->find($ref);

        if (! $booking) {
            return redirect()->route('customer.bookings')->with('error', 'Booking not found.');
        }

        if (! $booking->can_upload_proof) {
            return redirect()->route('customer.bookings.show', $ref)->with('error', 'This booking can no longer be paid online.');
        }

        // Older unfinished online attempts for this booking are closed so only one stays open.
        Payment::where('bookingID', $booking->getKey())->where('payType', 'paymongo')->where('payStatus', 'pending')
            ->update(['payStatus' => 'cancelled']);

        $payment = Payment::create([
            'bookingID' => $booking->getKey(),
            'payType' => 'paymongo',
            'amount' => $booking->amount_due,
            'payStatus' => 'pending',
            'referenceNo' => 'PM'.now()->format('YmdHis').$booking->getKey(),
        ]);

        try {
            $session = $this->paymongo->createSession(
                $booking,
                $payment,
                route('customer.bookings.pay-online.done', $ref),
                route('customer.bookings.show', $ref)
            );
        } catch (DomainException $e) {
            $payment->update(['payStatus' => 'failed']);

            return redirect()->route('customer.bookings.show', $ref)->with('error', $e->getMessage());
        }

        $payment->update(['gateway_ref' => $session['id']]);

        return redirect()->away($session['url']);
    }

    // PayMongo sends the customer back here after paying. The webhook usually finishes the job,
    // but we also ask PayMongo directly so it works even before the webhook arrives.
    public function done(string $ref): RedirectResponse
    {
        $booking = $this->find($ref);

        if (! $booking) {
            return redirect()->route('customer.bookings')->with('error', 'Booking not found.');
        }

        $payment = Payment::where('bookingID', $booking->getKey())->where('payType', 'paymongo')
            ->whereNotNull('gateway_ref')->orderByDesc('payID')->first();

        if ($payment && $payment->payStatus !== 'paid') {
            try {
                if ($this->paymongo->sessionIsPaid($payment->gateway_ref)) {
                    $this->paymongo->markPaid($payment);
                }
            } catch (Throwable $e) {
                report($e);
            }
        }

        $paid = $payment && $payment->fresh()->payStatus === 'paid';

        return redirect()->route('customer.bookings.show', $ref)->with(
            $paid ? 'status' : 'error',
            $paid ? 'Payment received. Thank you!' : 'We have not received your payment yet. If you already paid, it will update in a moment. Refresh this page.'
        );
    }

    private function find(string $ref): ?Booking
    {
        return Booking::ownedBy((int) Auth::guard('customer')->id())->where('bookingRef', $ref)->first();
    }
}
